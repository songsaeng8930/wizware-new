-- 법인카드 지출 한도 초과 예외 신청 지원
ALTER TABLE card_expenses
    ADD COLUMN exception_reason TEXT NULL COMMENT '한도 초과 예외 사유' AFTER compliance_status,
    ADD COLUMN regulation_limit INT NULL COMMENT '적용된 규정 한도 (원)' AFTER exception_reason;

-- compliance_status에 '예외신청' 값 허용 (기존: 미확인/준수/미준수)
-- VARCHAR(20) 그대로 사용, 값만 추가
