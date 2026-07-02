-- 일정 중요 표시 + 캘린더별 색상 지정 기능
-- 2026-06-22

-- 1) 일정 중요 표시 플래그
ALTER TABLE schedules ADD COLUMN is_important TINYINT(1) NOT NULL DEFAULT 0 AFTER visibility;
CREATE INDEX idx_schedules_important ON schedules(is_important);

-- 2) 오버레이 캘린더 사용자별 색상 커스터마이징
-- 사용자가 왼쪽 캘린더 목록에서 각 캘린더의 색상을 개인적으로 변경할 수 있음
CREATE TABLE IF NOT EXISTS calendar_color_overrides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    calendar_key VARCHAR(50) NOT NULL COMMENT '오버레이 키: schedules, holidays, tax, insurance, labor, company',
    color_code VARCHAR(20) NOT NULL COMMENT 'hex 또는 색상명',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_emp_cal (employee_id, calendar_key),
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
