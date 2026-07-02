<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getDBConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    // 1. payroll_pay_types 테이블
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS payroll_pay_types (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            code        VARCHAR(20)  NOT NULL UNIQUE,
            name        VARCHAR(50)  NOT NULL,
            category    ENUM('pay','deduct') NOT NULL,
            is_taxable  TINYINT(1)   NOT NULL DEFAULT 1,
            calc_type   ENUM('manual','rate') NOT NULL DEFAULT 'manual',
            calc_base   ENUM('base_salary','gross_pay') NULL,
            calc_rate   DECIMAL(10,6) NULL,
            has_hours   TINYINT(1)   NOT NULL DEFAULT 0,
            sort_order  INT          NOT NULL DEFAULT 0,
            is_active   TINYINT(1)   NOT NULL DEFAULT 1,
            is_system   TINYINT(1)   NOT NULL DEFAULT 0,
            created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "1. payroll_pay_types table OK\n";

    // 2. 지급 항목 시드
    $pdo->exec("
        INSERT INTO payroll_pay_types (code, name, category, is_taxable, calc_type, calc_base, calc_rate, has_hours, sort_order, is_system) VALUES
        ('BASE',  '기본급',     'pay',    1, 'manual', NULL,          NULL,     0,  10, 1),
        ('MEAL',  '식대',       'pay',    0, 'manual', NULL,          NULL,     0,  20, 1),
        ('CAR',   '차량지원',   'pay',    0, 'manual', NULL,          NULL,     0,  30, 0),
        ('CHILD', '육아수당',   'pay',    0, 'manual', NULL,          NULL,     0,  40, 0),
        ('OT',    '초과수당',   'pay',    1, 'manual', NULL,          NULL,     1,  50, 1)
        ON DUPLICATE KEY UPDATE name = VALUES(name)
    ");
    echo "2. pay types seed OK\n";

    // 3. 공제 항목 시드
    $pdo->exec("
        INSERT INTO payroll_pay_types (code, name, category, is_taxable, calc_type, calc_base, calc_rate, has_hours, sort_order, is_system) VALUES
        ('NP',    '국민연금',     'deduct', 0, 'rate', 'base_salary', 0.045000, 0, 110, 1),
        ('HI',    '건강보험',     'deduct', 0, 'rate', 'base_salary', 0.035450, 0, 120, 1),
        ('EI',    '고용보험',     'deduct', 0, 'rate', 'base_salary', 0.009000, 0, 130, 1),
        ('IT',    '소득세',       'deduct', 0, 'rate', 'gross_pay',   0.030000, 0, 140, 1),
        ('LT',    '지방소득세',   'deduct', 0, 'rate', 'gross_pay',   0.003000, 0, 150, 1)
        ON DUPLICATE KEY UPDATE name = VALUES(name)
    ");
    echo "3. deduct types seed OK\n";

    // 4. payslip_items 테이블
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS payslip_items (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            payslip_id  INT UNSIGNED NOT NULL,
            pay_type_id INT UNSIGNED NOT NULL,
            amount      BIGINT       NOT NULL DEFAULT 0,
            hours       DECIMAL(5,1) NULL,
            UNIQUE KEY uniq_payslip_type (payslip_id, pay_type_id),
            FOREIGN KEY (payslip_id) REFERENCES payslips(id) ON DELETE CASCADE,
            FOREIGN KEY (pay_type_id) REFERENCES payroll_pay_types(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "4. payslip_items table OK\n";

    // 5. 기존 payslips 데이터 이관
    $migrated = $pdo->exec("
        INSERT IGNORE INTO payslip_items (payslip_id, pay_type_id, amount, hours)
        SELECT p.id, pt.id,
            CASE pt.code
                WHEN 'BASE'  THEN p.base_salary
                WHEN 'MEAL'  THEN p.meal_allowance
                WHEN 'CAR'   THEN p.car_allowance
                WHEN 'CHILD' THEN p.child_allowance
                WHEN 'OT'    THEN p.overtime_pay
                WHEN 'NP'    THEN p.national_pension
                WHEN 'HI'    THEN p.health_insurance
                WHEN 'EI'    THEN p.emp_insurance
                WHEN 'IT'    THEN p.income_tax
                WHEN 'LT'    THEN 0
            END AS amount,
            CASE pt.code
                WHEN 'OT' THEN p.overtime_hours
                ELSE NULL
            END AS hours
        FROM payslips p
        CROSS JOIN payroll_pay_types pt
        WHERE p.id NOT IN (SELECT DISTINCT payslip_id FROM payslip_items)
    ");
    echo "5. migrated {$migrated} rows\n";

    // 결과 확인
    $ptCount = $pdo->query("SELECT COUNT(*) FROM payroll_pay_types")->fetchColumn();
    $siCount = $pdo->query("SELECT COUNT(*) FROM payslip_items")->fetchColumn();
    echo "\npayroll_pay_types: {$ptCount} rows\n";
    echo "payslip_items: {$siCount} rows\n";

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
