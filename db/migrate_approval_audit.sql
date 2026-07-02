-- 결재 운영 마이그레이션: 감사 로그 + 양식 확장 + soft delete + 인덱스
-- MySQL 9.6 호환
-- 실행: mysql -u root zaemit_groupware < db/migrate_approval_audit.sql

-- ─── 1. 감사 로그 테이블 (INSERT ONLY — UPDATE/DELETE 금지) ───

CREATE TABLE IF NOT EXISTS approval_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(30) NOT NULL COMMENT '이벤트 종류 (document_created, form_updated 등)',
    event_category VARCHAR(20) NOT NULL COMMENT '카테고리 (document/admin/form/config)',
    actor_id INT NULL COMMENT '수행자 employee_id',
    actor_name VARCHAR(50) NOT NULL COMMENT '수행자 이름 (비정규화)',
    target_type VARCHAR(30) NOT NULL COMMENT '대상 종류 (document/form/line/config)',
    target_id INT NULL COMMENT '대상 레코드 ID',
    target_label VARCHAR(200) NULL COMMENT '사람이 읽을 수 있는 대상 식별자',
    old_value JSON NULL COMMENT '변경 전 값',
    new_value JSON NULL COMMENT '변경 후 값',
    comment TEXT NULL COMMENT '사유 (강제 완료/반려 시 필수)',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_category (event_category),
    INDEX idx_audit_actor (actor_id),
    INDEX idx_audit_target (target_type, target_id),
    INDEX idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ─── 2. approval_documents 확장 (soft delete) ───

ALTER TABLE approval_documents
    ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'soft delete 플래그',
    ADD COLUMN IF NOT EXISTS deleted_by INT NULL COMMENT '삭제자 employee_id';

CREATE INDEX idx_doc_status_date ON approval_documents(status, draft_date);
CREATE INDEX idx_doc_deleted ON approval_documents(is_deleted);

-- ─── 3. approval_forms 확장 (권한/보존기간/설명) ───

ALTER TABLE approval_forms
    ADD COLUMN IF NOT EXISTS description TEXT NULL COMMENT '양식 용도 설명' AFTER title,
    ADD COLUMN IF NOT EXISTS allowed_departments JSON NULL COMMENT '허용 부서 ID 배열 (null=전체)',
    ADD COLUMN IF NOT EXISTS allowed_positions JSON NULL COMMENT '허용 직급 배열 (null=전체)',
    ADD COLUMN IF NOT EXISTS retention_days INT NULL DEFAULT NULL COMMENT '완료 문서 보존 일수 (null=무제한)',
    ADD COLUMN IF NOT EXISTS created_by INT NULL COMMENT '작성자 employee_id';
