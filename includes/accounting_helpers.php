<?php
/**
 * 통합 회계 거래 헬퍼
 *
 * bank_transactions(비분할) + transaction_splits + journal_entries를
 * 하나의 UNION 쿼리로 통합하여 모든 보고서가 공유하는 함수.
 * 테이블 존재 여부를 확인하여 Phase 1(통장만)에서도 안전하게 호출 가능.
 */

// 통장 거래의 반대편(현금성) 계정 · 모든 등록 계좌는 은행 보통예금이므로 단일 코드로 합성한다.
// account_categories 시드의 '보통예금' 코드와 반드시 일치해야 함 (db/schema_tax.sql).
const CASH_ACCOUNT_CODE = '10300';
const CASH_ACCOUNT_NAME = '보통예금';

// 세금계산서 발생주의 분개용 계정코드 · db/schema_tax.sql 시드와 일치 필수
const ACCRUAL_AR_CODE      = '10800'; // 외상매출금
const ACCRUAL_AP_CODE      = '25100'; // 외상매입금
const ACCRUAL_SALES_CODE   = '41200'; // 서비스매출
const ACCRUAL_PURCHASE_CODE = '45100'; // 상품매입
const ACCRUAL_VAT_IN_CODE  = '13500'; // 부가세대급금
const ACCRUAL_VAT_OUT_CODE = '25500'; // 부가세예수금

function tableExists(PDO $pdo, string $table): bool
{
    // information_schema 사용 — "SHOW TABLES LIKE ?" 는 EMULATE_PREPARES=false 환경에서 prepare 시 1064 에러 → 항상 false 반환되던 버그
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
        $stmt->execute([$table]);
        return (int)$stmt->fetchColumn() > 0;
    } catch (\Throwable $e) {
        return false;
    }
}

/**
 * 보고서용 통합 거래 SQL 조각 반환
 *
 * 반환하는 SQL은 다음 컬럼을 갖는 서브쿼리(unified_tx):
 *   transaction_date, account_code, account_name, amount, tx_type,
 *   description, counterparty, memo, bank_name, source
 *
 * @param PDO  $pdo
 * @param string $alias
 * @param bool $includeCashLeg  통장 거래마다 보통예금 반대편 분개를 합성할지 여부.
 *                              복식부기 대차평균이 필요한 보고서(시산표·재무상태표)에서만 true.
 *                              손익계산서·원장 등은 false(기존 동작) 유지.
 * @return string  SQL 서브쿼리 (괄호 포함, AS unified_tx)
 */
