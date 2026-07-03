<?php
/**
 * 문서보관함 더미데이터 시드 (테스트용)
 * - 대상: approval_documents(회수/임시저장) + approval_references(참조문서함)
 * - 기준 사용자: 김대표(id=1) — 회수함/임시저장함은 본인 기안, 참조문서함은 본인이 참조자
 * - 멱등: 김대표 회수 문서가 이미 있으면 skip. 재실행 안전.
 *
 * getArchive 규약(api/approval.php):
 *   회수함  = status '회수'  AND drafter_id = 1
 *   임시저장함 = status '임시저장' AND drafter_id = 1
 *   참조문서함 = status IN('승인','반려') + approval_references.ref_id = 1
 *
 * doc_number 포맷은 기존 규약 유지: Zaemit_<유형>_YYYYMMDDHHMMSS
 *
 * 실행: php db/seed_archive_dummy.php
 */
require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();
if (!$pdo) { fwrite(STDERR, "DB 연결 실패\n"); exit(1); }
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$ME_ID = 1; $ME_NAME = '김대표'; $ME_DEPT = '(주)재밋';

$already = (int)$pdo->query("SELECT COUNT(*) FROM approval_documents WHERE status='회수' AND drafter_id=1")->fetchColumn();
if ($already > 0) {
    echo "문서보관함 : 이미 회수 문서 {$already}건 존재 → skip (재실행 안전)\n";
    exit(0);
}

$insDoc = $pdo->prepare("
    INSERT INTO approval_documents
      (doc_number, title, content, doc_type, drafter_id, drafter_name, drafter_dept, status, draft_date, complete_date)
    VALUES (:num, :title, :content, :type, :did, :dname, :ddept, :status, :ddate, :cdate)
");
$insRef = $pdo->prepare("
    INSERT INTO approval_references (document_id, ref_id, ref_name, ref_dept, read_at)
    VALUES (:doc, :rid, :rname, :rdept, :read)
");

$pdo->beginTransaction();
try {
    $counts = ['recalled' => 0, 'temp' => 0, 'reference' => 0];

    /* ── 회수함: 김대표 기안 · status 회수 ── */
    $recalled = [
        ['Zaemit_품의서_20260601100000',   '6월 워크숍 비용 품의 (회수)',        '품의서',   '2026-06-01', '2026-06-02'],
        ['Zaemit_출장신청서_20260528140000', '대전 공공기관 입찰 출장 (일정 변경)', '출장신청서', '2026-05-28', '2026-05-29'],
        ['Zaemit_경비청구서_20260520110000', '클라우드 서버 비용 청구 (금액 수정)', '경비청구서', '2026-05-20', '2026-05-21'],
    ];
    foreach ($recalled as $r) {
        // $r = [doc_number, title, doc_type, draft_date, complete_date]
        $insDoc->execute([
            ':num' => $r[0], ':title' => $r[1], ':content' => $r[1] . ' 내용입니다.', ':type' => $r[2],
            ':did' => $ME_ID, ':dname' => $ME_NAME, ':ddept' => $ME_DEPT,
            ':status' => '회수', ':ddate' => $r[3], ':cdate' => $r[4],
        ]);
        $counts['recalled']++;
    }

    /* ── 임시저장함: 김대표 기안 · status 임시저장 (완료일 없음) ── */
    $temp = [
        ['Zaemit_품의서_20260702093000',   '3분기 채용 계획 품의 (작성 중)',   '품의서',   '2026-07-02'],
        ['Zaemit_지출결의서_20260630170000', '사무용품 정기 구매 지출결의 (초안)', '지출결의서', '2026-06-30'],
        ['Zaemit_휴가신청서_20260629110000', '하계 휴가 신청 (날짜 미정)',       '휴가신청서', '2026-06-29'],
    ];
    foreach ($temp as $t) {
        $insDoc->execute([
            ':num' => $t[0], ':title' => $t[1], ':content' => $t[1] . ' 내용입니다.', ':type' => $t[2],
            ':did' => $ME_ID, ':dname' => $ME_NAME, ':ddept' => $ME_DEPT,
            ':status' => '임시저장', ':ddate' => $t[3], ':cdate' => null,
        ]);
        $counts['temp']++;
    }

    /* ── 참조문서함: 타 직원 기안(승인/반려) + 김대표를 참조자로 지정 ── */
    $refs = [
        ['Zaemit_품의서_20260615100000',   '개발장비 추가 구매 품의',   '품의서',   5,  '정부장', '경영지원팀', '승인', '2026-06-15', '2026-06-17', '2026-06-17 14:30:00'],
        ['Zaemit_경비청구서_20260610140000', '외주 개발 용역비 청구',     '경비청구서', 9,  '윤과장', '개발2팀',   '승인', '2026-06-10', '2026-06-12', null],
        ['Zaemit_출장신청서_20260605090000', '부산 채용박람회 출장 신청', '출장신청서', 14, '이대리', '인사팀',    '반려', '2026-06-05', '2026-06-06', '2026-06-06 09:15:00'],
    ];
    foreach ($refs as $r) {
        $insDoc->execute([
            ':num' => $r[0], ':title' => $r[1], ':content' => $r[1] . ' 내용입니다.', ':type' => $r[2],
            ':did' => $r[3], ':dname' => $r[4], ':ddept' => $r[5],
            ':status' => $r[6], ':ddate' => $r[7], ':cdate' => $r[8],
        ]);
        $docId = (int)$pdo->lastInsertId();
        $insRef->execute([
            ':doc' => $docId, ':rid' => $ME_ID, ':rname' => $ME_NAME, ':rdept' => $ME_DEPT, ':read' => $r[9],
        ]);
        $counts['reference']++;
    }

    $pdo->commit();
    echo "생성 완료 — 회수함 {$counts['recalled']} / 임시저장함 {$counts['temp']} / 참조문서함 {$counts['reference']}\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, "시드 실패 (롤백됨): " . $e->getMessage() . "\n");
    exit(1);
}
