<?php
/**
 * 직원 정보 변경요청 더미데이터 시드 (테스트용)
 * - 테이블: employee_change_requests
 * - 상태 분포: 대기 / 승인 / 반려 골고루 (탭별 확인용)
 * - 멱등: 이미 데이터가 있으면 전체 skip. 재실행해도 중복 없음.
 *
 * changes_json 형식(API 규약): { field: {"old": 이전값, "new": 새값}, ... }
 * 승인 가능 필드는 birth_date/gender 뿐이므로 '대기' 행은 birth_date만 사용
 * (관리자가 실제로 승인 눌러도 반영되도록). 이미 처리된 행은 주소 등도 혼합.
 *
 * 실행: php db/seed_change_requests_dummy.php
 */
require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();
if (!$pdo) { fwrite(STDERR, "DB 연결 실패\n"); exit(1); }
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$existing = (int)$pdo->query("SELECT COUNT(*) FROM employee_change_requests")->fetchColumn();
if ($existing > 0) {
    echo "employee_change_requests : 이미 {$existing}건 존재 → skip (재실행 안전)\n";
    exit(0);
}

$REVIEWER = 1; // 김대표(admin)

/*
 * [employee_id, status, changes(json배열), reason, reject_reason, created_at, reviewed_at]
 * changes: [ [field, old, new], ... ]
 */
$rows = [
    // ── 대기 (birth_date만 → 승인 가능) ──
    [13, '대기', [['birth_date', '1994-03-08', '1994-03-18']], '주민등록상 생년월일과 달라 정정 요청드립니다.', null, '2026-07-01 09:12:00', null],
    [16, '대기', [['birth_date', '1996-04-09', '1996-04-05']], '가족관계증명서 기준으로 정정 부탁드립니다.', null, '2026-07-02 14:33:00', null],
    [19, '대기', [['birth_date', '1994-09-03', '1994-09-13']], '생년월일 오기 정정 요청합니다.', null, '2026-07-03 10:05:00', null],
    [11, '대기', [['birth_date', '1987-05-25', '1987-05-15']], '생년월일이 실제와 달라 정정 요청드립니다.', null, '2026-07-03 11:20:00', null],

    // ── 승인 (처리 완료) ──
    [9,  '승인', [['zipcode', '16514', '13529'], ['address1', '경기도 수원시 영통구 광교중앙로 170', '경기도 성남시 분당구 판교역로 235'], ['address2', '광교 엘포레 209동 1503호', '판교 알파리움 102동 801호']], '판교로 이사하여 주소 변경 요청합니다.', null, '2026-06-20 15:40:00', '2026-06-21 09:10:00'],
    [14, '승인', [['birth_date', '1992-07-16', '1992-07-26']], '생년월일 정정 요청드립니다.', null, '2026-06-25 13:15:00', '2026-06-25 16:02:00'],
    [10, '승인', [['zipcode', '06035', '06194'], ['address1', '서울시 강남구 가로수길 53', '서울시 강남구 선릉로 525'], ['address2', '신사동 한진타운 302호', '삼성동 아이파크 201동 1102호']], '이사로 인한 주소 변경입니다.', null, '2026-06-28 10:50:00', '2026-06-28 17:30:00'],

    // ── 반려 (사유 포함) ──
    [18, '반려', [['address1', '서울시 강동구 천호대로 1077', '서울시 송파구 문정동 622'], ['address2', '래미안 솔베뉴 305동 801호', '송파 헬리오시티 118동 2304호']], '주소 변경 요청합니다.', '전입신고 증빙 서류가 첨부되지 않았습니다. 서류 첨부 후 재요청 바랍니다.', '2026-06-22 09:00:00', '2026-06-23 11:15:00'],
    [17, '반려', [['birth_date', '1993-06-27', '1990-06-27']], '생년월일 정정 요청드립니다.', '연도 차이가 커 확인이 필요합니다. 가족관계증명서 제출 후 재요청 바랍니다.', '2026-06-26 14:20:00', '2026-06-27 09:45:00'],
];

$ins = $pdo->prepare("
    INSERT INTO employee_change_requests
      (employee_id, status, changes_json, reason, reject_reason, reviewed_by, reviewed_at, created_at, updated_at)
    VALUES (:eid, :st, :cj, :reason, :rej, :rby, :rat, :cat, :uat)
");

$pdo->beginTransaction();
try {
    $counts = ['대기' => 0, '승인' => 0, '반려' => 0];
    foreach ($rows as $r) {
        [$eid, $st, $changes, $reason, $rej, $cat, $rat] = $r;
        $cj = [];
        foreach ($changes as $c) {
            $cj[$c[0]] = ['old' => $c[1], 'new' => $c[2]];
        }
        $resolved = $st !== '대기';
        $ins->execute([
            ':eid'    => $eid,
            ':st'     => $st,
            ':cj'     => json_encode($cj, JSON_UNESCAPED_UNICODE),
            ':reason' => $reason,
            ':rej'    => $st === '반려' ? $rej : null,
            ':rby'    => $resolved ? $REVIEWER : null,
            ':rat'    => $resolved ? $rat : null,
            ':cat'    => $cat,
            ':uat'    => $resolved ? $rat : $cat,
        ]);
        $counts[$st]++;
    }
    $pdo->commit();
    echo "생성 완료 — 대기 {$counts['대기']} / 승인 {$counts['승인']} / 반려 {$counts['반려']} (총 " . count($rows) . "건)\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, "시드 실패 (롤백됨): " . $e->getMessage() . "\n");
    exit(1);
}
