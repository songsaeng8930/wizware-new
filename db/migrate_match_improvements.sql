-- =============================================================
-- 매칭 시스템 개선 마이그레이션
-- 이름 경고, 합산 감지, 패턴 이력, 확정 해제 지원
-- =============================================================

-- 1. invoice_bank_mappings: 이름 경고 + 합산 가능성 플래그
ALTER TABLE invoice_bank_mappings
  ADD COLUMN name_warning TINYINT(1) NOT NULL DEFAULT 0 COMMENT '거래처명 불완전 일치 경고' AFTER match_reason,
  ADD COLUMN aggregate_flag ENUM('none','potential_n1','potential_1n') NOT NULL DEFAULT 'none' COMMENT '합산/분할 가능성 플래그' AFTER name_warning;

-- 2. match_patterns: 이전 rule 이력 보존
ALTER TABLE match_patterns
  ADD COLUMN previous_rules JSON NULL COMMENT '이전 pattern_rule 이력 배열' AFTER pattern_rule;

-- 3. match_history: 확정 해제(unconfirm) 액션 추가
ALTER TABLE match_history
  MODIFY COLUMN action ENUM('confirm','modify','remove','auto_match','manual_match','pattern_edit','unconfirm') NOT NULL;
