-- =============================================================
-- 결재 감사로그 강화 마이그레이션
-- 1. IP 주소 컬럼 추가 (개인정보보호법 필수)
-- 2. 이벤트 유형 인덱스 추가 (필터 성능)
-- =============================================================

ALTER TABLE approval_audit_log
    ADD COLUMN IF NOT EXISTS ip_address VARCHAR(45) NULL
    COMMENT '접속 IP 주소 (IPv4/IPv6)'
    AFTER actor_name;

CREATE INDEX idx_audit_event_type ON approval_audit_log(event_type);

-- document_viewed 디바운스 쿼리 최적화 (actor+target+type+시간)
CREATE INDEX idx_audit_view_dedup ON approval_audit_log(event_type, actor_id, target_id, created_at);
