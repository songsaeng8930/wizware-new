-- =============================================================
-- Zaemit 그룹웨어 (BMS) - 데이터베이스 통합 초기화 스크립트
-- MySQL 9.6+ / MariaDB 10.6+ 호환
-- =============================================================
-- 실행:
--   mysql -u root -p < db/init.sql
--
-- 주의: 기존 zaemit_groupware DB가 있으면 DROP 후 재생성합니다.
-- =============================================================

-- 데이터베이스 생성
CREATE DATABASE IF NOT EXISTS zaemit_groupware
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE zaemit_groupware;

-- =============================================================
-- 1. 조직/인사 (schema_organization.sql)
-- =============================================================

CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT NULL,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) NULL COMMENT '부서코드',
    head_employee_id INT NULL COMMENT '부서장 ID',
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NULL,
    affiliation VARCHAR(50) NULL COMMENT '소속 (WEVEN, Zaemit 등)',
    name VARCHAR(50) NOT NULL,
    position VARCHAR(50) NULL COMMENT '직급 (대표이사, 이사, 부장, 과장, 대리, 사원 등)',
    title VARCHAR(100) NULL COMMENT '직책 (CEO, CTO, 팀장 등)',
    email VARCHAR(100) NULL,
    phone VARCHAR(20) NULL,
    employment_type VARCHAR(20) DEFAULT '정규직' COMMENT '고용형태 (정규직, 계약직, 시간제, 파견직)',
    employment_status VARCHAR(20) DEFAULT '재직' COMMENT '고용상태 (재직, 휴직, 육아휴직, 퇴사)',
    is_dept_head TINYINT(1) DEFAULT 0 COMMENT '부서장 여부',
    profile_image VARCHAR(255) NULL,
    hire_date DATE NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE departments ADD FOREIGN KEY (head_employee_id) REFERENCES employees(id) ON DELETE SET NULL;

-- 샘플: 부서
INSERT INTO departments (id, parent_id, name, code, sort_order) VALUES
(1, NULL, '(주)재밋', 'ZAEMIT', 0),
(2, 1, '경영지원본부', 'MGT', 1),
(3, 1, '기술개발본부', 'TECH', 2),
(4, 1, '영업본부', 'SALES', 3),
(5, 2, '경영지원팀', 'MGT-SUPPORT', 1),
(6, 2, '인사팀', 'MGT-HR', 2),
(7, 2, '재무회계팀', 'MGT-FIN', 3),
(8, 3, '개발1팀', 'TECH-DEV1', 1),
(9, 3, '개발2팀', 'TECH-DEV2', 2),
(10, 3, 'QA팀', 'TECH-QA', 3),
(11, 4, '국내영업팀', 'SALES-DOM', 1),
(12, 4, '해외영업팀', 'SALES-INT', 2);

-- 샘플: 직원
INSERT INTO employees (id, department_id, affiliation, name, position, title, email, phone, hire_date, employment_type, employment_status) VALUES
(1, 1, 'Zaemit', '김대표', '대표이사', 'CEO', 'ceo@zaemit.com', '010-1234-5678', '2020-01-01', '정규직', '재직'),
(2, 2, 'Zaemit', '이본부장', '이사', '경영지원본부장', 'lee@zaemit.com', '010-2345-6789', '2020-03-01', '정규직', '재직'),
(3, 3, 'Zaemit', '박기술', '이사', 'CTO', 'park@zaemit.com', '010-3456-7890', '2020-03-15', '정규직', '재직'),
(4, 4, 'Zaemit', '최영업', '이사', '영업본부장', 'choi@zaemit.com', '010-4567-8901', '2020-06-01', '정규직', '재직'),
(5, 5, 'Zaemit', '정지원', '부장', '경영지원팀장', 'jung@zaemit.com', '010-5678-9012', '2021-01-10', '정규직', '재직'),
(6, 6, 'Zaemit', '한인사', '부장', '인사팀장', 'han@zaemit.com', '010-6789-0123', '2021-02-01', '정규직', '재직'),
(7, 7, 'Zaemit', '오재무', '부장', '재무회계팀장', 'oh@zaemit.com', '010-7890-1234', '2021-03-01', '정규직', '재직'),
(8, 8, 'Zaemit', '강개발', '부장', '개발1팀장', 'kang@zaemit.com', '010-8901-2345', '2021-04-01', '정규직', '재직'),
(9, 9, 'Zaemit', '윤개발', '과장', '개발2팀장', 'yoon@zaemit.com', '010-9012-3456', '2021-05-01', '정규직', '재직'),
(10, 10, 'Zaemit', '임품질', '과장', 'QA팀장', 'lim@zaemit.com', '010-0123-4567', '2021-06-01', '정규직', '재직'),
(11, 11, 'Zaemit', '서영업', '과장', '국내영업팀장', 'seo@zaemit.com', '010-1111-2222', '2021-07-01', '정규직', '재직'),
(12, 12, 'Zaemit', '류해외', '과장', '해외영업팀장', 'ryu@zaemit.com', '010-3333-4444', '2021-08-01', '정규직', '재직'),
(13, 5, 'Zaemit', '김경영', '대리', NULL, 'kimm@zaemit.com', '010-5555-6666', '2022-01-15', '정규직', '재직'),
(14, 6, 'Zaemit', '이인사', '대리', NULL, 'leehr@zaemit.com', '010-7777-8888', '2022-03-01', '정규직', '재직'),
(15, 8, 'Zaemit', '박프론트', '대리', NULL, 'parkfe@zaemit.com', '010-9999-0000', '2022-04-01', '정규직', '재직'),
(16, 8, 'Zaemit', '송백엔드', '사원', NULL, 'songbe@zaemit.com', '010-1212-3434', '2023-01-02', '계약직', '재직'),
(17, 9, 'Zaemit', '조풀스택', '대리', NULL, 'jofs@zaemit.com', '010-5656-7878', '2022-06-01', '정규직', '재직'),
(18, 9, 'Zaemit', '황모바일', '사원', NULL, 'hwangmb@zaemit.com', '010-9090-1212', '2023-03-01', '시간제', '재직'),
(19, 10, 'Zaemit', '문테스터', '대리', NULL, 'moonqa@zaemit.com', '010-3434-5656', '2022-07-01', '정규직', '휴직'),
(20, 11, 'Zaemit', '배영업', '대리', NULL, 'baes@zaemit.com', '010-7878-9090', '2022-09-01', '파견직', '재직');

