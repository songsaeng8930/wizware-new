-- 대시보드 이번 주 위젯 더미데이터 (2026-06-15 ~ 2026-06-21)
-- 실행: mysql -u root zaemit_groupware < db/seed_dashboard_week.sql

-- ─── 1. 일정 (schedules) ───
INSERT INTO schedules (title, description, start_date, start_time, end_date, end_time, is_all_day, category_item_id, creator_id, status) VALUES
('주간 팀 회의', '개발팀 주간 진행상황 공유', '2026-06-15', '10:00:00', '2026-06-15', '11:00:00', 0, 62, 1, 'active'),
('거래처 미팅', '(주)한솔 계약 갱신 논의', '2026-06-16', '14:00:00', '2026-06-16', '15:30:00', 0, 63, 1, 'active'),
('신입사원 온보딩', '6월 입사자 교육 프로그램', '2026-06-17', '09:00:00', '2026-06-18', '17:00:00', 0, 65, 6, 'active'),
('대표이사 출장', '서울 본사 경영 보고', '2026-06-19', NULL, '2026-06-20', NULL, 1, 64, 1, 'active'),
('월간 마감 회의', '6월 실적 중간 점검', '2026-06-20', '16:00:00', '2026-06-20', '17:00:00', 0, 62, 7, 'active');

-- ─── 2. 전자결재 (approval_documents + approval_history) ───
INSERT INTO approval_documents (doc_number, title, doc_type, drafter_name, drafter_dept, status, draft_date) VALUES
('Zaemit_개발_품의_20260616093000', '6월 클라우드 인프라 비용 청구', '품의서', '강개발', '개발1팀', '진행', '2026-06-16'),
('Zaemit_경영_출장_20260617100000', '부산 거래처 출장 신청', '출장신청서', '김대표', '대표이사실', '기안', '2026-06-17'),
('Zaemit_인사_휴가_20260618140000', '연차 사용 신청 (6/23~24)', '휴가신청서', '정지원', '경영지원팀', '진행', '2026-06-18'),
('Zaemit_재무_품의_20260619110000', '사무용품 구매 품의', '품의서', '오재무', '재무회계팀', '기안', '2026-06-19'),
('Zaemit_영업_품의_20260615160000', '영업 마케팅 비용 집행', '품의서', '최영업', '영업팀', '진행', '2026-06-15');

-- 결재이력 (일부 결재 대기 상태)
INSERT INTO approval_history (document_id, approver_name, approver_dept, step_order, action, comment, action_date) VALUES
-- 6월 클라우드 비용: 이본부장 승인 → 김대표 대기
((SELECT id FROM approval_documents WHERE doc_number='Zaemit_개발_품의_20260616093000'), '이본부장', '경영지원본부', 1, '승인', '확인', '2026-06-16 14:00:00'),
((SELECT id FROM approval_documents WHERE doc_number='Zaemit_개발_품의_20260616093000'), '김대표', '대표이사실', 2, '대기', NULL, NULL),
-- 연차 신청: 한인사 대기
((SELECT id FROM approval_documents WHERE doc_number='Zaemit_인사_휴가_20260618140000'), '한인사', '인사팀', 1, '대기', NULL, NULL),
-- 영업 마케팅 비용: 이본부장 승인 → 김대표 대기
((SELECT id FROM approval_documents WHERE doc_number='Zaemit_영업_품의_20260615160000'), '이본부장', '경영지원본부', 1, '승인', NULL, '2026-06-15 17:00:00'),
((SELECT id FROM approval_documents WHERE doc_number='Zaemit_영업_품의_20260615160000'), '김대표', '대표이사실', 2, '대기', NULL, NULL);

-- ─── 3. 자원예약 (reservations) ───
-- 공통코드에 예약 자원이 없을 수 있으므로 resource_item_id=0으로 삽입 (FK 없음)
INSERT INTO reservations (resource_item_id, title, user_name, reservation_date, start_time, end_time, description, status) VALUES
(0, '회의실 A 예약', '김대표', '2026-06-16', '14:00:00', '15:30:00', '거래처 미팅용', 'confirmed'),
(0, '프로젝터 대여', '강개발', '2026-06-17', '09:00:00', '12:00:00', '신입사원 교육', 'confirmed'),
(0, '회의실 B 예약', '김대표', '2026-06-18', '10:00:00', '11:00:00', '팀장 회의', 'confirmed'),
(0, '법인차량 예약', '김대표', '2026-06-19', '08:00:00', '18:00:00', '서울 출장', 'confirmed'),
(0, '회의실 A 예약', '오재무', '2026-06-20', '16:00:00', '17:00:00', '월간 마감 회의', 'confirmed');
