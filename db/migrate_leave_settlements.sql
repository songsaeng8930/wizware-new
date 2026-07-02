-- 퇴사자 연차 정산 테이블
-- 2026-06-21
-- 퇴사 시 일할 계산 + 미사용 연차 수당 기록

CREATE TABLE IF NOT EXISTS leave_settlements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL COMMENT 'employees.id',
    year SMALLINT NOT NULL COMMENT '귀속년도',
    resign_date DATE NOT NULL COMMENT '퇴사일',
    hire_date DATE NOT NULL COMMENT '입사일',
    worked_months DECIMAL(4,1) NOT NULL COMMENT '해당년도 근무 월수',
    prorated_days DECIMAL(4,1) NOT NULL COMMENT '일할 부여일 = total × (months/12)',
    used_days DECIMAL(4,1) NOT NULL COMMENT '실 사용일',
    remaining_days DECIMAL(4,1) NOT NULL COMMENT '미사용 잔여일',
    base_salary BIGINT NOT NULL DEFAULT 0 COMMENT '기본급',
    daily_wage BIGINT NOT NULL DEFAULT 0 COMMENT '일급 = base_salary / 21.67',
    settlement_amount BIGINT NOT NULL DEFAULT 0 COMMENT '보상액 = remaining × daily_wage',
    settled_by INT NULL COMMENT '정산 처리자 employees.id',
    settled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    memo VARCHAR(200) NULL,
    INDEX idx_emp (employee_id),
    UNIQUE KEY uq_emp_year (employee_id, year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='퇴사자 연차 정산';
