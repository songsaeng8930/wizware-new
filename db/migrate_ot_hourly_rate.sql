-- =============================================================
-- 초과수당 시급 직접 설정 지원
-- payroll_pay_types에 custom_hourly_rate 컬럼 추가
-- NULL = 법정 공식 (기본급/209 × 1.5), 값 있으면 해당 시급 적용
-- =============================================================

ALTER TABLE payroll_pay_types
    ADD COLUMN custom_hourly_rate INT UNSIGNED NULL DEFAULT NULL
    COMMENT '시간급 항목의 커스텀 시급 (원). NULL이면 법정 공식 적용'
    AFTER has_hours;
