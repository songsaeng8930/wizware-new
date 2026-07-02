-- 결재선 개인별 저장 지원: created_by 컬럼 추가
-- scope: 'global' (전사 공통) / 'personal' (개인용)
ALTER TABLE approval_lines
    ADD COLUMN created_by INT NULL COMMENT '생성자 employee_id (NULL=전사 공통)' AFTER doc_type,
    ADD COLUMN scope ENUM('global','personal') NOT NULL DEFAULT 'global' COMMENT '전사/개인 구분' AFTER created_by;

-- 기존 결재선은 전사 공통(global)으로 유지
UPDATE approval_lines SET scope = 'global' WHERE created_by IS NULL;

-- 인덱스: 개인별 조회 최적화
CREATE INDEX idx_approval_lines_user ON approval_lines (created_by, scope);
