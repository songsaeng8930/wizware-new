-- ============================================
-- 일정 캘린더 스키마 (Phase 1)
-- ============================================

-- 일정 본체
CREATE TABLE IF NOT EXISTS schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL COMMENT '일정 제목',
    description TEXT NULL COMMENT '일정 내용',
    start_date DATE NOT NULL COMMENT '시작일',
    start_time TIME NULL COMMENT '시작시간 (NULL=종일)',
    end_date DATE NOT NULL COMMENT '종료일',
    end_time TIME NULL COMMENT '종료시간 (NULL=종일)',
    is_all_day TINYINT(1) NOT NULL DEFAULT 0 COMMENT '종일 여부',
    category_item_id INT NULL COMMENT 'common_code_items.id (일정유형)',
    creator_id INT NOT NULL COMMENT 'employees.id (작성자)',
    visibility ENUM('public','private','department') NOT NULL DEFAULT 'public' COMMENT '공개범위',
    recurrence_rule VARCHAR(255) NULL COMMENT 'Phase 2: 반복규칙',
    status ENUM('active','cancelled') NOT NULL DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_date_range (start_date, end_date),
    KEY idx_creator (creator_id),
    KEY idx_status (status),
    FOREIGN KEY (creator_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 참석자 (다대다)
CREATE TABLE IF NOT EXISTS schedule_attendees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    employee_id INT NOT NULL,
    response_status ENUM('pending','accepted','declined') NOT NULL DEFAULT 'pending' COMMENT 'Phase 2: 응답상태',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_schedule_employee (schedule_id, employee_id),
    FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 카테고리별 색상 매핑 (공통코드 일정유형 확장)
CREATE TABLE IF NOT EXISTS schedule_category_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL COMMENT 'common_code_items.id',
    color_code VARCHAR(20) NOT NULL DEFAULT 'blue',
    UNIQUE KEY uq_item (item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 색상 매핑 초기 데이터
INSERT INTO schedule_category_config (item_id, color_code) VALUES
(62, 'blue'),    -- 회의
(63, 'green'),   -- 외부미팅
(64, 'red'),     -- 출장
(65, 'purple'),  -- 교육
(66, 'yellow'),  -- 기타
(67, 'orange'),  -- 외근 (실제 ID는 마이그레이션 시점에 따라 다름)
(68, 'teal'),    -- 면담
(69, 'pink'),    -- 행사
(70, 'gray');    -- 마감

-- 샘플 일정 데이터
INSERT INTO schedules (title, description, start_date, start_time, end_date, end_time, is_all_day, category_item_id, creator_id) VALUES
('전사 회의', '4월 경영 현황 보고 및 목표 점검', '2026-04-03', '10:00:00', '2026-04-03', '11:00:00', 0, 62, 1),
('프로젝트 킥오프', '신규 웹서비스 프로젝트 시작', '2026-04-07', '14:00:00', '2026-04-07', '15:30:00', 0, 63, 1),
('디자인 리뷰', 'UI/UX 시안 검토', '2026-04-10', '11:00:00', '2026-04-10', '12:00:00', 0, 62, 1),
('고객사 출장', '서울 본사 미팅', '2026-04-14', NULL, '2026-04-15', NULL, 1, 64, 1),
('월간 보고', '4월 실적 정리 및 보고', '2026-04-18', '09:00:00', '2026-04-18', '10:00:00', 0, 62, 1),
('신입사원 교육', '온보딩 프로그램 1일차', '2026-04-21', '09:00:00', '2026-04-21', '17:00:00', 0, 65, 1),
('기술 세미나', 'AI 트렌드 세미나', '2026-04-24', '14:00:00', '2026-04-24', '16:00:00', 0, 65, 1),
('월말 정산', '4월 경비 정산 마감', '2026-04-28', '17:00:00', '2026-04-28', '18:00:00', 0, 66, 1);

-- 샘플 참석자 (직원 ID는 employees 테이블에 존재하는 값)
INSERT INTO schedule_attendees (schedule_id, employee_id) VALUES
(1, 1), (1, 2), (1, 3),
(2, 1), (2, 4),
(3, 1), (3, 2),
(5, 1), (5, 2), (5, 3), (5, 4),
(6, 1),
(7, 1), (7, 2), (7, 3), (7, 4);
