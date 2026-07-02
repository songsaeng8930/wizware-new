SET NAMES utf8mb4;

-- 대기 문서(21, 22)는 아직 결재이력 없음

-- 진행: 강개발 NHN클라우드 (id=23) · 박기술 승인, 김대표 대기
INSERT INTO approval_history (document_id, approver_name, approver_dept, step_order, action, comment, action_date) VALUES
(23, '박기술', '기술개발본부', 1, '승인', '확인했습니다.', '2026-03-13 10:00:00'),
(23, '김대표', '(주)재밋', 2, '대기', NULL, NULL);

-- 진행: 김경영 사무용품 (id=24) · 정지원 승인, 오재무 대기, 이본부장 대기
INSERT INTO approval_history (document_id, approver_name, approver_dept, step_order, action, comment, action_date) VALUES
(24, '정지원', '경영지원팀', 1, '승인', '승인합니다.', '2026-03-11 14:00:00'),
(24, '오재무', '재무회계팀', 2, '대기', NULL, NULL),
(24, '이본부장', '경영지원본부', 3, '대기', NULL, NULL);

-- 진행: 조풀스택 출장 (id=25) · 윤개발 승인, 박기술 승인, 김대표 대기
INSERT INTO approval_history (document_id, approver_name, approver_dept, step_order, action, comment, action_date) VALUES
(25, '윤개발', '개발2팀', 1, '승인', '확인.', '2026-03-05 15:00:00'),
(25, '박기술', '기술개발본부', 2, '승인', '승인합니다.', '2026-03-06 09:00:00'),
(25, '김대표', '(주)재밋', 3, '대기', NULL, NULL);

-- 진행: 이인사 교육예산 (id=26) · 한인사 승인, 이본부장 대기
INSERT INTO approval_history (document_id, approver_name, approver_dept, step_order, action, comment, action_date) VALUES
(26, '한인사', '인사팀', 1, '승인', '교육 예산 승인합니다.', '2026-03-16 11:00:00'),
(26, '이본부장', '경영지원본부', 2, '대기', NULL, NULL);

-- 반려: 서영업 접대비 (id=27) · 최영업 승인, 오재무 반려
INSERT INTO approval_history (document_id, approver_name, approver_dept, step_order, action, comment, action_date) VALUES
(27, '최영업', '영업본부', 1, '승인', '확인했습니다.', '2026-03-02 10:00:00'),
(27, '오재무', '재무회계팀', 2, '반려', '증빙서류 부족합니다.', '2026-03-03 14:00:00');
