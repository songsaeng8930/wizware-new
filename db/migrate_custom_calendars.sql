-- 사용자 정의 캘린더 테이블
-- 실행: mysql -u root zaemit_groupware < db/migrate_custom_calendars.sql

CREATE TABLE IF NOT EXISTS custom_calendars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    color_code VARCHAR(20) NOT NULL DEFAULT '#4F6AFF',
    creator_id INT NOT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES employees(id) ON DELETE CASCADE,
    KEY idx_creator_active (creator_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- schedules 테이블에 커스텀 캘린더 연결 컬럼 추가
ALTER TABLE schedules ADD COLUMN custom_calendar_id INT NULL AFTER category_item_id;
ALTER TABLE schedules ADD INDEX idx_custom_calendar (custom_calendar_id);
ALTER TABLE schedules ADD FOREIGN KEY fk_custom_cal (custom_calendar_id) REFERENCES custom_calendars(id) ON DELETE SET NULL;
