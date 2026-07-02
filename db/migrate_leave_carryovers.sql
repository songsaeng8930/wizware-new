-- 연차 이월 관리 테이블
-- 2026-06-21
-- 노사합의 하에 전년도 미사용 연차를 다음 연도로 이월

CREATE TABLE IF NOT EXISTS leave_carryovers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL COMMENT 'employees.id',
    from_year SMALLINT NOT NULL COMMENT '이월 원년도',
    to_year SMALLINT NOT NULL COMMENT '이월 대상년도',
    days DECIMAL(4,1) NOT NULL COMMENT '이월 일수',
    reason VARCHAR(200) NULL COMMENT '이월 사유',
    status ENUM('신청','승인','반려') NOT NULL DEFAULT '신청',
    agreement_date DATE NULL COMMENT '노사 합의일',
    approved_by INT NULL COMMENT 'employees.id',
    approved_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_emp_year (employee_id, from_year),
    UNIQUE KEY uq_emp_from_to (employee_id, from_year, to_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='연차 이월';
