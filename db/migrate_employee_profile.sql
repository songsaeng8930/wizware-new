-- Zaemit 그룹웨어 - 직원 상세 프로필 테이블 (1:N 이력)
-- MySQL 9.6 호환
-- 실행: mysql -u root zaemit_groupware < db/migrate_employee_profile.sql

-- 경력사항
CREATE TABLE IF NOT EXISTS employee_careers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    company_name VARCHAR(100) NOT NULL COMMENT '회사명',
    department VARCHAR(100) NULL COMMENT '부서',
    position VARCHAR(50) NULL COMMENT '직급/직책',
    start_date DATE NOT NULL COMMENT '입사일',
    end_date DATE NULL COMMENT '퇴사일 (NULL=재직중)',
    is_current TINYINT(1) DEFAULT 0 COMMENT '현재 재직 여부',
    description TEXT NULL COMMENT '담당 업무',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_career_emp (employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 학력
CREATE TABLE IF NOT EXISTS employee_educations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    school_name VARCHAR(100) NOT NULL COMMENT '학교명',
    major VARCHAR(100) NULL COMMENT '전공',
    degree VARCHAR(20) NOT NULL COMMENT '고졸/전문학사/학사/석사/박사',
    start_date DATE NULL COMMENT '입학일',
    end_date DATE NULL COMMENT '졸업일',
    status VARCHAR(20) DEFAULT '졸업' COMMENT '졸업/재학/중퇴/수료/졸업예정',
    description TEXT NULL COMMENT '비고',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_edu_emp (employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 자격증
CREATE TABLE IF NOT EXISTS employee_certifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    cert_name VARCHAR(100) NOT NULL COMMENT '자격증명',
    issuing_org VARCHAR(100) NOT NULL COMMENT '발급기관',
    cert_number VARCHAR(50) NULL COMMENT '자격증 번호',
    acquired_date DATE NOT NULL COMMENT '취득일',
    expiry_date DATE NULL COMMENT '만료일 (NULL=무기한)',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_cert_emp (employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 언어능력
CREATE TABLE IF NOT EXISTS employee_languages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    language VARCHAR(50) NOT NULL COMMENT '언어 (영어, 일본어 등)',
    level VARCHAR(20) NOT NULL COMMENT '초급/중급/고급/원어민',
    test_name VARCHAR(50) NULL COMMENT '시험명 (TOEIC, JLPT 등)',
    test_score VARCHAR(20) NULL COMMENT '점수/등급',
    test_date DATE NULL COMMENT '시험일',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_lang_emp (employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 가족정보
CREATE TABLE IF NOT EXISTS employee_families (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    relationship VARCHAR(20) NOT NULL COMMENT '관계 (배우자/자녀/부/모/형제 등)',
    name VARCHAR(50) NOT NULL COMMENT '이름',
    birth_date DATE NULL COMMENT '생년월일',
    phone VARCHAR(20) NULL COMMENT '연락처',
    is_cohabitant TINYINT(1) DEFAULT 1 COMMENT '동거 여부',
    is_dependent TINYINT(1) DEFAULT 0 COMMENT '부양가족 여부 (세금공제)',
    memo VARCHAR(200) NULL COMMENT '비고',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_fam_emp (employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 수상/징계
CREATE TABLE IF NOT EXISTS employee_awards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    type VARCHAR(10) NOT NULL COMMENT '수상/징계',
    title VARCHAR(100) NOT NULL COMMENT '상명/징계명',
    awarded_date DATE NOT NULL COMMENT '일자',
    awarding_org VARCHAR(100) NULL COMMENT '수여기관/결정기관',
    description TEXT NULL COMMENT '사유/내용',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_award_emp (employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