-- 부서장 설정
UPDATE departments SET head_employee_id = 1 WHERE id = 1;
UPDATE departments SET head_employee_id = 2 WHERE id = 2;
UPDATE departments SET head_employee_id = 3 WHERE id = 3;
UPDATE departments SET head_employee_id = 4 WHERE id = 4;
UPDATE departments SET head_employee_id = 5 WHERE id = 5;
UPDATE departments SET head_employee_id = 6 WHERE id = 6;
UPDATE departments SET head_employee_id = 7 WHERE id = 7;
UPDATE departments SET head_employee_id = 8 WHERE id = 8;
UPDATE departments SET head_employee_id = 9 WHERE id = 9;
UPDATE departments SET head_employee_id = 10 WHERE id = 10;
UPDATE departments SET head_employee_id = 11 WHERE id = 11;
UPDATE departments SET head_employee_id = 12 WHERE id = 12;

UPDATE employees SET is_dept_head = 1 WHERE id IN (1,2,3,4,5,6,7,8,9,10,11,12);

-- 근로계약 (계약직/시간제/파견직 계약기간 관리)
CREATE TABLE IF NOT EXISTS labor_contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    contract_type VARCHAR(20) NOT NULL COMMENT '계약유형 (계약직, 시간제, 파견직)',
    start_date DATE NOT NULL COMMENT '계약 시작일',
    end_date DATE NULL COMMENT '계약 종료일',
    memo TEXT NULL COMMENT '비고',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO labor_contracts (employee_id, contract_type, start_date, end_date, memo) VALUES
(16, '계약직', '2023-01-02', '2025-12-31', '개발1팀 백엔드 계약직'),
(18, '시간제', '2023-03-01', '2024-08-31', '개발2팀 모바일 시간제'),
(20, '파견직', '2022-09-01', '2025-06-30', '국내영업팀 파견직');

-- =============================================================
-- 2. 공통코드 (schema_common_codes.sql)
-- =============================================================

CREATE TABLE IF NOT EXISTS common_code_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module VARCHAR(30) NOT NULL COMMENT '모듈 (hr, attendance, card, business, reservation, schedule)',
    name VARCHAR(100) NOT NULL COMMENT '공통정보명',
    description TEXT NULL COMMENT '비고',
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS common_code_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    code VARCHAR(50) NULL COMMENT '코드',
    name VARCHAR(100) NOT NULL COMMENT '항목명(레이블)',
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES common_code_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 참고: 직급/직책은 hr_ranks/hr_duties 전용 테이블로 관리 (공통코드 아님)

-- 샘플: 인사
INSERT INTO common_code_groups (module, name, description, sort_order) VALUES
('hr', '고용형태', '직원의 고용 형태 분류', 1),
('hr', '고용상태', '직원의 재직/퇴직 상태 분류', 2);

INSERT INTO common_code_items (group_id, code, name, sort_order) VALUES
(1,'FT','정규직',1),(1,'CT','계약직',2),(1,'PT','시간제',3),(1,'DP','파견직',4),
(2,'ACT','재직',1),(2,'LOA','휴직',2),(2,'MAT','육아휴직',3),(2,'RES','퇴사',4);

-- 샘플: 근태
INSERT INTO common_code_groups (module, name, description, sort_order) VALUES
('attendance', '근무유형', '출퇴근 근무 유형 분류', 1),
('attendance', '휴가유형', '연차/반차 등 휴가 유형', 2);

INSERT INTO common_code_items (group_id, code, name, sort_order) VALUES
(3,'NRM','정상근무',1),(3,'WFH','재택근무',2),(3,'OUT','외근',3),(3,'BIZ','출장',4),(3,'OT','야근',5),(3,'HOL','휴일근무',6),
(4,'AL','연차',1),(4,'HAM','반차(오전)',2),(4,'HAP','반차(오후)',3),(4,'SL','병가',4),(4,'FL','경조사',5),(4,'OL','공가',6);

-- 샘플: 법인카드
INSERT INTO common_code_groups (module, name, description, sort_order) VALUES
('card', '비용항목', '법인카드 사용 시 비용 분류 항목', 1),
('card', '카드유형', '법인카드 종류', 2);

INSERT INTO common_code_items (group_id, code, name, sort_order) VALUES
(5,'FOOD','식대',1),(5,'TRANS','교통비',2),(5,'ENT','접대비',3),(5,'SUP','소모품',4),(5,'ETC','기타',5),
(6,'CORP','법인카드',1),(6,'PRIV','개인카드',2);

-- 샘플: 사업
INSERT INTO common_code_groups (module, name, description, sort_order) VALUES
('business', '사업원가항목', '사업 비용 책정 시 입력하는 사업원가항목 구분', 1),
('business', '사업상태', '사업 진행 상태 분류', 2),
('business', '사업구분', '사업 유형 분류', 3);

INSERT INTO common_code_items (group_id, code, name, sort_order) VALUES
(7,'OS_C','외주비(기업)',1),(7,'OS_P','외주비(개인)',2),(7,'RES','자원구입비',3),(7,'MKT','마케팅 수수료',4),(7,'PRM','사업 판촉비',5),(7,'EXP','진행 경비',6),(7,'FREE','무상 서비스 원가',7),
(8,'SALES','영업',1),(8,'CONT','계약',2),(8,'PROG','진행중',3),(8,'DONE','완료',4),(8,'HOLD','보류',5),
(9,'SI','SI',1),(9,'SM','SM',2),(9,'CONS','컨설팅',3),(9,'EDU','교육',4);

