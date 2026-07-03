<?php
/**
 * 세금계산서 매칭/패턴 더미데이터 시드 (테스트용)
 * - 테이블: match_patterns, invoice_bank_mappings (+ match_history)
 * - 선행: 위 테이블이 존재해야 함(schema_tax_invoice.sql). 없으면 안내 후 종료.
 * - 멱등: 각 테이블에 데이터가 있으면 개별 skip. 재실행 안전.
 *
 * 매핑은 2026-07 계산서 ↔ 2026-07 통장거래를 거래처명 기준으로 연결.
 * 상태 다양화: 확정완료 / 매칭완료(미확정) / 이름불일치 / 미매칭.
 *
 * 실행: php db/seed_match_dummy.php
 */
require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();
if (!$pdo) { fwrite(STDERR, "DB 연결 실패\n"); exit(1); }
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 선행 테이블 확인 (information_schema — SHOW TABLES LIKE ? 는 prepare 불가)
$tblChk = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
foreach (['match_patterns', 'invoice_bank_mappings', 'tax_invoices', 'bank_transactions'] as $t) {
    $tblChk->execute([$t]);
    if (!(int)$tblChk->fetchColumn()) { fwrite(STDERR, "필수 테이블 없음: {$t}\n"); exit(1); }
}

$pdo->beginTransaction();
try {

/* ── 1) 매칭 패턴 ── */
$patCount = (int)$pdo->query("SELECT COUNT(*) FROM match_patterns")->fetchColumn();
$patCreated = 0;
if ($patCount > 0) {
    echo "match_patterns : 이미 {$patCount}건 → skip\n";
} else {
    $insPat = $pdo->prepare("
        INSERT INTO match_patterns
          (partner_name, partner_bizno, pattern_type, pattern_rule, confidence, hit_count, miss_count, last_matched_at, source, is_active)
        VALUES (:pn, :pb, :pt, :pr, :conf, :hit, :miss, :lm, :src, :active)
    ");
    // [거래처명, 사업자번호, 유형, rule배열, 신뢰도%, 적중, 미적중, 최근적중일, 출처, 활성]
    $patterns = [
        ['(주)테크솔루션', '234-56-78901', 'description_keyword', ['keyword' => '테크솔루션'], 92.0, 5, 0, '2026-07-05 09:10:00', 'ai',   1],
        ['(주)테크솔루션', '234-56-78901', 'amount_exact',       ['amount' => 16500000],       95.0, 3, 0, '2026-07-05 09:10:00', 'user', 1],
        ['(주)스마트커머스', '456-78-90123', 'description_keyword', ['keyword' => '스마트커머스'], 85.0, 3, 1, '2026-06-25 10:00:00', 'user', 1],
        ['디자인웍스',      '345-67-89012', 'description_keyword', ['keyword' => '디자인웍스'],   70.0, 1, 0, '2026-05-12 14:00:00', 'rule', 1],
        ['사무실 임대료',   null,           'description_keyword', ['keyword' => '임대료'],       80.0, 6, 0, '2026-07-15 00:00:00', 'rule', 1],
        ['법인카드 대금',   null,           'description_keyword', ['keyword' => '법인카드'],     75.0, 4, 1, '2026-07-10 00:00:00', 'rule', 1],
        ['4대보험',        null,           'description_keyword', ['keyword' => '4대보험'],      88.0, 5, 0, '2026-07-25 00:00:00', 'ai',   1],
        ['넥스트비즈(비활성)', '789-01-23456', 'date_offset',    ['offset_days' => 3],          55.0, 0, 2, null,                  'rule', 0],
    ];
    foreach ($patterns as $p) {
        $insPat->execute([
            ':pn' => $p[0], ':pb' => $p[1], ':pt' => $p[2],
            ':pr' => json_encode($p[3], JSON_UNESCAPED_UNICODE),
            ':conf' => $p[4], ':hit' => $p[5], ':miss' => $p[6], ':lm' => $p[7], ':src' => $p[8], ':active' => $p[9],
        ]);
        $patCreated++;
    }
    echo "match_patterns : {$patCreated}건 생성\n";
}

/* ── 2) 세금계산서-통장 매핑 (2026-07) ── */
$mapCount = (int)$pdo->query("SELECT COUNT(*) FROM invoice_bank_mappings")->fetchColumn();
$mapCreated = 0;
if ($mapCount > 0) {
    echo "invoice_bank_mappings : 이미 {$mapCount}건 → skip\n";
} else {
    // 실제 존재하는 id인지 확인하며 삽입 (환경별 id 변동 방어)
    $invByNo = $pdo->query("
        SELECT id, invoice_type, buyer_name, supplier_name, total_amount
        FROM tax_invoices WHERE DATE_FORMAT(issue_date,'%Y-%m')='2026-07'
    ")->fetchAll(PDO::FETCH_ASSOC);
    $txByDesc = $pdo->query("
        SELECT id, description, tx_type, amount
        FROM bank_transactions WHERE DATE_FORMAT(transaction_date,'%Y-%m')='2026-07'
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 헬퍼: 계산서를 거래처명 부분일치로 찾기
    $findInv = function (string $needle) use ($invByNo) {
        foreach ($invByNo as $r) {
            $name = $r['invoice_type'] === '매출' ? $r['buyer_name'] : $r['supplier_name'];
            if (mb_strpos($name, $needle) !== false) return $r;
        }
        return null;
    };
    $findTx = function (string $needle) use ($txByDesc) {
        foreach ($txByDesc as $r) if (mb_strpos($r['description'], $needle) !== false) return $r;
        return null;
    };

    // [계산서검색어, 통장검색어, match_type, confidence, reason, name_warning, is_confirmed, confirmed_at]
    $maps = [
        ['테크솔루션',   '테크솔루션',   'auto',   95, '거래처명 일치 · 입금 확인',       0, 1, '2026-07-06 10:00:00'],
        ['스마트커머스', '스마트커머스', 'auto',   88, '거래처명 일치',                   0, 0, null],
        ['대한전자',     '법인카드',     'manual', 52, '금액 유사 · 거래처명 불일치(수동)', 1, 0, null],
        ['한빛통신',     '4대보험',      'auto',   40, '거래처명 불일치 · 검토 필요',      1, 0, null],
    ];
    $insMap = $pdo->prepare("
        INSERT INTO invoice_bank_mappings
          (invoice_id, transaction_id, match_type, confidence, match_reason, name_warning, aggregate_flag, is_confirmed, confirmed_at)
        VALUES (:inv, :tx, :mt, :conf, :reason, :nw, 'none', :conf2, :cat)
    ");
    $insHist = $pdo->prepare("
        INSERT INTO match_history (mapping_id, invoice_id, transaction_id, action, actor, memo)
        VALUES (:mid, :inv, :tx, :act, :actor, :memo)
    ");
    foreach ($maps as $m) {
        $inv = $findInv($m[0]); $tx = $findTx($m[1]);
        if (!$inv || !$tx) { echo "  매핑 skip (대상없음): {$m[0]} / {$m[1]}\n"; continue; }
        $insMap->execute([
            ':inv' => $inv['id'], ':tx' => $tx['id'], ':mt' => $m[2], ':conf' => $m[3],
            ':reason' => $m[4], ':nw' => $m[5], ':conf2' => $m[6], ':cat' => $m[7],
        ]);
        $mid = (int)$pdo->lastInsertId();
        $insHist->execute([
            ':mid' => $mid, ':inv' => $inv['id'], ':tx' => $tx['id'],
            ':act' => $m[2] === 'manual' ? 'manual_match' : 'auto_match',
            ':actor' => $m[2] === 'manual' ? '김대표' : 'system',
            ':memo' => $m[4],
        ]);
        if ($m[6]) {
            $insHist->execute([':mid' => $mid, ':inv' => $inv['id'], ':tx' => $tx['id'], ':act' => 'confirm', ':actor' => '김대표', ':memo' => '매칭 확정']);
        }
        $mapCreated++;
    }
    echo "invoice_bank_mappings : {$mapCreated}건 생성\n";
}

$pdo->commit();
echo "\n=== 완료 === 패턴 +{$patCreated} / 매핑 +{$mapCreated}\n";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, "시드 실패 (롤백됨): " . $e->getMessage() . "\n");
    exit(1);
}