function getUnifiedTransactionSQL(PDO $pdo, string $alias = 'unified_tx', bool $includeCashLeg = false): string
{
    $hasSplits   = tableExists($pdo, 'transaction_splits');
    $hasJournals = tableExists($pdo, 'journal_entries');

    // 1) 비분할 통장 거래
    $parts = [];

    if ($hasSplits) {
        $parts[] = "
            SELECT bt.transaction_date, bt.account_code, bt.account_name,
                   bt.amount, bt.tx_type,
                   bt.description, bt.counterparty, bt.memo,
                   ba.bank_name,
                   'bank' AS source
            FROM bank_transactions bt
            LEFT JOIN bank_accounts ba ON ba.id = bt.account_id
            WHERE bt.account_code IS NOT NULL AND bt.account_code != ''
              AND bt.id NOT IN (SELECT DISTINCT ts.transaction_id FROM transaction_splits ts)
        ";

        // 2) 분할 거래
        $parts[] = "
            SELECT bt.transaction_date, ts.account_code, ts.account_name,
                   ts.amount, bt.tx_type,
                   bt.description, bt.counterparty, ts.memo,
                   ba.bank_name,
                   'split' AS source
            FROM transaction_splits ts
            JOIN bank_transactions bt ON bt.id = ts.transaction_id
            LEFT JOIN bank_accounts ba ON ba.id = bt.account_id
        ";
    } else {
        $parts[] = "
            SELECT bt.transaction_date, bt.account_code, bt.account_name,
                   bt.amount, bt.tx_type,
                   bt.description, bt.counterparty, bt.memo,
                   ba.bank_name,
                   'bank' AS source
            FROM bank_transactions bt
            LEFT JOIN bank_accounts ba ON ba.id = bt.account_id
            WHERE bt.account_code IS NOT NULL AND bt.account_code != ''
        ";
    }

    // 3) 조정분개 · 차변
    if ($hasJournals) {
        $parts[] = "
            SELECT je.entry_date AS transaction_date, je.debit_code AS account_code,
                   je.debit_name AS account_name, je.amount, '출금' AS tx_type,
                   je.description, '' AS counterparty, je.memo, '조정분개' AS bank_name,
                   'journal_dr' AS source
            FROM journal_entries je
        ";

        // 4) 조정분개 · 대변
        $parts[] = "
            SELECT je.entry_date AS transaction_date, je.credit_code AS account_code,
                   je.credit_name AS account_name, je.amount, '입금' AS tx_type,
                   je.description, '' AS counterparty, je.memo, '조정분개' AS bank_name,
                   'journal_cr' AS source
            FROM journal_entries je
        ";
    }

    // 5) 통장 거래의 보통예금 반대편 레그 (대차평균 보정)
    //    상대계정이 기록된 통장 거래에 한해, 금액 그대로 tx_type을 뒤집어 보통예금 분개를 합성.
    //    출금(상대계정 차변) → 보통예금 대변 / 입금(상대계정 대변) → 보통예금 차변.
    //    조정분개(journal_entries)는 이미 양변을 갖추므로 합성 대상이 아니다.
    if ($includeCashLeg) {
        if ($hasSplits) {
            $counterpartCond = "(
                (bt.account_code IS NOT NULL AND bt.account_code != ''
                 AND bt.id NOT IN (SELECT DISTINCT ts2.transaction_id FROM transaction_splits ts2))
                OR bt.id IN (SELECT DISTINCT ts3.transaction_id FROM transaction_splits ts3)
            )";
        } else {
            $counterpartCond = "bt.account_code IS NOT NULL AND bt.account_code != ''";
        }

        $cashCode = CASH_ACCOUNT_CODE;
        $cashName = CASH_ACCOUNT_NAME;
        $parts[] = "
            SELECT bt.transaction_date, '{$cashCode}' AS account_code, '{$cashName}' AS account_name,
                   bt.amount,
                   CASE WHEN bt.tx_type = '출금' THEN '입금' ELSE '출금' END AS tx_type,
                   bt.description, bt.counterparty, bt.memo,
                   ba.bank_name,
                   'cash_leg' AS source
            FROM bank_transactions bt
            LEFT JOIN bank_accounts ba ON ba.id = bt.account_id
            WHERE {$counterpartCond}
        ";
    }

    // 6) 세금계산서 발생주의 분개
    //    매출: DR 외상매출금(total) / CR 매출(supply) + 부가세예수금(tax)
    //    매입: DR 매입(supply) + 부가세대급금(tax) / CR 외상매입금(total)
    //    통장 입금 전에도 장부에 잡히도록 세금계산서 발행일(issue_date) 기준으로 인식.
    if (tableExists($pdo, 'tax_invoices')) {
        $ar = ACCRUAL_AR_CODE;
        $ap = ACCRUAL_AP_CODE;
        $sl = ACCRUAL_SALES_CODE;
        $pu = ACCRUAL_PURCHASE_CODE;
        $vi = ACCRUAL_VAT_IN_CODE;
        $vo = ACCRUAL_VAT_OUT_CODE;

        // 매출 → 외상매출금 (차변)
        $parts[] = "
            SELECT ti.issue_date AS transaction_date, '{$ar}' AS account_code, '외상매출금' AS account_name,
                   ti.total_amount AS amount, '출금' AS tx_type,
                   CONCAT('세금계산서 ', ti.invoice_number) AS description,
                   ti.buyer_name AS counterparty, '' AS memo, '세금계산서' AS bank_name,
                   'inv_ar' AS source
            FROM tax_invoices ti
            WHERE ti.invoice_type = '매출' AND ti.invoice_status != '취소'
        ";
        // 매출 → 서비스매출 (대변, 공급가액)
        $parts[] = "
            SELECT ti.issue_date AS transaction_date, '{$sl}' AS account_code, '서비스매출' AS account_name,
                   ti.supply_amount AS amount, '입금' AS tx_type,
                   CONCAT('세금계산서 ', ti.invoice_number) AS description,
                   ti.buyer_name AS counterparty, '' AS memo, '세금계산서' AS bank_name,
                   'inv_sales' AS source
            FROM tax_invoices ti
            WHERE ti.invoice_type = '매출' AND ti.invoice_status != '취소'
        ";
        // 매출 → 부가세예수금 (대변, 세액)
        $parts[] = "
            SELECT ti.issue_date AS transaction_date, '{$vo}' AS account_code, '부가세예수금' AS account_name,
                   ti.tax_amount AS amount, '입금' AS tx_type,
                   CONCAT('세금계산서 ', ti.invoice_number) AS description,
                   ti.buyer_name AS counterparty, '' AS memo, '세금계산서' AS bank_name,
                   'inv_vat_out' AS source
            FROM tax_invoices ti
            WHERE ti.invoice_type = '매출' AND ti.invoice_status != '취소' AND ti.tax_amount != 0
        ";

        // 매입 → 상품매입 (차변, 공급가액)
        $parts[] = "
            SELECT ti.issue_date AS transaction_date, '{$pu}' AS account_code, '상품매입' AS account_name,
                   ti.supply_amount AS amount, '출금' AS tx_type,
                   CONCAT('세금계산서 ', ti.invoice_number) AS description,
                   ti.supplier_name AS counterparty, '' AS memo, '세금계산서' AS bank_name,
                   'inv_purchase' AS source
            FROM tax_invoices ti
            WHERE ti.invoice_type = '매입' AND ti.invoice_status != '취소'
        ";
        // 매입 → 부가세대급금 (차변, 세액)
        $parts[] = "
            SELECT ti.issue_date AS transaction_date, '{$vi}' AS account_code, '부가세대급금' AS account_name,
                   ti.tax_amount AS amount, '출금' AS tx_type,
                   CONCAT('세금계산서 ', ti.invoice_number) AS description,
                   ti.supplier_name AS counterparty, '' AS memo, '세금계산서' AS bank_name,
                   'inv_vat_in' AS source
            FROM tax_invoices ti
            WHERE ti.invoice_type = '매입' AND ti.invoice_status != '취소' AND ti.tax_amount != 0
        ";
        // 매입 → 외상매입금 (대변)
        $parts[] = "
            SELECT ti.issue_date AS transaction_date, '{$ap}' AS account_code, '외상매입금' AS account_name,
                   ti.total_amount AS amount, '입금' AS tx_type,
                   CONCAT('세금계산서 ', ti.invoice_number) AS description,
                   ti.supplier_name AS counterparty, '' AS memo, '세금계산서' AS bank_name,
                   'inv_ap' AS source
            FROM tax_invoices ti
            WHERE ti.invoice_type = '매입' AND ti.invoice_status != '취소'
        ";
    }

    return '(' . implode("\nUNION ALL\n", $parts) . ') AS ' . $alias;
}

