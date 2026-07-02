-- Zaemit 그룹웨어 - 자원예약 스키마
-- 실행: mysql -u root zaemit_groupware < db/schema_reservation.sql

-- 자원별 최대 동시 점유 수 설정
-- (공통코드 common_code_items의 reservation 모듈 항목과 연결)
CREATE TABLE IF NOT EXISTS reservation_resource_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL COMMENT 'common_code_items.id',
    max_count INT NOT NULL DEFAULT 1 COMMENT '최대 동시 예약 수',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_item (item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 예약 내역
CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_item_id INT NOT NULL COMMENT 'common_code_items.id',
    title VARCHAR(200) NOT NULL COMMENT '예약 제목',
    user_name VARCHAR(100) NOT NULL DEFAULT '' COMMENT '예약자명',
    reservation_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    description TEXT NULL COMMENT '메모',
    status ENUM('confirmed','cancelled') NOT NULL DEFAULT 'confirmed',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_date (reservation_date),
    KEY idx_resource_date (resource_item_id, reservation_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
