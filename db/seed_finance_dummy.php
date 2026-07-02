<?php
/**
 * 재무 모듈 더미데이터 시드 (테스트용)
 * - 대상: tax_invoices/tax_invoice_items, bank_transactions, card_expenses,
 *         payslips, tax_credit_simulations, doc_uploads
 * - 범위: 2026-03 ~ 2026-07 (기존 데이터 없는 최근월 위주) + 빈 테이블 채움
 * - 멱등: 월/키 단위로 이미 데이터가 있으면 건너뜀. 재실행해도 중복 없음.
 *
 * 실행: php db/seed_finance_dummy.php
 */
require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();
if (!$pdo) { fwrite(STDERR, "DB 연결 실패\n"); exit(1); }
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$log = [];
function note(array &$log, string $msg): void { $log[] = $msg; echo $msg . "\n"; }

/* 회사(매출 공급자 / 매입 매입자) 정보 */
$ME = ['bizno' => '123-45-67890', 'name' => '주식회사 재밋', 'ceo' => '송승환'];

/* 매출 거래처 (재밋 → 고객) */
$customers = [
    ['234-56-78901', '(주)테크솔루션', '김태호'],
    ['345-67-89012', '디자인웍스', '박지연'],
    ['456-78-90123', '(주)스마트커머스', '이준혁'],
    ['567-89-01234', '한국데이터', '최수진'],
    ['678-90-12345', '(주)클라우드베이스', '정민우'],
    ['789-01-23456', '넥스트비즈', '한서영'],
];
/* 매입 거래처 (공급업체 → 재밋) */
$vendors = [
    ['111-22-33344', '(주)오피스마트', '고영수', '사무용품'],
    ['222-33-44455', '클라우드호스팅', '남기훈', '클라우드 서버 이용료'],
    ['333-44-55566', '한빛통신', '조현우', '통신비'],
    ['444-55-66677', '스마트물류', '백승현', '물류 대행 수수료'],
    ['555-66-77788', '대한전자', '문태식', '전자부품 매입'],
];
$saleItems = ['소프트웨어 개발 용역', '유지보수 계약', '기술 컨설팅', '라이선스 사용료'];

/* 대상 월 (당해 최근월 중심) */
$months = ['2026-03', '2026-04', '2026-05', '2026-06', '2026-07'];

