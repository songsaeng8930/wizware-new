<?php
/**
 * 샌드박스(목) 은행 제공자 · bankapi.co.kr 자격증명 없이도 계좌연동 전 과정을
 * 로컬에서 end-to-end 동작시키기 위한 가짜 응답 생성기.
 *
 * - 실제 외부 호출 0건. 결정적(accountNumber+기간 seed)으로 같은 입력엔 같은 거래내역 반환
 *   → 동기화를 여러 번 돌려도 멱등(중복 적재 안 됨).
 * - 응답 형태/필드는 bankapi.co.kr 및 기존 소비자(pages/acct_bank_history.php)에 맞춘다:
 *     transactions[] = { transactionDate:'YYYYMMDD', transactionTime:'HHMMSS',
 *                        description, transactionType:'입금'|'출금', amount, balance }
 * - 실키가 생기면 provider.php 가 자동으로 real 로 승격 → 이 파일은 호출되지 않음.
 */

/** 목 거래 라이브러리 · 다양한 계정과목 규칙에 매칭되도록 구성(분류 엔진 검증 겸용). */
function bank_mock_tx_library(): array
{
    return [
        // [적요, 구분, 금액]
        ['KT 통신비 자동이체',          '출금', 121000],
        ['(주)강남타워 임대료',         '출금', 1650000],
        ['네이버 검색광고 결제',        '출금', 770000],
        ['삼성화재 화재보험료',         '출금', 88000],
        ['3월 급여 이체',               '출금', 8200000],
        ['부가가치세 납부',             '출금', 1430000],
        ['GS25 편의점 간식',            '출금', 23400],
        ['쿠팡 사무용품 구매',          '출금', 156000],
        ['스타벅스 강남점',             '출금', 38500],
        ['프리랜서 개발 용역비',        '출금', 2200000],
        ['GS칼텍스 주유',               '출금', 95000],
        ['하이패스 통행료',             '출금', 12800],
        ['온라인 세무교육 수강',        '출금', 240000],
        ['카카오 택시',                 '출금', 18600],
        ['법인차량 정비',               '출금', 320000],
        ['4대보험 납부',                '출금', 540000],
        ['(주)ABC 용역대금 입금',       '입금', 5500000],
        ['제품 판매대금 수금',          '입금', 3200000],
        ['서비스 이용료 입금',          '입금', 1800000],
        ['예금이자',                    '입금', 23400],
        ['상가 임대료 입금',            '입금', 1200000],
        ['결제대금 입금',               '입금', 880000],
    ];
}

/** YYYYMMDD → unix ts(정오) */
function bank_mock_ymd_to_ts(string $ymd): int
{
    $ymd = preg_replace('/[^0-9]/', '', $ymd);
    if (strlen($ymd) !== 8) return time();
    return mktime(12, 0, 0, (int)substr($ymd, 4, 2), (int)substr($ymd, 6, 2), (int)substr($ymd, 0, 4));
}

/** 결정적 거래내역 생성 (accountNumber+기간 seed) */
function bank_mock_transactions(string $accountNumber, string $startDate, string $endDate): array
{
    $lib   = bank_mock_tx_library();
    $startTs = bank_mock_ymd_to_ts($startDate ?: date('Ymd', strtotime('-1 month')));
    $endTs   = bank_mock_ymd_to_ts($endDate ?: date('Ymd'));
    if ($endTs < $startTs) { [$startTs, $endTs] = [$endTs, $startTs]; }
    $span = max(1, (int)floor(($endTs - $startTs) / 86400)); // 일수

    $seed = crc32($accountNumber . $startDate . $endDate);
    // 라이브러리에서 seed 기반으로 18~22건 선택(결정적)
    $n = 18 + ($seed % 5);
    $picked = [];
    for ($i = 0; $i < $n; $i++) {
        $idx = ($seed + $i * 7) % count($lib);
        [$desc, $type, $amount] = $lib[$idx];
        // 날짜를 기간에 고르게 분포 + seed 흔들기
        $dayOffset = (int)floor(($i + ($seed % 3)) * $span / max(1, $n));
        $dayOffset = min($span, $dayOffset);
        $ts = $startTs + $dayOffset * 86400;
        $picked[] = [
            'ts'     => $ts,
            'date'   => date('Ymd', $ts),
            'time'   => sprintf('%02d%02d%02d', 9 + ($i % 9), ($seed + $i * 13) % 60, ($i * 17) % 60),
            'desc'   => $desc,
            'type'   => $type,
            'amount' => $amount,
        ];
    }
    // 시간순 정렬 후 잔액 누적
    usort($picked, fn($a, $b) => $a['ts'] <=> $b['ts'] ?: strcmp($a['time'], $b['time']));
    $balance = 50000000 + ($seed % 30000000); // 시작 잔액(결정적) · 출금 누적에도 음수가 안 되게 충분히 크게
    $out = [];
    foreach ($picked as $p) {
        $balance += ($p['type'] === '입금') ? $p['amount'] : -$p['amount'];
        $out[] = [
            'transactionDate' => $p['date'],
            'transactionTime' => $p['time'],
            'description'     => $p['desc'],
            'transactionType' => $p['type'],
            'amount'          => $p['amount'],
            'balance'         => $balance,
        ];
    }
    // 최신순으로 보여주기(소비자 기대와 동일)
    return array_reverse($out);
}

/**
 * 목 제공자 디스패처 · bankapi_request() 와 동일한 반환 형태 ['ok','status','data'].
 */
function bank_mock_request(string $method, string $path, array $data = [], array $query = []): array
{
    $method = strtoupper($method);

    // 거래내역 조회
    if ($path === '/v1/transactions' && $method === 'POST') {
        $acct  = preg_replace('/[^0-9]/', '', $data['accountNumber'] ?? '');
        $txs   = bank_mock_transactions($acct, $data['startDate'] ?? '', $data['endDate'] ?? '');
        return ['ok' => true, 'status' => 200, 'data' => ['transactions' => $txs, '_sandbox' => true]];
    }

    // 계좌 등록 / 확인 / 삭제 / 목록
    if ($path === '/v1/accounts/check' && $method === 'POST') {
        return ['ok' => true, 'status' => 200, 'data' => ['registered' => true, '_sandbox' => true]];
    }
    if ($path === '/v1/accounts' && $method === 'POST') {
        return ['ok' => true, 'status' => 200, 'data' => [
            'registered'    => true,
            'bankCode'      => $data['bankCode'] ?? '',
            'accountNumber' => $data['accountNumber'] ?? '',
            '_sandbox'      => true,
        ]];
    }
    if ($path === '/v1/accounts' && $method === 'DELETE') {
        return ['ok' => true, 'status' => 200, 'data' => ['deleted' => true, '_sandbox' => true]];
    }
    if ($path === '/v1/accounts' && $method === 'GET') {
        // 등록된 계좌 목록은 DB(bank_accounts)에서 · 샌드박스도 DB를 단일 출처로.
        $accounts = [];
        try {
            require_once __DIR__ . '/../../config/database.php';
            $pdo = getDBConnection();
            if ($pdo) {
                $accounts = $pdo->query("SELECT bank_name, account_no, account_alias FROM bank_accounts WHERE is_active = 1")
                                ->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }
        } catch (Throwable $e) { error_log('[bank_mock] list_accounts: ' . $e->getMessage()); }
        return ['ok' => true, 'status' => 200, 'data' => $accounts];
    }

    return ['ok' => false, 'status' => 404, 'data' => ['error' => '샌드박스 미지원 경로: ' . $method . ' ' . $path]];
}
