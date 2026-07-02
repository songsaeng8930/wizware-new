-- 데이터 연결성 일괄 수정 마이그레이션
-- 실행 전 반드시 백업할 것

-- ─── Fix 5: match_history.invoice_id NULL 허용 ───
ALTER TABLE match_history MODIFY invoice_id INT UNSIGNED NULL;
UPDATE match_history SET invoice_id = NULL WHERE invoice_id = 0;

-- ─── Fix 6: classification_history.transaction_id NULL 허용 ───
ALTER TABLE classification_history MODIFY transaction_id INT UNSIGNED NULL;
UPDATE classification_history SET transaction_id = NULL WHERE transaction_id = 0;

-- ─── Fix 8: cards 테이블에 FK용 ID 컬럼 추가 ───
ALTER TABLE cards
    ADD COLUMN manager_employee_id INT UNSIGNED NULL AFTER manager_name,
    ADD COLUMN department_id INT UNSIGNED NULL AFTER department;

-- 기존 문자열 데이터로부터 ID 매핑
-- 주의: 동명이인이 있을 경우 첫 번째 매칭 직원으로 설정됨.
-- 실행 후 SELECT card_alias, manager_name, manager_employee_id FROM cards WHERE manager_employee_id IS NOT NULL; 로 검증 필요.
UPDATE cards c
    JOIN (SELECT MIN(id) AS id, name FROM employees WHERE is_active = 1 GROUP BY name) e ON e.name = c.manager_name
SET c.manager_employee_id = e.id
WHERE c.manager_name IS NOT NULL AND c.manager_name != '';

UPDATE cards c
    JOIN departments d ON d.name = c.department AND d.is_active = 1
SET c.department_id = d.id
WHERE c.department IS NOT NULL AND c.department != '';

-- ─── Fix 4: 기존 직원 발령이력 일괄 시드 ───
INSERT INTO employee_appointments (
    employee_id, appointment_type, appointment_date, source,
    new_department_id, new_department_name, new_position, new_title,
    new_employment_type, new_employment_status, reason
)
SELECT
    e.id,
    '신규입사',
    COALESCE(e.hire_date, CURDATE()),
    'auto',
    e.department_id,
    d.name,
    e.position,
    e.title,
    e.employment_type,
    e.employment_status,
    '시스템 자동 생성 (현재 기준 값)'
FROM employees e
LEFT JOIN departments d ON e.department_id = d.id
WHERE e.is_active = 1
  AND NOT EXISTS (
      SELECT 1 FROM employee_appointments ea WHERE ea.employee_id = e.id
  );
