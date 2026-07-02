<?php
/**
 * 결재 문서 공용 유틸.
 *
 * - 문서번호 포맷은 이 파일의 함수에서만 생성/파싱한다.
 *   포맷이 바뀌면 card_expenses ↔ approval_documents 의 문자열 기반 JOIN도 따라서
 *   "조용히" 깨지므로, 반드시 단일 진입점으로 유지한다.
 * - 새로운 모듈이 결재 문서를 만들 때는 buildApprovalDocNumber()를 호출한다.
 */

require_once __DIR__ . '/approval_settings.php';

/**
 * 결재 문서번호 생성.
 *
 * 포맷: Zaemit_{부서슬러그}_{타입슬러그}_{YYYYMMDDHHMMSS}
 * - 슬러그는 공백/제어문자 제거 + 선두 10자 한정 (한글 포함)
 * - 호출 측에서 부서/타입이 비어있을 때의 기본값을 지정할 것.
 *
 * @param string $dept     부서명 (예: "경영진", "개발1팀")
 * @param string $docType  문서종류 (예: "품의서", "경비청구서")
 * @return string
 */
function buildApprovalDocNumber(string $dept, string $docType): string
{
    $deptSlug = approvalSlug($dept, 10);
    $typeSlug = approvalSlug($docType, 10);
    if ($deptSlug === '') $deptSlug = 'Zaemit';
    if ($typeSlug === '') $typeSlug = '문서';
    return sprintf('Zaemit_%s_%s_%s', $deptSlug, $typeSlug, date('YmdHis'));
}

/** 문서번호/슬러그 공용 정규화 · 공백·제어문자 제거 + 길이 제한 */
function approvalSlug(string $s, int $max): string
{
    $s = preg_replace('/\s+/u', '', $s) ?? '';
    return mb_substr($s, 0, $max);
}

/**
 * 결재선 해결:
 *  1) approval_lines 중 (doc_type, department) 일치 = 문서종류 예외 규칙. line_data에서 결재자 추출.
 *     - line_data 항목은 두 종류:
 *         person: {"type":"person"|생략, "name":"...", "id":N?, "dept":"...?"}  · 고정 인물
 *         slot:   {"type":"slot", "slot":"부서장", "slot_key":"department.head"} · 작성자 조직 기준 동적 해소 (slot_key 우선, 없으면 slot으로 slot_map 조회)
 *       (id 없이 name만 있는 기존 시드 데이터 호환 · 없으면 이름으로 employees 조회)
 *  2) 예외 없으면 기본 결재 라인 = 작성자 부서에서 조직도(parent_id) 따라 올라가며 부서장(head_employee_id)
 *     수집, 설정된 depth(부서장/본부장/대표까지)만큼. ($drafterDeptId 필요)
 *  3) 그래도 비면 admin 역할 직원 1명을 1단계 결재자로 자동 구성
 *  4) 그것도 없으면 null → 호출 측에서 명시적 오류 처리
 *
 * 반환 형식: 각 단계 = ['employee_id' => int|null, 'name' => string, 'dept' => string, 'role' => '결재'|'전결']
 *
 * @param ?int $drafterDeptId 작성자 부서 id (기본 라인 해소용). null이면 슬롯/기본라인 생략.
 * @param ?int $drafterEmpId  작성자 본인 id (본인을 결재자에서 제외).
 * @return array<int, array{employee_id: ?int, name: string, dept: string, role: string}>|null
 */
