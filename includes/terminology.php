<?php
/**
 * 회사 용어 설정 V2 — 핵심 헬퍼
 *
 * - formatPersonDisplay($emp, $context)  : 맥락별 사람 표시
 * - enrichEmployeeTerminology($emp)      : rank_name/duty_name 등 채움
 * - buildPersonSnapshot($emp)            : 결재용 스냅샷 생성
 * - getDisplayConfigs()                  : 표시 형식 설정 로드
 * - getTerminologyForJS()                : 프론트 전달용 설정
 */

require_once __DIR__ . '/../config/database.php';

if (!defined('TERMINOLOGY_CONTEXTS')) {
    define('TERMINOLOGY_CONTEXTS', ['default', 'org_chart', 'approval', 'board', 'profile']);
}

if (!defined('TERMINOLOGY_DEFAULTS')) {
    define('TERMINOLOGY_DEFAULTS', [
        'default'   => ['format_pattern' => '{name} {rank}',                 'suffix' => null],
        'org_chart' => ['format_pattern' => '{name} {duty}',                 'suffix' => null],
        'approval'  => ['format_pattern' => '{name} {rank}',                 'suffix' => null],
        'board'     => ['format_pattern' => '{name}{suffix}',                'suffix' => '님'],
        'profile'   => ['format_pattern' => '{name} {rank} / {dept} {duty}', 'suffix' => null],
    ]);
}


function getDisplayConfigs(): array
{
    static $cache = null;
    if ($cache !== null) return $cache;

    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $rows = $pdo->query(
                "SELECT context_key, format_pattern, suffix FROM terminology_display_config WHERE is_active = 1"
            )->fetchAll(PDO::FETCH_ASSOC);

            $configs = TERMINOLOGY_DEFAULTS;
            foreach ($rows as $r) {
                $configs[$r['context_key']] = [
                    'format_pattern' => $r['format_pattern'],
                    'suffix'         => $r['suffix'],
                ];
            }
            $cache = $configs;
            return $cache;
        } catch (PDOException $e) {
            error_log('[terminology] display_config 로드 실패, 기본값 사용: ' . $e->getMessage());
        }
    }

    $cache = TERMINOLOGY_DEFAULTS;
    return $cache;
}


/**
 * 직원 배열에 rank_name, duty_name, position_name 채우기.
 *
 * $emp에 rank_id/duty_id/position_id가 있으면 DB 조회로 이름 채움.
 * 없으면 기존 position/title 텍스트를 그대로 사용 (전환기 호환).
 *
 * 주의: 직원 1명당 최대 3회 DB 조회. 목록 렌더링에는 terminologyJoinClauses()로
 * JOIN하여 rank_name/duty_name을 미리 채운 뒤 이 함수를 호출할 것.
 */
function enrichEmployeeTerminology(array $emp): array
{
    if (!empty($emp['rank_name']) && !empty($emp['duty_name'])) {
        return $emp;
    }

    $pdo = getDBConnection();

    if (!empty($emp['rank_id']) && empty($emp['rank_name']) && $pdo) {
        try {
            $stmt = $pdo->prepare("SELECT name FROM hr_ranks WHERE id = ?");
            $stmt->execute([(int)$emp['rank_id']]);
            $emp['rank_name'] = $stmt->fetchColumn() ?: ($emp['position'] ?? '');
        } catch (PDOException $e) {
            $emp['rank_name'] = $emp['position'] ?? '';
        }
    } else {
        $emp['rank_name'] = $emp['rank_name'] ?? $emp['position'] ?? '';
    }

    if (!empty($emp['duty_id']) && empty($emp['duty_name']) && $pdo) {
        try {
            $stmt = $pdo->prepare("SELECT name FROM hr_duties WHERE id = ?");
            $stmt->execute([(int)$emp['duty_id']]);
            $emp['duty_name'] = $stmt->fetchColumn() ?: ($emp['title'] ?? '');
        } catch (PDOException $e) {
            $emp['duty_name'] = $emp['title'] ?? '';
        }
    } else {
        $emp['duty_name'] = $emp['duty_name'] ?? $emp['title'] ?? '';
    }

    if (!empty($emp['position_id']) && empty($emp['position_name']) && $pdo) {
        try {
            $stmt = $pdo->prepare("SELECT name FROM hr_positions WHERE id = ?");
            $stmt->execute([(int)$emp['position_id']]);
            $emp['position_name'] = $stmt->fetchColumn() ?: '';
        } catch (PDOException $e) {
            $emp['position_name'] = '';
        }
    } else {
        $emp['position_name'] = $emp['position_name'] ?? '';
    }

    return $emp;
}