-- 샘플: 자원예약
INSERT INTO common_code_groups (module, name, description, sort_order) VALUES
('reservation', '자원목록', '회사가 보유하고 운영하는 자원 정보 (회의실, 비품, 차량 등)', 1);

INSERT INTO common_code_items (group_id, code, name, sort_order) VALUES
(10,'MR1','319호 - 회의실',1),(10,'MR2','319호 - 탕비실(회의용)',2),(10,'NB1','노트북 1 (내부)',3),(10,'TAB','태블릿',4);

-- 샘플: 일정
INSERT INTO common_code_groups (module, name, description, sort_order) VALUES
('schedule', '일정유형', '일정 분류 유형', 1),
('schedule', '캘린더 색상', '일정 캘린더 색상 구분', 2);

INSERT INTO common_code_items (group_id, code, name, sort_order) VALUES
(11,'MTG','회의',1),(11,'EXT','외부미팅',2),(11,'TRIP','출장',3),(11,'EDU','교육',4),(11,'ETC','기타',5),(11,'OUT','외근',6),(11,'INTV','면담',7),(11,'EVT','행사',8),(11,'DUE','마감',9),
(12,'BLUE','파랑',1),(12,'RED','빨강',2),(12,'GREEN','초록',3),(12,'YELLOW','노랑',4),(12,'PURPLE','보라',5);

-- =============================================================
-- 3. 전자결재 (schema_approval.sql)
-- =============================================================

