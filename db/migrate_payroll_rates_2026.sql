-- 2026년 4대보험 요율 업데이트 마이그레이션
-- 근거:
--   국민연금 4.75%: 국민연금법 시행령 개정 (2026.1.1 시행, 총 9.5% → 근로자 4.75%)
--   건강보험 3.595%: 2026년도 건강보험료율 고시 (총 7.19% → 근로자 3.595%)
--   장기요양 0.4724%: 보건복지부 고시 (건강보험료의 13.14%, 실효 근로자 부담 0.4724%)
--   고용보험 0.9%: 고용보험법 시행령 (변경 없음)

-- 1. 기존 요율 업데이트 (2025→2026)
UPDATE payroll_pay_types SET calc_rate = 0.047500 WHERE code = 'NP' AND category = 'deduct';
UPDATE payroll_pay_types SET calc_rate = 0.035950 WHERE code = 'HI' AND category = 'deduct';

-- 2. 장기요양보험 추가 (누락 항목)
INSERT INTO payroll_pay_types (code, name, category, is_taxable, calc_type, calc_base, calc_rate, has_hours, sort_order, is_system)
VALUES ('LC', '장기요양보험', 'deduct', 0, 'rate', 'base_salary', 0.004724, 0, 125, 1)
ON DUPLICATE KEY UPDATE calc_rate = VALUES(calc_rate);
