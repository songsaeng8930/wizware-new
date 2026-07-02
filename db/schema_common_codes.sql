-- Zaemit 그룹웨어 - 공통코드 설정 테이블
-- MySQL 9.6 호환
-- 실행: mysql -u root zaemit_groupware < db/schema_common_codes.sql

-- 공통정보 그룹 (탭별 카테고리)
CREATE TABLE IF NOT EXISTS common_code_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module VARCHAR(30) NOT NULL COMMENT '모듈 (hr, attendance, card, business, reservation, schedule)',
    name VARCHAR(100) NOT NULL COMMENT '공통정보명',
    description TEXT NULL COMMENT '비고',
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 공통코드 항목
CREATE TABLE IF NOT EXISTS common_code_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    code VARCHAR(50) NULL COMMENT '코드',
    name VARCHAR(100) NOT NULL COMMENT '항목명(레이블)',
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES common_code_groups(id) ON DELETE CASCADE,
    UNIQUE KEY uq_group_name (group_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
