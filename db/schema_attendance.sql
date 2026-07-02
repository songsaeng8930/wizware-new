-- Zaemit 그룹웨어 - 출퇴근 테이블 스키마
-- MySQL 8.4 / 9.6 호환
-- 실행: mysql -u root zaemit_groupware < db/schema_attendance.sql
--
-- 배경:
--   api/attendance.php와 pages/dashboard.php가 attendance_records / outside_work_records 를
--   사용하고 있으나 init.sql에 DDL이 누락되어 있었다.
--   이 파일은 누락된 두 테이블을 멱등적으로 생성한다.

-- 출퇴근 레코드
CREATE TABLE IF NOT EXISTS attendance_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL COMMENT 'employees.id',
    record_date DATE NOT NULL,
    clock_in TIME NULL,
    work_plan TEXT NULL COMMENT '출근 시 작성한 오늘의 업무 계획',
    clock_out TIME NULL,
    leave_note TEXT NULL COMMENT '퇴근 시 작성한 특이사항 메모',
    work_type VARCHAR(20) NULL COMMENT '외근/출장/재택 등',
    note VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_emp_date (employee_id, record_date),
    INDEX idx_employee_id (employee_id),
    INDEX idx_record_date (record_date),
    CONSTRAINT fk_att_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 외근 상세 기록 (출발/복귀 시각, 방문처, 사유)
CREATE TABLE IF NOT EXISTS outside_work_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    work_date DATE NOT NULL,
    departure_time TIME NULL,
    return_time TIME NULL,
    destination VARCHAR(200) NOT NULL,
    purpose VARCHAR(300) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_employee_date (employee_id, work_date),
    CONSTRAINT fk_ow_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