function resolveApprovalRoute(PDO $pdo, string $department, string $docType, ?int $drafterDeptId = null, ?int $drafterEmpId = null, ?int $amount = null): ?array
{
    // 1) approval_lines 매칭 · 부서 정확히 일치하는 행을 우선 (문서종류 예외 규칙)
    //    amount가 주어지면 amount_threshold <= amount 중 가장 높은 threshold 우선
    $amountFilter = ($amount !== null) ? $amount : 0;
    $stmt = $pdo->prepare(
        "SELECT line_data FROM approval_lines
         WHERE doc_type = ? AND (department = ? OR department = '' OR department IS NULL)
           AND amount_threshold <= ?
         ORDER BY (department = ?) DESC, amount_threshold DESC, id DESC LIMIT 1"
    );
    $stmt->execute([$docType, $department, $amountFilter, $department]);
    $raw = $stmt->fetchColumn();
    $route = [];
    if ($raw) {
        $decoded = json_decode((string)$raw, true);
        if (is_array($decoded)) {
            // name → id 일괄 조회용 prepared stmt
            $empByName = $pdo->prepare(
                "SELECT e.id, COALESCE(d.name, '') AS dept_name
                 FROM employees e LEFT JOIN departments d ON e.department_id = d.id
                 WHERE e.name = ? AND e.is_active = 1 LIMIT 1"
            );
            foreach ($decoded as $step) {
                // slot 항목 · 작성자 조직 기준 동적 해소
                $stepRole = (string)($step['role'] ?? '결재');
                if (!in_array($stepRole, ['결재', '협조', '참조', '전결'], true)) $stepRole = '결재';

                if (($step['type'] ?? '') === 'slot') {
                    $slotRef = (string)($step['slot_key'] ?? $step['slot'] ?? '');
                    $resolved = resolveSlot($pdo, $slotRef, $drafterDeptId, $drafterEmpId);
                    if ($resolved) {
                        $resolved['role'] = $stepRole;
                        $route[] = $resolved;
                    }
                    continue;
                }
                // person 항목
                if (empty($step['name'])) continue;
                $name = (string)$step['name'];
                $empId = !empty($step['id']) ? (int)$step['id'] : null;
                $dept = (string)($step['dept'] ?? '');
                if ($empId === null) {
                    $empByName->execute([$name]);
                    $row = $empByName->fetch(PDO::FETCH_ASSOC);
                    if ($row) {
                        $empId = (int)$row['id'];
                        if ($dept === '') $dept = (string)$row['dept_name'];
                    }
                }
                $route[] = [
                    'employee_id' => $empId,
                    'name'        => $name,
                    'dept'        => $dept,
                    'role'        => $stepRole,
                ];
            }
        }
    }

    // 2) 예외 규칙 없으면 기본 결재 라인 = 조직도 따라 위로 (설정 depth까지)
    if (empty($route) && $drafterDeptId) {
        $route = resolveOrgChain(
            $pdo,
            (int)$drafterDeptId,
            getApprovalDefaultDepthLevels(),
            $drafterEmpId,
            getApprovalDefaultDepthIndexes()
        );
    }

    // 작성자 본인/중복 결재자 제거 (employee_id 기준, 첫 등장 유지)
    if (!empty($route)) {
        $seen = [];
        if ($drafterEmpId) $seen[(int)$drafterEmpId] = true;
        $deduped = [];
        foreach ($route as $r) {
            $eid = !empty($r['employee_id']) ? (int)$r['employee_id'] : null;
            if ($eid !== null) {
                if (isset($seen[$eid])) continue;
                $seen[$eid] = true;
            }
            $deduped[] = $r;
        }
        $route = $deduped;
    }

    // 3) 결재선/기본라인 없으면 admin 1명 자동 매칭
    if (empty($route)) {
        $adm = $pdo->prepare(
            "SELECT e.id, e.name, COALESCE(d.name, '') AS dept_name
             FROM employees e LEFT JOIN departments d ON e.department_id = d.id
             WHERE e.user_role = 'admin' AND e.is_active = 1
               AND (e.employment_status IS NULL OR e.employment_status <> '퇴사')
             ORDER BY e.id LIMIT 1"
        );
        $adm->execute();
        $admin = $adm->fetch(PDO::FETCH_ASSOC);
        if ($admin) {
            $route[] = [
                'employee_id' => (int)$admin['id'],
                'name'        => (string)$admin['name'],
                'dept'        => (string)$admin['dept_name'],
                'role'        => '결재',
            ];
        }
    }

    if (empty($route)) return null;

    // employee_id가 하나도 채워지지 않은 경우 · approveDocument에서 승인자 검증이 불가하므로 실패 반환
    $anyResolved = false;
    foreach ($route as $r) {
        if (!empty($r['employee_id'])) { $anyResolved = true; break; }
    }
    if (!$anyResolved) return null;

    // 마지막 '결재' 역할을 '전결'로 마킹 (협조/참조는 건드리지 않음)
    for ($i = count($route) - 1; $i >= 0; $i--) {
        if ($route[$i]['role'] === '결재') {
            $route[$i]['role'] = '전결';
            break;
        }
    }

    return $route;
}

