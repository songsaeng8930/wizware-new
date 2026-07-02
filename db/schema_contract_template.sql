-- 근로계약서 표준 양식(템플릿) 스키마
-- 2026-04-24: 다중 버전/종류 지원 · 여러 양식을 만들고 default 를 지정할 수 있음
-- 실행: mysql -u root zaemit_groupware < db/schema_contract_template.sql

CREATE TABLE IF NOT EXISTS contract_templates (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL DEFAULT '표준 근로계약서' COMMENT '양식 이름 (예: 정규직, 계약직, 인턴)',
    version_label VARCHAR(50)  NULL                              COMMENT '버전 레이블 (예: v1, 2026 개정)',
    description   VARCHAR(255) NULL                              COMMENT '양식 설명',
    body          LONGTEXT NOT NULL                              COMMENT '계약서 본문 (HTML, 표 포함)',
    is_default    TINYINT(1) DEFAULT 0                           COMMENT '기본 양식 여부',
    is_active     TINYINT(1) DEFAULT 1                           COMMENT '사용 여부',
    updated_by    VARCHAR(50) NULL                               COMMENT '최근 수정자',
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_default (is_default, is_active),
    KEY idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='근로계약서 양식 · 다중 버전/종류';

-- 기존 테이블에 version_label 이 없으면 추가 (이미 있으면 무시됨)
ALTER TABLE contract_templates
    ADD COLUMN IF NOT EXISTS version_label VARCHAR(50) NULL AFTER name,
    ADD KEY IF NOT EXISTS idx_name (name);

-- 조문-단위 저장 테이블(contract_template_articles) 은 더 이상 사용하지 않음.
-- DROP TABLE IF EXISTS contract_template_articles;
