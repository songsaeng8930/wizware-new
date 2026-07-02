-- Zaemit 그룹웨어 - 조직도 테이블 스키마
-- MySQL 9.6 호환
-- 실행: mysql -u root zaemit_groupware < db/schema_organization.sql

-- 부서 테이블
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT NULL,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) NULL COMMENT '부서코드',
    head_employee_id INT NULL COMMENT '부서장 ID',
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 직원 테이블
CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NULL,
    name VARCHAR(50) NOT NULL,
    position VARCHAR(50) NULL COMMENT '직급 (대표이사, 이사, 부장, 과장, 대리, 사원 등)',
    title VARCHAR(100) NULL COMMENT '직책 (CEO, CTO, 팀장 등)',
    email VARCHAR(100) NULL,
    phone VARCHAR(20) NULL,
    profile_image VARCHAR(255) NULL,
    hire_date DATE NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 부서장 외래키 추가
ALTER TABLE departments ADD FOREIGN KEY (head_employee_id) REFERENCES employees(id) ON DELETE SET NULL;

-- 샘플 데이터: 부서
INSERT INTO departments (id, parent_id, name, code, sort_order) VALUES
(1, NULL, '(주)재밋', 'ZAEMIT', 0),
(2, 1, '경영지원본부', 'MGT', 1),
(3, 1, '기술개발본부', 'TECH', 2),
(4, 1, '영업본부', 'SALES', 3),
(5, 2, '경영지원팀', 'MGT-SUPPORT', 1),
(6, 2, '인사팀', 'MGT-HR', 2),
(7, 2, '재무회계팀', 'MGT-FIN', 3),
(8, 3, '개발1팀', 'TECH-DEV1', 1),
(9, 3, '개발2팀', 'TECH-DEV2', 2),
(10, 3, 'QA팀', 'TECH-QA', 3),
(11, 4, '국내영업팀', 'SALES-DOM', 1),
(12, 4, '해외영업팀', 'SALES-INT', 2);

-- 샘플 데이터: 직원
INSERT INTO employees (id, department_id, name, position, title, email, phone, hire_date) VALUES
(1, 1, '김대표', '대표이사', 'CEO', 'ceo@zaemit.com', '010-1234-5678', '2020-01-01'),
(2, 2, '이본부장', '이사', '경영지원본부장', 'lee@zaemit.com', '010-2345-6789', '2020-03-01'),
(3, 3, '박기술', '이사', 'CTO', 'park@zaemit.com', '010-3456-7890', '2020-03-15'),
(4, 4, '최영업', '이사', '영업본부장', 'choi@zaemit.com', '010-4567-8901', '2020-06-01'),
(5, 5, '정지원', '부장', '경영지원팀장', 'jung@zaemit.com', '010-5678-9012', '2021-01-10'),
(6, 6, '한인사', '부장', '인사팀장', 'han@zaemit.com', '010-6789-0123', '2021-02-01'),
(7, 7, '오재무', '부장', '재무회계팀장', 'oh@zaemit.com', '010-7890-1234', '2021-03-01'),
(8, 8, '강개발', '부장', '개발1팀장', 'kang@zaemit.com', '010-8901-2345', '2021-04-01'),
(9, 9, '윤개발', '과장', '개발2팀장', 'yoon@zaemit.com', '010-9012-3456', '2021-05-01'),
(10, 10, '임품질', '과장', 'QA팀장', 'lim@zaemit.com', '010-0123-4567', '2021-06-01'),
(11, 11, '서영업', '과장', '국내영업팀장', 'seo@zaemit.com', '010-1111-2222', '2021-07-01'),
(12, 12, '류해외', '과장', '해외영업팀장', 'ryu@zaemit.com', '010-3333-4444', '2021-08-01'),
(13, 5, '김경영', '대리', NULL, 'kimm@zaemit.com', '010-5555-6666', '2022-01-15'),
(14, 6, '이인사', '대리', NULL, 'leehr@zaemit.com', '010-7777-8888', '2022-03-01'),
(15, 8, '박프론트', '대리', NULL, 'parkfe@zaemit.com', '010-9999-0000', '2022-04-01'),
(16, 8, '송백엔드', '사원', NULL, 'songbe@zaemit.com', '010-1212-3434', '2023-01-02'),
(17, 9, '조풀스택', '대리', NULL, 'jofs@zaemit.com', '010-5656-7878', '2022-06-01'),
(18, 9, '황모바일', '사원', NULL, 'hwangmb@zaemit.com', '010-9090-1212', '2023-03-01'),
(19, 10, '문테스터', '대리', NULL, 'moonqa@zaemit.com', '010-3434-5656', '2022-07-01'),
(20, 11, '배영업', '대리', NULL, 'baes@zaemit.com', '010-7878-9090', '2022-09-01');

-- 부서장 설정
UPDATE departments SET head_employee_id = 1 WHERE id = 1;
UPDATE departments SET head_employee_id = 2 WHERE id = 2;
UPDATE departments SET head_employee_id = 3 WHERE id = 3;
UPDATE departments SET head_employee_id = 4 WHERE id = 4;
UPDATE departments SET head_employee_id = 5 WHERE id = 5;
UPDATE departments SET head_employee_id = 6 WHERE id = 6;
UPDATE departments SET head_employee_id = 7 WHERE id = 7;
UPDATE departments SET head_employee_id = 8 WHERE id = 8;
UPDATE departments SET head_employee_id = 9 WHERE id = 9;
UPDATE departments SET head_employee_id = 10 WHERE id = 10;
UPDATE departments SET head_employee_id = 11 WHERE id = 11;
UPDATE departments SET head_employee_id = 12 WHERE id = 12;
