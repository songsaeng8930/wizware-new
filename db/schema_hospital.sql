-- Zaemit 그룹웨어 - 병원 전용 모듈

CREATE TABLE IF NOT EXISTS hospital_shift_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slot_date DATE NOT NULL,
    shift_type VARCHAR(20) NOT NULL,
    role_name VARCHAR(50) NOT NULL,
    employee_name VARCHAR(50) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    note VARCHAR(200) NULL,
    status VARCHAR(20) NOT NULL DEFAULT '확정',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_shift_date (slot_date),
    INDEX idx_shift_employee (employee_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS hospital_daily_checks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    check_date DATE NOT NULL,
    shift_type VARCHAR(20) NOT NULL DEFAULT '오픈',
    item_name VARCHAR(100) NOT NULL,
    category VARCHAR(40) NOT NULL,
    is_done TINYINT(1) NOT NULL DEFAULT 0,
    checked_by VARCHAR(50) NULL,
    checked_at DATETIME NULL,
    note VARCHAR(200) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_check_day_item (check_date, shift_type, item_name),
    INDEX idx_check_date (check_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS hospital_cash_closings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    closing_date DATE NOT NULL UNIQUE,
    cash_amount INT NOT NULL DEFAULT 0,
    card_amount INT NOT NULL DEFAULT 0,
    transfer_amount INT NOT NULL DEFAULT 0,
    refund_amount INT NOT NULL DEFAULT 0,
    unpaid_amount INT NOT NULL DEFAULT 0,
    patient_count INT NOT NULL DEFAULT 0,
    memo VARCHAR(300) NULL,
    status VARCHAR(20) NOT NULL DEFAULT '작성중',
    closed_by VARCHAR(50) NULL,
    approved_by VARCHAR(50) NULL,
    approved_at DATETIME NULL,
    bank_transaction_id INT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS hospital_assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_type VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    current_qty INT NULL,
    min_qty INT NULL,
    unit VARCHAR(20) NULL,
    expire_date DATE NULL,
    location VARCHAR(80) NULL,
    vendor VARCHAR(100) NULL,
    last_checked_at DATE NULL,
    next_due_date DATE NULL,
    status VARCHAR(20) NOT NULL DEFAULT '정상',
    memo VARCHAR(300) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_asset_type (asset_type),
    INDEX idx_asset_due (next_due_date),
    INDEX idx_asset_expire (expire_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS hospital_staff_credentials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_name VARCHAR(50) NOT NULL,
    credential_type VARCHAR(50) NOT NULL,
    credential_name VARCHAR(120) NOT NULL,
    issue_date DATE NULL,
    expire_date DATE NULL,
    status VARCHAR(20) NOT NULL DEFAULT '유효',
    memo VARCHAR(300) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_credential_expire (expire_date),
    INDEX idx_credential_employee (employee_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS hospital_leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_name VARCHAR(50) NOT NULL,
    leave_type VARCHAR(30) NOT NULL DEFAULT '연차',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    substitute_name VARCHAR(50) NULL,
    reason VARCHAR(300) NULL,
    status VARCHAR(20) NOT NULL DEFAULT '신청',
    approved_by VARCHAR(50) NULL,
    approved_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_leave_dates (start_date, end_date),
    INDEX idx_leave_employee (employee_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS hospital_purchase_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NULL,
    item_name VARCHAR(100) NOT NULL,
    requested_qty INT NOT NULL DEFAULT 1,
    unit VARCHAR(20) NULL,
    requester_name VARCHAR(50) NULL,
    vendor VARCHAR(100) NULL,
    reason VARCHAR(300) NULL,
    status VARCHAR(20) NOT NULL DEFAULT '요청',
    approved_by VARCHAR(50) NULL,
    approved_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_purchase_status (status),
    INDEX idx_purchase_asset (asset_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO hospital_shift_slots (slot_date, shift_type, role_name, employee_name, start_time, end_time, note, status)
SELECT * FROM (
    SELECT CURDATE(), '오전', '원무', '김대표', '09:00:00', '13:00:00', '접수/수납', '확정'
    UNION ALL SELECT CURDATE(), '오전', '진료지원', '이정민', '09:00:00', '13:00:00', '진료실 보조', '확정'
    UNION ALL SELECT CURDATE(), '오후', '원무', '박지현', '14:00:00', '18:00:00', '예약/마감', '확정'
    UNION ALL SELECT CURDATE(), '오후', '간호', '최민호', '14:00:00', '18:00:00', '처치실', '확정'
) seed
WHERE NOT EXISTS (SELECT 1 FROM hospital_shift_slots);

INSERT INTO hospital_daily_checks (check_date, shift_type, item_name, category, is_done, checked_by, checked_at, note)
SELECT * FROM (
    SELECT CURDATE(), '오픈', '진료실 소독 상태 확인', '감염관리', 1, '김대표', NOW(), ''
    UNION ALL SELECT CURDATE(), '오픈', '냉장 보관 의약품 온도 확인', '약품관리', 0, NULL, NULL, '2~8도 유지'
    UNION ALL SELECT CURDATE(), '마감', '카드 단말기 매출 마감', '수납마감', 0, NULL, NULL, ''
    UNION ALL SELECT CURDATE(), '마감', '의료폐기물 보관함 잠금 확인', '시설점검', 0, NULL, NULL, ''
) seed
WHERE NOT EXISTS (SELECT 1 FROM hospital_daily_checks);

INSERT INTO hospital_cash_closings (closing_date, cash_amount, card_amount, transfer_amount, refund_amount, unpaid_amount, patient_count, memo, status, closed_by)
SELECT CURDATE(), 180000, 1280000, 240000, 30000, 50000, 32, '시범 마감 데이터', '작성중', '김대표'
WHERE NOT EXISTS (SELECT 1 FROM hospital_cash_closings);

INSERT INTO hospital_assets (asset_type, name, category, current_qty, min_qty, unit, expire_date, location, vendor, next_due_date, status, memo)
SELECT asset_type, name, category, current_qty, min_qty, unit, expire_date, location, vendor, next_due_date, status, memo FROM (
    SELECT '재고' AS asset_type, '니트릴 장갑' AS name, '소모품' AS category, 12 AS current_qty, 20 AS min_qty, '박스' AS unit, NULL AS expire_date, '처치실' AS location, '메디서플라이' AS vendor, NULL AS next_due_date, '부족' AS status, '최소재고 미달' AS memo
    UNION ALL SELECT '재고', '소독용 알코올', '소독/위생', 8, 5, '병', DATE_ADD(CURDATE(), INTERVAL 75 DAY), '진료실', '메디서플라이', NULL, '정상', ''
    UNION ALL SELECT '장비', '멸균기 A-01', '의료장비', NULL, NULL, NULL, NULL, '소독실', '케어테크', DATE_ADD(CURDATE(), INTERVAL 20 DAY), '점검예정', '분기 점검 예정'
    UNION ALL SELECT '장비', '카드단말기', '수납장비', NULL, NULL, NULL, NULL, '접수대', '페이먼트코리아', DATE_ADD(CURDATE(), INTERVAL 180 DAY), '정상', ''
) seed
WHERE NOT EXISTS (SELECT 1 FROM hospital_assets);

INSERT INTO hospital_staff_credentials (employee_name, credential_type, credential_name, issue_date, expire_date, status, memo)
SELECT employee_name, credential_type, credential_name, issue_date, expire_date, status, memo FROM (
    SELECT '이정민' AS employee_name, '면허/자격' AS credential_type, '간호조무사 자격' AS credential_name, '2022-03-01' AS issue_date, NULL AS expire_date, '유효' AS status, '' AS memo
    UNION ALL SELECT '박지현', '법정교육', '개인정보보호교육', '2026-01-15', '2027-01-14', '유효', '연 1회'
    UNION ALL SELECT '최민호', '법정교육', '감염관리교육', '2025-06-10', DATE_ADD(CURDATE(), INTERVAL 25 DAY), '만료예정', '갱신 필요'
) seed
WHERE NOT EXISTS (SELECT 1 FROM hospital_staff_credentials);

INSERT INTO hospital_leave_requests (employee_name, leave_type, start_date, end_date, substitute_name, reason, status)
SELECT '박지현', '반차', DATE_ADD(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), '김대표', '오후 개인 일정', '신청'
WHERE NOT EXISTS (SELECT 1 FROM hospital_leave_requests);

INSERT INTO hospital_purchase_requests (asset_id, item_name, requested_qty, unit, requester_name, vendor, reason, status)
SELECT id, name, GREATEST(min_qty - current_qty, 1), unit, '김대표', vendor, '최소재고 미달 자동 발주 후보', '요청'
FROM hospital_assets
WHERE asset_type = '재고'
  AND current_qty IS NOT NULL
  AND min_qty IS NOT NULL
  AND current_qty <= min_qty
  AND NOT EXISTS (SELECT 1 FROM hospital_purchase_requests);
