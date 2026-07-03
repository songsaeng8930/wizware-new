<?php
/**
 * RAG 분류 제안 (2층) · 학습규칙/정적규칙에도 안 걸리는 신규·모호 거래를 위한 검색기반 제안.
 *
 * 동작 (LLM 없이도 유효):
 *  1) Retrieval — 과거 "사람이 확정한(is_confirmed=1)" 거래 중 이번 거래와 유사한 것들을 찾음
 *  2) Aggregate — 유사 사례들의 account_code 다수결(유사도 가중) → 제안 + 신뢰도
 *  3) (선택) LLM re-rank — provider 가 설정돼 있으면 검색 사례를 컨텍스트로 LLM 이 최종 판단(=RAG).
 *     provider 미설정이면 검색결과만으로 제안(graceful). 세무 데이터라 기본은 로컬/미사용.
 *
 * 가드레일: 제안(is_confirmed=0)만 생성. 사람이 확정해야 학습으로 굳는다. 세무·금액 계산 불변.
 */

/** 토큰화 · 숫자/기호 제거, 2자 이상 토큰 집합 */
function bank_rag_tokens(string $text): array
{
    $t = mb_strtolower(preg_replace('/[0-9\-\_\*\/\.\,\(\)\[\]]+/u', ' ', $text));
    $toks = preg_split('/\s+/u', trim($t), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $out = [];
    foreach ($toks as $w) { if (mb_strlen($w) >= 2) $out[$w] = true; }
    return array_keys($out);
}

/** 자카드 유사도 */
function bank_rag_similarity(array $a, array $b): float
{
    if (!$a || !$b) return 0.0;
    $sa = array_flip($a);
    $inter = 0;
    foreach ($b as $w) if (isset($sa[$w])) $inter++;
    $union = count($a) + count($b) - $inter;
    return $union > 0 ? $inter / $union : 0.0;
}

/**
 * 검색기반 제안 · 과거 확정 거래에서 유사 사례 다수결.
 * @return array|null ['code','name','confidence','source'=>'rag','evidence'=>[...]] 또는 null
 */
function bank_rag_suggest(PDO $pdo, array $tx, int $topK = 5): ?array
{
    $desc = (string)($tx['description'] ?? '');
    $cp   = (string)($tx['counterparty'] ?? '');
    $txType = (string)($tx['tx_type'] ?? '');
    $queryTokens = bank_rag_tokens($desc . ' ' . $cp);
    if (!$queryTokens) return null;

    // 후보(확정 거래)는 요청 단위 캐시 · 재분류 루프에서 거래마다 500행 재조회/재토큰화 방지
    static $candidateCache = [];
    if (!isset($candidateCache[$txType])) {
        try {
            $st = $pdo->prepare(
                "SELECT id, description, counterparty, account_code, account_name
                 FROM bank_transactions
                 WHERE is_confirmed = 1 AND account_code IS NOT NULL AND account_code <> ''
                   AND tx_type = ?
                 ORDER BY transaction_date DESC
                 LIMIT 500"
            );
            $st->execute([$txType]);
            $cands = [];
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $r['tokens'] = bank_rag_tokens((string)$r['description'] . ' ' . (string)$r['counterparty']);
                $cands[] = $r;
            }
            $candidateCache[$txType] = $cands;
        } catch (Throwable $e) {
            return null;
        }
    }

    $selfId = (int)($tx['id'] ?? 0);
    $scored = [];
    foreach ($candidateCache[$txType] as $r) {
        if ($selfId > 0 && (int)$r['id'] === $selfId) continue; // 자기 자신 제외
        $sim = bank_rag_similarity($queryTokens, $r['tokens']);
        if ($sim <= 0) continue;
        $scored[] = ['sim' => $sim, 'code' => $r['account_code'], 'name' => $r['account_name'], 'desc' => $r['description']];
    }
    if (!$scored) return null;

    usort($scored, fn($x, $y) => $y['sim'] <=> $x['sim']);
    $top = array_slice($scored, 0, $topK);

    // 유사도 가중 다수결
    $weight = []; $names = []; $simSum = 0;
    foreach ($top as $s) {
        $weight[$s['code']] = ($weight[$s['code']] ?? 0) + $s['sim'];
        $names[$s['code']] = $s['name'];
        $simSum += $s['sim'];
    }
    arsort($weight);
    $bestCode = array_key_first($weight);
    $agreement = $simSum > 0 ? $weight[$bestCode] / $simSum : 0;   // 최다코드 지지율
    $avgSim = $simSum / count($top);
    $confidence = (int) round(min(85, max(35, $agreement * $avgSim * 100 + 30)));

    $suggestion = [
        'code'       => $bestCode,
        'name'       => $names[$bestCode] ?? '',
        'confidence' => $confidence,
        'source'     => 'rag',
        'evidence'   => array_map(fn($s) => ['desc' => $s['desc'], 'code' => $s['code'], 'sim' => round($s['sim'], 2)], $top),
    ];

    // LLM seam · provider 설정 시 검색사례를 컨텍스트로 재판단(RAG). 미설정이면 검색결과 그대로.
    $llm = bank_rag_llm($pdo, $tx, $suggestion);
    return $llm ?? $suggestion;
}

/** 분류 AI provider 설정 읽기 (config/api_settings.json). 기본 'none'. */
function bank_rag_provider(): string
{
    static $cached = null;                       // 요청 단위 캐시 · 제안마다 파일 재읽기 방지
    if ($cached !== null) return $cached;
    $f = __DIR__ . '/../../config/api_settings.json';
    if (!is_file($f)) return $cached = 'none';
    $j = json_decode((string)file_get_contents($f), true);
    return $cached = (is_array($j) ? (string)($j['ai_classify_provider'] ?? 'none') : 'none');
}

/**
 * LLM re-rank seam · provider 미정이면 null 반환(검색결과가 그대로 쓰임).
 * 나중에 Ollama/클라우드 결정 시 이 함수 안에서만 호출을 구현하면 RAG 완성.
 * @return array|null 재판단 제안 또는 null
 */
function bank_rag_llm(PDO $pdo, array $tx, array $retrieval): ?array
{
    $provider = bank_rag_provider();
    if ($provider === 'none' || $provider === '') {
        return null; // 아직 LLM 미결정 → 검색기반 제안만 사용 (안전·무료)
    }
    // TODO(provider 결정 후 구현): 검색사례 evidence + 거래를 프롬프트로 구성해
    //   - 'ollama' : 로컬 Ollama /api/generate 호출 (세무데이터 외부유출 없음)
    //   - 'cloud'  : 클라우드 LLM 호출 (건당 비용 · 민감정보 주의)
    //   응답의 계정코드를 account_categories 로 검증 후 confidence 와 함께 반환.
    // 미구현 동안엔 검색결과를 그대로 사용.
    return null;
}
