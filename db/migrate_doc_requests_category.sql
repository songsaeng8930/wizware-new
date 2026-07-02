-- Zaemit 그룹웨어 · doc_requests 카테고리 컬럼 추가
-- 실행: mysql -u root zaemit_groupware < db/migrate_doc_requests_category.sql

SET @c := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'doc_requests'
      AND COLUMN_NAME = 'category'
);
SET @sql := IF(@c = 0,
    "ALTER TABLE doc_requests ADD COLUMN category VARCHAR(30) NOT NULL DEFAULT 'general' COMMENT 'business_docs 탭 키' AFTER doc_name, ADD INDEX idx_category (category)",
    'SELECT "doc_requests.category 이미 존재" AS note'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