/**
 * 조직도 체인 해소: 작성자 부서에서 parent_id 따라 올라가며 각 부서의 부서장(head_employee_id)을
 * 수집한다. 작성자 본인/중복/빈 부서장(미지정·퇴사)은 건너뛴다.
 *
 * @param int  $drafterDeptId 시작 부서 id
 * @param int  $maxDepth      DB parent 체인을 올라갈 물리 단계 수
 * @param ?int $excludeEmpId  결과에서 제외할 직원 id (작성자 본인)
 * @param ?array<int, int> $includeDepthIndexes 포함할 parent 체인 인덱스. null이면 모든 단계 포함.
 * @return array<int, array{employee_id: int, name: string, dept: string, role: string}>
 */
function resolveOrgChain(PDO $pdo, int $drafterDeptId, int $maxDepth, ?int $excludeEmpId = null, ?array $includeDepthIndexes = null): array
{
    $route = [];
    $seen = [];
    if ($excludeEmpId) $seen[(int)$excludeEmpId] = true;
    $includeSet = null;
    if (is_array($includeDepthIndexes)) {
        $includeSet = [];
        foreach ($includeDepthIndexes as $idx) {
            $includeSet[(int)$idx] = true;
        }
    }

    $stmt = $pdo->prepare(
        "SELECT d.parent_id, d.head_employee_id,
                e.id AS emp_id, e.name AS emp_name,
                COALESCE(ed.name, d.name, '') AS emp_dept
         FROM departments d
         LEFT JOIN employees e ON e.id = d.head_employee_id AND e.is_active = 1
              AND (e.employment_status IS NULL OR e.employment_status <> '퇴사')
         LEFT JOIN departments ed ON e.department_id = ed.id
         WHERE d.id = ? LIMIT 1"
    );

    $deptId = $drafterDeptId;
    $guard = 0;
    $chainIndex = 0;
    while ($deptId && $chainIndex < $maxDepth && $guard++ < 20) {
        $stmt->execute([$deptId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) break;
        $headId = !empty($row['emp_id']) ? (int)$row['emp_id'] : null;
        $includeLevel = $includeSet === null || isset($includeSet[$chainIndex]);
        if ($includeLevel && $headId !== null && !isset($seen[$headId])) {
            $seen[$headId] = true;
            $route[] = [
                'employee_id' => $headId,
                'name'        => (string)$row['emp_name'],
                'dept'        => (string)$row['emp_dept'],
                'role'        => '결재',
            ];
        }
        $deptId = !empty($row['parent_id']) ? (int)$row['parent_id'] : 0;
        $chainIndex++;
    }
    return $route;
}

function resolveSlotChainIndex(string $slot, array $hierarchy, int $chainCount): ?int
{
    if ($chainCount <= 0) return null;
    $slotMap = $hierarchy['slot_map'] ?? [];
    $levels = $hierarchy['levels'] ?? [];

    if (str_contains($slot, '.')) {
        $semanticKey = $slot;
    } else {
        $semanticKey = $slotMap[$slot] ?? null;
        if ($semanticKey === null) return null;
    }

    $levelKey = str_replace('.head', '', (string)$semanticKey);
    usort($levels, fn($a, $b) => (int)($a['depth'] ?? 0) <=> (int)($b['depth'] ?? 0));

    $company = null;
    $nonCompany = [];
    $targetEnabled = false;
    foreach ($levels as $lv) {
        $key = (string)($lv['key'] ?? '');
        if ($key === $levelKey && !empty($lv['enabled'])) {
            $targetEnabled = true;
        }
        if ($key === 'company') $company = $lv;
        else $nonCompany[] = $lv;
    }
    if (!$targetEnabled) return null;

    $slotLevels = array_reverse($nonCompany);
    if ($company !== null) $slotLevels[] = $company;

    foreach ($slotLevels as $i => $lv) {
        if (($lv['key'] ?? '') === $levelKey) {
            return $i < $chainCount ? $i : null;
        }
    }
    return null;
}

/**
 * 슬롯 → 해당 레벨 부서장 1명 해소 (예외 규칙의 동적 항목용).
 *
 * $slot 은 두 가지 형태를 모두 받는다:
 *  - semantic key ("department.head") — slot_key 필드가 있는 경우 우선 사용
 *  - display label ("부서장") — 레거시 호환. slot_map 경유 변환
 *
 * @return array{employee_id: int, name: string, dept: string, role: string}|null
 */
function resolveSlot(PDO $pdo, string $slot, ?int $drafterDeptId, ?int $excludeEmpId = null): ?array
{
    if (!$drafterDeptId) return null;
    $chain = resolveOrgChain($pdo, (int)$drafterDeptId, 20, $excludeEmpId);
    if (empty($chain)) return null;
    $n = count($chain);

    require_once __DIR__ . '/org_hierarchy.php';
    $hierarchy = getOrgHierarchy();
    $idx = resolveSlotChainIndex($slot, $hierarchy, $n);
    if ($idx === null) {
        error_log('[approval_doc] 결재 슬롯 해소 실패: ' . $slot);
        return null;
    }

    return $chain[$idx] ?? null;
}

/**
 * approval_lines의 slot 항목에 slot_key를 백필하고, head_title 변경 시 표시 라벨을 갱신한다.
 *
 * head_title을 바꾸면 slot_map 키가 달라져서 기존 approval_lines가 해소 불능이 되는 문제를 방지.
 * - slot_key가 없는 레거시 행: oldSlotMap으로 slot_key 추가
 * - slot_key가 있는 행: 해당 level의 현재 head_title로 slot(표시 라벨) 갱신
 *
 * @param array $oldSlotMap 변경 전 slot_map (saveOrgHierarchy 호출 전에 캡처)
 */
function migrateApprovalLineSlotKeys(PDO $pdo, array $newLevels, array $oldSlotMap = []): int
{
    $headByKey = [];
    foreach ($newLevels as $lv) {
        $headByKey[$lv['key']] = $lv['head_title'];
    }

    try {
        $rows = $pdo->query("SELECT id, line_data FROM approval_lines")->fetchAll(PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
        return 0;
    }

    $updateStmt = $pdo->prepare("UPDATE approval_lines SET line_data = ? WHERE id = ?");
    $migrated = 0;

    foreach ($rows as $row) {
        $data = json_decode($row['line_data'] ?? '[]', true);
        if (!is_array($data)) continue;

        $changed = false;
        foreach ($data as &$step) {
            if (($step['type'] ?? '') !== 'slot') continue;

            if (empty($step['slot_key'])) {
                $label = (string)($step['slot'] ?? '');
                $semanticKey = $oldSlotMap[$label] ?? null;
                if ($semanticKey) {
                    $step['slot_key'] = $semanticKey;
                    $levelKey = str_replace('.head', '', $semanticKey);
                    if (isset($headByKey[$levelKey])) {
                        $step['slot'] = $headByKey[$levelKey];
                    }
                    $changed = true;
                }
            } else {
                $levelKey = str_replace('.head', '', (string)$step['slot_key']);
                if (isset($headByKey[$levelKey]) && ($step['slot'] ?? '') !== $headByKey[$levelKey]) {
                    $step['slot'] = $headByKey[$levelKey];
                    $changed = true;
                }
            }
        }
        unset($step);

        if ($changed) {
            $updateStmt->execute([json_encode($data, JSON_UNESCAPED_UNICODE), $row['id']]);
            $migrated++;
        }
    }

    return $migrated;
}
