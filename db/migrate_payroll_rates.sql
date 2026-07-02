-- 급여 공제요율 테이블 (연도별 관리)
-- 4대보험+소득세 요율을 DB에서 관리하여 코드 수정 없이 변경 가능

CREATE TABLE IF NOT EXISTS payroll_rates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    year SMALLINT UNSIGNED NOT NULL COMMENT '적용 연도',
    rate_pension DECIMAL(7,5) NOT NULL DEFAULT 0.04500 COMMENT '국민연금 근로자부담률',
    rate_health DECIMAL(7,5) NOT NULL DEFAULT 0.03545 COMMENT '건강보험 근로자부담률',
    rate_employ DECIMAL(7,5) NOT NULL DEFAULT 0.00900 COMMENT '고용보험 근로자부담률',
    rate_tax    DECIMAL(7,5) NOT NULL DEFAULT 0.03300 COMMENT '소득세+지방소득세 합산률',
    memo VARCHAR(200) NOT NULL DEFAULT '' COMMENT '비고 (고시번호 등)',
    updated_by INT UNSIGNED NULL COMMENT '수정자 employees.id',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE INDEX uq_year (year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='급여 공제요율 (연도별)';

-- 2025년 시드 (현행 상수값 그대로)
INSERT INTO payroll_rates (year, rate_pension, rate_health, rate_employ, rate_tax, memo)
VALUES (2025, 0.04500, 0.03545, 0.00900, 0.03300, '2025년 고시 기준 (시범 운영용 간이 세율)')
ON DUPLICATE KEY UPDATE year = year;

-- 2026년 시드 (동일 요율 — 변경 시 UI에서 수정)
INSERT INTO payroll_rates (year, rate_pension, rate_health, rate_employ, rate_tax, memo)
VALUES (2026, 0.04500, 0.03545, 0.00900, 0.03300, '2026년 (2025년과 동일, 고시 확인 후 수정)')
ON DUPLICATE KEY UPDATE year = year;
