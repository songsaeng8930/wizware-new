-- Zaemit 그룹웨어 · approval_history / approval_references 식별자 컬럼 보강
-- 실행: mysql -u root zaemit_groupware < db/migrate_approval_approver_id.sql
--
-- 배경:
--   기존 init.sql에는 approver_id, role, ref_id 컬럼이 빠져 있었으나
--   api/approval.php 및 api/card.php 코드는 이 컬럼들을 사용한다.
--   실제 운영 DB에는 임시로 ALTER TABLE이 실행된 상태였지만 스키마 파일에 기록이 없었음.
--   이 파일은 그 간극을 메우는 재실행 가능한 멱등 마이그레이션이다.
--
-- 안전성: IF NOT EXISTS를 쓸 수 없는 ADD COLUMN에 대비해 information_schema 확인 로직 사용.

-- approval_history.approver_id
SET @c := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'approval_history'
      AND COLUMN_NAME = 'approver_id'
);
SET @sql := IF(@c = 0,
    'ALTER TABLE approval_history ADD COLUMN approver_id INT NULL AFTER document_id, ADD INDEX idx_approver_id (approver_id)',
    'SELECT "approval_history.approver_id 이미 존재" AS note'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- approval_history.role  (결재/전결/참조)
SET @c := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'approval_history'
      AND COLUMN_NAME = 'role'
);
SET @sql := IF(@c = 0,
    'ALTER TABLE approval_history ADD COLUMN `role` VARCHAR(10) NOT NULL DEFAULT ''결재'' AFTER approver_dept',
    'SELECT "approval_history.role 이미 존재" AS note'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- approval_references.ref_id
SET @c := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'approval_references'
      AND COLUMN_NAME = 'ref_id'
);
SET @sql := IF(@c = 0,
    'ALTER TABLE approval_references ADD COLUMN ref_id INT NULL AFTER document_id, ADD INDEX idx_ref_id (ref_id)',
    'SELECT "approval_references.ref_id 이미 존재" AS note'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
