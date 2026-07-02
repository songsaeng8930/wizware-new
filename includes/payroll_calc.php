<?php
/**
 * 4대보험+소득세 공제 계산 (공유 헬퍼)
 * 요율 소스: payroll_pay_types 테이블 (deduct 카테고리의 calc_rate).
 * 테이블 없으면 아래 상수로 폴백.
 * 프로덕션 사용 전 공인노무사/세무사 확인 필수.
 */

// 2026년 4대보험 근로자 부담 요율 (DB 미연결 시 폴백)
const PAYROLL_RATE_PENSION  = 0.0475;
const PAYROLL_RATE_HEALTH   = 0.03595;
const PAYROLL_RATE_LONGCARE = 0.004724;
const PAYROLL_RATE_EMPLOY   = 0.009;
const PAYROLL_RATE_TAX      = 0.033;

function getPayrollRates(PDO $pdo, int $year = 0): array {
    try {
        $stmt = $pdo->query("SELECT code, calc_rate FROM payroll_pay_types WHERE category = 'deduct' AND calc_type = 'rate' AND is_active = 1");
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        if (!empty($rows)) {
            $taxTotal = (float)($rows['IT'] ?? 0.03) + (float)($rows['LT'] ?? 0.003);
            return [
                'pension'  => (float)($rows['NP'] ?? PAYROLL_RATE_PENSION),
                'health'   => (float)($rows['HI'] ?? PAYROLL_RATE_HEALTH),
                'longcare' => (float)($rows['LC'] ?? PAYROLL_RATE_LONGCARE),
                'employ'   => (float)($rows['EI'] ?? PAYROLL_RATE_EMPLOY),
                'tax'      => $taxTotal,
            ];
        }
    } catch (\PDOException $e) {
        if ($e->getCode() !== '42S02') {
            error_log('payroll_calc: rates query failed: ' . $e->getMessage());
        }
    }
    return [
        'pension'  => PAYROLL_RATE_PENSION,
        'health'   => PAYROLL_RATE_HEALTH,
        'longcare' => PAYROLL_RATE_LONGCARE,
        'employ'   => PAYROLL_RATE_EMPLOY,
        'tax'      => PAYROLL_RATE_TAX,
    ];
}

function getPayrollRatesFull(PDO $pdo): array {
    try {
        $stmt = $pdo->query("SELECT id, code, name, calc_rate, calc_base FROM payroll_pay_types WHERE category = 'deduct' AND calc_type = 'rate' AND is_active = 1 ORDER BY sort_order");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
        if ($e->getCode() !== '42S02') {
            error_log('payroll_calc: getPayrollRatesFull failed: ' . $e->getMessage());
        }
    }
    return [];
}

function calcPayrollDeductions(int $baseSalary, int $grossPay, ?array $rates = null): array {
    $r = $rates ?? [
        'pension'  => PAYROLL_RATE_PENSION,
        'health'   => PAYROLL_RATE_HEALTH,
        'longcare' => PAYROLL_RATE_LONGCARE,
        'employ'   => PAYROLL_RATE_EMPLOY,
        'tax'      => PAYROLL_RATE_TAX,
    ];
    $pension  = (int)round($baseSalary * $r['pension']);
    $health   = (int)round($baseSalary * $r['health']);
    $longcare = (int)round($baseSalary * ($r['longcare'] ?? PAYROLL_RATE_LONGCARE));
    $employ   = (int)round($baseSalary * $r['employ']);
    $tax      = (int)round($grossPay * $r['tax']);
    $total    = $pension + $health + $longcare + $employ + $tax;
    return compact('pension', 'health', 'longcare', 'employ', 'tax', 'total');
}

function getPayTypes(PDO $pdo): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    try {
        $stmt = $pdo->query("SELECT * FROM payroll_pay_types WHERE is_active = 1 ORDER BY sort_order");
        $cache = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
        error_log('payroll_calc: getPayTypes failed: ' . $e->getMessage());
        $cache = [];
    }
    return $cache;
}

function calcDeductionsFromTypes(array $deductTypes, int $baseSalary, int $grossPay): array {
    $result = [];
    $total = 0;
    foreach ($deductTypes as $dt) {
        if ($dt['calc_type'] !== 'rate' || !$dt['calc_rate']) continue;
        $base = ($dt['calc_base'] === 'gross_pay') ? $grossPay : $baseSalary;
        $amount = (int)round($base * (float)$dt['calc_rate']);
        $result[] = ['pay_type_id' => (int)$dt['id'], 'code' => $dt['code'], 'amount' => $amount];
        $total += $amount;
    }
    return ['items' => $result, 'total' => $total];
}
