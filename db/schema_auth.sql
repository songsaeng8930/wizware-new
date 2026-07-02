-- 인증 시스템 스키마
-- 최초 작성: 2026-04-14
-- 갱신: 2026-04-21 · 역할(user_role) 3단계로 확장 (admin / manager / user)
--
-- 실행 방법:
--   - 최초 설치: 아래 ALTER 블록만 실행 (컬럼 추가)
--   - 기존 설치: "갱신 블록" 재실행 가능 (idempotent)

-- ============================================================
-- 1. 컬럼 추가 (최초 1회)
-- ============================================================
-- 이미 컬럼이 있으면 에러가 납니다. 그 경우 이 블록은 건너뛰세요.
ALTER TABLE employees
    ADD COLUMN password_hash VARCHAR(255) NULL COMMENT '비밀번호 해시 (bcrypt)' AFTER email,
    ADD COLUMN user_role VARCHAR(20) NOT NULL DEFAULT 'user' COMMENT '역할 (admin/manager/user)' AFTER password_hash;

-- 빠른 조회를 위한 인덱스
ALTER TABLE employees ADD INDEX idx_user_role (user_role);
ALTER TABLE employees ADD INDEX idx_email_active (email, is_active);

-- ============================================================
-- 2. 갱신 블록 · 역할 재분류 (idempotent, 반복 실행 안전)
-- ============================================================
-- admin : 대표이사 / 이사 (전사 권한)
UPDATE employees
   SET user_role = 'admin'
 WHERE position IN ('대표이사', '이사');

-- manager : 부서장(is_dept_head=1) 이면서 admin 이 아닌 사람 (부서 단위 권한)
UPDATE employees
   SET user_role = 'manager'
 WHERE is_dept_head = 1
   AND user_role <> 'admin';

-- user : 위에 해당하지 않는 나머지 재직자
UPDATE employees
   SET user_role = 'user'
 WHERE user_role NOT IN ('admin', 'manager');

-- ============================================================
-- 3. (옵션) 무결성 체크 제약 · MySQL 8.0.16+ / MariaDB 10.2+
-- ============================================================
-- 잘못된 role 값이 들어가는 것을 막습니다.
-- 기존 설치에 적용할 때는 먼저 아래 SELECT 로 이상 데이터를 확인하세요:
--   SELECT id, name, user_role FROM employees
--    WHERE user_role NOT IN ('admin','manager','user');
ALTER TABLE employees
  ADD CONSTRAINT chk_employees_user_role
      CHECK (user_role IN ('admin', 'manager', 'user'));
