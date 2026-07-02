-- Zaemit 그룹웨어 - 전자결재 모듈 테이블
-- MySQL 9.6 호환
-- 실행: mysql -u root zaemit_groupware < db/schema_approval.sql

-- 결재양식
CREATE TABLE IF NOT EXISTS approval_forms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doc_type VARCHAR(50) NOT NULL COMMENT '문서종류',
    title VARCHAR(200) NOT NULL COMMENT '양식 제목',
    content_template TEXT NULL COMMENT '양식 HTML 템플릿',
    is_active TINYINT(1) DEFAULT 1 COMMENT '사용유무',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 결재선 설정
CREATE TABLE IF NOT EXISTS approval_lines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT '결재선 이름',
    department VARCHAR(100) NULL COMMENT '소속/부서',
    doc_type VARCHAR(50) NULL COMMENT '문서종류',
    line_data JSON NULL COMMENT '결재선 정보 (결재자 목록 JSON)',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 결재 문서
CREATE TABLE IF NOT EXISTS approval_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doc_number VARCHAR(100) NOT NULL COMMENT '문서번호',
    title VARCHAR(300) NOT NULL COMMENT '제목',
    content TEXT NULL COMMENT '문서 내용',
    metadata JSON NULL COMMENT '연동 데이터 (예: {"source":"card_expense","source_id":123})',
    form_id INT NULL COMMENT '결재양식 ID',
    doc_type VARCHAR(50) NOT NULL COMMENT '문서종류',
    drafter_name VARCHAR(50) NOT NULL COMMENT '기안자',
    drafter_dept VARCHAR(100) NOT NULL COMMENT '기안부서',
    status VARCHAR(30) NOT NULL DEFAULT '기안' COMMENT '상태 (기안/진행/승인/반려/임시저장)',
    draft_date DATE NOT NULL COMMENT '기안일',
    complete_date DATE NULL COMMENT '완료일',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (form_id) REFERENCES approval_forms(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 결재 이력 (각 결재자의 승인/반려 기록)
CREATE TABLE IF NOT EXISTS approval_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    approver_name VARCHAR(50) NOT NULL COMMENT '결재자',
    approver_dept VARCHAR(100) NULL COMMENT '결재자 부서',
    step_order INT NOT NULL DEFAULT 0 COMMENT '결재 순서',
    action VARCHAR(20) NOT NULL DEFAULT '대기' COMMENT '처리 (대기/승인/반려/협의)',
    comment TEXT NULL COMMENT '의견',
    action_date DATETIME NULL COMMENT '처리일',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES approval_documents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 참조자
CREATE TABLE IF NOT EXISTS approval_references (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    ref_name VARCHAR(50) NOT NULL COMMENT '참조자',
    ref_dept VARCHAR(100) NULL COMMENT '참조자 부서',
    read_at DATETIME NULL COMMENT '열람일',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES approval_documents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 샘플: 결재양식
INSERT INTO approval_forms (doc_type, title, is_active, created_at) VALUES
('법인카드 지출', '법인카드 지출', 1, '2025-09-23'),
('변경품의서', '원가변경품의', 0, '2022-11-17'),
('지급품의서', '비용지급품의', 0, '2022-11-17'),
('발의품의서', '발의품의서', 0, '2022-11-17'),
('기안취소', '기안취소', 0, '2022-11-17'),
('휴일근무', '휴일근무', 1, '2022-11-15'),
('휴가신청서', '휴가신청서', 1, '2022-07-22'),
('출장신청서', '출장신청서', 1, '2022-07-22'),
('외근신청서', '외근신청서', 1, '2022-07-22'),
('야근신청서', '야근신청서', 1, '2022-07-22'),
('품의서', '품의서', 1, '2022-07-22'),
('경비청구서', '경비청구서', 1, '2022-07-22');

-- 샘플: 결재선 설정 (슬롯 기반 · 조직도에서 동적 해소)
INSERT INTO approval_lines (name, department, doc_type, line_data) VALUES
('휴가 예외', '', '휴가신청서', '[{"type":"slot","slot":"부서장","role":"결재"},{"type":"person","name":"한인사","id":6,"dept":"인사팀","role":"전결"}]'),
('경비 예외', '', '경비청구서', '[{"type":"slot","slot":"부서장","role":"결재"},{"type":"person","name":"오재무","id":7,"dept":"재무회계팀","role":"전결"}]');

-- 샘플: 결재문서 (기안중/결재중/완료)
INSERT INTO approval_documents (doc_number, title, doc_type, drafter_name, drafter_dept, status, draft_date, complete_date) VALUES
('Zaemit_개발_품의_20260212110327', 'NHN클라우드 2026년 01월 청구서', '품의서', '강개발', '개발1팀', '진행', '2026-02-12', NULL),
('Zaemit_개발_품의_20251212153040', 'NHN클라우드 2025년 11월 청구서', '품의서', '강개발', '개발1팀', '승인', '2025-12-12', '2025-12-13'),
('Zaemit_개발_품의_20251111163956', 'NHN클라우드 2025년 10월 청구서', '품의서', '강개발', '개발1팀', '승인', '2025-11-11', '2025-11-12'),
('Zaemit_개발_품의_20251021133245', 'NHN클라우드 2025년 9월 청구서', '품의서', '강개발', '개발1팀', '승인', '2025-10-21', '2025-10-22'),
('Zaemit_개발_품의_20250912084647', 'NHN클라우드 2025년 8월 청구서', '품의서', '강개발', '개발1팀', '승인', '2025-09-12', '2025-09-13'),
('Zaemit_개발_품의_20250808093827', 'NHN클라우드 2025년 7월 청구서', '품의서', '강개발', '개발1팀', '승인', '2025-08-08', '2025-08-09'),
('Zaemit_개발_품의_20250715091450', 'NHN클라우드 2025년 6월 청구서', '품의서', '강개발', '개발1팀', '승인', '2025-07-15', '2025-07-16'),
('Zaemit_개발_품의_20250619163208', 'NHN클라우드 2025년 5월 청구서', '품의서', '강개발', '개발1팀', '승인', '2025-06-19', '2025-06-20'),
('ZgAi_개발_품의_20250520162607', 'NHN클라우드 2025년 4월 청구서', '품의서', '강개발', '개발1팀', '승인', '2025-05-20', '2025-05-21'),
('ZgAi_개발_품의_20250421150148', 'NHN클라우드 2025년 3월 청구서', '품의서', '강개발', '개발1팀', '승인', '2025-04-21', '2025-04-22'),
('Zaemit_개발_휴가_20260225100000', '연차 사용 신청', '휴가신청서', '김영수', 'Zaemit 개발', '기안', '2026-02-25', NULL),
('Zaemit_개발_출장_20260224090000', '부산 출장 신청', '출장신청서', '이정민', 'Zaemit 개발', '임시저장', '2026-02-24', NULL),
('Zaemit_경영_품의_20260220140000', '사무용품 구매 품의', '품의서', '박지현', '위즈웨어 경영지원', '반려', '2026-02-20', '2026-02-21');

-- 샘플: 결재이력
INSERT INTO approval_history (document_id, approver_name, approver_dept, step_order, action, comment, action_date) VALUES
(1, '이정민', 'Zaemit 개발', 1, '승인', '확인했습니다.', '2026-02-12 14:00:00'),
(1, '최민호', '경영진', 2, '대기', NULL, NULL),
(2, '이정민', 'Zaemit 개발', 1, '승인', NULL, '2025-12-12 16:00:00'),
(2, '최민호', '경영진', 2, '승인', NULL, '2025-12-13 09:30:00'),
(11, '이정민', 'Zaemit 개발', 1, '대기', NULL, NULL),
(13, '최민호', '경영진', 1, '반려', '금액 재확인 필요', '2026-02-21 10:00:00');

-- 샘플: 참조자
INSERT INTO approval_references (document_id, ref_name, ref_dept, read_at) VALUES
(1, '박지현', '위즈웨어 경영지원', NULL),
(1, '김영수', 'Zaemit 개발', '2026-02-12 15:00:00'),
(2, '박지현', '위즈웨어 경영지원', '2025-12-13 10:00:00');
