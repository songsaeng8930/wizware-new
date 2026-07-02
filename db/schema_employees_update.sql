-- Zaemit 그룹웨어 - 직원 테이블 확장 (구성원관리용)
-- MySQL 9.6 호환
-- 실행: mysql -u root zaemit_groupware < db/schema_employees_update.sql

-- 소속(회사), 고용형태, 고용상태 컬럼 추가
ALTER TABLE employees
    ADD COLUMN affiliation VARCHAR(50) NULL COMMENT '소속 (WEVEN, Zaemit 등)' AFTER department_id,
    ADD COLUMN employment_type VARCHAR(20) DEFAULT '정규직' COMMENT '고용형태 (정규직, 계약직, 시간제, 파견직)' AFTER phone,
    ADD COLUMN employment_status VARCHAR(20) DEFAULT '재직' COMMENT '고용상태 (재직, 휴직, 육아휴직, 퇴사)' AFTER employment_type,
    ADD COLUMN is_dept_head TINYINT(1) DEFAULT 0 COMMENT '부서장 여부' AFTER employment_status;

-- 기존 샘플 데이터 업데이트
UPDATE employees SET affiliation = 'Zaemit', employment_type = '정규직', employment_status = '재직';
UPDATE employees SET is_dept_head = 1 WHERE id IN (
    SELECT head_employee_id FROM departments WHERE head_employee_id IS NOT NULL
);
