-- Zaemit 그룹웨어 - 결재문서 메타데이터 컬럼 추가
-- Phase 1 워크플로우 통합: 경비청구서 → 회계 연동
-- 실행: mysql -u root zaemit_groupware < db/migrate_approval_metadata.sql
--
-- 역할: approval_documents에 metadata JSON 컬럼을 추가하여
--       결재문서와 원본 데이터(카드 경비, 휴가신청 등)의 연결고리를 저장.
--       예: {"source":"card_expense","source_id":123}

ALTER TABLE approval_documents
    ADD COLUMN metadata JSON NULL COMMENT '연동 데이터 (예: {"source":"card_expense","source_id":123})'
    AFTER content;
