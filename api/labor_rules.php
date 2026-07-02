<?php
/**
 * Zaemit 그룹웨어 - 취업규칙 API
 * action: load (저장된 규칙 조회), save (섹션별 저장)
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? '';

$writeActions = ['save', 'saveAll', 'saveDoc'];
if (in_array($action, $writeActions, true)) {
    $role = (string)($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['admin', 'manager'], true)) {
        respond(403, ['error' => '관리자 권한이 필요합니다.']);
    }
}

switch ($action) {
    case 'load':    loadRules();    break;
    case 'save':    saveSection();  break;
    case 'saveAll': saveAll();      break;
    // 신규: 단일 HTML 문서 (계약서 양식과 동일한 TFEditor 기반)
    case 'loadDoc': loadDoc();      break;
    case 'saveDoc': saveDoc();      break;
    default:
        respond(400, ['error' => '알 수 없는 액션']);
}

function respond(int $code, array $data): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/* 테이블은 db/zaemit_groupware_dump.sql 에서 생성. 런타임 CREATE TABLE 제거됨. */

/* ── 기본 양식 배열(PHP) → HTML 직렬화 ── */
function ruleSectionsToHtml(array $sections): string
{
    $html = '';
    foreach ($sections as $idx => $sec) {
        $chNo = $idx + 1;
        $html .= '<h2>제' . $chNo . '장 ' . htmlspecialchars($sec['title'] ?? '', ENT_QUOTES, 'UTF-8') . '</h2>' . "\n";
        foreach ($sec['articles'] ?? [] as $art) {
            $n = (int)($art['n'] ?? 0);
            $t = htmlspecialchars((string)($art['t'] ?? ''), ENT_QUOTES, 'UTF-8');
            $html .= '<h3>제' . $n . '조 (' . $t . ')</h3>' . "\n";
            foreach (explode("\n", (string)($art['c'] ?? '')) as $line) {
                $line = trim($line);
                if ($line === '') continue;
                $html .= '<p>' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</p>' . "\n";
            }
        }
    }
    return $html;
}