/**
 * 보고서용 통합 집계 쿼리 · 계정별 차변/대변 합계
 *
 * @param PDO    $pdo
 * @param string $dateFrom  시작일 (YYYY-MM-DD)
 * @param string $dateTo    종료일 (YYYY-MM-DD)
 * @param string|null $accountCode  특정 계정만 필터 (null = 전체)
 * @param bool $includeCashLeg  보통예금 반대편 분개 합성 여부 (시산표·재무상태표에서 true)
 * @return array  [{code, name, type, parent_code, group_name, grp_sort, total_debit, total_credit, tx_count}, ...]
 */
function getUnifiedAccountSummary(PDO $pdo, string $dateFrom, string $dateTo, ?string $accountCode = null, bool $includeCashLeg = false): array
{
    $a = 'ut';
    $unified = getUnifiedTransactionSQL($pdo, $a, $includeCashLeg);

    $where = "{$a}.transaction_date BETWEEN ? AND ?";
    $params = [$dateFrom, $dateTo];

    if ($accountCode) {
        $where .= " AND {$a}.account_code = ?";
        $params[] = $accountCode;
    }

    $sql = "
        SELECT ac.code, ac.name, ac.type, ac.parent_code,
               pg.name AS group_name,
               COALESCE(pg.sort_order, ac.sort_order) AS grp_sort,
               ac.sort_order,
               SUM(CASE WHEN {$a}.tx_type = '출금' THEN {$a}.amount ELSE 0 END) AS total_debit,
               SUM(CASE WHEN {$a}.tx_type = '입금' THEN {$a}.amount ELSE 0 END) AS total_credit,
               COUNT(*) AS tx_count
        FROM {$unified}
        JOIN account_categories ac ON {$a}.account_code = ac.code
        LEFT JOIN account_categories pg ON ac.parent_code = pg.code
        WHERE {$where}
          AND ac.code NOT LIKE 'G\\_%' ESCAPE '\\\\'
        GROUP BY ac.code, ac.name, ac.type, ac.parent_code, pg.name, pg.sort_order, ac.sort_order
        ORDER BY COALESCE(pg.sort_order, ac.sort_order), ac.sort_order
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * 보고서용 통합 거래 내역 · 특정 계정의 개별 거래 목록
 *
 * @param PDO    $pdo
 * @param string $accountCode
 * @param string $dateFrom
 * @param string $dateTo
 * @return array  [{transaction_date, account_code, amount, tx_type, description, counterparty, memo, bank_name, source}, ...]
 */
function getUnifiedTransactions(PDO $pdo, string $accountCode, string $dateFrom, string $dateTo): array
{
    $a = 'ut';
    $unified = getUnifiedTransactionSQL($pdo, $a);

    $sql = "
        SELECT {$a}.*
        FROM {$unified}
        WHERE {$a}.account_code = ?
          AND {$a}.transaction_date BETWEEN ? AND ?
        ORDER BY {$a}.transaction_date ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$accountCode, $dateFrom, $dateTo]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
