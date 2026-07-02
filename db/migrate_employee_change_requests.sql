-- Zaemit 그룹웨어 - 직원 정보 변경요청 테이블
-- MySQL 9.6 호환
-- 실행: mysql -u root zaemit_groupware < db/migrate_employee_change_requests.sql

CREATE TABLE IF NOT EXISTS employee_change_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL COMMENT '요청 직원',
    status ENUM('대기','승인','반려') NOT NULL DEFAULT '대기' COMMENT '처리 상태',
    changes_json JSON NOT NULL COMMENT '변경 내용 {"field":{"old":"...","new":"..."}, ...}',
    reason TEXT NULL COMMENT '변경 사유 (직원 입력)',
    reject_reason TEXT NULL COMMENT '반려 사유 (관리자 입력)',
    reviewed_by INT NULL COMMENT '처리한 관리자 ID',
    reviewed_at DATETIME NULL COMMENT '처리 일시',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES employees(id) ON DELETE SET NULL,
    INDEX idx_ecr_emp_status (employee_id, status),
    INDEX idx_ecr_status_date (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
