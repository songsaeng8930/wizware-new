<?php
/**
 * 거래 분류 학습 루프 (1층) · classification_patterns / classification_history 를 실제로 구동한다.
 *
 * 설계 의도(가드레일 준수):
 *  - 계정 "분류 제안" 메타만 다룬다(account_code/name/ai_confidence/is_confirmed + 패턴/이력 테이블).
 *    세율·공제·세액 등 세무·금액 계산 로직은 절대 손대지 않는다.
 *  - 사람이 확정할 때마다 규칙(pattern)을 만들거나 강화한다 → 다음엔 자동분류.
 *    맞으면 hit_count↑·confidence↑, 틀리면 miss_count↑·confidence↓. (결정적·투명, LLM 불필요)
 *  - 최종 확정은 항상 사람. 여기서 만드는 건 "제안"의 근거가 되는 학습 규칙이다.
 */

require_once __DIR__ . '/classify.php';

/** 학습/매칭에 쓸 핵심 키워드 추출 · 거래처 우선, 없으면 적요에서 의미있는 토큰 */
function bank_learn_extract_key(string $description, ?string $counterparty = null): string
{
    $cp = trim((string)$counterparty);
    if ($cp !== '' && mb_strlen($cp) >= 2) {
        // 거래처는 뒤쪽 접미(주식회사/(주)/지점 등)·공백 정리 후 앞부분 사용
        $cp = preg_replace('/\s*(주식회사|㈜|\(주\)|지점|\d+호점?)\s*/u', ' ', $cp);
        $cp = trim(preg_replace('/\s+/u', ' ', $cp));
        if ($cp !== '') return mb_substr($cp, 0, 40);
    }
    // 적요에서 토큰화 · 숫자/기호/짧은 조사 제거 후 가장 긴 토큰
    // 범용 은행용어는 제외 — 이런 걸 키워드로 배우면 아무 거래나 잡는 광범위 규칙이 생긴다
    static $stopwords = [
        '입금', '출금', '이체', '자동이체', '계좌이체', '인터넷뱅킹', '모바일뱅킹',
        '텔레뱅킹', '폰뱅킹', '카드결제', '체크카드', '결제', '송금', '납부', '지급',
        '수납', '대금', '거래', '타행', '당행', '창구', '전자금융', '온라인',
    ];
    $desc = preg_replace('/[0-9\-\_\*\/\.\,\(\)\[\]]+/u', ' ', (string)$description);
    $tokens = preg_split('/\s+/u', trim($desc), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $best = '';
    foreach ($tokens as $t) {
        if (mb_strlen($t) < 2) continue;
        if (in_array($t, $stopwords, true)) continue;
        if (mb_strlen($t) > mb_strlen($best)) $best = $t;
    }
    return mb_substr($best, 0, 40);
}

/** 반복 주기일 근접 판정 · 월말/월초 넘어가는 차이(예: 31일↔2일)도 처리 */
function bank_learn_day_near(int $day, int $recurrenceDay, int $tolerance = 3): bool
{
    $diff = abs($day - $recurrenceDay);
    return min($diff, 31 - $diff) <= $tolerance;
}

/**
 * 학습된 규칙으로 분류 시도. classification_patterns 를 읽어 최적 매치 반환.
 *  - 키워드/거래처 패턴: 적요·거래처 텍스트 매칭 (구체적일수록 우선)
 *  - 금액·주기 패턴(keyword=''): 적요가 범용어뿐이라 텍스트 근거가 없을 때,
 *    "매달 비슷한 날 같은 금액" 신호로 매칭 (예: 매달 25일 350만원 = 임대료)
 * @return array|null ['code','name','confidence','pattern_id','source'=>'learned'] 또는 null
 */
function bank_pattern_classify(PDO $pdo, string $description, string $txType, ?string $counterparty = null, ?int $amount = null, ?string $txDate = null): ?array
{
    $hay = mb_strtolower(trim($description . ' ' . (string)$counterparty));

    try {
        $st = $pdo->prepare(
            "SELECT id, keyword, account_code, account_name, counterparty, amount_min, amount_max,
                    recurrence, recurrence_day, priority, confidence, hit_count, miss_count
             FROM classification_patterns
             WHERE is_active = 1 AND (tx_type = ? OR tx_type IS NULL OR tx_type = '')
             ORDER BY priority DESC, confidence DESC, hit_count DESC"
        );
        $st->execute([$txType]);
    } catch (Throwable $e) {
        return null; // 패턴 테이블 없거나 조회 실패 → 학습 미적용
    }

    $txDay = ($txDate && ($ts = strtotime($txDate)) !== false) ? (int)date('j', $ts) : null;

    $best = null; $bestScore = -1;
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $p) {
        $kw = mb_strtolower(trim((string)$p['keyword']));
        $cp = mb_strtolower(trim((string)$p['counterparty']));
        $score = -1;

        if ($kw !== '' || $cp !== '') {
            // 텍스트 패턴
            $matched = ($hay !== '') && (
                ($kw !== '' && mb_strpos($hay, $kw) !== false)
                || ($cp !== '' && mb_strpos($hay, $cp) !== false)
            );
            if (!$matched) continue;
            $hit = (int)$p['hit_count']; $miss = (int)$p['miss_count'];
            $reliability = ($hit + $miss) > 0 ? $hit / ($hit + $miss) : 0.5;
            // 점수 = 신뢰도 × 적중률 × 키워드 길이(구체적일수록 우선)
            $score = (int)$p['confidence'] * $reliability * (1 + mb_strlen($kw) / 20);
        } elseif ($p['amount_min'] !== null && $amount !== null && $amount > 0) {
            // 금액·주기 패턴 (텍스트 근거 없는 반복 거래용)
            if ($amount < (int)$p['amount_min'] || $amount > (int)$p['amount_max']) continue;
            $recDay = $p['recurrence_day'] !== null ? (int)$p['recurrence_day'] : null;
            if ($recDay !== null && $txDay !== null && !bank_learn_day_near($txDay, $recDay)) continue;
            $hit = (int)$p['hit_count']; $miss = (int)$p['miss_count'];
            $reliability = ($hit + $miss) > 0 ? $hit / ($hit + $miss) : 0.5;
            // 주기일까지 맞으면 가산 · 금액만 맞으면 텍스트 패턴보다 낮게
            $dayBonus = ($recDay !== null && $txDay !== null) ? 1.1 : 0.9;
            $score = (int)$p['confidence'] * $reliability * $dayBonus;
        } else {
            continue;
        }

        if ($score > $bestScore) {
            $bestScore = $score;
            $best = [
                'code'       => $p['account_code'],
                'name'       => $p['account_name'],
                'confidence' => (int)$p['confidence'],
                'pattern_id' => (int)$p['id'],
                'source'     => 'learned',
            ];
        }
    }
    return $best;
}

