-- 병역사항 (직원당 1건, UNIQUE)
CREATE TABLE IF NOT EXISTS employee_military (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    military_status VARCHAR(20) NOT NULL DEFAULT '해당없음' COMMENT '병역구분',
    branch VARCHAR(20) NULL COMMENT '군별 (육군/해군/공군/해병대 등)',
    rank_title VARCHAR(30) NULL COMMENT '계급',
    enlist_date DATE NULL COMMENT '입대일',
    discharge_date DATE NULL COMMENT '전역일',
    exemption_reason VARCHAR(100) NULL COMMENT '면제사유',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_military_emp (employee_id),
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 보유 스킬 (자유 태그)
CREATE TABLE IF NOT EXISTS employee_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    skill_name VARCHAR(100) NOT NULL COMMENT '스킬명',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_skill_emp (employee_id),
    UNIQUE KEY uq_skill_emp_name (employee_id, skill_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
