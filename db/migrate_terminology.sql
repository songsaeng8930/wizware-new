-- Zaemit 그룹웨어 · 회사 용어 설정 V2 마이그레이션 (DDL)
-- MySQL 9.6 호환 · 멱등 실행 가능
-- 실행: mysql -u root zaemit_groupware < db/migrate_terminology.sql
-- 데이터 시딩: php db/migrate_terminology_data.php

-- ============================================================
-- 1. org_levels — 조직 계층 단계 (JSON → DB 이관)
-- ============================================================
CREATE TABLE IF NOT EXISTS org_levels (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    depth       TINYINT NOT NULL COMMENT '정렬 순서 (0=최상위, 6=최하위)',
    key_name    VARCHAR(30) NOT NULL COMMENT '시스템 키 (company, division, department 등)',
    label       VARCHAR(50) NOT NULL COMMENT '표시명 (회사, 본부, 부서 등)',
    head_title  VARCHAR(50) NOT NULL COMMENT '책임자 호칭 (대표, 본부장, 부서장 등)',
    is_enabled  TINYINT(1) NOT NULL DEFAULT 1,
    is_required TINYINT(1) NOT NULL DEFAULT 0 COMMENT '삭제 불가 필수 레벨 (company, division, department)',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_key (key_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- 2. hr_ranks — 직급 체계
-- ============================================================
CREATE TABLE IF NOT EXISTS hr_ranks (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(50) NOT NULL COMMENT '직급명 (사원, 대리, 과장 등)',
    code        VARCHAR(20) NULL COMMENT '코드 (STF, AM, MGR 등)',
    tier        INT NOT NULL DEFAULT 0 COMMENT '등급 그룹 (0=미분류)',
    sort_order  INT NOT NULL DEFAULT 0 COMMENT '서열 (1=최고위)',
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- 3. hr_duties — 직책 체계
-- ============================================================
CREATE TABLE IF NOT EXISTS hr_duties (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(50) NOT NULL COMMENT '직책명 (CEO, 본부장, 팀장 등)',
    code        VARCHAR(20) NULL COMMENT '코드 (CEO, HEAD, TL 등)',
    tier        INT NOT NULL DEFAULT 0 COMMENT '등급 그룹 (0=미분류)',
    sort_order  INT NOT NULL DEFAULT 0,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- 4. hr_positions — 직위 (Phase 2 활성화, 지금은 빈 테이블)
-- ============================================================
CREATE TABLE IF NOT EXISTS hr_positions (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(50) NOT NULL COMMENT '직위명 (수석연구원, 선임컨설턴트 등)',
    code        VARCHAR(20) NULL,
    tier        INT NOT NULL DEFAULT 0 COMMENT '등급 그룹 (0=미분류)',
    sort_order  INT NOT NULL DEFAULT 0,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- 5. terminology_display_config — 맥락별 사람 표시 형식
-- ============================================================
CREATE TABLE IF NOT EXISTS terminology_display_config (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    context_key     VARCHAR(30) NOT NULL COMMENT '맥락 키 (default, org_chart, approval, board, profile)',
    format_pattern  VARCHAR(100) NOT NULL DEFAULT '{name} {rank}' COMMENT '표시 패턴 ({name},{rank},{duty},{position},{dept},{suffix})',
    suffix          VARCHAR(20) NULL COMMENT '호칭 접미사 (님, 씨 등)',
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_context (context_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- 5-1. hr_ranks/duties/positions tier 컬럼 추가 (기존 환경 마이그레이션)
-- ============================================================
SET @col_rank_tier = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hr_ranks' AND COLUMN_NAME = 'tier'
);
SET @sql_rank_tier = IF(@col_rank_tier = 0,
    'ALTER TABLE hr_ranks ADD COLUMN tier INT NOT NULL DEFAULT 0 COMMENT ''등급 그룹'' AFTER code',
    'SELECT ''hr_ranks.tier already exists'' AS note'
);
PREPARE stmt FROM @sql_rank_tier; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_duty_tier = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hr_duties' AND COLUMN_NAME = 'tier'
);
SET @sql_duty_tier = IF(@col_duty_tier = 0,
    'ALTER TABLE hr_duties ADD COLUMN tier INT NOT NULL DEFAULT 0 COMMENT ''등급 그룹'' AFTER code',
    'SELECT ''hr_duties.tier already exists'' AS note'
);
PREPARE stmt FROM @sql_duty_tier; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_pos_tier = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hr_positions' AND COLUMN_NAME = 'tier'
);
SET @sql_pos_tier = IF(@col_pos_tier = 0,
    'ALTER TABLE hr_positions ADD COLUMN tier INT NOT NULL DEFAULT 0 COMMENT ''등급 그룹'' AFTER code',
    'SELECT ''hr_positions.tier already exists'' AS note'
);
PREPARE stmt FROM @sql_pos_tier; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- ============================================================
-- 6. departments.level_id 컬럼 추가
-- ============================================================
SET @col_dept_level = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'departments' AND COLUMN_NAME = 'level_id'
);
SET @sql_dept_level = IF(@col_dept_level = 0,
    'ALTER TABLE departments ADD COLUMN level_id INT NULL COMMENT ''org_levels.id'' AFTER parent_id',
    'SELECT ''departments.level_id already exists'' AS note'
);
PREPARE stmt FROM @sql_dept_level; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- ============================================================
-- 7. employees 컬럼 추가 (rank_id, duty_id, position_id)
-- ============================================================

-- rank_id (직급 FK)
SET @col_rank = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employees' AND COLUMN_NAME = 'rank_id'
);
SET @sql_rank = IF(@col_rank = 0,
    'ALTER TABLE employees ADD COLUMN rank_id INT NULL COMMENT ''hr_ranks.id'' AFTER position',
    'SELECT ''employees.rank_id already exists'' AS note'
);
PREPARE stmt FROM @sql_rank; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- duty_id (직책 FK)
SET @col_duty = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employees' AND COLUMN_NAME = 'duty_id'
);
SET @sql_duty = IF(@col_duty = 0,
    'ALTER TABLE employees ADD COLUMN duty_id INT NULL COMMENT ''hr_duties.id'' AFTER title',
    'SELECT ''employees.duty_id already exists'' AS note'
);
PREPARE stmt FROM @sql_duty; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- position_id (직위 FK — Phase 2)
SET @col_pos = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employees' AND COLUMN_NAME = 'position_id'
);
SET @sql_pos = IF(@col_pos = 0,
    'ALTER TABLE employees ADD COLUMN position_id INT NULL COMMENT ''hr_positions.id'' AFTER duty_id',
    'SELECT ''employees.position_id already exists'' AS note'
);
PREPARE stmt FROM @sql_pos; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- ============================================================
-- 8. approval_history.approver_rank 스냅샷 컬럼 추가
-- ============================================================
SET @col_ap = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'approval_history' AND COLUMN_NAME = 'approver_rank'
);
SET @sql_ap = IF(@col_ap = 0,
    'ALTER TABLE approval_history ADD COLUMN approver_rank VARCHAR(50) NULL COMMENT ''결재자 직급 스냅샷'' AFTER approver_dept',
    'SELECT ''approval_history.approver_rank already exists'' AS note'
);
PREPARE stmt FROM @sql_ap; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- ============================================================
-- 9. FK 제약 (데이터 채운 후 추가해도 안전하도록 별도 블록)
--    migrate_terminology_data.php 실행 후 이 블록을 실행하거나,
--    아래에서 자동으로 체크 후 추가
-- ============================================================

