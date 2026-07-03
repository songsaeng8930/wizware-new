<?php
/**
 * 계좌관리(은행) 더미데이터 시드 — 연결계좌 / 거래내역 / 분류 / 분류규칙 탭 채우기
 *
 * 하는 일 (전부 멱등 · 재실행해도 중복/훼손 없음):
 *  1) 누락 테이블 생성      : classification_patterns, classification_history (db/schema_tax.sql 기준 DDL)
 *  2) 계정과목 보강         : account_categories 표준 5자리 코드 INSERT ... ON DUPLICATE (분류 드롭다운용)
 *  3) 연결계좌              : 기존 3계좌 용도(급여/세금) 지정 + 예비계좌 1개 추가
 *  4) 거래내역              : 급여/세금/예비 계좌에 월별 거래 적재 + 러닝 잔액 재계산
 *  5) 분류                  : 미확정 거래에 계정과목 제안(ai_confidence) 채우고 일부는 확정 처리
 *  6) 분류규칙              : classification_patterns 규칙 시드 + classification_history 로그
 *
 * 참고: 이건 데모용 시드다. 세율/공제/급여 계산 로직은 건드리지 않고, 분류 메타
 *       (account_code/name/ai_confidence/is_confirmed)와 데모 거래 데이터만 다룬다.
 *
 * 실행: C:\xampp\php\php.exe db/seed_bank_dummy.php   (또는 php db/seed_bank_dummy.php)
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();
if (!$pdo) { fwrite(STDERR, "DB 연결 실패\n"); exit(1); }
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function note(string $msg): void { echo $msg . "\n"; }

$SEED_TAG = 'seed_bank_dummy';           // 시드가 넣은 거래 표식(멱등 판별)
$months   = ['2026-01','2026-02','2026-03','2026-04','2026-05','2026-06','2026-07'];

/* ─────────────────────────────────────────────
 * 1) 누락 테이블 생성 (db/schema_tax.sql 과 동일 DDL)
 *    DDL 은 MySQL 에서 암묵적 커밋을 일으키므로 트랜잭션 밖에서 먼저 실행.
 * ───────────────────────────────────────────── */
