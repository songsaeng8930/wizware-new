<?php
/**
 * 계정별원장 데모 시드 — 수동 조정 분개(journal_entries) 복식부기 더미
 *
 * - journal_entries 테이블이 없으면 스키마를 먼저 생성한다 (db/schema_journal_entries.sql).
 * - 2025-07 ~ 2026-07 각 월마다 계정과목별 균형 분개를 넣어, 어느 달/계정을 골라도
 *   계정별원장에 거래가 표시되게 한다.
 * - 멱등: memo 가 '[샘플]' 로 시작하는 기존 분개를 지우고 다시 넣는다.
 *
 * 실행: php db/seed_journal_demo.php
 */

require_once __DIR__ . '/../config/database.php';
$pdo = getDBConnection();
if (!$pdo) { fwrite(STDERR, "DB 연결 실패\n"); exit(1); }
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$TAG = '[샘플]';

// 1) 테이블 보장 (스키마 파일 그대로 적용)
$schema = file_get_contents(__DIR__ . '/schema_journal_entries.sql');
if ($schema !== false) {
    $pdo->exec($schema);
}

// 계정과목명 (account_categories 기준)
$acct = [
    '10100' => '현금',       '25100' => '외상매입금', '40100' => '상품매출',
    '40200' => '제품매출',   '40400' => '임대수입',   '45100' => '상품매입',
    '45200' => '원재료매입', '80900' => '감가상각비', '82700' => '차량유지비',
    'G_FA'  => '유형자산',
];
$n = fn($c) => $acct[$c] ?? $c;

// 월별로 넣을 분개 템플릿: [차변코드, 대변코드, 적요, 기준금액]
$templates = [
    ['10100', '40100', '상품 판매 대금 입금',   3200000],
    ['10100', '40200', '제품 판매 대금 입금',   2800000],
    ['10100', '40400', '사무실 임대료 수령',     1500000],
    ['45100', '25100', '상품 외상 매입',         2100000],
    ['45200', '25100', '원재료 매입',            1750000],
    ['82700', '10100', '법인차량 주유·정비',      420000],
    ['80900', 'G_FA',  '월 감가상각 계상',        650000],
];

// 2) 기존 샘플 제거 (멱등)
$deleted = $pdo->prepare("DELETE FROM journal_entries WHERE memo LIKE ?");
$deleted->execute([$TAG . '%']);
$delCount = $deleted->rowCount();

$ins = $pdo->prepare("INSERT INTO journal_entries
    (entry_date, description, debit_code, debit_name, credit_code, credit_name,
     amount, entry_type, period_year, period_month, memo)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'adjusting', ?, ?, ?)");

// 3) 2025-07 ~ 2026-07 (13개월)
$count = 0;
$ym = ['year' => 2025, 'month' => 7];
for ($i = 0; $i < 13; $i++) {
    $y = $ym['year'];
    $m = $ym['month'];
    foreach ($templates as $idx => $t) {
        [$dc, $cc, $desc, $base] = $t;
        // 월·항목별로 금액에 변화
        $amount = $base + (($y * 12 + $m + $idx) % 7) * 50000;
        $day = 3 + ($idx * 4) % 25;               // 월 중 분산
        $date = sprintf('%04d-%02d-%02d', $y, $m, $day);
        $ins->execute([
            $date, $desc, $dc, $n($dc), $cc, $n($cc),
            $amount, $y, $m, $TAG . ' ' . $desc,
        ]);
        $count++;
    }
    // 다음 달
    $ym['month']++;
    if ($ym['month'] > 12) { $ym['month'] = 1; $ym['year']++; }
}

printf("완료: 샘플삭제 %d건 · 삽입 %d건 (2025-07~2026-07, 월 %d개 분개)\n",
    $delCount, $count, count($templates));

// 요약
$sum = $pdo->query("SELECT COUNT(*) c, MIN(entry_date) mn, MAX(entry_date) mx FROM journal_entries")->fetch(PDO::FETCH_ASSOC);
printf("journal_entries 총 %d건 · 기간 %s ~ %s\n", $sum['c'], $sum['mn'], $sum['mx']);
