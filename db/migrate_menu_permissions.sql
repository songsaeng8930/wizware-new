-- Zaemit 그룹웨어 · 메뉴/기능 접근권한 관리
-- 실행: mysql -u root zaemit_groupware < db/migrate_menu_permissions.sql
--
-- 배경 (2026-04-23):
--   지금까지 메뉴 접근은 "로그인 여부"만 가지고 판정했다. 일부 API 만 admin/manager
--   체크를 수동으로 했으나(annual_leave, docs 등), 화면 단에서 메뉴를 숨기거나
--   페이지 진입을 차단하는 공통 체계가 없었다.
--   이 마이그레이션은 메뉴 키 × (역할 or 부서) × 접근 레벨을 일괄 관리하는
--   매트릭스 테이블을 도입한다.
--
-- 운영 원칙:
--   · role_key='admin' 행이 있으면 admin 은 모든 메뉴에 admin 레벨로 접근 (헬퍼에서 우선 처리).
--   · role_key 와 department_id 는 OR 조합 · 둘 중 하나라도 맞으면 통과.
--   · 한 (menu_key, role_key, department_id) 조합은 유니크.
--   · access_level view < edit < admin. 상위 레벨은 하위 권한 포함.

CREATE TABLE IF NOT EXISTS menu_permissions (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    menu_key       VARCHAR(60) NOT NULL COMMENT '예: dashboard, accounting.settle, labor.rules',
    role_key       VARCHAR(30) NULL     COMMENT 'admin | manager | user (NULL 이면 역할 조건 없음)',
    department_id  INT NULL             COMMENT '특정 부서에만 한정 (NULL 이면 부서 조건 없음)',
    access_level   ENUM('view','edit','admin') NOT NULL DEFAULT 'view',
    note           VARCHAR(200) NULL    COMMENT '운영 메모',
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_menu_role_dept (menu_key, role_key, department_id),
    INDEX idx_menu (menu_key),
    INDEX idx_role (role_key),
    INDEX idx_dept (department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 기본 시드 · admin 은 모든 메뉴 admin
INSERT IGNORE INTO menu_permissions (menu_key, role_key, access_level, note) VALUES
    ('*',              'admin',   'admin', '관리자는 모든 메뉴에 접근'),
    ('dashboard',      'manager', 'edit',  ''),
    ('dashboard',      'user',    'view',  ''),
    ('attendance',     'manager', 'edit',  ''),
    ('attendance',     'user',    'edit',  ''),
    ('schedule',       'manager', 'edit',  ''),
    ('schedule',       'user',    'edit',  ''),
    ('approval',       'manager', 'edit',  ''),
    ('approval',       'user',    'edit',  ''),
    ('board',          'manager', 'edit',  ''),
    ('board',          'user',    'edit',  ''),
    ('hr',             'manager', 'edit',  '부서장은 본인 부서 직원 조회'),
    ('hr',             'user',    'view',  ''),
    ('accounting',     'manager', 'view',  ''),
    ('accounting.settle', 'manager', 'edit', '회계 정산 · 본부장/회계팀만'),
    ('labor',          'manager', 'view',  ''),
    ('labor.rules',    'manager', 'view',  '취업규칙 편집은 admin 전용 (기본)'),
    ('business',       'manager', 'edit',  ''),
    ('business',       'user',    'view',  ''),
    ('business_docs',  'manager', 'view',  ''),
    ('business_docs',  'user',    'view',  ''),
    ('groupware',      'admin',   'admin', '시스템 관리는 관리자만'),
    ('groupware.permissions', 'admin', 'admin', '접근권한 관리는 관리자만');