/**
 * 맥락별 사람 표시 문자열 생성.
 *
 * @param array  $emp     직원 데이터 (name, rank_name|position, duty_name|title, dept_name 등)
 * @param string $context 맥락 키 (default, org_chart, approval, board, profile)
 * @return string 포맷된 표시 문자열
 */
function formatPersonDisplay(array $emp, string $context = 'default'): string
{
    $emp = enrichEmployeeTerminology($emp);
    $configs = getDisplayConfigs();
    $cfg = $configs[$context] ?? $configs['default'];

    $pattern = $cfg['format_pattern'];
    $suffix  = $cfg['suffix'] ?? '';

    $replacements = [
        '{name}'     => $emp['name'] ?? '',
        '{rank}'     => $emp['rank_name'] ?? '',
        '{duty}'     => $emp['duty_name'] ?? '',
        '{position}' => $emp['position_name'] ?? '',
        '{dept}'     => $emp['dept_name'] ?? $emp['department_name'] ?? '',
        '{suffix}'   => $suffix,
    ];

    $result = str_replace(array_keys($replacements), array_values($replacements), $pattern);

    $result = preg_replace('/\s{2,}/', ' ', $result);
    $result = preg_replace('/\s*\/\s*$/', '', $result);
    $result = trim($result, ' /');

    return $result;
}


/**
 * 결재 이력용 스냅샷.
 * 현재 시점의 이름/직급/부서를 문자열로 고정해서, 이후 변경에 영향받지 않는다.
 */
function buildPersonSnapshot(array $emp): array
{
    $emp = enrichEmployeeTerminology($emp);

    return [
        'employee_id' => $emp['id'] ?? null,
        'name'        => $emp['name'] ?? '',
        'dept'        => $emp['dept_name'] ?? $emp['department_name'] ?? '',
        'rank'        => $emp['rank_name'] ?? '',
        'duty'        => $emp['duty_name'] ?? '',
    ];
}


/**
 * 프론트엔드 전달용 설정 (header.php에서 window.TERMINOLOGY로 주입).
 */
function getTerminologyForJS(): array
{
    $configs = getDisplayConfigs();

    $pdo = getDBConnection();
    $ranks = [];
    $duties = [];
    if ($pdo) {
        try {
            $ranks = $pdo->query(
                "SELECT id, name, code, sort_order FROM hr_ranks WHERE is_active = 1 ORDER BY sort_order"
            )->fetchAll(PDO::FETCH_ASSOC);
            $duties = $pdo->query(
                "SELECT id, name, code, sort_order FROM hr_duties WHERE is_active = 1 ORDER BY sort_order"
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[terminology] JS용 데이터 로드 실패: ' . $e->getMessage());
        }
    }

    return [
        'display' => $configs,
        'ranks'   => $ranks,
        'duties'  => $duties,
    ];
}


/**
 * v_employees VIEW용 SQL 조각.
 * 기존 쿼리에 LEFT JOIN으로 붙여서 rank_name, duty_name 자동 포함.
 *
 * 사용 예:
 *   $sql = "SELECT e.*, " . terminologyJoinColumns()
 *        . " FROM employees e " . terminologyJoinClauses();
 */
function terminologyJoinColumns(): string
{
    return "_tr.name AS rank_name, _td.name AS duty_name, _tp.name AS position_name";
}

function terminologyJoinClauses(): string
{
    return "LEFT JOIN hr_ranks _tr ON _tr.id = e.rank_id "
         . "LEFT JOIN hr_duties _td ON _td.id = e.duty_id "
         . "LEFT JOIN hr_positions _tp ON _tp.id = e.position_id";
}