-- departments.level_id → org_levels.id
SET @fk_dept = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'departments' AND CONSTRAINT_NAME = 'fk_dept_level'
);
SET @sql_fk_dept = IF(@fk_dept = 0,
    'ALTER TABLE departments ADD CONSTRAINT fk_dept_level FOREIGN KEY (level_id) REFERENCES org_levels(id) ON DELETE SET NULL',
    'SELECT ''fk_dept_level already exists'' AS note'
);
PREPARE stmt FROM @sql_fk_dept; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- employees.rank_id → hr_ranks.id
SET @fk_rank = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employees' AND CONSTRAINT_NAME = 'fk_emp_rank'
);
SET @sql_fk_rank = IF(@fk_rank = 0,
    'ALTER TABLE employees ADD CONSTRAINT fk_emp_rank FOREIGN KEY (rank_id) REFERENCES hr_ranks(id) ON DELETE SET NULL',
    'SELECT ''fk_emp_rank already exists'' AS note'
);
PREPARE stmt FROM @sql_fk_rank; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- employees.duty_id → hr_duties.id
SET @fk_duty = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employees' AND CONSTRAINT_NAME = 'fk_emp_duty'
);
SET @sql_fk_duty = IF(@fk_duty = 0,
    'ALTER TABLE employees ADD CONSTRAINT fk_emp_duty FOREIGN KEY (duty_id) REFERENCES hr_duties(id) ON DELETE SET NULL',
    'SELECT ''fk_emp_duty already exists'' AS note'
);
PREPARE stmt FROM @sql_fk_duty; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- employees.position_id → hr_positions.id
SET @fk_pos = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employees' AND CONSTRAINT_NAME = 'fk_emp_position'
);
SET @sql_fk_pos = IF(@fk_pos = 0,
    'ALTER TABLE employees ADD CONSTRAINT fk_emp_position FOREIGN KEY (position_id) REFERENCES hr_positions(id) ON DELETE SET NULL',
    'SELECT ''fk_emp_position already exists'' AS note'
);
PREPARE stmt FROM @sql_fk_pos; EXECUTE stmt; DEALLOCATE PREPARE stmt;
