-- 연차 수동 조정 이력 테이블
-- 2026-06-21
-- 관리자가 포상연차, 이월, 착오보정 등으로 연차를 추가/차감할 때 기록

CREATE TABLE IF NOT EXISTS leave_adjustments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL COMMENT 'employees.id',
    year SMALLINT NOT NULL COMMENT '귀속년도',
    adjust_type ENUM('add','deduct') NOT NULL COMMENT '추가/차감',
    adjust_days DECIMAL(4,1) NOT NULL COMMENT '조정일수 (양수)',
    reason VARCHAR(200) NOT NULL COMMENT '조정 사유',
    category VARCHAR(30) NULL COMMENT '분류 (포상/이월/보정/기타)',
    created_by INT NULL COMMENT '등록자 employees.id',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_emp_year (employee_id, year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='연차 수동 조정 이력';
