-- Zaemit 그룹웨어 · approval_documents.drafter_id 추가 마이그레이션
-- 실행: mysql -u root zaemit_groupware < db/migrate_approval_drafter_id.sql
--
-- 배경:
--   api/approval.php는 d.drafter_id 를 참조(내가 기안한 문서 조회, 결재함 필터 등)하지만
--   기존 schema_approval.sql/init.sql 에는 해당 컬럼이 없다.
--   이 파일은 누락된 컬럼을 멱등적으로 추가한다.

-- approval_documents.drafter_id
SET @c := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'approval_documents'
      AND COLUMN_NAME = 'drafter_id'
);
SET @sql := IF(@c = 0,
    'ALTER TABLE approval_documents ADD COLUMN drafter_id INT NULL AFTER doc_type, ADD INDEX idx_drafter_id (drafter_id)',
    'SELECT "approval_documents.drafter_id 이미 존재" AS note'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 기존 drafter_name 데이터로 drafter_id 매핑 (동명이인은 첫 번째 매칭 기준)
UPDATE approval_documents d
JOIN employees e ON e.name = d.drafter_name
   SET d.drafter_id = e.id
 WHERE d.drafter_id IS NULL;
