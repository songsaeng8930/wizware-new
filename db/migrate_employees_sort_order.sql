-- ============================================
-- employees 테이블에 sort_order 추가 (부서 내 직원 순서)
-- ============================================
-- 조직도에서 같은 부서 내 직원을 위아래로 드래그앤드롭해 재정렬하기 위한 컬럼.
-- 초깃값은 기존 기본 정렬(직급 우선순위 → 이름)을 그대로 반영하여 채움.

ALTER TABLE employees
    ADD COLUMN IF NOT EXISTS sort_order INT NOT NULL DEFAULT 0
        COMMENT '부서 내 표시 순서 (작을수록 위)' AFTER employment_status,
    ADD INDEX IF NOT EXISTS idx_dept_sort (department_id, sort_order);

-- 기존 직원들에 대해 부서별로 순차적으로 10 간격으로 할당 (향후 삽입 여지 확보)
UPDATE employees e
JOIN (
    SELECT id,
           ROW_NUMBER() OVER (
               PARTITION BY department_id
               ORDER BY FIELD(position,
                   '대표이사','이사','부장','차장','과장','대리','주임','사원'),
                   name
           ) AS rn
      FROM employees
) ranked ON e.id = ranked.id
SET e.sort_order = ranked.rn * 10
WHERE e.sort_order = 0;
