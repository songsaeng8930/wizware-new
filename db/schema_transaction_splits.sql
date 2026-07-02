-- =============================================================
-- 거래 분할 테이블
-- 하나의 통장 거래를 여러 계정과목으로 배분
-- 예: 150만원 출금 → 재료비 100만 + 운반비 50만
-- =============================================================

CREATE TABLE IF NOT EXISTS transaction_splits (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id  INT UNSIGNED NOT NULL COMMENT '원본 bank_transactions.id',
    account_code    VARCHAR(10)  NOT NULL COMMENT '분할 계정과목 코드',
    account_name    VARCHAR(100) NOT NULL COMMENT '분할 계정과목명',
    amount          BIGINT       NOT NULL COMMENT '분할 금액 (양수)',
    memo            VARCHAR(200) NULL     COMMENT '분할 메모',
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES bank_transactions(id) ON DELETE CASCADE,
    INDEX idx_split_tx (transaction_id),
    INDEX idx_split_account (account_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='거래 분할 (하나의 거래를 여러 계정과목에 배분)';
