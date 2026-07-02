-- 연차관리 시스템 스키마
-- 2026-04-14

-- 직원별 연도별 연차 잔액
CREATE TABLE IF NOT EXISTS annual_leave (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL COMMENT 'employees.id',
    year SMALLINT NOT NULL COMMENT '귀속년도',
    total_days DECIMAL(4,1) NOT NULL DEFAULT 15.0 COMMENT '총 부여일수',
    used_days DECIMAL(4,1) NOT NULL DEFAULT 0.0 COMMENT '사용일수',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_emp_year (employee_id, year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='연차 잔액';

-- 개별 연차 사용/신청 기록
-- created_at = 신청 일시, approved_at = 승인 일시 (승인 프로세스를 거친 후에만 채워짐)
CREATE TABLE IF NOT EXISTS leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL COMMENT 'employees.id',
    leave_type VARCHAR(10) NOT NULL DEFAULT 'AL' COMMENT 'AL/HAM/HAP/SL/FL/OL',
    start_date DATE NOT NULL COMMENT '시작일',
    end_date DATE NOT NULL COMMENT '종료일',
    days_used DECIMAL(3,1) NOT NULL DEFAULT 1.0 COMMENT '사용일수 (0.5=반차)',
    reason VARCHAR(200) NULL COMMENT '사유',
    status VARCHAR(10) NOT NULL DEFAULT '대기' COMMENT '대기/승인/반려/취소',
    approved_at DATETIME NULL COMMENT '승인 일시',
    approver_id INT NULL COMMENT '승인자 employees.id',
    penalty_flag TINYINT(1) NOT NULL DEFAULT 0 COMMENT '페널티 여부 (예: 지각 반차)',
    penalty_reason VARCHAR(100) NULL COMMENT '페널티 사유 문구',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '신청 일시',
    INDEX idx_emp_year (employee_id, start_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='연차 사용 기록';

-- 기존 DB 마이그레이션 (이미 테이블이 있는 경우 수동 실행)
-- 실행 방법: 각 줄을 ERROR 1060 (컬럼 존재)이 나도 무시하고 진행
-- ALTER TABLE leave_requests ADD COLUMN approved_at DATETIME NULL COMMENT '승인 일시' AFTER status;
-- ALTER TABLE leave_requests ADD COLUMN approver_id INT NULL COMMENT '승인자 employees.id' AFTER approved_at;
-- ALTER TABLE leave_requests ADD COLUMN penalty_flag TINYINT(1) NOT NULL DEFAULT 0 AFTER approver_id;
-- ALTER TABLE leave_requests ADD COLUMN penalty_reason VARCHAR(100) NULL AFTER penalty_flag;
-- ALTER TABLE leave_requests ALTER COLUMN status SET DEFAULT '대기';
-- CREATE INDEX idx_status ON leave_requests (status);
