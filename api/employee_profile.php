<?php
/**
 * 직원 상세 프로필 API · 1:N 이력 데이터 CRUD
 *
 * 엔티티: careers, educations, certifications, languages, families, awards
 *
 * GET  ?action=getCareers&employee_id=X
 * POST ?action=saveCareer       {employee_id, ...fields, id?}
 * POST ?action=deleteCareer     {id}
 * (동일 패턴으로 6개 엔티티)
 * GET  ?action=getProfileSummary&employee_id=X
 *
 * 권한: 본인 또는 admin/manager
 * 응답: {ok, data} / {ok: false, error: {code, message}}
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/api_common.php';

$pdo = getDBConnection();
if (!$pdo) apiError('DB_DOWN', 'DB 연결 실패', 500);

$action = $_GET['action'] ?? '';

function canAccessProfile(int $targetEmpId): bool
{
    if ($targetEmpId <= 0) return false;
    if (apiIsAdminOrManager()) return true;
    return apiSessionUserId() === $targetEmpId;
}

function requireProfileAccess(int $empId): void
{
    if (!canAccessProfile($empId)) {
        apiError('FORBIDDEN', '접근 권한이 없습니다.', 403);
    }
}

function getTargetEmployeeId(): int
{
    $empId = (int)($_GET['employee_id'] ?? 0);
    if ($empId <= 0) apiError('BAD_REQUEST', 'employee_id가 필요합니다.');
    requireProfileAccess($empId);
    return $empId;
}

function getPostEmployeeId(array $input): int
{
    $empId = (int)($input['employee_id'] ?? 0);
    if ($empId <= 0) apiError('BAD_REQUEST', 'employee_id가 필요합니다.');
    requireProfileAccess($empId);
    return $empId;
}

// ── Careers (경력사항) ──

function getCareers(PDO $pdo): void
{
    $empId = getTargetEmployeeId();
    $stmt = $pdo->prepare('SELECT * FROM employee_careers WHERE employee_id = ? ORDER BY start_date DESC');
    $stmt->execute([$empId]);
    apiOk(['items' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function saveCareer(PDO $pdo): void
{
    $input = apiJsonInput();
    $empId = getPostEmployeeId($input);
    $id = (int)($input['id'] ?? 0);

    $companyName = trim($input['company_name'] ?? '');
    if ($companyName === '') apiError('VALIDATION', '회사명은 필수입니다.');

    $startDate = $input['start_date'] ?? '';
    if ($startDate === '') apiError('VALIDATION', '입사일은 필수입니다.');

    $fields = [
        'employee_id'     => $empId,
        'company_name'    => $companyName,
        'department'      => trim($input['department'] ?? ''),
        'position'        => trim($input['position'] ?? ''),
        'job_type'        => trim($input['job_type'] ?? '') ?: null,
        'employment_type' => trim($input['employment_type'] ?? '정규직'),
        'start_date'      => $startDate,
        'end_date'        => $input['end_date'] ?: null,
        'is_current'      => (int)($input['is_current'] ?? 0),
        'leave_reason'    => trim($input['leave_reason'] ?? '') ?: null,
        'description'     => trim($input['description'] ?? ''),
    ];

    if ($id > 0) {
        $sets = [];
        $vals = [];
        foreach ($fields as $col => $val) {
            if ($col === 'employee_id') continue;
            $sets[] = "$col = ?";
            $vals[] = $val;
        }
        $vals[] = $id;
        $vals[] = $empId;
        $pdo->prepare('UPDATE employee_careers SET ' . implode(', ', $sets) . ' WHERE id = ? AND employee_id = ?')->execute($vals);
    } else {
        $cols = implode(', ', array_keys($fields));
        $phs = implode(', ', array_fill(0, count($fields), '?'));
        $pdo->prepare("INSERT INTO employee_careers ($cols) VALUES ($phs)")->execute(array_values($fields));
        $id = (int)$pdo->lastInsertId();
    }
    apiOk(['id' => $id]);
}

function deleteCareer(PDO $pdo): void
{
    $input = apiJsonInput();
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) apiError('BAD_REQUEST', 'id가 필요합니다.');

    $stmt = $pdo->prepare('SELECT employee_id FROM employee_careers WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) apiError('NOT_FOUND', '경력 정보를 찾을 수 없습니다.', 404);
    requireProfileAccess((int)$row['employee_id']);

    $pdo->prepare('DELETE FROM employee_careers WHERE id = ?')->execute([$id]);
    apiOk();
}

// ── Educations (학력) ──

function getEducations(PDO $pdo): void
{
    $empId = getTargetEmployeeId();
    $stmt = $pdo->prepare('SELECT * FROM employee_educations WHERE employee_id = ? ORDER BY end_date DESC, start_date DESC');
    $stmt->execute([$empId]);
    apiOk(['items' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function saveEducation(PDO $pdo): void
{
    $input = apiJsonInput();
    $empId = getPostEmployeeId($input);
    $id = (int)($input['id'] ?? 0);

    $schoolName = trim($input['school_name'] ?? '');
    if ($schoolName === '') apiError('VALIDATION', '학교명은 필수입니다.');
    $degree = trim($input['degree'] ?? '');
    if ($degree === '') apiError('VALIDATION', '학위는 필수입니다.');

    $gpa = isset($input['gpa']) && $input['gpa'] !== '' ? (float)$input['gpa'] : null;
    $gpaScale = isset($input['gpa_scale']) && $input['gpa_scale'] !== '' ? (float)$input['gpa_scale'] : null;

    $fields = [
        'employee_id' => $empId,
        'school_name' => $schoolName,
        'major'       => trim($input['major'] ?? ''),
        'minor'       => trim($input['minor'] ?? '') ?: null,
        'degree'      => $degree,
        'school_type' => trim($input['school_type'] ?? '') ?: null,
        'gpa'         => $gpa,
        'gpa_scale'   => $gpaScale,
        'start_date'  => $input['start_date'] ?: null,
        'end_date'    => $input['end_date'] ?: null,
        'status'      => trim($input['status'] ?? '졸업'),
        'description' => trim($input['description'] ?? ''),
    ];

    if ($id > 0) {
        $sets = [];
        $vals = [];
        foreach ($fields as $col => $val) {
            if ($col === 'employee_id') continue;
            $sets[] = "$col = ?";
            $vals[] = $val;
        }
        $vals[] = $id;
        $vals[] = $empId;
        $pdo->prepare('UPDATE employee_educations SET ' . implode(', ', $sets) . ' WHERE id = ? AND employee_id = ?')->execute($vals);
    } else {
        $cols = implode(', ', array_keys($fields));
        $phs = implode(', ', array_fill(0, count($fields), '?'));
        $pdo->prepare("INSERT INTO employee_educations ($cols) VALUES ($phs)")->execute(array_values($fields));
        $id = (int)$pdo->lastInsertId();
    }
    apiOk(['id' => $id]);
}

function deleteEducation(PDO $pdo): void
{
    $input = apiJsonInput();
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) apiError('BAD_REQUEST', 'id가 필요합니다.');

    $stmt = $pdo->prepare('SELECT employee_id FROM employee_educations WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) apiError('NOT_FOUND', '학력 정보를 찾을 수 없습니다.', 404);
    requireProfileAccess((int)$row['employee_id']);

    $pdo->prepare('DELETE FROM employee_educations WHERE id = ?')->execute([$id]);
    apiOk();
}

// ── Certifications (자격증) ──

function getCertifications(PDO $pdo): void
{
    $empId = getTargetEmployeeId();
    $stmt = $pdo->prepare('SELECT * FROM employee_certifications WHERE employee_id = ? ORDER BY acquired_date DESC');
    $stmt->execute([$empId]);
    apiOk(['items' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function saveCertification(PDO $pdo): void
{
    $input = apiJsonInput();
    $empId = getPostEmployeeId($input);
    $id = (int)($input['id'] ?? 0);

    $certName = trim($input['cert_name'] ?? '');
    if ($certName === '') apiError('VALIDATION', '자격증명은 필수입니다.');
    $issuingOrg = trim($input['issuing_org'] ?? '');
    if ($issuingOrg === '') apiError('VALIDATION', '발급기관은 필수입니다.');
    $acquiredDate = $input['acquired_date'] ?? '';
    if ($acquiredDate === '') apiError('VALIDATION', '취득일은 필수입니다.');

    $fields = [
        'employee_id'   => $empId,
        'cert_name'     => $certName,
        'issuing_org'   => $issuingOrg,
        'cert_number'   => trim($input['cert_number'] ?? ''),
        'cert_grade'    => trim($input['cert_grade'] ?? '') ?: null,
        'acquired_date' => $acquiredDate,
        'expiry_date'   => $input['expiry_date'] ?: null,
    ];

    if ($id > 0) {
        $sets = [];
        $vals = [];
        foreach ($fields as $col => $val) {
            if ($col === 'employee_id') continue;
            $sets[] = "$col = ?";
            $vals[] = $val;
        }
        $vals[] = $id;
        $vals[] = $empId;
        $pdo->prepare('UPDATE employee_certifications SET ' . implode(', ', $sets) . ' WHERE id = ? AND employee_id = ?')->execute($vals);
    } else {
        $cols = implode(', ', array_keys($fields));
        $phs = implode(', ', array_fill(0, count($fields), '?'));
        $pdo->prepare("INSERT INTO employee_certifications ($cols) VALUES ($phs)")->execute(array_values($fields));
        $id = (int)$pdo->lastInsertId();
    }
    apiOk(['id' => $id]);
}

function deleteCertification(PDO $pdo): void
{
    $input = apiJsonInput();
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) apiError('BAD_REQUEST', 'id가 필요합니다.');

    $stmt = $pdo->prepare('SELECT employee_id FROM employee_certifications WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) apiError('NOT_FOUND', '자격증 정보를 찾을 수 없습니다.', 404);
    requireProfileAccess((int)$row['employee_id']);

    $pdo->prepare('DELETE FROM employee_certifications WHERE id = ?')->execute([$id]);
    apiOk();
}

// ── Languages (언어능력) ──

function getLanguages(PDO $pdo): void
{
    $empId = getTargetEmployeeId();
    $stmt = $pdo->prepare('SELECT * FROM employee_languages WHERE employee_id = ? ORDER BY id');
    $stmt->execute([$empId]);
    apiOk(['items' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function saveLanguage(PDO $pdo): void
{
    $input = apiJsonInput();
    $empId = getPostEmployeeId($input);
    $id = (int)($input['id'] ?? 0);

    $language = trim($input['language'] ?? '');
    if ($language === '') apiError('VALIDATION', '언어는 필수입니다.');
    $level = trim($input['level'] ?? '');
    if ($level === '') apiError('VALIDATION', '수준은 필수입니다.');

    $fields = [
        'employee_id'    => $empId,
        'language'       => $language,
        'level'          => $level,
        'test_type'      => trim($input['test_type'] ?? '') ?: null,
        'test_name'      => trim($input['test_name'] ?? ''),
        'test_score'     => trim($input['test_score'] ?? ''),
        'test_date'      => $input['test_date'] ?: null,
        'validity_years' => trim($input['validity_years'] ?? '') ?: null,
    ];

    if ($id > 0) {
        $sets = [];
        $vals = [];
        foreach ($fields as $col => $val) {
            if ($col === 'employee_id') continue;
            $sets[] = "$col = ?";
            $vals[] = $val;
        }
        $vals[] = $id;
        $vals[] = $empId;
        $pdo->prepare('UPDATE employee_languages SET ' . implode(', ', $sets) . ' WHERE id = ? AND employee_id = ?')->execute($vals);
    } else {
        $cols = implode(', ', array_keys($fields));
        $phs = implode(', ', array_fill(0, count($fields), '?'));
        $pdo->prepare("INSERT INTO employee_languages ($cols) VALUES ($phs)")->execute(array_values($fields));
        $id = (int)$pdo->lastInsertId();
    }
    apiOk(['id' => $id]);
}

function deleteLanguage(PDO $pdo): void
{
    $input = apiJsonInput();
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) apiError('BAD_REQUEST', 'id가 필요합니다.');

    $stmt = $pdo->prepare('SELECT employee_id FROM employee_languages WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) apiError('NOT_FOUND', '언어 정보를 찾을 수 없습니다.', 404);
    requireProfileAccess((int)$row['employee_id']);

    $pdo->prepare('DELETE FROM employee_languages WHERE id = ?')->execute([$id]);
    apiOk();
}

// ── Families (가족정보) ──

function getFamilies(PDO $pdo): void
{
    $empId = getTargetEmployeeId();
    $stmt = $pdo->prepare('SELECT * FROM employee_families WHERE employee_id = ? ORDER BY id');
    $stmt->execute([$empId]);
    apiOk(['items' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function saveFamily(PDO $pdo): void
{
    $input = apiJsonInput();
    $empId = getPostEmployeeId($input);
    $id = (int)($input['id'] ?? 0);

    $relationship = trim($input['relationship'] ?? '');
    if ($relationship === '') apiError('VALIDATION', '관계는 필수입니다.');
    $name = trim($input['name'] ?? '');
    if ($name === '') apiError('VALIDATION', '이름은 필수입니다.');

    $fields = [
        'employee_id'       => $empId,
        'relationship'      => $relationship,
        'name'              => $name,
        'birth_date'        => $input['birth_date'] ?: null,
        'phone'             => trim($input['phone'] ?? ''),
        'is_cohabitant'     => (int)($input['is_cohabitant'] ?? 1),
        'is_dependent'      => (int)($input['is_dependent'] ?? 0),
        'is_health_dependent' => (int)($input['is_health_dependent'] ?? 0),
        'memo'              => trim($input['memo'] ?? ''),
    ];

    if ($id > 0) {
        $sets = [];
        $vals = [];
        foreach ($fields as $col => $val) {
            if ($col === 'employee_id') continue;
            $sets[] = "$col = ?";
            $vals[] = $val;
        }
        $vals[] = $id;
        $vals[] = $empId;
        $pdo->prepare('UPDATE employee_families SET ' . implode(', ', $sets) . ' WHERE id = ? AND employee_id = ?')->execute($vals);
    } else {
        $cols = implode(', ', array_keys($fields));
        $phs = implode(', ', array_fill(0, count($fields), '?'));
        $pdo->prepare("INSERT INTO employee_families ($cols) VALUES ($phs)")->execute(array_values($fields));
        $id = (int)$pdo->lastInsertId();
    }
    apiOk(['id' => $id]);
}

function deleteFamily(PDO $pdo): void
{
    $input = apiJsonInput();
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) apiError('BAD_REQUEST', 'id가 필요합니다.');

    $stmt = $pdo->prepare('SELECT employee_id FROM employee_families WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) apiError('NOT_FOUND', '가족 정보를 찾을 수 없습니다.', 404);
    requireProfileAccess((int)$row['employee_id']);

    $pdo->prepare('DELETE FROM employee_families WHERE id = ?')->execute([$id]);
    apiOk();
}

// ── Awards (수상/징계) ──

function getAwards(PDO $pdo): void
{
    $empId = getTargetEmployeeId();
    $stmt = $pdo->prepare('SELECT * FROM employee_awards WHERE employee_id = ? ORDER BY awarded_date DESC');
    $stmt->execute([$empId]);
    apiOk(['items' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function saveAward(PDO $pdo): void
{
    $input = apiJsonInput();
    $empId = getPostEmployeeId($input);
    $id = (int)($input['id'] ?? 0);

    $type = trim($input['type'] ?? '');
    if (!in_array($type, ['수상', '징계'], true)) apiError('VALIDATION', '구분은 수상 또는 징계만 가능합니다.');
    $title = trim($input['title'] ?? '');
    if ($title === '') apiError('VALIDATION', '명칭은 필수입니다.');
    $awardedDate = $input['awarded_date'] ?? '';
    if ($awardedDate === '') apiError('VALIDATION', '일자는 필수입니다.');

    $fields = [
        'employee_id'     => $empId,
        'type'            => $type,
        'discipline_level'=> trim($input['discipline_level'] ?? '') ?: null,
        'title'           => $title,
        'awarded_date'    => $awardedDate,
        'follow_up_date'  => $input['follow_up_date'] ?: null,
        'awarding_org'    => trim($input['awarding_org'] ?? ''),
        'description'     => trim($input['description'] ?? ''),
    ];

    if ($id > 0) {
        $sets = [];
        $vals = [];
        foreach ($fields as $col => $val) {
            if ($col === 'employee_id') continue;
            $sets[] = "$col = ?";
            $vals[] = $val;
        }
        $vals[] = $id;
        $vals[] = $empId;
        $pdo->prepare('UPDATE employee_awards SET ' . implode(', ', $sets) . ' WHERE id = ? AND employee_id = ?')->execute($vals);
    } else {
        $cols = implode(', ', array_keys($fields));
        $phs = implode(', ', array_fill(0, count($fields), '?'));
        $pdo->prepare("INSERT INTO employee_awards ($cols) VALUES ($phs)")->execute(array_values($fields));
        $id = (int)$pdo->lastInsertId();
    }
    apiOk(['id' => $id]);
}

function deleteAward(PDO $pdo): void
{
    $input = apiJsonInput();
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) apiError('BAD_REQUEST', 'id가 필요합니다.');

    $stmt = $pdo->prepare('SELECT employee_id FROM employee_awards WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) apiError('NOT_FOUND', '수상/징계 정보를 찾을 수 없습니다.', 404);
    requireProfileAccess((int)$row['employee_id']);

    $pdo->prepare('DELETE FROM employee_awards WHERE id = ?')->execute([$id]);
    apiOk();
}

// ── Military (1:1 singleton) ──

function getMilitary(PDO $pdo): void
{
    $empId = getTargetEmployeeId();
    $stmt = $pdo->prepare('SELECT * FROM employee_military WHERE employee_id = ?');
    $stmt->execute([$empId]);
    $row = $stmt->fetch();
    apiOk(['items' => $row ? [$row] : []]);
}

function saveMilitary(PDO $pdo): void
{
    $input = apiJsonInput();
    $empId = getPostEmployeeId($input);

    $status = trim($input['military_status'] ?? '해당없음');

    $fields = [
        'employee_id'      => $empId,
        'military_status'  => $status,
        'branch'           => trim($input['branch'] ?? '') ?: null,
        'branch_specialty' => trim($input['branch_specialty'] ?? '') ?: null,
        'rank_title'       => trim($input['rank_title'] ?? '') ?: null,
        'enlist_date'      => $input['enlist_date'] ?: null,
        'discharge_date'   => $input['discharge_date'] ?: null,
        'discharge_type'   => trim($input['discharge_type'] ?? '') ?: null,
        'exemption_reason' => trim($input['exemption_reason'] ?? '') ?: null,
    ];

    $cols = implode(', ', array_keys($fields));
    $phs = implode(', ', array_fill(0, count($fields), '?'));
    $updates = [];
    foreach ($fields as $col => $val) {
        if ($col === 'employee_id') continue;
        $updates[] = "$col = VALUES($col)";
    }
    $pdo->prepare("INSERT INTO employee_military ($cols) VALUES ($phs) ON DUPLICATE KEY UPDATE " . implode(', ', $updates))
        ->execute(array_values($fields));

    $stmt = $pdo->prepare('SELECT id FROM employee_military WHERE employee_id = ?');
    $stmt->execute([$empId]);
    $id = (int)$stmt->fetchColumn();
    apiOk(['id' => $id]);
}

function deleteMilitary(PDO $pdo): void
{
    $input = apiJsonInput();
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) apiError('BAD_REQUEST', 'id가 필요합니다.');

    $stmt = $pdo->prepare('SELECT employee_id FROM employee_military WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) apiError('NOT_FOUND', '병역 정보를 찾을 수 없습니다.', 404);
    requireProfileAccess((int)$row['employee_id']);

    $pdo->prepare('DELETE FROM employee_military WHERE id = ?')->execute([$id]);
    apiOk();
}

// ── Skills (tag-based) ──

function getSkills(PDO $pdo): void
{
    $empId = getTargetEmployeeId();
    $stmt = $pdo->prepare('SELECT * FROM employee_skills WHERE employee_id = ? ORDER BY created_at ASC');
    $stmt->execute([$empId]);
    apiOk(['items' => $stmt->fetchAll()]);
}

function saveSkill(PDO $pdo): void
{
    $input = apiJsonInput();
    $empId = getPostEmployeeId($input);

    $skillName = trim($input['skill_name'] ?? '');
    if ($skillName === '') apiError('VALIDATION', '스킬명은 필수입니다.');

    $stmt = $pdo->prepare('SELECT id FROM employee_skills WHERE employee_id = ? AND skill_name = ?');
    $stmt->execute([$empId, $skillName]);
    if ($stmt->fetch()) apiError('DUPLICATE', '이미 등록된 스킬입니다.');

    $pdo->prepare('INSERT INTO employee_skills (employee_id, skill_name) VALUES (?, ?)')
        ->execute([$empId, $skillName]);
    $id = (int)$pdo->lastInsertId();
    apiOk(['id' => $id]);
}

function deleteSkill(PDO $pdo): void
{
    $input = apiJsonInput();
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) apiError('BAD_REQUEST', 'id가 필요합니다.');

    $stmt = $pdo->prepare('SELECT employee_id FROM employee_skills WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) apiError('NOT_FOUND', '스킬을 찾을 수 없습니다.', 404);
    requireProfileAccess((int)$row['employee_id']);

    $pdo->prepare('DELETE FROM employee_skills WHERE id = ?')->execute([$id]);
    apiOk();
}

// ── Profile Summary ──

function getProfileSummary(PDO $pdo): void
{
    $empId = getTargetEmployeeId();
    $tables = [
        'careers'        => 'employee_careers',
        'educations'     => 'employee_educations',
        'certifications' => 'employee_certifications',
        'languages'      => 'employee_languages',
        'families'       => 'employee_families',
        'awards'         => 'employee_awards',
        'military'       => 'employee_military',
        'skills'         => 'employee_skills',
    ];
    $counts = [];
    foreach ($tables as $key => $table) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE employee_id = ?");
        $stmt->execute([$empId]);
        $counts[$key] = (int)$stmt->fetchColumn();
    }
    apiOk(['counts' => $counts]);
}

// ── Router ──

switch ($action) {
    case 'getCareers':        getCareers($pdo); break;
    case 'saveCareer':        saveCareer($pdo); break;
    case 'deleteCareer':      deleteCareer($pdo); break;
    case 'getEducations':     getEducations($pdo); break;
    case 'saveEducation':     saveEducation($pdo); break;
    case 'deleteEducation':   deleteEducation($pdo); break;
    case 'getCertifications': getCertifications($pdo); break;
    case 'saveCertification': saveCertification($pdo); break;
    case 'deleteCertification': deleteCertification($pdo); break;
    case 'getLanguages':      getLanguages($pdo); break;
    case 'saveLanguage':      saveLanguage($pdo); break;
    case 'deleteLanguage':    deleteLanguage($pdo); break;
    case 'getFamilies':       getFamilies($pdo); break;
    case 'saveFamily':        saveFamily($pdo); break;
    case 'deleteFamily':      deleteFamily($pdo); break;
    case 'getAwards':         getAwards($pdo); break;
    case 'saveAward':         saveAward($pdo); break;
    case 'deleteAward':       deleteAward($pdo); break;
    case 'getMilitary':       getMilitary($pdo); break;
    case 'saveMilitary':      saveMilitary($pdo); break;
    case 'deleteMilitary':    deleteMilitary($pdo); break;
    case 'getSkills':         getSkills($pdo); break;
    case 'saveSkill':         saveSkill($pdo); break;
    case 'deleteSkill':       deleteSkill($pdo); break;
    case 'getProfileSummary': getProfileSummary($pdo); break;
    default:
        apiError('BAD_ACTION', "알 수 없는 액션: $action");
}