/* ── 레거시 조문별 데이터를 HTML 로 직렬화 ── */
function legacyRulesToHtml(PDO $pdo, array $templateSections): ?string
{
    try {
        $rows = $pdo->query("SELECT section_idx, article_num, content FROM labor_rules")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { return null; }
    if (empty($rows)) return null;

    $byKey = [];
    foreach ($rows as $r) {
        $byKey[(int)$r['section_idx']][(int)$r['article_num']] = (string)$r['content'];
    }
    // 템플릿 순서를 유지하되, 저장된 내용이 있으면 그걸로 교체
    $merged = $templateSections;
    foreach ($merged as $si => &$sec) {
        foreach ($sec['articles'] as &$art) {
            $n = (int)($art['n'] ?? 0);
            if (isset($byKey[$si][$n])) $art['c'] = $byKey[$si][$n];
        }
        unset($art);
    }
    unset($sec);
    return ruleSectionsToHtml($merged);
}

/** 단일 HTML 문서 조회 · 없으면 레거시/템플릿 순으로 시드 */
function loadDoc(): void
{
    try {
        $pdo = getDBConnection();
        if (!$pdo) respond(500, ['error' => 'DB 연결 실패']);

        $row = $pdo->query("SELECT body, updated_by, updated_at FROM labor_rules_document WHERE id = 1")->fetch(PDO::FETCH_ASSOC);

        if ($row && isset($row['body']) && $row['body'] !== '') {
            respond(200, [
                'success' => true,
                'body' => $row['body'],
                'updated_by' => $row['updated_by'],
                'updated_at' => $row['updated_at'],
            ]);
        }

        // 시드: 레거시 → 실패 시 템플릿
        $template = require __DIR__ . '/../data/labor_rules_template.php';
        $seed = legacyRulesToHtml($pdo, $template);
        if ($seed === null) {
            $seed = ruleSectionsToHtml($template);
        }

        // 시드 본문은 바로 저장하지 않고 응답만. (사용자가 최초 수정 저장 시 기록됨)
        respond(200, [
            'success' => true,
            'body' => $seed,
            'updated_by' => null,
            'updated_at' => null,
            'seeded' => true,
        ]);
    } catch (PDOException $e) {
        error_log('[labor_rules] loadDoc 실패: ' . $e->getMessage());
        respond(500, ['error' => 'DB 조회 실패']);
    }
}

/** 단일 HTML 문서 저장 */
function saveDoc(): void
{
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $body = (string)($input['body'] ?? '');
    if (trim(strip_tags($body)) === '') {
        respond(400, ['error' => '본문이 비어 있습니다.']);
    }

    $body = sanitizeLaborHtml($body);

    $userId = (int)($_SESSION['user_id'] ?? 0) ?: null;

    try {
        $pdo = getDBConnection();
        if (!$pdo) respond(500, ['error' => 'DB 연결 실패']);

        $stmt = $pdo->prepare("
            INSERT INTO labor_rules_document (id, body, updated_by)
            VALUES (1, :b, :u)
            ON DUPLICATE KEY UPDATE body = VALUES(body), updated_by = VALUES(updated_by), updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([':b' => $body, ':u' => $userId]);

        $row = $pdo->query("SELECT updated_by, updated_at FROM labor_rules_document WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
        respond(200, [
            'success' => true,
            'updated_by' => $row['updated_by'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
        ]);
    } catch (PDOException $e) {
        error_log('[labor_rules] saveDoc 실패: ' . $e->getMessage());
        respond(500, ['error' => 'DB 저장 실패']);
    }
}

/** 저장된 취업규칙 전체 조회 */
function loadRules(): void
{
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->query("SELECT section_idx, article_num, content FROM labor_rules ORDER BY section_idx, article_num");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // section_idx → [article_num → content] 구조로 변환
        $result = [];
        foreach ($rows as $r) {
            $result[(int)$r['section_idx']][(int)$r['article_num']] = $r['content'];
        }
        respond(200, ['success' => true, 'data' => $result]);
    } catch (PDOException $e) {
        respond(500, ['error' => 'DB 오류']);
    }
}

/**
 * 전 섹션 일괄 저장 (UPSERT).
 * 입력: { sections: [{ section_idx, articles: [{ num, content }] }, ...] }
 * 하나의 트랜잭션으로 전체 반영 · 부분 실패 시 전부 롤백.
 */
function saveAll(): void
{
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $sections = $input['sections'] ?? [];
    if (!is_array($sections) || empty($sections)) respond(400, ['error' => 'sections 배열이 필요합니다']);

    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            INSERT INTO labor_rules (section_idx, article_num, content)
            VALUES (:si, :an, :c)
            ON DUPLICATE KEY UPDATE content = VALUES(content), updated_at = CURRENT_TIMESTAMP
        ");
        $pdo->beginTransaction();
        $total = 0;
        foreach ($sections as $sec) {
            $si = (int)($sec['section_idx'] ?? -1);
            if ($si < 0) continue;
            $articles = $sec['articles'] ?? [];
            if (!is_array($articles)) continue;
            foreach ($articles as $a) {
                $num = (int)($a['num'] ?? 0);
                $content = trim((string)($a['content'] ?? ''));
                if ($num <= 0 || $content === '') continue;
                $stmt->execute([':si' => $si, ':an' => $num, ':c' => $content]);
                $total++;
            }
        }
        $pdo->commit();
        respond(200, ['success' => true, 'saved' => $total]);
    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        error_log('[labor_rules] saveAll 실패: ' . $e->getMessage());
        respond(500, ['error' => 'DB 저장 실패']);
    }
}

/** 섹션 단위 저장 (UPSERT) */
function saveSection(): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        respond(400, ['error' => '요청 데이터가 없습니다']);
    }

    $sectionIdx = $input['section_idx'] ?? null;
    $articles = $input['articles'] ?? [];

    if ($sectionIdx === null || !is_array($articles) || count($articles) === 0) {
        respond(400, ['error' => 'section_idx와 articles가 필요합니다']);
    }

    $sectionIdx = (int)$sectionIdx;

    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            INSERT INTO labor_rules (section_idx, article_num, content)
            VALUES (:si, :an, :c)
            ON DUPLICATE KEY UPDATE content = VALUES(content), updated_at = CURRENT_TIMESTAMP
        ");

        $pdo->beginTransaction();
        $saved = 0;
        foreach ($articles as $a) {
            $num = (int)($a['num'] ?? 0);
            $content = trim($a['content'] ?? '');
            if ($num <= 0 || $content === '') continue;

            $stmt->execute([':si' => $sectionIdx, ':an' => $num, ':c' => $content]);
            $saved++;
        }
        $pdo->commit();

        respond(200, ['success' => true, 'saved' => $saved]);
    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        respond(500, ['error' => 'DB 저장 실패']);
    }
}

function sanitizeLaborHtml(string $html): string
{
    static $purifier = null;
    if ($purifier === null) {
        require_once __DIR__ . '/../includes/lib/htmlpurifier/HTMLPurifier.auto.php';
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.DefinitionImpl', null);
        $config->set('HTML.Allowed',
            'p,br,b,strong,i,em,u,s,span[style],div[style],'
            . 'h1,h2,h3,h4,h5,h6,ul,ol,li,hr,blockquote,'
            . 'table,thead,tbody,tr,td[colspan|rowspan|style],th[colspan|rowspan|style],a[href]');
        $config->set('CSS.AllowedProperties',
            'font-weight,font-style,text-align,text-decoration,width,color,background-color');
        $config->set('Attr.AllowedFrameTargets', []);
        $purifier = new HTMLPurifier($config);
    }
    return trim($purifier->purify($html));
}
