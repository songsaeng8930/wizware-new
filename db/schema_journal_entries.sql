-- =============================================================
-- 수동 조정 분개 테이블
-- 통장을 거치지 않는 거래 입력 (감가상각, 미지급비용, 대손충당금 등)
-- 복식부기: 차변 계정과 대변 계정을 동시에 기록
-- =============================================================

CREATE TABLE IF NOT EXISTS journal_entries (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entry_date      DATE         NOT NULL COMMENT '분개 일자',
    description     VARCHAR(200) NOT NULL COMMENT '적요',
    debit_code      VARCHAR(10)  NOT NULL COMMENT '차변 계정과목 코드',
    debit_name      VARCHAR(100) NOT NULL COMMENT '차변 계정과목명',
    credit_code     VARCHAR(10)  NOT NULL COMMENT '대변 계정과목 코드',
    credit_name     VARCHAR(100) NOT NULL COMMENT '대변 계정과목명',
    amount          BIGINT       NOT NULL COMMENT '금액 (양수)',
    entry_type      ENUM('adjusting','closing','opening') NOT NULL DEFAULT 'adjusting' COMMENT '분개 유형',
    period_year     SMALLINT UNSIGNED NOT NULL COMMENT '귀속 회계연도',
    period_month    TINYINT UNSIGNED  NOT NULL COMMENT '귀속 월',
    memo            VARCHAR(200) NULL     COMMENT '비고',
    created_by      INT UNSIGNED NULL     COMMENT '작성자 ID',
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_period (period_year, period_month),
    INDEX idx_entry_date (entry_date),
    INDEX idx_debit (debit_code),
    INDEX idx_credit (credit_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='수동 조정 분개 (통장 외 거래)';
