<?php
/**
 * 규칙기반 거래 분류 · 적요(description) 키워드 → account_categories.code 제안.
 *
 * 설계 의도(가드레일 준수):
 *  - 이건 "계정과목 분류 제안" 메타데이터만 만든다. 세율/공제/세액 등 세무·금액 계산 로직은
 *    절대 손대지 않는다(account_categories.tax_type 등은 읽기만).
 *  - 제안일 뿐 → ai_confidence 와 함께 is_confirmed=0 으로 저장되고, 최종 확정은 사람이 한다.
 *  - LLM 미사용(투명·결정적). 추후 LLM 분류 엔진은 별도 PR 로 이 인터페이스를 교체/보강.
 *
 * 반환: ['code' => '526', 'name' => '소모품비', 'confidence' => 88]
 */

require_once __DIR__ . '/../../config/database.php';

/** code => name 맵 (account_categories, 요청 단위 캐시) */
function bank_category_map(): array
{
    static $map = null;
    if ($map !== null) return $map;
    $map = [];
    try {
        $pdo = getDBConnection();
        if ($pdo) {
            $rows = $pdo->query("SELECT code, name FROM account_categories WHERE is_active = 1")
                        ->fetchAll(PDO::FETCH_KEY_PAIR);
            $map = is_array($rows) ? $rows : [];
        }
    } catch (Throwable $e) {
        error_log('[bank_classify] 카테고리 로드 실패: ' . $e->getMessage());
    }
    return $map;
}

/**
 * 출금(비용성) 키워드 규칙 · [정규식, 계정코드, 신뢰도]. 위에서부터 첫 매치.
 * 코드는 db/schema_tax.sql 의 account_categories 시드 기준.
 */
function bank_classify_rules_withdraw(): array
{
    // 코드는 5자리 표준 차트(account_categories · db/migrate_account_codes.sql) 기준
    return [
        ['/통신|인터넷|KT|SKT|LG\s?U\+?|유플러스|텔레콤/iu', '81400', 96], // 통신비
        ['/급여|월급|상여|봉급|임금|페이롤/u',                 '80200', 95], // 직원급여
        ['/법인세|부가가치세|부가세|원천세|지방세|소득세|세금|과태료|공과금|국세|관세|전기료|수도료|가스료/u', '81700', 97], // 세금과공과금
        ['/임대|임차|월세|렌트|사무실\s?임/u',                 '81900', 91], // 지급임차료
        ['/광고|마케팅|네이버\s?광고|구글\s?광고|google\s?ads|페이스북|인스타|퍼포먼스/iu', '83300', 92], // 광고선전비
        ['/보험|화재보험|4대\s?보험|국민연금|건강보험|고용보험|산재/u', '82100', 89], // 보험료
        ['/주유|주차|하이패스|택시|버스|지하철|KTX|항공|교통|출장/iu', '81200', 86], // 여비교통비
        ['/식당|음식|커피|스타벅스|배달|회식|접대|유흥|골프/u',  '81300', 70], // 접대비
        ['/편의점|GS25|CU|세븐일레븐|이마트|마트|복리|간식|경조/iu', '81100', 78], // 복리후생비
        ['/쿠팡|문구|사무용품|소모품|오피스|토너|용지|비품/iu',  '83000', 88], // 소모품비
        ['/수수료|용역|프리랜서|외주|컨설팅|대행|자문/u',        '83100', 80], // 지급수수료
        ['/교육|강의|세미나|학원|연수|컨퍼런스|워크샵/u',        '82500', 86], // 교육훈련비
        ['/차량|자동차|정비|타이어|엔진오일|세차/u',            '82700', 84], // 차량유지비
        ['/이자|대출\s?이자|융자/u',                          '93100', 90], // 이자비용
        ['/매입|원재료|자재|부품|상품\s?구매/u',               '45100', 82], // 상품매입
    ];
}

/**
 * 입금(수익성) 키워드 규칙.
 */
function bank_classify_rules_deposit(): array
{
    return [
        ['/이자|예금\s?이자/u',                              '90100', 88], // 이자수익
        ['/임대료|월세\s?입금|렌트\s?수입/u',                 '40400', 84], // 임대수입
        ['/용역|서비스|컨설팅|개발\s?대금/u',                 '41200', 80], // 서비스매출
        ['/제품|완제품/u',                                   '40200', 78], // 제품매출
        ['/상품|판매|매출|결제\s?대금|대금\s?입금|입금|수금/u', '40100', 72], // 상품매출
        ['/환급|환불|잡수익|기타\s?수입/u',                   '93000', 65], // 잡이익
    ];
}

/**
 * 한 건 분류. description 과 tx_type('입금'|'출금')을 받아 제안 코드/이름/신뢰도 반환.
 */
function bank_classify_one(string $description, string $txType): array
{
    $map   = bank_category_map();
    $desc  = trim($description);
    $rules = ($txType === '입금') ? bank_classify_rules_deposit() : bank_classify_rules_withdraw();

    foreach ($rules as [$re, $code, $conf]) {
        if ($desc !== '' && preg_match($re, $desc)) {
            return ['code' => $code, 'name' => $map[$code] ?? '', 'confidence' => $conf];
        }
    }

    // 폴백 · 매칭 없음. 낮은 신뢰도로 수동확인 유도(입금=상품매출, 출금=소모품비 기본).
    $code = ($txType === '입금') ? '40100' : '83000';
    return ['code' => $code, 'name' => $map[$code] ?? '', 'confidence' => 40];
}
