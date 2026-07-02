-- 결산 체크리스트 + 첨부파일
-- 기존 api/closing.php 런타임 CREATE TABLE에서 이관

CREATE TABLE IF NOT EXISTS closing_checklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fiscal_year INT NOT NULL,
    item_key VARCHAR(50) NOT NULL,
    is_checked TINYINT(1) DEFAULT 0,
    checked_by VARCHAR(50) NULL,
    checked_at DATETIME NULL,
    note TEXT NULL,
    UNIQUE KEY uq_fy_item (fiscal_year, item_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS closing_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fiscal_year INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT DEFAULT 0,
    uploaded_by VARCHAR(50) NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
