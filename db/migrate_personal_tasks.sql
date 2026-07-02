-- ============================================================
-- 개인 할 일 (직원별 To-do) — 캘린더 옆 '내 할 일' 패널용
-- 일정(calendar)은 '시간 약속'용, personal_tasks 는 '체크리스트'용.
-- ============================================================

CREATE TABLE IF NOT EXISTS personal_tasks (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    owner_id     INT NOT NULL COMMENT '할 일 소유자(employees.id) — 세션에서 주입',
    title        VARCHAR(200) NOT NULL,
    description  TEXT NULL,
    due_date     DATE NULL COMMENT 'NULL=기한 없음',
    priority     ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
    status       ENUM('todo','done') NOT NULL DEFAULT 'todo',
    completed_at DATETIME NULL COMMENT '완료 처리 시각 (완료 탭 정렬·보존 기간 판단용)',
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_owner_due (owner_id, due_date),
    INDEX idx_owner_status (owner_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='개인 할 일 (직원별 To-do)';
