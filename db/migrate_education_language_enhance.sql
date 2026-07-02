-- 학력 테이블 필드 확장: 학교구분, 학점, 학점만점
-- 멱등 실행: 컬럼이 이미 존재하면 건너뜀

-- school_type 컬럼 추가
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_educations' AND COLUMN_NAME = 'school_type');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE employee_educations ADD COLUMN school_type VARCHAR(30) NULL COMMENT ''학교구분 (고등학교/대학교(2,3년)/대학교(4년)/대학원(석사)/대학원(박사))'' AFTER degree', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- gpa 컬럼 추가
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_educations' AND COLUMN_NAME = 'gpa');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE employee_educations ADD COLUMN gpa DECIMAL(3,2) NULL COMMENT ''학점'' AFTER school_type', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- gpa_scale 컬럼 추가
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_educations' AND COLUMN_NAME = 'gpa_scale');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE employee_educations ADD COLUMN gpa_scale DECIMAL(2,1) NULL COMMENT ''학점 만점 (4.5 또는 4.0)'' AFTER gpa', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 어학 테이블 필드 확장: 시험유형
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_languages' AND COLUMN_NAME = 'test_type');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE employee_languages ADD COLUMN test_type VARCHAR(20) NULL COMMENT ''시험유형 (공인시험/회화/자격)'' AFTER level', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