CREATE TABLE IF NOT EXISTS approval_forms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doc_type VARCHAR(50) NOT NULL COMMENT '문서종류',
    title VARCHAR(200) NOT NULL COMMENT '양식 제목',
    content_template TEXT NULL COMMENT '양식 HTML 템플릿',
    is_active TINYINT(1) DEFAULT 1 COMMENT '사용유무',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS approval_lines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT '결재선 이름',
    department VARCHAR(100) NULL COMMENT '소속/부서',
    doc_type VARCHAR(50) NULL COMMENT '문서종류',
    line_data JSON NULL COMMENT '결재선 정보 (결재자 목록 JSON)',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS approval_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doc_number VARCHAR(100) NOT NULL COMMENT '문서번호',
    title VARCHAR(300) NOT NULL COMMENT '제목',
    content TEXT NULL COMMENT '문서 내용',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS approval_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    approver_id INT NULL COMMENT '결재자 employees.id',
    approver_name VARCHAR(50) NOT NULL COMMENT '결재자 이름 (스냅샷)',
    approver_dept VARCHAR(100) NULL COMMENT '결재자 부서 (스냅샷)',
    `role` VARCHAR(10) NOT NULL DEFAULT '결재' COMMENT '역할 (결재/전결/참조)',
    step_order INT NOT NULL DEFAULT 0 COMMENT '결재 순서',
    action VARCHAR(20) NOT NULL DEFAULT '대기' COMMENT '처리 (대기/승인/반려/건너뜀/협의)',
    comment TEXT NULL COMMENT '의견',
    action_date DATETIME NULL COMMENT '처리일',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_approver_id (approver_id),
    FOREIGN KEY (document_id) REFERENCES approval_documents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS approval_references (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    ref_id INT NULL COMMENT '참조자 employees.id',
    ref_name VARCHAR(50) NOT NULL COMMENT '참조자 이름 (스냅샷)',
    ref_dept VARCHAR(100) NULL COMMENT '참조자 부서 (스냅샷)',
    read_at DATETIME NULL COMMENT '열람일',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ref_id (ref_id),
    FOREIGN KEY (document_id) REFERENCES approval_documents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- 샘플: 결재선 (슬롯 기반)
INSERT INTO approval_lines (name, department, doc_type, line_data) VALUES
('휴가 예외', '', '휴가신청서', '[{"type":"slot","slot":"부서장","slot_key":"department.head","role":"결재"},{"type":"person","name":"한인사","id":6,"dept":"인사팀","role":"전결"}]'),
('경비 예외', '', '경비청구서', '[{"type":"slot","slot":"부서장","slot_key":"department.head","role":"결재"},{"type":"person","name":"오재무","id":7,"dept":"재무회계팀","role":"전결"}]');

-- 샘플: 결재문서
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

-- =============================================================
-- 4. 법인카드 (schema_card.sql)
-- =============================================================

CREATE TABLE IF NOT EXISTS cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_alias VARCHAR(100) NOT NULL COMMENT '카드별칭',
    card_number VARCHAR(30) NULL COMMENT '카드번호 (마스킹)',
    memo TEXT NULL COMMENT '비고',
    manager_name VARCHAR(50) NOT NULL COMMENT '책임자',
    affiliation VARCHAR(100) NULL COMMENT '소속',
    department VARCHAR(100) NULL COMMENT '부서',
    is_active TINYINT(1) DEFAULT 1 COMMENT '사용여부',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS card_expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id INT NOT NULL,
    registrant_name VARCHAR(50) NOT NULL COMMENT '등록자',
    approval_number VARCHAR(50) NULL COMMENT '승인번호',
    usage_type VARCHAR(20) NOT NULL DEFAULT '법인' COMMENT '사용구분 (법인/개인)',
    category VARCHAR(50) NOT NULL COMMENT '항목 (식대,교통비,접대비 등)',
    sub_category VARCHAR(50) NULL COMMENT '세부항목',
    amount INT NOT NULL DEFAULT 0 COMMENT '사용금액',
    description TEXT NULL COMMENT '적요',
    business_name VARCHAR(200) NULL COMMENT '사업명',
    business_code VARCHAR(50) NULL COMMENT '사업코드',
    document_number VARCHAR(50) NULL COMMENT '문서번호',
    user_name VARCHAR(50) NULL COMMENT '사용자',
    usage_date DATE NOT NULL COMMENT '사용일',
    is_settled TINYINT(1) DEFAULT 0 COMMENT '정산여부',
    compliance_status VARCHAR(20) DEFAULT '미확인' COMMENT '규정준수여부 (준수/미준수/미확인)',
    settlement_updater VARCHAR(50) NULL COMMENT '정산 업데이트 작성자',
    settlement_date DATETIME NULL COMMENT '최종 업데이트일',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS card_regulations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL COMMENT '항목',
    sub_category VARCHAR(100) NOT NULL COMMENT '세부항목',
    limit_amount INT DEFAULT 0 COMMENT '한도 (원)',
    required_fields TEXT NULL COMMENT '입력시 필수사항',
    guide TEXT NULL COMMENT '세부항목 가이드',
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS card_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_id INT NULL COMMENT '관련 지출내역 ID',
    card_id INT NOT NULL,
    approval_number VARCHAR(50) NOT NULL COMMENT '승인번호',
    merchant_name VARCHAR(200) NULL COMMENT '가맹점명',
    approval_amount INT NOT NULL DEFAULT 0 COMMENT '승인금액',
    approval_date DATETIME NOT NULL COMMENT '승인일시',
    approval_status VARCHAR(20) DEFAULT '승인' COMMENT '승인상태 (승인/취소)',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE,
    FOREIGN KEY (expense_id) REFERENCES card_expenses(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 샘플: 법인카드
INSERT INTO cards (card_alias, card_number, memo, manager_name, affiliation, department, is_active) VALUES
('영업팀 법인카드', '9410-****-****-1234', '영업팀 업무용', '김영수', '위즈웨어', '영업팀', 1),
('개발팀 법인카드', '9410-****-****-5678', '개발팀 업무용', '이정민', '위즈웨어', '개발팀', 1),
('경영지원 법인카드', '5412-****-****-9012', '경영지원실 업무용', '박지현', '위즈웨어', '경영지원실', 1),
('대표이사 법인카드', '4532-****-****-3456', '대표이사 전용', '최민호', '위즈웨어', '경영진', 1),
('마케팅팀 법인카드', '9410-****-****-7890', '마케팅 업무용', '정수진', '위즈웨어', '마케팅팀', 0);

-- 샘플: 지출내역
INSERT INTO card_expenses (card_id, registrant_name, approval_number, usage_type, category, sub_category, amount, description, business_name, document_number, user_name, usage_date, is_settled, compliance_status) VALUES
(1, '김영수', 'AP-2026-001', '법인', '식대', '식사', 45000, '거래처 미팅 식사', 'A프로젝트', 'DOC-2026-0101', '김영수', '2026-02-20', 0, '미확인'),
(1, '김영수', 'AP-2026-002', '법인', '교통비', '여비교통비', 32000, '거래처 방문 택시비', 'A프로젝트', 'DOC-2026-0102', '김영수', '2026-02-21', 0, '미확인'),
(2, '이정민', 'AP-2026-003', '법인', '소모품', '구입비', 128000, '개발용 장비 구입', '', '', '이정민', '2026-02-19', 1, '준수'),
(3, '박지현', 'AP-2026-004', '법인', '접대비', '영업사업비', 85000, '고객사 접대', 'B프로젝트', 'DOC-2026-0201', '박지현', '2026-02-18', 1, '준수'),
(1, '홍길동', 'AP-2026-005', '개인', '교통비', '여비교통비', 15000, '외근 교통비', '', '', '홍길동', '2026-02-22', 0, '미확인'),
(2, '이정민', 'AP-2026-006', '법인', '식대', '식사', 62000, '팀 회식', '', '', '이정민', '2026-02-17', 0, '미준수'),
(4, '최민호', 'AP-2026-007', '법인', '접대비', '영업사업비', 250000, 'VIP 고객 미팅', 'C프로젝트', 'DOC-2026-0301', '최민호', '2026-02-15', 1, '준수'),
(3, '박지현', 'AP-2026-008', '법인', '기타', '구입비', 35000, '사무용품 구입', '', '', '박지현', '2026-02-16', 0, '미확인'),
(1, '김영수', 'AP-2026-009', '법인', '식대', '식사', 55000, '프로젝트 킥오프 식사', 'D프로젝트', 'DOC-2026-0401', '김영수', '2026-02-23', 0, '미확인'),
(2, '서지우', 'AP-2026-010', '개인', '교통비', '여비교통비', 28000, '출장 교통비', 'A프로젝트', '', '서지우', '2026-02-24', 0, '미확인');

-- 샘플: 규정
INSERT INTO card_regulations (category, sub_category, limit_amount, required_fields, guide, sort_order) VALUES
('식사', '중식/석식', 15000, '영수증, 참석자명단', '1인당 15,000원 이내. 4인 이상 시 사전 승인 필요', 1),
('식사', '회식', 50000, '영수증, 참석자명단, 사유서', '1인당 50,000원 이내. 팀장 사전승인 필수', 2),
('식사', '간식/음료', 10000, '영수증', '1인당 10,000원 이내', 3),
('여비교통비', '시내교통', 0, '영수증, 출발/도착지', '실비 정산. 택시 이용 시 사유 기재 필수', 4),
('여비교통비', '출장교통', 0, '영수증, 출장보고서', '실비 정산. KTX 이상 시 사전승인 필요', 5),
('여비교통비', '주차비/톨비', 0, '영수증', '실비 정산', 6),
('영업사업비', '거래처 접대', 100000, '영수증, 접대보고서', '1건당 100,000원 이내. 초과 시 부서장 승인', 7),
('영업사업비', '경조사비', 50000, '경조사 증빙', '건당 50,000원. 경조사 규정 참조', 8),
('영업사업비', '선물/기념품', 30000, '영수증, 사유서', '1건당 30,000원 이내', 9),
('구입비', '사무용품', 50000, '영수증, 구매요청서', '건당 50,000원 이내. 초과 시 구매부서 경유', 10),
('구입비', '소프트웨어', 0, '영수증, 구매요청서, 라이선스 정보', 'IT부서 사전 승인 필수', 11),
('구입비', '장비/비품', 0, '영수증, 구매요청서, 자산등록', '50만원 이상 시 자산등록 필수', 12);

-- 샘플: 승인내역
INSERT INTO card_approvals (expense_id, card_id, approval_number, merchant_name, approval_amount, approval_date, approval_status) VALUES
(1, 1, 'AP-2026-001', '한우마을 강남점', 45000, '2026-02-20 12:30:00', '승인'),
(2, 1, 'AP-2026-002', '카카오택시', 32000, '2026-02-21 09:15:00', '승인'),
(3, 2, 'AP-2026-003', '쿠팡', 128000, '2026-02-19 14:20:00', '승인'),
(4, 3, 'AP-2026-004', '스시오마카세', 85000, '2026-02-18 18:45:00', '승인'),
(5, 1, 'AP-2026-005', '카카오택시', 15000, '2026-02-22 10:00:00', '승인'),
(6, 2, 'AP-2026-006', '고깃집 서초점', 62000, '2026-02-17 19:30:00', '승인'),
(7, 4, 'AP-2026-007', '르씨엘 레스토랑', 250000, '2026-02-15 12:00:00', '승인'),
(8, 3, 'AP-2026-008', '오피스디포', 35000, '2026-02-16 11:10:00', '승인'),
(9, 1, 'AP-2026-009', '더플레이스 역삼', 55000, '2026-02-23 12:15:00', '승인'),
(10, 2, 'AP-2026-010', 'KTX', 28000, '2026-02-24 08:00:00', '승인'),
(NULL, 1, 'AP-2026-011', 'GS25 역삼점', 8500, '2026-02-25 15:30:00', '승인'),
(NULL, 3, 'AP-2026-012', '네이버페이', 42000, '2026-02-25 16:00:00', '취소');

-- =============================================================
-- 5. 자원예약 (schema_reservation.sql)
-- =============================================================

CREATE TABLE IF NOT EXISTS reservation_resource_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL COMMENT 'common_code_items.id',
    max_count INT NOT NULL DEFAULT 1 COMMENT '최대 동시 예약 수',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_item (item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_item_id INT NOT NULL COMMENT 'common_code_items.id',
    title VARCHAR(200) NOT NULL COMMENT '예약 제목',
    user_name VARCHAR(100) NOT NULL DEFAULT '' COMMENT '예약자명',
    reservation_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    description TEXT NULL COMMENT '메모',
    status ENUM('confirmed','cancelled') NOT NULL DEFAULT 'confirmed',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_date (reservation_date),
    KEY idx_resource_date (resource_item_id, reservation_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================
-- 6. 세무/회계 (schema_tax.sql)
-- =============================================================

CREATE TABLE IF NOT EXISTS account_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE COMMENT '계정과목 코드 (예: 511)',
    name VARCHAR(100) NOT NULL COMMENT '계정과목명 (예: 급여)',
    parent_code VARCHAR(10) NULL COMMENT '상위 계정과목 코드',
    type ENUM('매출','매입','자산','부채','자본','비용','수익') NOT NULL DEFAULT '비용',
    tax_type ENUM('과세','면세','영세율','불공제') NOT NULL DEFAULT '과세',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='계정과목 마스터';

-- 계정과목 그룹 (중간 분류 헤더)
INSERT INTO account_categories (code, name, parent_code, type, tax_type, is_active, sort_order) VALUES
('G_CA',  '유동자산',       NULL, '자산', '불공제', 1, 1000),
('G_FA',  '유형자산',       NULL, '자산', '불공제', 1, 1085),
('G_CL',  '유동부채',       NULL, '부채', '불공제', 1, 2000),
('G_EQ',  '자본',           NULL, '자본', '불공제', 1, 3000),
('G_SL',  '매출',           NULL, '매출', '과세',   1, 4000),
('G_CG',  '매출원가',       NULL, '매입', '과세',   1, 4500),
('G_NI',  '영업외수익',     NULL, '수익', '불공제', 1, 4505),
('G_SGA', '판매비와관리비', NULL, '비용', '불공제', 1, 5000),
('G_NE',  '영업외비용',     NULL, '비용', '불공제', 1, 5235);

INSERT INTO account_categories (code, name, parent_code, type, tax_type, sort_order) VALUES
-- 유동자산
('10100', '현금',         'G_CA', '자산', '불공제', 1001),
('10300', '보통예금',     'G_CA', '자산', '불공제', 1010),
('10800', '외상매출금',   'G_CA', '자산', '불공제', 1020),
('12000', '미수금',       'G_CA', '자산', '불공제', 1030),
('13100', '선급금',       'G_CA', '자산', '불공제', 1050),
('13500', '부가세대급금', 'G_CA', '자산', '과세',   1070),
-- 유형자산
('21200', '비품',         'G_FA', '자산', '과세',   1090),
-- 유동부채
('25100', '외상매입금',   'G_CL', '부채', '불공제', 2001),
('25300', '미지급금',     'G_CL', '부채', '불공제', 2010),
('25400', '예수금',       'G_CL', '부채', '불공제', 2020),
('25500', '부가세예수금', 'G_CL', '부채', '과세',   2030),
('26000', '단기차입금',   'G_CL', '부채', '불공제', 2060),
-- 자본
('33100', '자본금',       'G_EQ', '자본', '불공제', 3010),
-- 매출
('40100', '상품매출',     'G_SL', '매출', '과세',   4010),
('40200', '제품매출',     'G_SL', '매출', '과세',   4020),
('41200', '서비스매출',   'G_SL', '매출', '과세',   4030),
('40400', '임대수입',     'G_SL', '매출', '과세',   4040),
-- 매출원가
('45100', '상품매입',     'G_CG', '매입', '과세',   4510),
('45200', '원재료매입',   'G_CG', '매입', '과세',   4520),
-- 영업외수익
('90100', '이자수익',     'G_NI', '수익', '불공제', 4520),
('93000', '잡이익',       'G_NI', '수익', '불공제', 4530),
-- 판매비와관리비
('80200', '직원급여',     'G_SGA','비용', '불공제', 5010),
('80600', '퇴직급여',     'G_SGA','비용', '불공제', 5030),
('80900', '감가상각비',   'G_SGA','비용', '불공제', 5035),
('81100', '복리후생비',   'G_SGA','비용', '과세',   5040),
('81200', '여비교통비',   'G_SGA','비용', '과세',   5050),
('81300', '접대비',       'G_SGA','비용', '불공제', 5060),
('81400', '통신비',       'G_SGA','비용', '과세',   5070),
('81700', '세금과공과금', 'G_SGA','비용', '불공제', 5090),
('81900', '지급임차료',   'G_SGA','비용', '과세',   5100),
('82100', '보험료',       'G_SGA','비용', '불공제', 5120),
('82500', '교육훈련비',   'G_SGA','비용', '과세',   5140),
('82700', '차량유지비',   'G_SGA','비용', '과세',   5145),
('83000', '소모품비',     'G_SGA','비용', '과세',   5170),
('83100', '지급수수료',   'G_SGA','비용', '과세',   5180),
('83300', '광고선전비',   'G_SGA','비용', '과세',   5190),
-- 영업외비용
('93100', '이자비용',     'G_NE', '비용', '불공제', 5250);

CREATE TABLE IF NOT EXISTS bank_accounts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL DEFAULT 1,
    bank_name VARCHAR(50) NOT NULL COMMENT '은행명',
    account_no VARCHAR(30) NOT NULL COMMENT '계좌번호',
    account_alias VARCHAR(50) NULL COMMENT '계좌 별칭',
    owner_name VARCHAR(50) NOT NULL COMMENT '예금주',
    consent_agreed TINYINT(1) NOT NULL DEFAULT 0 COMMENT '자동수집 동의 여부',
    consent_agreed_at DATETIME NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='등록 계좌 정보';

INSERT INTO bank_accounts (bank_name, account_no, account_alias, owner_name, consent_agreed, consent_agreed_at) VALUES
('국민은행', '123-456-789012', '운영계좌', '주식회사 재밋', 1, '2026-01-15 10:00:00'),
('신한은행', '234-567-890123', '급여계좌', '주식회사 재밋', 1, '2026-01-15 10:00:00'),
('기업은행', '345-678-901234', '세금납부계좌', '주식회사 재밋', 0, NULL);

CREATE TABLE IF NOT EXISTS bank_transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_id INT UNSIGNED NOT NULL COMMENT 'bank_accounts.id',
    transaction_date DATE NOT NULL,
    description VARCHAR(200) NOT NULL COMMENT '거래 적요',
    amount BIGINT NOT NULL COMMENT '금액 (양수)',
    tx_type ENUM('입금','출금') NOT NULL,
    balance BIGINT NULL COMMENT '거래 후 잔액',
    account_code VARCHAR(10) NULL COMMENT '분류된 계정과목 코드',
    account_name VARCHAR(100) NULL COMMENT '분류된 계정과목명',
    ai_confidence TINYINT UNSIGNED NULL COMMENT 'AI 신뢰도 0-100',
    is_confirmed TINYINT(1) NOT NULL DEFAULT 0 COMMENT '사용자 확정 여부',
    memo VARCHAR(200) NULL,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES bank_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='통장 입출금 내역';

CREATE TABLE IF NOT EXISTS doc_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    requester_id INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '요청자(세무사) ID',
    company_id INT UNSIGNED NOT NULL DEFAULT 1,
    doc_name VARCHAR(200) NOT NULL COMMENT '요청 서류명',
    description TEXT NULL COMMENT '요청 상세 설명',
    due_date DATE NULL COMMENT '제출 기한',
    status ENUM('요청중','업로드완료','확인완료','취소') NOT NULL DEFAULT '요청중',
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='세무 서류 요청';

INSERT INTO doc_requests (doc_name, description, due_date, status) VALUES
('2025년 4분기 통장거래내역', '국민은행 운영계좌 전체 내역 PDF', '2026-03-10', '요청중'),
('2025년 12월 급여대장', '전직원 급여 지급 내역', '2026-03-07', '업로드완료'),
('사업자등록증 사본', '최신 발급본', '2026-03-15', '확인완료'),
('2025년 부가세 신고용 매입세금계산서 목록', '엑셀 또는 PDF 형식', '2026-03-20', '요청중');

CREATE TABLE IF NOT EXISTS doc_uploads (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_id INT UNSIGNED NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT UNSIGNED NULL COMMENT 'bytes',
    uploaded_by INT UNSIGNED NOT NULL DEFAULT 1,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES doc_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='서류 업로드 파일';

CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL DEFAULT 1,
    type VARCHAR(50) NOT NULL COMMENT '알림 유형',
    title VARCHAR(200) NOT NULL,
    message TEXT NULL,
    link_url VARCHAR(500) NULL COMMENT '클릭 시 이동 URL',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='알림';

INSERT INTO notifications (type, title, message, link_url) VALUES
('doc_upload', '서류 업로드 완료', '2025년 12월 급여대장이 업로드되었습니다.', '/pages/tax_docs.php'),
('doc_request', '새 서류 요청', '세무사가 사업자등록증 사본을 요청했습니다.', '/pages/tax_docs.php'),
('doc_confirmed', '서류 확인 완료', '사업자등록증 사본 확인이 완료되었습니다.', '/pages/tax_docs.php');

CREATE TABLE IF NOT EXISTS payslips (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    employee_name VARCHAR(50) NOT NULL,
    year SMALLINT UNSIGNED NOT NULL,
    month TINYINT UNSIGNED NOT NULL,
    base_salary BIGINT NOT NULL DEFAULT 0 COMMENT '기본급',
    overtime_hours DECIMAL(5,1) NOT NULL DEFAULT 0 COMMENT '초과근무시간',
    overtime_pay BIGINT NOT NULL DEFAULT 0 COMMENT '초과수당',
    meal_allowance BIGINT NOT NULL DEFAULT 0 COMMENT '식대',
    car_allowance BIGINT NOT NULL DEFAULT 0 COMMENT '차량지원',
    child_allowance BIGINT NOT NULL DEFAULT 0 COMMENT '육아수당',
    gross_pay BIGINT NOT NULL DEFAULT 0 COMMENT '총지급액',
    national_pension BIGINT NOT NULL DEFAULT 0 COMMENT '국민연금',
    health_insurance BIGINT NOT NULL DEFAULT 0 COMMENT '건강보험',
    emp_insurance BIGINT NOT NULL DEFAULT 0 COMMENT '고용보험',
    income_tax BIGINT NOT NULL DEFAULT 0 COMMENT '소득세',
    total_deduction BIGINT NOT NULL DEFAULT 0 COMMENT '총공제액',
    net_pay BIGINT NOT NULL DEFAULT 0 COMMENT '실수령액',
    status ENUM('draft','confirmed','paid') NOT NULL DEFAULT 'draft' COMMENT '상태',
    confirmed_at DATETIME NULL COMMENT '확정일시',
    confirmed_by INT UNSIGNED NULL COMMENT '확정자 ID',
    paid_at DATETIME NULL COMMENT '지급일시',
    memo VARCHAR(200) NOT NULL DEFAULT '' COMMENT '비고',
    generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE INDEX uq_emp_yearmonth (employee_id, year, month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='급여 명세';

CREATE TABLE IF NOT EXISTS tax_credit_simulations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL DEFAULT 1,
    sim_year SMALLINT UNSIGNED NOT NULL COMMENT '공제 적용 연도',
    base_employee_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '기준년도 상시근로자 수',
    current_employee_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '당해년도 상시근로자 수',
    youth_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '청년 증가 인원',
    elder_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '장년 증가 인원',
    region ENUM('수도권','비수도권') NOT NULL DEFAULT '수도권',
    youth_credit_per BIGINT NOT NULL DEFAULT 11000000 COMMENT '청년 인당 공제액',
    elder_credit_per BIGINT NOT NULL DEFAULT 7700000 COMMENT '장년 인당 공제액',
    total_credit BIGINT NOT NULL DEFAULT 0 COMMENT '총 세액공제액',
    memo TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='세액공제 시뮬레이션 이력';

-- =============================================================
-- 7. 세금계산서 (schema_tax_invoice.sql)
-- =============================================================

CREATE TABLE IF NOT EXISTS tax_invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL DEFAULT 1,
    invoice_type ENUM('매출','매입') NOT NULL COMMENT '매출/매입 구분',
    invoice_number VARCHAR(32) NOT NULL COMMENT '세금계산서 승인번호',
    issue_date DATE NOT NULL COMMENT '작성일자',
    send_date DATE NULL COMMENT '전송일자',
    supplier_bizno VARCHAR(12) NOT NULL COMMENT '공급자 사업자번호',
    supplier_name VARCHAR(100) NOT NULL COMMENT '공급자 상호',
    supplier_ceo VARCHAR(50) NULL COMMENT '공급자 대표자',
    buyer_bizno VARCHAR(12) NOT NULL COMMENT '공급받는자 사업자번호',
    buyer_name VARCHAR(100) NOT NULL COMMENT '공급받는자 상호',
    buyer_ceo VARCHAR(50) NULL COMMENT '공급받는자 대표자',
    supply_amount BIGINT NOT NULL DEFAULT 0 COMMENT '공급가액',
    tax_amount BIGINT NOT NULL DEFAULT 0 COMMENT '세액',
    total_amount BIGINT NOT NULL DEFAULT 0 COMMENT '합계금액',
    tax_type ENUM('과세','영세율','면세') NOT NULL DEFAULT '과세' COMMENT '과세유형',
    invoice_status ENUM('정상','수정','취소') NOT NULL DEFAULT '정상',
    hometax_sync TINYINT(1) NOT NULL DEFAULT 0 COMMENT '홈택스 동기화 여부',
    synced_at DATETIME NULL COMMENT '마지막 동기화 시각',
    memo VARCHAR(200) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type_date (invoice_type, issue_date),
    INDEX idx_supplier (supplier_bizno),
    INDEX idx_buyer (buyer_bizno)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='세금계산서';

CREATE TABLE IF NOT EXISTS tax_invoice_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT UNSIGNED NOT NULL,
    item_date DATE NULL COMMENT '품목 일자',
    item_name VARCHAR(200) NOT NULL COMMENT '품목명',
    spec VARCHAR(100) NULL COMMENT '규격',
    quantity DECIMAL(12,2) NOT NULL DEFAULT 1 COMMENT '수량',
    unit_price BIGINT NOT NULL DEFAULT 0 COMMENT '단가',
    supply_amount BIGINT NOT NULL DEFAULT 0 COMMENT '공급가액',
    tax_amount BIGINT NOT NULL DEFAULT 0 COMMENT '세액',
    FOREIGN KEY (invoice_id) REFERENCES tax_invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='세금계산서 품목';

CREATE TABLE IF NOT EXISTS hometax_sync_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL DEFAULT 1,
    sync_type VARCHAR(30) NOT NULL COMMENT 'sales_invoice, purchase_invoice 등',
    sync_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '동기화 건수',
    status ENUM('성공','실패','진행중') NOT NULL DEFAULT '성공',
    message VARCHAR(500) NULL,
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    finished_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='홈택스 동기화 이력';

-- 샘플: 매출 세금계산서
INSERT INTO tax_invoices (invoice_type, invoice_number, issue_date, send_date, supplier_bizno, supplier_name, supplier_ceo, buyer_bizno, buyer_name, buyer_ceo, supply_amount, tax_amount, total_amount, tax_type, invoice_status, hometax_sync, synced_at) VALUES
('매출', '20260201-41000001-00000001', '2026-02-01', '2026-02-02', '123-45-67890', '주식회사 재밋', '송승환', '234-56-78901', '(주)테크솔루션', '김태호', 15000000, 1500000, 16500000, '과세', '정상', 1, '2026-02-28 09:00:00'),
('매출', '20260205-41000001-00000002', '2026-02-05', '2026-02-06', '123-45-67890', '주식회사 재밋', '송승환', '345-67-89012', '디자인웍스', '박지연', 8500000, 850000, 9350000, '과세', '정상', 1, '2026-02-28 09:00:00'),
('매출', '20260210-41000001-00000003', '2026-02-10', '2026-02-11', '123-45-67890', '주식회사 재밋', '송승환', '456-78-90123', '(주)스마트커머스', '이준혁', 3200000, 320000, 3520000, '과세', '정상', 1, '2026-02-28 09:00:00'),
('매출', '20260215-41000001-00000004', '2026-02-15', '2026-02-16', '123-45-67890', '주식회사 재밋', '송승환', '567-89-01234', '한국데이터', '최수진', 12000000, 1200000, 13200000, '과세', '정상', 1, '2026-02-28 09:00:00'),
('매출', '20260218-41000001-00000005', '2026-02-18', '2026-02-19', '123-45-67890', '주식회사 재밋', '송승환', '234-56-78901', '(주)테크솔루션', '김태호', 5500000, 550000, 6050000, '과세', '정상', 1, '2026-02-28 09:00:00'),
('매출', '20260220-41000001-00000006', '2026-02-20', '2026-02-21', '123-45-67890', '주식회사 재밋', '송승환', '678-90-12345', '그린에너지(주)', '정민우', 2000000, 0, 2000000, '영세율', '정상', 1, '2026-02-28 09:00:00'),
('매출', '20260225-41000001-00000007', '2026-02-25', '2026-02-26', '123-45-67890', '주식회사 재밋', '송승환', '789-01-23456', '(주)미래건설', '한동훈', 9800000, 980000, 10780000, '과세', '정상', 1, '2026-02-28 09:00:00'),
-- 매입 세금계산서
('매입', '20260203-52000001-00000001', '2026-02-03', '2026-02-04', '890-12-34567', 'NHN클라우드(주)', '김동훈', '123-45-67890', '주식회사 재밋', '송승환', 4200000, 420000, 4620000, '과세', '정상', 1, '2026-02-28 09:00:00'),
('매입', '20260205-52000001-00000002', '2026-02-05', '2026-02-06', '901-23-45678', '(주)오피스허브', '윤서영', '123-45-67890', '주식회사 재밋', '송승환', 1800000, 180000, 1980000, '과세', '정상', 1, '2026-02-28 09:00:00'),
('매입', '20260210-52000001-00000003', '2026-02-10', '2026-02-11', '012-34-56789', '세종사무기기', '강현수', '123-45-67890', '주식회사 재밋', '송승환', 650000, 65000, 715000, '과세', '정상', 1, '2026-02-28 09:00:00'),
('매입', '20260212-52000001-00000004', '2026-02-12', '2026-02-13', '890-12-34567', 'NHN클라우드(주)', '김동훈', '123-45-67890', '주식회사 재밋', '송승환', 3800000, 380000, 4180000, '과세', '정상', 1, '2026-02-28 09:00:00'),
('매입', '20260215-52000001-00000005', '2026-02-15', '2026-02-16', '234-56-00001', '(주)디지털마케팅', '오지훈', '123-45-67890', '주식회사 재밋', '송승환', 3500000, 350000, 3850000, '과세', '정상', 1, '2026-02-28 09:00:00'),
('매입', '20260220-52000001-00000006', '2026-02-20', '2026-02-21', '345-67-00002', '코리아호스팅', '임재현', '123-45-67890', '주식회사 재밋', '송승환', 980000, 98000, 1078000, '과세', '정상', 1, '2026-02-28 09:00:00'),
('매입', '20260222-52000001-00000007', '2026-02-22', NULL, '456-78-00003', '인테리어플러스', '배수진', '123-45-67890', '주식회사 재밋', '송승환', 2500000, 250000, 2750000, '과세', '수정', 0, NULL);

-- 홈택스 동기화 이력
INSERT INTO hometax_sync_log (sync_type, sync_count, status, message, started_at, finished_at) VALUES
('sales_invoice', 7, '성공', '2026년 2월 매출 세금계산서 7건 동기화 완료', '2026-02-28 09:00:00', '2026-02-28 09:00:12'),
('purchase_invoice', 6, '성공', '2026년 2월 매입 세금계산서 6건 동기화 완료', '2026-02-28 09:00:12', '2026-02-28 09:00:25');

-- =============================================================
-- 초기화 완료
-- =============================================================
SELECT '✅ Zaemit 그룹웨어 DB 초기화 완료' AS result;
SELECT CONCAT('테이블 수: ', COUNT(*)) AS result FROM information_schema.tables WHERE table_schema = 'zaemit_groupware';