$pdo->beginTransaction();
try {

/* ─────────────────────────────────────────────
 * 1) 세금계산서 (tax_invoices) + 품목(tax_invoice_items)
 *    월별로 이미 존재하면 건너뜀.
 * ───────────────────────────────────────────── */
$hasMonthInv = $pdo->prepare("SELECT COUNT(*) FROM tax_invoices WHERE DATE_FORMAT(issue_date,'%Y-%m') = ?");
$insInv = $pdo->prepare("
    INSERT INTO tax_invoices
      (company_id, invoice_type, invoice_number, issue_date, send_date,
       supplier_bizno, supplier_name, supplier_ceo, buyer_bizno, buyer_name, buyer_ceo,
       supply_amount, tax_amount, total_amount, tax_type, invoice_status, hometax_sync, synced_at, memo)
    VALUES
      (1, :itype, :inum, :idate, :sdate,
       :sbizno, :sname, :sceo, :bbizno, :bname, :bceo,
       :supply, :tax, :total, '과세', '정상', 1, :synced, :memo)
");
$insItem = $pdo->prepare("
    INSERT INTO tax_invoice_items
      (invoice_id, item_date, item_name, spec, quantity, unit_price, supply_amount, tax_amount)
    VALUES (:iid, :idate, :iname, :spec, :qty, :uprice, :supply, :tax)
");

$invCreated = 0; $itemCreated = 0;
foreach ($months as $mi => $ym) {
    $hasMonthInv->execute([$ym]);
    if ((int)$hasMonthInv->fetchColumn() > 0) { note($log, "tax_invoices $ym : 이미 존재 → skip"); continue; }
    [$Y, $M] = explode('-', $ym);

    // 매출 3건
    for ($k = 0; $k < 3; $k++) {
        $day = str_pad((string)(4 + $k * 8), 2, '0', STR_PAD_LEFT);
        $date = "$ym-$day";
        $cust = $customers[($mi * 3 + $k) % count($customers)];
        $supply = (1500000 + (($mi * 3 + $k) % 6) * 850000); // 150만~575만
        $tax = (int)round($supply * 0.1);
        $inum = "{$Y}{$M}{$day}-41000001-" . str_pad((string)($k + 1), 6, '0', STR_PAD_LEFT);
        $insInv->execute([
            ':itype' => '매출', ':inum' => $inum, ':idate' => $date, ':sdate' => $date,
            ':sbizno' => $ME['bizno'], ':sname' => $ME['name'], ':sceo' => $ME['ceo'],
            ':bbizno' => $cust[0], ':bname' => $cust[1], ':bceo' => $cust[2],
            ':supply' => $supply, ':tax' => $tax, ':total' => $supply + $tax,
            ':synced' => "$date 09:00:00", ':memo' => null,
        ]);
        $iid = (int)$pdo->lastInsertId();
        $iname = $saleItems[($mi + $k) % count($saleItems)];
        $insItem->execute([
            ':iid' => $iid, ':idate' => $date, ':iname' => $iname, ':spec' => '월정액',
            ':qty' => 1, ':uprice' => $supply, ':supply' => $supply, ':tax' => $tax,
        ]);
        $invCreated++; $itemCreated++;
    }
    // 매입 3건
    for ($k = 0; $k < 3; $k++) {
        $day = str_pad((string)(6 + $k * 7), 2, '0', STR_PAD_LEFT);
        $date = "$ym-$day";
        $ven = $vendors[($mi * 3 + $k) % count($vendors)];
        $supply = (300000 + (($mi * 3 + $k) % 5) * 640000); // 30만~286만
        $tax = (int)round($supply * 0.1);
        $inum = "{$Y}{$M}{$day}-{$k}2000002-" . str_pad((string)($k + 1), 6, '0', STR_PAD_LEFT);
        $insInv->execute([
            ':itype' => '매입', ':inum' => $inum, ':idate' => $date, ':sdate' => $date,
            ':sbizno' => $ven[0], ':sname' => $ven[1], ':sceo' => $ven[2],
            ':bbizno' => $ME['bizno'], ':bname' => $ME['name'], ':bceo' => $ME['ceo'],
            ':supply' => $supply, ':tax' => $tax, ':total' => $supply + $tax,
            ':synced' => "$date 09:00:00", ':memo' => null,
        ]);
        $iid = (int)$pdo->lastInsertId();
        $insItem->execute([
            ':iid' => $iid, ':idate' => $date, ':iname' => $ven[3], ':spec' => '',
            ':qty' => 1, ':uprice' => $supply, ':supply' => $supply, ':tax' => $tax,
        ]);
        $invCreated++; $itemCreated++;
    }
    note($log, "tax_invoices $ym : 매출3 + 매입3 생성");
}

/* 기존 세금계산서 중 품목 없는 건 → 대표 품목 1줄 백필 (데이터 정합) */
$noItemRows = $pdo->query("
    SELECT ti.id, ti.issue_date, ti.invoice_type, ti.supply_amount, ti.tax_amount, ti.buyer_name, ti.supplier_name
    FROM tax_invoices ti
    LEFT JOIN tax_invoice_items it ON it.invoice_id = ti.id
    WHERE it.id IS NULL
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($noItemRows as $r) {
    $iname = $r['invoice_type'] === '매출' ? '용역 대금' : '매입 대금';
    $insItem->execute([
        ':iid' => (int)$r['id'], ':idate' => $r['issue_date'], ':iname' => $iname, ':spec' => '',
        ':qty' => 1, ':uprice' => (int)$r['supply_amount'],
        ':supply' => (int)$r['supply_amount'], ':tax' => (int)$r['tax_amount'],
    ]);
    $itemCreated++;
}
note($log, "tax_invoice_items : 신규+백필 총 {$itemCreated}건 (기존 품목없는 " . count($noItemRows) . "건 백필 포함)");

/* ─────────────────────────────────────────────
 * 2) 은행 거래내역 (bank_transactions) — 05,06,07 월
 * ───────────────────────────────────────────── */
$hasMonthBank = $pdo->prepare("SELECT COUNT(*) FROM bank_transactions WHERE account_id = ? AND DATE_FORMAT(transaction_date,'%Y-%m') = ?");
$insBank = $pdo->prepare("
    INSERT INTO bank_transactions
      (account_id, transaction_date, description, amount, tx_type, balance, account_code, account_name, ai_confidence, is_confirmed, memo)
    VALUES (:acc, :date, :desc, :amt, :type, :bal, :acode, :aname, :conf, :conf2, :memo)
");
$bankRows = [
    // [일, 설명, 금액, 입/출, 계정코드, 계정명, 확정]
    [5,  '테크솔루션 세금계산서 입금', 16500000, '입금', '40200', '제품매출', 1],
    [7,  '5월 급여 이체',            -48000000, '출금', null,     null,       0],
    [10, '법인카드 대금 결제',        -3850000,  '출금', '82700', '차량유지비', 1],
    [15, '사무실 임대료',            -2200000,  '출금', null,     null,       1],
    [20, '4대보험 납부',            -3120000,  '출금', null,     null,       0],
    [25, '스마트커머스 용역대금 입금', 9350000,   '입금', '40200', '제품매출', 1],
    [28, '예금 이자',                42500,     '입금', 'G_NI',  '영업외수익', 0],
];
$bankCreated = 0;
foreach (['2026-05', '2026-06', '2026-07'] as $bm) {
    $hasMonthBank->execute([1, $bm]);
    if ((int)$hasMonthBank->fetchColumn() > 0) { note($log, "bank_transactions $bm : 이미 존재 → skip"); continue; }
    foreach ($bankRows as $br) {
        $day = str_pad((string)$br[0], 2, '0', STR_PAD_LEFT);
        $amt = $br[2];
        $insBank->execute([
            ':acc' => 1, ':date' => "$bm-$day", ':desc' => $br[1],
            ':amt' => abs($amt), ':type' => $br[3], ':bal' => null,
            ':acode' => $br[4], ':aname' => $br[5],
            ':conf' => $br[4] ? 92 : null, ':conf2' => $br[6], ':memo' => null,
        ]);
        $bankCreated++;
    }
    note($log, "bank_transactions $bm : " . count($bankRows) . "건 생성");
}

/* ─────────────────────────────────────────────
 * 3) 법인카드 지출 (card_expenses) — 05,06,07 월
 * ───────────────────────────────────────────── */
$hasMonthCard = $pdo->prepare("SELECT COUNT(*) FROM card_expenses WHERE DATE_FORMAT(usage_date,'%Y-%m') = ?");
$insCard = $pdo->prepare("
    INSERT INTO card_expenses
      (card_id, registrant_name, approval_number, usage_type, category, sub_category, amount,
       description, business_name, usage_date, is_settled, compliance_status, user_name)
    VALUES (:card, :reg, :appr, '법인', :cat, :sub, :amt, :desc, :biz, :date, :settled, :comp, :user)
");
$cardRows = [
    // [card_id, 등록자, 카테고리, 세부, 금액, 가맹점, 일, 정산여부]
    [1, '최영업', '접대비',   '식대',   180000, '한우명가',     6,  1],
    [2, '박기술', '소모품비', '개발장비', 320000, '테크몰',       9,  1],
    [3, '정지원', '복리후생', '간식',   95000,  '이마트',       12, 0],
    [4, '김대표', '접대비',   '골프',   450000, '레이크힐스CC', 15, 0],
    [5, '한인사', '광고선전비','온라인광고', 700000, '메타광고',     18, 1],
    [1, '최영업', '여비교통비','주유',   88000,  'GS칼텍스',     22, 0],
    [2, '박기술', '통신비',   '클라우드', 264000, 'AWS',         25, 1],
];
$cardCreated = 0;
foreach (['2026-05', '2026-06', '2026-07'] as $cm) {
    $hasMonthCard->execute([$cm]);
    if ((int)$hasMonthCard->fetchColumn() > 0) { note($log, "card_expenses $cm : 이미 존재 → skip"); continue; }
    foreach ($cardRows as $i => $cr) {
        $day = str_pad((string)$cr[6], 2, '0', STR_PAD_LEFT);
        $insCard->execute([
            ':card' => $cr[0], ':reg' => $cr[1],
            ':appr' => '3' . str_pad((string)(100000 + $i), 7, '0', STR_PAD_LEFT),
            ':cat' => $cr[2], ':sub' => $cr[3], ':amt' => $cr[4],
            ':desc' => $cr[2] . ' 지출', ':biz' => $cr[5], ':date' => "$cm-$day",
            ':settled' => $cr[7], ':comp' => $cr[7] ? '준수' : '미확인', ':user' => $cr[1],
        ]);
        $cardCreated++;
    }
    note($log, "card_expenses $cm : " . count($cardRows) . "건 생성");
}

/* ─────────────────────────────────────────────
 * 4) 급여명세 (payslips) — 05,06 월 (07월은 기존 존재)
 *    07월 데이터를 템플릿으로 각 직원 동일 금액 복제.
 * ───────────────────────────────────────────── */
$tpl = $pdo->query("SELECT * FROM payslips WHERE year=2026 AND month=7")->fetchAll(PDO::FETCH_ASSOC);
$hasPay = $pdo->prepare("SELECT COUNT(*) FROM payslips WHERE year=? AND month=?");
$payCreated = 0;
if ($tpl) {
    $cols = ['employee_id','employee_name','year','month','base_salary','overtime_hours','overtime_pay',
             'meal_allowance','car_allowance','child_allowance','gross_pay','national_pension','health_insurance',
             'emp_insurance','income_tax','total_deduction','net_pay','status','memo'];
    $ph = implode(',', array_map(fn($c) => ":$c", $cols));
    $insPay = $pdo->prepare("INSERT INTO payslips (" . implode(',', $cols) . ") VALUES ($ph)");
    foreach ([5, 6] as $pm) {
        $hasPay->execute([2026, $pm]);
        if ((int)$hasPay->fetchColumn() > 0) { note($log, "payslips 2026-$pm : 이미 존재 → skip"); continue; }
        foreach ($tpl as $row) {
            $bind = [];
            foreach ($cols as $c) {
                if ($c === 'month') { $bind[":$c"] = $pm; }
                elseif ($c === 'status') { $bind[":$c"] = 'paid'; }
                else { $bind[":$c"] = $row[$c]; }
            }
            $insPay->execute($bind);
            $payCreated++;
        }
        note($log, "payslips 2026-$pm : " . count($tpl) . "건 생성 (7월 기준 복제)");
    }
} else {
    note($log, "payslips : 7월 템플릿 없음 → 급여 시드 skip");
}

/* ─────────────────────────────────────────────
 * 5) 세액공제 시뮬레이션 (tax_credit_simulations)
 * ───────────────────────────────────────────── */
$hasSim = $pdo->prepare("SELECT COUNT(*) FROM tax_credit_simulations WHERE sim_year=?");
$insSim = $pdo->prepare("
    INSERT INTO tax_credit_simulations
      (company_id, sim_year, base_employee_count, current_employee_count, youth_count, elder_count,
       region, youth_credit_per, elder_credit_per, total_credit, memo)
    VALUES (1, :yr, :base, :cur, :youth, :elder, '수도권', 11000000, 7700000, :total, :memo)
");
$simRows = [
    [2025, 18, 20, 5, 1, '2025년 고용증대 세액공제 시뮬레이션'],
    [2026, 20, 22, 6, 1, '2026년 고용증대 세액공제 시뮬레이션'],
];
$simCreated = 0;
foreach ($simRows as $sr) {
    // $sr = [sim_year, base_cnt, current_cnt, youth_cnt, elder_cnt, memo]
    $hasSim->execute([$sr[0]]);
    if ((int)$hasSim->fetchColumn() > 0) { note($log, "tax_credit_simulations {$sr[0]} : 이미 존재 → skip"); continue; }
    $total = $sr[3] * 11000000 + $sr[4] * 7700000;
    $insSim->execute([
        ':yr' => $sr[0], ':base' => $sr[1], ':cur' => $sr[2], ':youth' => $sr[3], ':elder' => $sr[4],
        ':total' => $total, ':memo' => $sr[5],
    ]);
    $simCreated++;
}
note($log, "tax_credit_simulations : {$simCreated}건 생성");

/* ─────────────────────────────────────────────
 * 6) 서류 업로드 (doc_uploads) — 완료/확인된 요청에 파일 부착
 * ───────────────────────────────────────────── */
$hasUp = $pdo->prepare("SELECT COUNT(*) FROM doc_uploads WHERE request_id=?");
$insUp = $pdo->prepare("
    INSERT INTO doc_uploads (request_id, file_name, file_path, file_size, uploaded_by)
    VALUES (:rid, :fn, :fp, :sz, 1)
");
$doneReqs = $pdo->query("SELECT id, doc_name FROM doc_requests WHERE status IN ('업로드완료','확인완료')")->fetchAll(PDO::FETCH_ASSOC);
$upCreated = 0;
foreach ($doneReqs as $dr) {
    $hasUp->execute([$dr['id']]);
    if ((int)$hasUp->fetchColumn() > 0) continue;
    $fn = preg_replace('/[^\w가-힣]+/u', '_', $dr['doc_name']) . '.pdf';
    $insUp->execute([':rid' => $dr['id'], ':fn' => $fn, ':fp' => "/uploads/docs/{$dr['id']}_" . $fn, ':sz' => 248000]);
    $upCreated++;
}
note($log, "doc_uploads : {$upCreated}건 생성");

$pdo->commit();

echo "\n=== 완료 요약 ===\n";
echo "tax_invoices    +{$invCreated}\n";
echo "tax_invoice_items +{$itemCreated}\n";
echo "bank_transactions +{$bankCreated}\n";
echo "card_expenses   +{$cardCreated}\n";
echo "payslips        +{$payCreated}\n";
echo "tax_credit_sim  +{$simCreated}\n";
echo "doc_uploads     +{$upCreated}\n";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, "시드 실패 (롤백됨): " . $e->getMessage() . "\n");
    exit(1);
}