/**
 * 통합 분류기 · 학습규칙 우선 → 정적규칙 폴백. (RAG는 API 오케스트레이션에서 저신뢰건에 추가)
 * @return array ['code','name','confidence','source','pattern_id'?]
 */
function bank_classify_smart(PDO $pdo, string $description, string $txType, ?string $counterparty = null, ?int $amount = null, ?string $txDate = null): array
{
    $learned = bank_pattern_classify($pdo, $description, $txType, $counterparty, $amount, $txDate);
    if ($learned && $learned['confidence'] >= 60) {
        return $learned;
    }
    $static = bank_classify_one($description, $txType);
    // 학습규칙이 있으나 저신뢰면, 정적규칙과 비교해 높은 쪽
    if ($learned && $learned['confidence'] >= (int)$static['confidence']) {
        return $learned;
    }
    $static['source'] = 'rule';
    return $static;
}

/**
 * 사람 확정으로부터 학습 · 패턴 UPSERT + 오분류 감점 + 이력 기록.
 * @param array $tx  ['id','description','tx_type','counterparty'?,'account_code'(이전 제안)?]
 */
function bank_learn_from_confirmation(PDO $pdo, array $tx, string $code, string $name, string $actor = 'user'): void
{
    $txType = (string)($tx['tx_type'] ?? '');
    $desc   = (string)($tx['description'] ?? '');
    $cp     = $tx['counterparty'] ?? null;
    $oldCode = trim((string)($tx['account_code'] ?? ''));
    $amount = isset($tx['amount']) ? (int)$tx['amount'] : 0;
    $txDate = (string)($tx['transaction_date'] ?? '');
    $keyword = bank_learn_extract_key($desc, $cp);

    // 텍스트 근거가 없으면(범용어뿐) 금액·주기 패턴으로 학습 시도
    if ($keyword === '') {
        if ($amount > 0) {
            bank_learn_amount_pattern($pdo, $tx, $code, $name, $actor);
        }
        return;
    }

    // 1) 사람이 이전 AI제안과 다른 코드로 바꿨으면, 잘못 예측했던 패턴 감점
    if ($oldCode !== '' && $oldCode !== $code) {
        // 텍스트 패턴 · keyword='' (금액패턴)는 LIKE '%%' 전체매칭이 되므로 반드시 제외
        $mis = $pdo->prepare(
            "UPDATE classification_patterns
             SET miss_count = miss_count + 1,
                 confidence = GREATEST(30, confidence - 10),
                 updated_at = NOW()
             WHERE is_active = 1 AND account_code = ? AND keyword <> ''
               AND (tx_type = ? OR tx_type IS NULL OR tx_type = '')
               AND ? LIKE CONCAT('%', keyword, '%')"
        );
        $mis->execute([$oldCode, $txType, mb_strtolower($desc . ' ' . (string)$cp)]);
        // 금액 패턴 · 이 거래 금액에 걸렸던 오답 패턴 감점
        if ($amount > 0) {
            $misAmt = $pdo->prepare(
                "UPDATE classification_patterns
                 SET miss_count = miss_count + 1,
                     confidence = GREATEST(30, confidence - 10),
                     updated_at = NOW()
                 WHERE is_active = 1 AND account_code = ? AND keyword = ''
                   AND (tx_type = ? OR tx_type IS NULL OR tx_type = '')
                   AND amount_min IS NOT NULL AND ? BETWEEN amount_min AND amount_max"
            );
            $misAmt->execute([$oldCode, $txType, $amount]);
        }
    }

    // 2) 정답 코드 패턴 UPSERT (동일 keyword+tx_type+code 있으면 강화, 없으면 신규)
    $find = $pdo->prepare(
        "SELECT id, confidence, hit_count FROM classification_patterns
         WHERE keyword = ? AND account_code = ? AND (tx_type = ? OR tx_type IS NULL OR tx_type = '')
         LIMIT 1"
    );
    $find->execute([$keyword, $code, $txType]);
    $row = $find->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $patternId = (int)$row['id'];
        $pdo->prepare(
            "UPDATE classification_patterns
             SET hit_count = hit_count + 1,
                 confidence = LEAST(98, confidence + 5),
                 account_name = ?, is_active = 1, updated_at = NOW()
             WHERE id = ?"
        )->execute([$name, $patternId]);
    } else {
        $ins = $pdo->prepare(
            "INSERT INTO classification_patterns
                (keyword, tx_type, account_code, account_name, counterparty,
                 priority, confidence, hit_count, miss_count, source, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, 10, 75, 1, 0, 'user', 1, NOW(), NOW())"
        );
        $cpKey = (trim((string)$cp) !== '') ? mb_substr(trim((string)$cp), 0, 100) : null;
        $ins->execute([$keyword, $txType, $code, $name, $cpKey]);
        $patternId = (int)$pdo->lastInsertId();
    }

    // 3) 이력 기록 (감사 · 되돌리기 근거)
    try {
        $action = ($oldCode !== '' && $oldCode !== $code) ? 'modify' : 'confirm';
        $pdo->prepare(
            "INSERT INTO classification_history
                (transaction_id, old_account_code, new_account_code, new_account_name, action, pattern_id, actor, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
        )->execute([
            (int)($tx['id'] ?? 0), ($oldCode !== '' ? $oldCode : null), $code, $name, $action, $patternId, $actor
        ]);
    } catch (Throwable $e) {
        error_log('[bank_learn] history 기록 실패: ' . $e->getMessage());
    }
}

