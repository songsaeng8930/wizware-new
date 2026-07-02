-- Zaemit 그룹웨어 · 대결자 지정 (Proxy/Delegate Approver) 테이블
-- 실행: mysql -u root zaemit_groupware < db/migrate_approval_delegates.sql
--
-- 배경:
--   결재자가 휴가/출장 중일 때 대리 결재자를 지정하는 기능.
--   기간 기반 자동 활성화/비활성화, 1인 1대결 제한, 감사 추적 지원.

-- 1) 대결자 지정 테이블
CREATE TABLE IF NOT EXISTS approval_delegates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    delegator_id INT NOT NULL COMMENT '원결재자 employees.id',
    delegator_name VARCHAR(50) NOT NULL COMMENT '원결재자 이름 스냅샷',
    delegate_id INT NOT NULL COMMENT '대결자 employees.id',
    delegate_name VARCHAR(50) NOT NULL COMMENT '대결자 이름 스냅샷',
    start_date DATE NOT NULL COMMENT '대결 시작일',
    end_date DATE NOT NULL COMMENT '대결 종료일',
    status VARCHAR(10) NOT NULL DEFAULT 'active' COMMENT 'active/expired/cancelled',
    created_by_id INT NOT NULL COMMENT '설정자 employees.id',
    created_by_type VARCHAR(10) NOT NULL DEFAULT 'self' COMMENT 'self/admin',
    reason VARCHAR(200) NULL COMMENT '대결 사유',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    cancelled_at DATETIME NULL COMMENT '해제 일시',
    INDEX idx_delegator (delegator_id, status),
    INDEX idx_delegate (delegate_id, status),
    INDEX idx_date_range (start_date, end_date, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2) approval_history.delegate_id — 대결 처리 시 실제 결재자 기록
SET @c := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'approval_history'
      AND COLUMN_NAME = 'delegate_id'
);
SET @sql := IF(@c = 0,
    'ALTER TABLE approval_history ADD COLUMN delegate_id INT NULL COMMENT ''대결자 employees.id'' AFTER approver_id',
    'SELECT "approval_history.delegate_id 이미 존재" AS note'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 3) approval_history.delegate_name — 대결자 이름 스냅샷
SET @c := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'approval_history'
      AND COLUMN_NAME = 'delegate_name'
);
SET @sql := IF(@c = 0,
    'ALTER TABLE approval_history ADD COLUMN delegate_name VARCHAR(50) NULL COMMENT ''대결자 이름'' AFTER delegate_id',
    'SELECT "approval_history.delegate_name 이미 존재" AS note'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
