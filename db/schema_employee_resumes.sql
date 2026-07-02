-- ============================================
-- 직원 이력서 첨부 (employees 확장)
-- ============================================
-- 기존 employee_register.php 의 학력/경력 인라인 테이블은 백엔드 미연동
-- 상태였고 실데이터 없음 → 이력서 파일 업로드 방식으로 단순화.
-- 사용자별로 여러 버전 누적 가능, 개별 삭제 가능.

CREATE TABLE IF NOT EXISTS employee_resumes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL COMMENT 'employees.id',
    file_name VARCHAR(255) NOT NULL COMMENT '원본 파일명',
    file_path VARCHAR(500) NOT NULL COMMENT '저장 상대경로 (uploads/resumes/...)',
    file_size INT NOT NULL DEFAULT 0 COMMENT 'bytes',
    mime_type VARCHAR(100) NULL,
    uploaded_by INT NULL COMMENT 'employees.id (업로더)',
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_employee (employee_id, uploaded_at DESC),
    CONSTRAINT fk_er_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='직원 이력서 파일 (여러 버전 누적)';
