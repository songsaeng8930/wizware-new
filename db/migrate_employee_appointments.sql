-- Zaemit 그룹웨어 - 인사발령 이력 테이블
-- MySQL 9.6 호환
-- 실행: mysql -u root zaemit_groupware < db/migrate_employee_appointments.sql

CREATE TABLE IF NOT EXISTS employee_appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL COMMENT '대상 직원',

    appointment_type VARCHAR(30) NOT NULL COMMENT '발령유형 (신규입사/전보/승진/직급변경/직책변경/고용형태변경/상태변경/복합발령/퇴사)',
    appointment_date DATE NOT NULL COMMENT '발령일 (시행일)',
    appointment_no VARCHAR(50) NULL COMMENT '발령번호 (선택)',
    source VARCHAR(10) NOT NULL DEFAULT 'auto' COMMENT '등록 경로 (auto=자동기록, manual=수동입력)',

    prev_department_id INT NULL COMMENT '이전 부서 ID',
    prev_department_name VARCHAR(100) NULL COMMENT '이전 부서명 (스냅샷)',
    prev_position VARCHAR(50) NULL COMMENT '이전 직급',
    prev_title VARCHAR(100) NULL COMMENT '이전 직책',
    prev_employment_type VARCHAR(20) NULL COMMENT '이전 고용형태',
    prev_employment_status VARCHAR(20) NULL COMMENT '이전 고용상태',

    new_department_id INT NULL COMMENT '변경 부서 ID',
    new_department_name VARCHAR(100) NULL COMMENT '변경 부서명 (스냅샷)',
    new_position VARCHAR(50) NULL COMMENT '변경 직급',
    new_title VARCHAR(100) NULL COMMENT '변경 직책',
    new_employment_type VARCHAR(20) NULL COMMENT '변경 고용형태',
    new_employment_status VARCHAR(20) NULL COMMENT '변경 고용상태',

    reason TEXT NULL COMMENT '발령 사유',
    created_by INT NULL COMMENT '등록자 (employees.id)',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE RESTRICT,
    INDEX idx_appt_emp (employee_id),
    INDEX idx_appt_date (appointment_date),
    INDEX idx_appt_type (appointment_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 발령유형 공통코드 (hr 모듈) — 멱등성 보장: 이미 존재하면 건너뜀
INSERT INTO common_code_groups (module, name, description, sort_order)
SELECT 'hr', '발령유형', '인사발령 유형 분류', 5
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM common_code_groups WHERE module = 'hr' AND name = '발령유형'
);

SET @appt_grp = (SELECT id FROM common_code_groups WHERE module = 'hr' AND name = '발령유형' LIMIT 1);

INSERT IGNORE INTO common_code_items (group_id, code, name, sort_order) VALUES
(@appt_grp, 'NEW_HIRE',     '신규입사',       1),
(@appt_grp, 'TRANSFER',     '전보',           2),
(@appt_grp, 'PROMOTION',    '승진',           3),
(@appt_grp, 'POS_CHANGE',   '직급변경',       4),
(@appt_grp, 'TITLE_CHANGE', '직책변경',       5),
(@appt_grp, 'TYPE_CHANGE',  '고용형태변경',   6),
(@appt_grp, 'DISPATCH',     '파견',           7),
(@appt_grp, 'TRANSFER_OUT', '전출',           8),
(@appt_grp, 'TRANSFER_IN',  '전입',           9),
(@appt_grp, 'LEAVE',        '휴직',          10),
(@appt_grp, 'RETURN',       '복직',          11),
(@appt_grp, 'STATUS_CHANGE','상태변경',       12),
(@appt_grp, 'COMPOUND',     '복합발령',      13),
(@appt_grp, 'RESIGN',       '퇴사',          14);