/**
 * 금액·주기 패턴 학습 · 적요가 범용어뿐이라 텍스트 근거가 없는 반복 거래용.
 * "같은 금액이 매달 비슷한 날" 신호를 규칙으로 저장한다 (keyword='' 행).
 */
function bank_learn_amount_pattern(PDO $pdo, array $tx, string $code, string $name, string $actor = 'user'): void
{
    $txType = (string)($tx['tx_type'] ?? '');
    $amount = (int)($tx['amount'] ?? 0);
    if ($amount <= 0) return;
    $txDate = (string)($tx['transaction_date'] ?? '');
    $day = ($txDate && ($ts = strtotime($txDate)) !== false) ? (int)date('j', $ts) : null;

    // 사람이 이전 제안과 다른 코드로 정정했으면, 이 금액대에 걸렸던 오답 금액패턴 감점
    $oldCode = trim((string)($tx['account_code'] ?? ''));
    if ($oldCode !== '' && $oldCode !== $code) {
        $mis = $pdo->prepare(
            "UPDATE classification_patterns
             SET miss_count = miss_count + 1,
                 confidence = GREATEST(30, confidence - 10),
                 updated_at = NOW()
             WHERE is_active = 1 AND account_code = ? AND keyword = ''
               AND (tx_type = ? OR tx_type IS NULL OR tx_type = '')
               AND amount_min IS NOT NULL AND ? BETWEEN amount_min AND amount_max"
        );
        $mis->execute([$oldCode, $txType, $amount]);
    }

    // 동일 금액대(±2%) 기존 패턴 있으면 강화, 없으면 신규
    $find = $pdo->prepare(
        "SELECT id FROM classification_patterns
         WHERE keyword = '' AND account_code = ?
           AND (tx_type = ? OR tx_type IS NULL OR tx_type = '')
           AND amount_min IS NOT NULL AND ? BETWEEN amount_min AND amount_max
         LIMIT 1"
    );
    $find->execute([$code, $txType, $amount]);
    $row = $find->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $patternId = (int)$row['id'];
        $pdo->prepare(
            "UPDATE classification_patterns
             SET hit_count = hit_count + 1,
                 confidence = LEAST(98, confidence + 5),
                 account_name = ?, is_active = 1, updated_at = NOW()
             WHERE id = ?"
        )->execute([$name, $patternId]);
    } else {
        // 금액 허용폭 ±2% (자동이체 금액 미세 변동 대응) · 시작 신뢰도는 텍스트 패턴보다 낮게
        $min = (int)floor($amount * 0.98);
        $max = (int)ceil($amount * 1.02);
        $ins = $pdo->prepare(
            "INSERT INTO classification_patterns
                (keyword, tx_type, account_code, account_name, counterparty,
                 amount_min, amount_max, recurrence, recurrence_day,
                 priority, confidence, hit_count, miss_count, source, is_active, created_at, updated_at)
             VALUES ('', ?, ?, ?, NULL, ?, ?, ?, ?, 5, 65, 1, 0, 'user', 1, NOW(), NOW())"
        );
        $ins->execute([$txType, $code, $name, $min, $max, $day !== null ? 'monthly' : 'none', $day]);
        $patternId = (int)$pdo->lastInsertId();
    }

    try {
        $pdo->prepare(
            "INSERT INTO classification_history
                (transaction_id, old_account_code, new_account_code, new_account_name, action, pattern_id, actor, memo, created_at)
             VALUES (?, ?, ?, ?, 'confirm', ?, ?, '금액·주기 패턴 학습', NOW())"
        )->execute([
            (int)($tx['id'] ?? 0),
            (trim((string)($tx['account_code'] ?? '')) !== '' ? $tx['account_code'] : null),
            $code, $name, $patternId, $actor
        ]);
    } catch (Throwable $e) {
        error_log('[bank_learn] amount history 기록 실패: ' . $e->getMessage());
    }
}