$pdo->exec("
CREATE TABLE IF NOT EXISTS classification_patterns (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    keyword         VARCHAR(100) NOT NULL,
    tx_type         ENUM('입금','출금','전체') NOT NULL DEFAULT '전체',
    account_code    VARCHAR(20)  NOT NULL,
    account_name    VARCHAR(50)  NOT NULL,
    amount_min      BIGINT       NULL,
    amount_max      BIGINT       NULL,
    counterparty    VARCHAR(100) NULL,
    recurrence      ENUM('none','daily','weekly','monthly','quarterly','semi_annual','annual') NOT NULL DEFAULT 'none',
    recurrence_day  TINYINT      NULL,
    priority        TINYINT UNSIGNED NOT NULL DEFAULT 0,
    confidence      DECIMAL(5,2) NOT NULL DEFAULT 70,
    hit_count       INT UNSIGNED NOT NULL DEFAULT 0,
    miss_count      INT UNSIGNED NOT NULL DEFAULT 0,
    source          ENUM('rule','user','ai') NOT NULL DEFAULT 'user',
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_keyword_type (keyword, tx_type, account_code),
    INDEX idx_keyword (keyword),
    INDEX idx_account (account_code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='분류 패턴'");

$pdo->exec("
CREATE TABLE IF NOT EXISTS classification_history (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id   INT UNSIGNED NOT NULL,
    old_account_code VARCHAR(20)  NULL,
    new_account_code VARCHAR(20)  NOT NULL,
    new_account_name VARCHAR(50)  NULL,
    action           ENUM('auto_classify','manual_classify','confirm','modify','pattern_edit') NOT NULL,
    pattern_id       INT UNSIGNED NULL,
    actor            VARCHAR(50)  NULL,
    memo             VARCHAR(200) NULL,
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tx (transaction_id),
    INDEX idx_pattern (pattern_id),
    INDEX idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='분류 이력'");
note("1) 테이블 확인/생성: classification_patterns, classification_history");

$pdo->beginTransaction();
try {

/* ─────────────────────────────────────────────
 * 2) 계정과목 보강 (표준 5자리 코드 · 분류 드롭다운용)
 * ───────────────────────────────────────────── */
$pdo->exec("
INSERT INTO account_categories (code, name, parent_code, type, tax_type, is_active, sort_order) VALUES
('12600','계좌간거래',   'G_CA', '자산','불공제',1,1040),
('25400','예수금',       'G_CL', '부채','불공제',1,2020),
('25500','부가세예수금', 'G_CL', '부채','과세',  1,2030),
('41200','서비스매출',   'G_SL', '매출','과세',  1,4030),
('90100','이자수익',     'G_NI', '수익','불공제',1,4520),
('93000','잡이익',       'G_NI', '수익','불공제',1,4530),
('80200','직원급여',     'G_SGA','비용','불공제',1,5010),
('81100','복리후생비',   'G_SGA','비용','과세',  1,5040),
('81200','여비교통비',   'G_SGA','비용','과세',  1,5050),
('81300','접대비',       'G_SGA','비용','불공제',1,5060),
('81400','통신비',       'G_SGA','비용','과세',  1,5070),
('81700','세금과공과금', 'G_SGA','비용','불공제',1,5090),
('81900','지급임차료',   'G_SGA','비용','과세',  1,5100),
('82100','보험료',       'G_SGA','비용','불공제',1,5120),
('82500','교육훈련비',   'G_SGA','비용','과세',  1,5140),
('83000','소모품비',     'G_SGA','비용','과세',  1,5170),
('83100','지급수수료',   'G_SGA','비용','과세',  1,5180),
('83300','광고선전비',   'G_SGA','비용','과세',  1,5190)
ON DUPLICATE KEY UPDATE name=VALUES(name), parent_code=VALUES(parent_code),
    type=VALUES(type), tax_type=VALUES(tax_type), is_active=1");
$catCount = (int)$pdo->query("SELECT COUNT(*) FROM account_categories")->fetchColumn();
note("2) 계정과목 보강 완료 (현재 {$catCount}개)");

/* ─────────────────────────────────────────────
 * 3) 연결계좌 — 용도 지정 + 예비계좌 추가
 * ───────────────────────────────────────────── */
$pdo->prepare("UPDATE bank_accounts SET account_type='급여' WHERE account_alias='급여계좌'")->execute();
$pdo->prepare("UPDATE bank_accounts SET account_type='세금' WHERE account_alias='세금납부계좌'")->execute();

// 주의: 이 DB 의 bank_accounts 에는 (bank_code, account_no) UNIQUE 키가 없어(스키마 드리프트)
// ON DUPLICATE 가 동작하지 않는다. 존재 여부를 직접 확인해 멱등 보장.
$rsvChk = $pdo->prepare("SELECT id FROM bank_accounts WHERE bank_code='WR' AND account_no='456-789-012345' LIMIT 1");
$rsvChk->execute();
if (!$rsvChk->fetchColumn()) {
    $pdo->prepare("
        INSERT INTO bank_accounts (bank_code, bank_name, account_no, account_alias, account_type, owner_name, memo, consent_agreed, consent_agreed_at, sort_order)
        VALUES ('WR','우리은행','456-789-012345','예비자금계좌','예비','주식회사 재밋','비상 운영자금 / 예비비', 1, '2026-01-15 10:00:00', 4)
    ")->execute();
}

$acc = [];
foreach ($pdo->query("SELECT id, account_alias, account_type FROM bank_accounts") as $r) {
    $acc[$r['account_type']] = (int)$r['id'];
}
$idOper = $pdo->query("SELECT id FROM bank_accounts WHERE account_alias='운영계좌'")->fetchColumn();
$idPay  = $pdo->query("SELECT id FROM bank_accounts WHERE account_alias='급여계좌'")->fetchColumn();
$idTax  = $pdo->query("SELECT id FROM bank_accounts WHERE account_alias='세금납부계좌'")->fetchColumn();
$idRsv  = $pdo->query("SELECT id FROM bank_accounts WHERE account_alias='예비자금계좌'")->fetchColumn();
note("3) 연결계좌: 운영#{$idOper} 급여#{$idPay} 세금#{$idTax} 예비#{$idRsv}");

/* ─────────────────────────────────────────────
 * 4) 거래내역 — 급여/세금/예비 계좌에 월별 거래 적재
 *    (운영계좌는 이미 48건 존재 → 손대지 않음)
 * ───────────────────────────────────────────── */
$insTx = $pdo->prepare("
    INSERT INTO bank_transactions
      (account_id, transaction_date, description, counterparty, amount, tx_type, balance,
       account_code, account_name, ai_confidence, is_confirmed, memo)
    VALUES (:acc,:date,:desc,:cp,:amt,:type,0,:code,:cname,:conf,:confirmed,:memo)
    ON DUPLICATE KEY UPDATE memo = VALUES(memo)
");
// 계정 코드→이름 맵
$catMap = $pdo->query("SELECT code, name FROM account_categories WHERE is_active=1")->fetchAll(PDO::FETCH_KEY_PAIR);

/**
 * 월별 거래 세트를 넣는다. rows: [일, 시간, 적요, 거래처, 금액, 입/출, 계정코드|null, 신뢰도|null, 확정0/1]
 * 이미 해당 (계좌,월)에 시드 표식 거래가 있으면 건너뜀(멱등).
 */
function seedMonth(PDO $pdo, $insTx, array $catMap, string $SEED_TAG, int $accId, string $ym, array $rows): int {
    $chk = $pdo->prepare("SELECT COUNT(*) FROM bank_transactions WHERE account_id=? AND DATE_FORMAT(transaction_date,'%Y-%m')=? AND memo=?");
    $chk->execute([$accId, $ym, $SEED_TAG]);
    if ((int)$chk->fetchColumn() > 0) return 0;
    $n = 0;
    foreach ($rows as $r) {
        [$day,$time,$desc,$cp,$amt,$type,$code,$conf,$confirmed] = $r;
        $insTx->execute([
            ':acc'=>$accId, ':date'=>sprintf('%s-%02d',$ym,$day),
            ':desc'=>$desc, ':cp'=>$cp, ':amt'=>$amt, ':type'=>$type,
            ':code'=>$code, ':cname'=>$code ? ($catMap[$code] ?? null) : null,
            ':conf'=>$conf, ':confirmed'=>$confirmed, ':memo'=>$SEED_TAG,
        ]);
        $n++;
    }
    return $n;
}

$txCreated = 0;
foreach ($months as $ym) {
    [$Y,$M] = explode('-', $ym);
    $mi = (int)$M;

    // ── 급여계좌: 운영→급여 이체 입금 + 급여/4대보험 출금 ──
    if ($idPay) {
        $txCreated += seedMonth($pdo, $insTx, $catMap, $SEED_TAG, (int)$idPay, $ym, [
            [5,  '09:10', '운영계좌 급여재원 이체',        '주식회사 재밋',  52000000, '입금', '12600', 100, 1],
            [10, '10:00', "{$mi}월 급여 지급 - 김서준",     '김서준',          3800000,  '출금', '80200', 100, 1],
            [10, '10:00', "{$mi}월 급여 지급 - 이하윤",     '이하윤',          3500000,  '출금', '80200', 100, 1],
            [10, '10:00', "{$mi}월 급여 지급 - 박도현",     '박도현',          4200000,  '출금', '80200', 100, 1],
            [10, '10:00', "{$mi}월 급여 지급 - 최지우",     '최지우',          3100000,  '출금', '80200', 95,  1],
            [10, '14:30', '국민연금 원천징수 납부',          '국민연금공단',    1240000,  '출금', '25400', 92,  0],
            [10, '14:31', '건강보험료 납부',                '국민건강보험공단', 980000,   '출금', '25400', 92,  0],
            [25, '11:20', '식대 지원 - 구내식당 정산',       '한끼식당',        620000,   '출금', '81100', 78,  0],
        ]);
    }

    // ── 세금납부계좌: 운영→세금 이체 입금 + 세금 출금 ──
    if ($idTax) {
        $rowsTax = [
            [5,  '09:15', '운영계좌 세금재원 이체',   '주식회사 재밋', 10000000, '입금', '12600', 100, 1],
            [10, '13:00', '근로소득세 원천징수 납부',  '국세청',        1850000,  '출금', '25400', 96,  1],
            [10, '13:05', '지방소득세 납부',          '관할구청',       185000,   '출금', '81700', 90,  0],
        ];
        // 분기 말(3/6월): 부가가치세
        if (in_array($mi, [1,4,7], true)) {
            $rowsTax[] = [25, '15:00', '부가가치세 예정신고 납부', '국세청', 4200000, '출금', '25500', 97, 1];
        }
        $txCreated += seedMonth($pdo, $insTx, $catMap, $SEED_TAG, (int)$idTax, $ym, $rowsTax);
    }

    // ── 예비자금계좌: 이체 입금 + 이자수익 + 운용 출금(미분류 데모) ──
    if ($idRsv) {
        $interest = 58000 + $mi * 1500;
        $txCreated += seedMonth($pdo, $insTx, $catMap, $SEED_TAG, (int)$idRsv, $ym, [
            [15, '00:05', '정기예금 이자',            '우리은행',       $interest, '입금', '90100', 100, 1],
            [20, '10:40', '운영계좌 예비자금 이체',    '주식회사 재밋',  5000000,   '입금', '12600', 100, 1],
            [28, '16:20', '예비자금 MMF 매입',        '우리자산운용',   3000000,   '출금', null,    null, 0],
        ]);
    }
}
note("4) 거래내역 생성: +{$txCreated}건 (급여/세금/예비 계좌)");

/* ─────────────────────────────────────────────
 * 4-b) 러닝 잔액 재계산 (운영 포함 전 계좌 · 데모 표시용)
 * ───────────────────────────────────────────── */
// 운영계좌는 기존 DEMO_LEDGER 대량 출금이 있어 기초잔액을 넉넉히 잡아 잔액이 음수가 되지 않게 함
$opening = [ (int)$idOper => 200000000, (int)$idPay => 20000000, (int)$idTax => 12000000, (int)$idRsv => 25000000 ];
$updBal = $pdo->prepare("UPDATE bank_transactions SET balance=? WHERE id=?");
foreach ($opening as $accId => $open) {
    if (!$accId) continue;
    $rows = $pdo->prepare("SELECT id, amount, tx_type FROM bank_transactions WHERE account_id=? ORDER BY transaction_date ASC, id ASC");
    $rows->execute([$accId]);
    $running = $open;
    foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $t) {
        $running += ($t['tx_type'] === '입금') ? (int)$t['amount'] : -(int)$t['amount'];
        $updBal->execute([$running, (int)$t['id']]);
    }
}
note("4-b) 러닝 잔액 재계산 완료 (4개 계좌)");

/* ─────────────────────────────────────────────
 * 5) 분류 — 미확정·미분류 거래에 계정과목 제안 채우기 + 일부 확정
 *    (키워드 규칙 · 5자리 코드 기준. is_confirmed 는 대부분 0 으로 두어 '분류 대기' 데모)
 * ───────────────────────────────────────────── */
$suggestRules = [
    // [정규식, 코드, 신뢰도]  (출금 성격)
    ['/급여|월급|상여|봉급/u',                         '80200', 95],
    ['/국민연금|건강보험|고용보험|산재|4대\s?보험/u',   '25400', 92],
    ['/소득세|원천세|원천징수/u',                       '25400', 94],
    ['/부가가치세|부가세/u',                            '25500', 97],
    ['/지방소득세|주민세|재산세|자동차세|세금|공과금/u', '81700', 90],
    ['/임대|임차|월세|사무실\s?임/u',                   '81900', 91],
    ['/통신|인터넷|KT|SKT|LG\s?U|AWS|클라우드|호스팅/iu','81400', 90],
    ['/광고|마케팅|메타|네이버\s?광고|구글\s?광고/iu',   '83300', 90],
    ['/보험료|화재보험/u',                              '82100', 88],
    ['/주유|GS칼텍스|SK에너지|하이패스|주차|택시|교통/iu','82700', 87],
    ['/접대|회식|골프|한우|식대|구내식당/u',            '81100', 76],
    ['/문구|사무용품|소모품|토너|비품|쿠팡/iu',          '83000', 85],
    ['/수수료|용역|외주|컨설팅|대행|자문/u',            '83100', 80],
    ['/카드대금|법인카드/u',                            '83100', 68],
];
$suggestRulesIn = [
    // 입금 성격
    ['/이자|예금\s?이자/u',                             '90100', 96],
    ['/이체|재원|대체/u',                               '12600', 90],
    ['/세금계산서|용역대금|서비스\s?대금|개발\s?대금/u', '41200', 84],
    ['/제품|완제품|납품/u',                             '40200', 80],
    ['/입금|수금|결제\s?대금/u',                        '40100', 70],
];
function suggestOne(string $desc, string $type, array $out, array $in): ?array {
    foreach (($type === '입금' ? $in : $out) as [$re,$code,$conf]) {
        if (preg_match($re, $desc)) return [$code, $conf];
    }
    return null;
}

$un = $pdo->query("SELECT id, description, tx_type FROM bank_transactions WHERE is_confirmed=0 AND (account_code IS NULL OR account_code='')")->fetchAll(PDO::FETCH_ASSOC);
$updSug = $pdo->prepare("UPDATE bank_transactions SET account_code=?, account_name=?, ai_confidence=? WHERE id=?");
$suggested = 0;
foreach ($un as $t) {
    $hit = suggestOne((string)$t['description'], (string)$t['tx_type'], $suggestRules, $suggestRulesIn);
    if (!$hit) continue;
    [$code,$conf] = $hit;
    $updSug->execute([$code, $catMap[$code] ?? null, $conf, (int)$t['id']]);
    $suggested++;
}

// 신뢰도 높은(>=90) 제안 중 절반 정도를 자동 확정 처리 → '확정 완료' 데모
$hi = $pdo->query("SELECT id, account_code, account_name FROM bank_transactions WHERE is_confirmed=0 AND ai_confidence>=90 AND account_code IS NOT NULL ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$confd = $pdo->prepare("UPDATE bank_transactions SET is_confirmed=1 WHERE id=?");
$confirmedRows = [];
foreach ($hi as $i => $t) {
    if ($i % 2 === 0) { $confd->execute([(int)$t['id']]); $confirmedRows[] = $t; }
}
note("5) 분류: 제안 {$suggested}건 채움 · 확정 " . count($confirmedRows) . "건 처리");

/* ─────────────────────────────────────────────
 * 6) 분류규칙 (classification_patterns) + 이력(classification_history)
 * ───────────────────────────────────────────── */
$insPat = $pdo->prepare("
    INSERT INTO classification_patterns
      (keyword, tx_type, account_code, account_name, amount_min, amount_max, counterparty,
       recurrence, recurrence_day, priority, confidence, hit_count, miss_count, source, is_active)
    VALUES (:kw,:type,:code,:name,:amin,:amax,:cp,:rec,:recday,:prio,:conf,:hit,:miss,:src,1)
    ON DUPLICATE KEY UPDATE account_name=VALUES(account_name), confidence=VALUES(confidence),
       recurrence=VALUES(recurrence), recurrence_day=VALUES(recurrence_day),
       priority=VALUES(priority), source=VALUES(source), is_active=1
");
// [키워드, 구분, 코드, min, max, 거래처, 반복, 반복일, 우선순위, 신뢰도, hit, miss, source]
$patterns = [
    ['급여 지급',   '출금','80200', 1000000, 6000000, null,          'monthly', 10, 8, 100, 24, 0, 'user'],
    ['국민연금',    '출금','25400', null, null, '국민연금공단',        'monthly', 10, 7, 100, 6,  0, 'user'],
    ['건강보험',    '출금','25400', null, null, '국민건강보험공단',     'monthly', 10, 7, 100, 6,  0, 'user'],
    ['근로소득세',  '출금','25400', null, null, '국세청',              'monthly', 10, 7, 100, 6,  0, 'user'],
    ['부가가치세',  '출금','25500', null, null, '국세청',              'quarterly', 25, 6, 100, 3, 0, 'user'],
    ['지방소득세',  '출금','81700', null, null, '관할구청',            'monthly', 10, 6, 96,  6,  1, 'user'],
    ['임차료',      '출금','81900', null, null, null,                 'monthly', 15, 5, 100, 12, 0, 'user'],
    ['통신비',      '출금','81400', null, null, null,                 'monthly', null, 4, 92, 9,  1, 'ai'],
    ['AWS',        '출금','81400', null, null, null,                 'monthly', null, 4, 90, 5,  0, 'ai'],
    ['클라우드',    '출금','81400', null, null, null,                 'none',    null, 3, 88, 4,  1, 'ai'],
    ['GS칼텍스',    '출금','82700', null, null, null,                 'none',    null, 3, 88, 7,  2, 'ai'],
    ['메타광고',    '출금','83300', null, null, '메타',                'monthly', null, 5, 90, 4,  0, 'ai'],
    ['정기예금 이자','입금','90100', null, null, null,                 'monthly', 15, 6, 100, 7,  0, 'user'],
    ['이체',        '입금','12600', null, null, '주식회사 재밋',        'monthly', 5,  5, 100, 21, 0, 'user'],
    ['용역대금',    '입금','41200', 1000000, null, null,              'none',    null, 3, 84, 6,  2, 'ai'],
    ['접대비',      '출금','81300', null, 500000, null,               'none',    null, 3, 72, 5,  3, 'ai'],
];
$patCreated = 0;
foreach ($patterns as $p) {
    [$kw,$type,$code,$amin,$amax,$cp,$rec,$recday,$prio,$conf,$hit,$miss,$src] = $p;
    $insPat->execute([
        ':kw'=>$kw, ':type'=>$type, ':code'=>$code, ':name'=>$catMap[$code] ?? '',
        ':amin'=>$amin, ':amax'=>$amax, ':cp'=>$cp, ':rec'=>$rec, ':recday'=>$recday,
        ':prio'=>$prio, ':conf'=>$conf, ':hit'=>$hit, ':miss'=>$miss, ':src'=>$src,
    ]);
    $patCreated++;
}
note("6) 분류규칙: {$patCreated}건 시드");

// 이력 — 방금 확정한 거래에 대해 로그 남김(비어있을 때만)
$histHas = (int)$pdo->query("SELECT COUNT(*) FROM classification_history")->fetchColumn();
$histCreated = 0;
if ($histHas === 0 && $confirmedRows) {
    $insHist = $pdo->prepare("
        INSERT INTO classification_history (transaction_id, new_account_code, new_account_name, action, actor, memo)
        VALUES (?,?,?,?,?,?)
    ");
    foreach ($confirmedRows as $i => $t) {
        $action = ($i % 2 === 0) ? 'confirm' : 'auto_classify';
        $actor  = ($action === 'confirm') ? 'user' : 'system';
        $insHist->execute([(int)$t['id'], $t['account_code'], $t['account_name'], $action, $actor, '더미데이터 분류']);
        $histCreated++;
    }
}
note("6-b) 분류이력: +{$histCreated}건");

$pdo->commit();

echo "\n=== 완료 요약 ===\n";
$sum = fn(string $t, string $w='') => (int)$pdo->query("SELECT COUNT(*) FROM $t $w")->fetchColumn();
echo "bank_accounts            : " . $sum('bank_accounts') . "\n";
echo "bank_transactions        : " . $sum('bank_transactions') . " (확정 " . $sum('bank_transactions',"WHERE is_confirmed=1") . " / 미확정 " . $sum('bank_transactions',"WHERE is_confirmed=0") . ")\n";
echo "classification_patterns  : " . $sum('classification_patterns') . "\n";
echo "classification_history   : " . $sum('classification_history') . "\n";
echo "account_categories       : " . $sum('account_categories') . "\n";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, "시드 실패 (롤백됨): " . $e->getMessage() . "\n");
    exit(1);
}
