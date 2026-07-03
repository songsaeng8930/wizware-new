<?php
/**
 * 결재 감사로그 더미데이터 시드 (테스트용)
 * - 테이블: approval_audit_log
 * - 목적: 이벤트/카테고리 필터가 실제로 동작함을 확인할 수 있도록 다양한 event_type·event_category 채움
 *   (기존엔 document_viewed 만 있어 다른 필터가 항상 0건 → "필터가 안 되는 것처럼" 보였음)
 * - 멱등: document_viewed 외 데이터가 이미 있으면 skip. 재실행 안전.
 *
 * event_type ↔ event_category 매핑 (api/approval_audit.php 상수 기준):
 *   document_* → document / form_* → form / config_changed → config / admin_* → admin
 *
 * 실행: php db/seed_audit_log_dummy.php
 */
require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();
if (!$pdo) { fwrite(STDERR, "DB 연결 실패\n"); exit(1); }
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$already = (int)$pdo->query("SELECT COUNT(*) FROM approval_audit_log WHERE event_type <> 'document_viewed'")->fetchColumn();
if ($already > 0) {
    echo "approval_audit_log : 이미 다양한 이벤트 {$already}건 존재 → skip (재실행 안전)\n";
    exit(0);
}

/*
 * [event_type, category, actor_id, actor_name, target_type, target_id, target_label,
 *  old_value(array|null), new_value(array|null), comment, created_at]
 */
$rows = [
    // ── document ──
    ['document_created',   'document', 5, '정부장', 'document', 101, 'Zaemit_영업_지출결의_20260618', null, null, null, '2026-06-18 09:14:00'],
    ['document_submitted', 'document', 5, '정부장', 'document', 101, 'Zaemit_영업_지출결의_20260618', null, null, null, '2026-06-18 09:20:00'],
    ['document_updated',   'document', 6, '한부장', 'document', 102, 'Zaemit_경영_품의서_20260620', ['title' => '비품 구매 품의'], ['title' => '비품 구매 품의(수정)'], '제목 오기 수정', '2026-06-20 11:02:00'],
    ['document_deleted',   'document', 1, '김대표', 'document', 103, 'Zaemit_TEST_반려정리_1776222076', null, null, '중복 기안 삭제', '2026-06-22 16:45:00'],
    ['document_deleted',   'document', 2, '이이사', 'document', 104, 'Zaemit_기술_외주계약_20260625', null, null, '오작성 문서 삭제', '2026-06-25 10:30:00'],

    // ── form ──
    ['form_created',       'form', 1, '김대표', 'form', 11, '지출결의서 양식 v2', null, null, null, '2026-06-19 13:40:00'],
    ['form_updated',       'form', 1, '김대표', 'form', 11, '지출결의서 양식 v2', ['fields' => 8], ['fields' => 10], '필드 2개 추가', '2026-06-24 15:12:00'],
    ['form_toggled',       'form', 1, '김대표', 'form', 12, '휴가신청서 양식', ['active' => true], ['active' => false], '사용 중지', '2026-06-27 09:05:00'],

    // ── line (결재선 설정) → config 카테고리 ──
    ['config_changed',     'config', 1, '김대표', 'config', 3, '금액별 결재선 기준 변경', ['threshold' => 1000000], ['threshold' => 3000000], '전결 한도 상향', '2026-06-30 10:22:00'],

    // ── admin (관리자 조작) ──
    ['admin_change_approver',      'admin', 1, '김대표', 'document', 105, 'Zaemit_영업_외근_20260321090000', ['approver' => '윤과장'], ['approver' => '박이사'], '담당자 부재로 결재자 변경', '2026-07-01 14:18:00'],
    ['admin_force_complete',       'admin', 1, '김대표', 'document', 106, 'Zaemit_경영_긴급승인_20260702', ['status' => '진행'], ['status' => '승인'], '긴급 사안 강제 승인 처리', '2026-07-02 09:33:00'],
    ['admin_force_reject',         'admin', 1, '김대표', 'document', 107, 'Zaemit_TEST_반려정리_1776222076', ['status' => '진행'], ['status' => '반려'], '규정 위반으로 강제 반려', '2026-07-02 17:50:00'],
    ['admin_soft_delete',          'admin', 1, '김대표', 'document', 108, 'Zaemit_기술_폐기문서_20260415', null, ['deleted' => true], '보존기간 만료 문서 정리', '2026-07-03 08:40:00'],
];

$ins = $pdo->prepare("
    INSERT INTO approval_audit_log
      (event_type, event_category, actor_id, actor_name, ip_address,
       target_type, target_id, target_label, old_value, new_value, comment, created_at)
    VALUES (:et, :cat, :aid, :an, '127.0.0.1', :tt, :tid, :tl, :ov, :nv, :cm, :ca)
");

$pdo->beginTransaction();
try {
    $byCat = [];
    foreach ($rows as $r) {
        [$et, $cat, $aid, $an, $tt, $tid, $tl, $ov, $nv, $cm, $ca] = $r;
        $ins->execute([
            ':et'  => $et, ':cat' => $cat, ':aid' => $aid, ':an' => $an,
            ':tt'  => $tt, ':tid' => $tid, ':tl' => $tl,
            ':ov'  => $ov !== null ? json_encode($ov, JSON_UNESCAPED_UNICODE) : null,
            ':nv'  => $nv !== null ? json_encode($nv, JSON_UNESCAPED_UNICODE) : null,
            ':cm'  => $cm, ':ca' => $ca,
        ]);
        $byCat[$cat] = ($byCat[$cat] ?? 0) + 1;
    }
    $pdo->commit();
    echo "생성 완료 (총 " . count($rows) . "건)\n";
    foreach ($byCat as $c => $n) echo "  카테고리 $c : {$n}건\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, "시드 실패 (롤백됨): " . $e->getMessage() . "\n");
    exit(1);
}
