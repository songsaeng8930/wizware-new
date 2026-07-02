-- 연차 촉진 통보 테이블
-- 2026-06-21
-- 근로기준법 제61조: 사용기간 종료 6개월/2개월 전 서면 통보

CREATE TABLE IF NOT EXISTS leave_promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL COMMENT 'employees.id',
    year SMALLINT NOT NULL COMMENT '귀속년도',
    stage TINYINT NOT NULL COMMENT '1=1차(6개월전), 2=2차(2개월전)',
    notified_at DATETIME NULL COMMENT '통보 일시',
    deadline DATE NOT NULL COMMENT '응답 기한',
    response_status ENUM('미응답','계획제출','지정통보') NOT NULL DEFAULT '미응답',
    use_plan_dates JSON NULL COMMENT '1차: 직원 제출 사용 계획 날짜',
    designated_dates JSON NULL COMMENT '2차: 회사 지정 사용 날짜',
    responded_at DATETIME NULL,
    created_by INT NULL COMMENT '통보 생성자 employees.id',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_emp_year_stage (employee_id, year, stage),
    INDEX idx_year_stage (year, stage)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='연차 촉진 통보';
