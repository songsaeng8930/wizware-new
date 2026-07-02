<?php
$__viewMode = ($_GET['mode'] ?? '') === 'view';
$pageTitle = '조직도';
$currentPage = $__viewMode ? 'company' : 'hr';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../config/database.php';
$__orgRole = function_exists('getCurrentUserRole') ? (getCurrentUserRole() ?: 'user') : 'user';
$__orgCanEdit = $__viewMode ? false : in_array($__orgRole, ['admin', 'manager'], true);
// DB에서 조직도 데이터 로드 시도
$orgData = null;
$useDB = false;

$pdo = getDBConnection();
if ($pdo) {
    try {
        // departments 테이블 존재 여부 확인
        $check = $pdo->query("SHOW TABLES LIKE 'departments'");
        if ($check->rowCount() > 0) {
            // 부서 데이터
            $deptStmt = $pdo->query('
                SELECT d.id, d.parent_id, d.name, d.code, d.head_employee_id, d.sort_order
                FROM departments d WHERE d.is_active = 1
                ORDER BY d.sort_order, d.name
            ');
            $departments = $deptStmt->fetchAll();

            // 직원 데이터 · sort_order 우선, 미지정(=0)은 직급→이름 fallback
            // (migrate_employees_sort_order.sql 로 초깃값 채워짐)
            $empStmt = $pdo->query("
                SELECT e.id, e.department_id, e.name, e.position, e.title, e.email, e.phone, e.profile_image, e.sort_order
                FROM employees e
                LEFT JOIN hr_ranks _tr ON _tr.id = e.rank_id
                WHERE e.is_active = 1
                ORDER BY e.department_id,
                         CASE WHEN e.sort_order > 0 THEN 0 ELSE 1 END,
                         e.sort_order,
                         COALESCE(_tr.sort_order, 999),
                         e.name
            ");
            $employees = $empStmt->fetchAll();

            if (!empty($departments)) {
                $useDB = true;
                $orgData = json_encode([
                    'departments' => $departments,
                    'employees' => $employees,
                ], JSON_UNESCAPED_UNICODE);
            }
        }
    } catch (PDOException $e) {
        // 테이블이 없으면 샘플 데이터 사용
    }
}

// DB 미연결 시 빈 데이터 (직원/부서 데이터는 DB에서만 로드)
if (!$useDB) {
    $orgData = json_encode(['departments' => [], 'employees' => []], JSON_UNESCAPED_UNICODE);
}
?>

<!-- 메인 컨텐츠 영역 -->
<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6">

        <!-- 헤더 영역 -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-lg font-bold text-slate-100">조직도</h2>
                <p class="text-sm text-slate-400 mt-1">회사의 <?= htmlspecialchars(getOrgLabel('department')) ?> 구조와 소속 인원을 확인할 수 있습니다.</p>
            </div>
            <div class="flex items-center gap-2">
                <!-- 뷰 전환 버튼 -->
                <div class="flex bg-slate-800 rounded-lg p-0.5">
                    <button id="btnOrgView" class="view-btn active px-3 py-1.5 text-sm font-medium rounded-md transition-colors" data-view="org">
                        <i data-lucide="network" class="w-4 h-4"></i> 조직도
                    </button>
                    <button id="btnListView" class="view-btn px-3 py-1.5 text-sm font-medium rounded-md transition-colors text-slate-400" data-view="list">
                        <i data-lucide="table-2" class="w-4 h-4"></i> 리스트뷰
                    </button>
                </div>
                <?php if ($__orgCanEdit): ?>
                <button id="btnAddDept" class="px-3 py-1.5 text-sm text-white bg-primary rounded-lg hover:opacity-90 transition-colors">
                    <i data-lucide="folder-plus" class="w-4 h-4"></i> <?= htmlspecialchars(getOrgLabel('department')) ?> 추가
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- 인라인 검색바 -->
        <div class="flex items-center gap-3 mb-5">
            <div class="relative flex-1 max-w-xs">
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
                <input type="text" id="searchEmployee" placeholder="이름으로 검색" autocomplete="off"
                       class="w-full pl-9 pr-8 py-2 text-sm bg-slate-900 border border-slate-700 rounded-lg text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-gray-300/40 focus:border-gray-300">
                <button id="btnSearchClear" type="button" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 hidden" aria-label="검색 초기화">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </button>
            </div>
            <span class="text-sm text-slate-400">총 <span id="totalCount" class="font-semibold text-gray-700">0</span>명</span>
        </div>

        <!-- 조직도 통합 뷰 (차트 + 선택 부서 상세) -->
        <div id="orgViewContainer" class="grid grid-cols-1 lg:grid-cols-10 gap-3">
            <!-- 좌측: 조직 차트 (70%) -->
            <div class="lg:col-span-7 bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
                <div class="flex items-center justify-between px-4 py-2.5 border-b border-slate-800 bg-slate-950/50">
                    <div class="flex items-center gap-2 text-xs text-slate-400">
                        <i data-lucide="git-branch" class="w-3.5 h-3.5"></i>
                        <span>카드를 클릭하면 상세가 표시됩니다<?php if ($__orgCanEdit): ?> · 직원을 드래그해 <?= htmlspecialchars(getOrgLabel('department')) ?>로 이동<?php endif; ?></span>
                    </div>
                    <div class="flex items-center gap-1">
                        <!-- 줌 컨트롤: 차트가 넓을 때 한 화면에 맞춤 -->
                        <div class="flex items-center gap-0.5 mr-1">
                            <button id="chartZoomOut" title="축소" aria-label="축소" class="btn btn-secondary btn-xs w-7 h-7">
                                <i data-lucide="minus" class="w-3.5 h-3.5"></i>
                            </button>
                            <button id="chartZoomReset" title="화면에 맞춤" aria-label="화면에 맞춤" class="btn btn-secondary btn-xs h-7 text-[11px] tabular-nums" style="min-width:46px;">100%</button>
                            <button id="chartZoomIn" title="확대" aria-label="확대" class="btn btn-secondary btn-xs w-7 h-7">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                        <button id="chartExpandAll" class="btn btn-secondary btn-xs">전체 펼치기</button>
                        <button id="chartCollapseAll" class="btn btn-secondary btn-xs">전체 접기</button>
                    </div>
                </div>
                <div id="chartScrollArea" style="overflow-x:auto; overflow-y:visible; text-align:center;">
                    <div id="orgChart" style="padding:24px 10px 36px; display:inline-block;"></div>
                </div>
            </div>

            <!-- 우측: 선택된 부서 상세 패널 (30%) -->
            <aside id="orgDetailPanel" class="lg:col-span-3 bg-slate-900 rounded-xl border border-slate-800 overflow-hidden flex flex-col self-start lg:sticky lg:top-20 lg:max-h-[calc(100vh-6rem)]">
                <div id="detailPanelHeader" class="px-5 py-4 bg-slate-950/50 border-b border-slate-800">
                    <!-- 헤더는 renderDetailPanel()에서 주입 -->
                </div>
                <div id="detailPanelBody" class="flex-1 overflow-y-auto p-5 space-y-5">
                    <!-- 본문도 JS가 주입 -->
                </div>
            </aside>
        </div>

        <!-- 리스트뷰 컨테이너 -->
        <div id="listViewContainer" class="hidden">
            <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
                <table class="w-full text-sm emp-table">
                    <thead>
                        <tr class="bg-slate-950 border-b border-slate-800">
                            <th class="px-4 py-3 text-center text-slate-300 font-medium">이름</th>
                            <th class="px-4 py-3 text-center text-slate-300 font-medium"><?= htmlspecialchars(getOrgLabel('department')) ?></th>
                            <th class="px-4 py-3 text-center text-slate-300 font-medium">직급</th>
                            <th class="px-4 py-3 text-center text-slate-300 font-medium">직책</th>
                            <th class="px-4 py-3 text-center text-slate-300 font-medium">이메일</th>
                            <th class="px-4 py-3 text-center text-slate-300 font-medium">연락처</th>
                        </tr>
                    </thead>
                    <tbody id="listViewBody"></tbody>
                </table>
            </div>
        </div>

        <style>
        /* ── 조직 차트 (간결화) ── ID 셀렉터로 우선순위 확보 */
        #orgChart { font-size: 13px; text-align: center; color: var(--zm-text-default); }
        #orgChart ul { display: flex !important; justify-content: center; list-style: none; margin: 0; padding: 24px 0 0 0; position: relative; z-index: 0; }
        #orgChart > ul { padding-top: 0; }
        #orgChart li { display: flex !important; flex-direction: column; align-items: center; position: relative; z-index: 0; padding: 24px 10px 0 10px; }
        #orgChart > ul > li { padding-top: 0; }

        /* 연결선 · 카드/토글보다 항상 아래 레이어에 두고 hover hit-test 에서 제외 */
        #orgChart li::before { content: ''; position: absolute; top: 0; left: 50%; width: 0; height: 24px; border-left: 1.5px solid var(--zm-border) !important; z-index: 0; pointer-events: none; }
        #orgChart > ul > li::before { display: none; }
        #orgChart li::after { content: ''; position: absolute; top: 0; border-top: 1.5px solid var(--zm-border) !important; z-index: 0; pointer-events: none; }
        #orgChart li:first-child::after { left: 50%; width: 50%; }
        #orgChart li:last-child::after { left: 0; width: 50%; }
        #orgChart li:not(:first-child):not(:last-child)::after { left: 0; width: 100%; }
        #orgChart li:only-child::after { display: none; }
        #orgChart ul::before { content: ''; position: absolute; top: 0; left: 50%; width: 0; height: 24px; border-left: 1.5px solid var(--zm-border) !important; z-index: 0; pointer-events: none; }
        #orgChart > ul::before { display: none; }

        /* 부서 노드 · 세로 스택 레이아웃 (가로폭 최소화) */
        #orgChart .chart-node {
            position: relative;
            display: inline-flex; flex-direction: column; align-items: center;
            gap: 4px;
            min-width: 120px; max-width: 170px;
            padding: 10px 12px 10px;
            background: var(--zm-surface-1);
            border: 1px solid var(--zm-border) !important;
            border-radius: 10px !important;
            cursor: pointer;
            transition: background-color 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
            box-shadow: 0 1px 0 rgba(255,255,255,0.02) inset, 0 1px 3px rgba(0,0,0,0.25);
            text-align: center;
            z-index: 2;
        }
        #orgChart .chart-node:hover {
            border-color: var(--zm-text-subtle) !important;
            background: var(--zm-surface-2);
            transform: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.35);
            z-index: 4;
        }
        /* 토글 버튼 주변의 선 영역까지 카드 hover 범위로 흡수해 경계 깜빡임 방지 */
        #orgChart .chart-node__hover-bridge {
            position: absolute;
            left: 10px;
            right: 10px;
            bottom: -36px;
            height: 36px;
            z-index: 3;
            pointer-events: auto;
            background: transparent;
        }
        /* 선택 상태 */
        #orgChart .chart-node--selected {
            border-color: var(--zm-text-strong) !important;
            background: rgba(0,0,0,0.06);
            box-shadow: 0 0 0 1px rgba(0,0,0,0.08), 0 6px 20px rgba(0,0,0,0.08);
        }

        /* 루트: 살짝 더 두드러지게 */
        #orgChart .chart-node--root {
            background: linear-gradient(135deg, var(--zm-primary-bg, rgba(79,106,255,0.18)), rgba(112,144,255,0.06));
            border-color: var(--zm-primary) !important;
        }

        /* 부서장 아바타 */
        #orgChart .chart-head-avatars {
            display: flex; gap: -4px; margin-bottom: 2px; justify-content: center;
        }
        #orgChart .chart-head-avatar {
            width: 30px; height: 30px; border-radius: 50%;
            object-fit: cover; border: 2px solid var(--zm-surface-1);
            flex-shrink: 0;
        }
        #orgChart .chart-head-avatar + .chart-head-avatar { margin-left: -8px; }
        #orgChart .chart-head-avatar--init {
            display: flex; align-items: center; justify-content: center;
            background: var(--zm-primary-bg, rgba(79,106,255,0.18)); color: var(--zm-primary-fg);
            font-size: 13px; font-weight: 700;
        }
        #orgChart .chart-head-avatar--empty {
            display: flex; align-items: center; justify-content: center;
            background: var(--zm-surface-2); color: var(--zm-text-muted);
        }

        /* 본문 */
        #orgChart .chart-node__content { width: 100%; min-width: 0; }
        #orgChart .chart-node__name {
            font-size: 14px; font-weight: 600; color: var(--zm-text-strong);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        #orgChart .chart-node__meta {
            font-size: 12px; color: var(--zm-text-muted); margin-top: 1px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        /* 카운트 · 하단 중앙 pill */
        #orgChart .chart-node__count {
            display: inline-flex; align-items: center; gap: 3px;
            margin-top: 4px;
            font-size: 12px; font-weight: 600; color: var(--zm-primary-fg);
            background: rgba(0,0,0,0.06); padding: 1px 9px; border-radius: 20px;
            line-height: 1.5;
        }

        /* 접기/펼치기 토글 */
        #orgChart .chart-node__toggle {
            position: absolute; bottom: -12px; left: 50%; transform: translateX(-50%);
            width: 22px; height: 22px; border-radius: 50% !important;
            background: var(--zm-surface-1); border: 1.5px solid var(--zm-border) !important;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; z-index: 8; font-size: 13px; font-weight: 700;
            color: var(--zm-text-muted); line-height: 1; transition: all 0.15s ease;
            user-select: none; -webkit-user-select: none;
        }
        #orgChart .chart-node__toggle:hover { border-color: var(--zm-surface-3) !important; color: var(--zm-text-default); background: var(--zm-surface-2); }
        #orgChart .chart-branch--collapsed > ul { display: none !important; }
        #orgChart .chart-branch--collapsed > .chart-node .chart-node__toggle { border-color: var(--zm-text-strong) !important; background: var(--zm-surface-2); color: var(--zm-text-strong); }

        /* 필터 하이라이트 */
        #orgChart .chart-node--highlighted { border-color: var(--zm-text-strong) !important; box-shadow: 0 0 0 2px rgba(0,0,0,0.08); }
        #orgChart .chart-node--dimmed { opacity: 0.35; }
        /* ── 상세 패널 · 직원 줄 리스트 ── */
        .org-detail-emp {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 10px; border-radius: 10px;
            background: var(--zm-surface-2);
            transition: background 0.15s, opacity 0.15s;
            cursor: pointer;
        }
        .org-detail-emp:hover { background: rgba(0,0,0,0.08); }
        .org-detail-emp[draggable="true"]:hover .org-detail-emp__drag { opacity: 1; }
        .org-detail-emp__drag {
            flex-shrink: 0;
            color: var(--zm-text-subtle); opacity: 0.4;
            cursor: grab; transition: opacity 0.15s;
            display: flex; align-items: center; padding: 2px;
        }
        .org-detail-emp__drag:active { cursor: grabbing; }
        .org-detail-emp.is-dragging { opacity: 0.4; }

        /* 드래그 중: 모든 드롭 가능한 부서 노드를 은은하게 강조 */
        body.is-dragging-emp #orgChart .chart-node {
            outline: 1.5px dashed rgba(71,85,105,0.6); outline-offset: 2px;
        }
        /* 드래그 중 출발지 부서 노드는 제외 */
        body.is-dragging-emp #orgChart .chart-node--drag-source {
            outline: 1.5px dashed rgba(239,68,68,0.4); outline-offset: 2px; opacity: 0.5;
        }
        /* 드롭 타겟 호버 */
        #orgChart .chart-node--drop-hover {
            outline: 2px solid var(--zm-primary) !important; outline-offset: 2px;
            background: var(--zm-primary-bg, rgba(79,106,255,0.18)) !important;
            transform: translateY(-2px);
        }

        /* ── 같은 부서 내 위아래 순서 변경용 드롭 인디케이터 (결재선 패턴) ── */
        #empList { position: relative; }
        .emp-drop-indicator {
            position: absolute;
            left: 12px;
            right: 12px;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--zm-primary) 8%, var(--zm-primary) 92%, transparent);
            border-radius: 1px;
            pointer-events: none;
            opacity: 0;
            transform: translateY(-50%);
            transition: top 90ms ease-out, opacity 90ms ease-out;
            z-index: 5;
        }
        .emp-drop-indicator::before {
            content: '';
            position: absolute;
            left: 0; top: 50%;
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--zm-primary);
            transform: translate(-50%, -50%);
        }
        .emp-drop-indicator::after { content: none; }
        .emp-drop-indicator.show { opacity: 1; }
        #empList.dragging-inside { background: rgba(0,0,0,0.03); border-radius: 12px; }

        /* ── 부서 트리 DnD ── */
        #orgTreePanel .tree-row {
            transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        #orgTreePanel .tree-row:hover:not(.dept-dragging) {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
        }
        #orgTreePanel .tree-row.dept-dragging {
            opacity: 0.15 !important;
            transform: scale(0.92);
            filter: grayscale(0.5);
            pointer-events: none;
        }
        #deptModal.is-dept-dragging .tree-row:not(.dept-dragging):not(.drop-ref):not(.drop-container) {
            opacity: 0.45;
        }
        #deptModal.is-dept-dragging .tree-row.drop-ref,
        #deptModal.is-dept-dragging .tree-row.drop-container {
            opacity: 1 !important;
        }

        /* 레퍼런스 카드: 형제 모드 — 위치 기준점 */
        #orgTreePanel .tree-row.drop-ref {
            box-shadow: 0 0 0 1px rgba(79,106,255,0.25) !important;
        }
        /* 컨테이너 모드: 하위로 넣을 때 카드 강조 */
        #orgTreePanel .tree-row.drop-container {
            box-shadow: 0 0 0 1.5px rgba(79,106,255,0.45), 0 0 12px rgba(79,106,255,0.08) !important;
            background-image: linear-gradient(rgba(79,106,255,0.08), rgba(79,106,255,0.08)) !important;
        }
        /* 삽입 위치 인디케이터: 빈 슬롯 */
        .dept-drop-indicator {
            height: 0; opacity: 0; overflow: hidden;
            width: 208px;
            border-radius: 12px;
            transition: height 100ms ease, opacity 80ms ease, margin 80ms ease, margin-left 120ms ease;
            pointer-events: none;
            border: 1.5px dashed transparent;
        }
        .dept-drop-indicator.show {
            height: 36px;
            opacity: 1;
            margin: 2px 0;
            border-color: rgba(79,106,255,0.22);
        }
        /* 이동 성공 피드백 */
        @keyframes dept-drop-success {
            0%   { box-shadow: 0 0 0 0 rgba(79,106,255,0.6); }
            40%  { box-shadow: 0 0 0 4px rgba(79,106,255,0.3); }
            100% { box-shadow: 0 0 0 0 transparent; }
        }
        #orgTreePanel .tree-row.drop-success {
            animation: dept-drop-success 0.7s ease-out;
        }
        /* Pointer DnD 고스트 카드 */
        .dept-ghost {
            position: fixed;
            z-index: 9999;
            pointer-events: none;
            display: flex;
            align-items: center;
            padding: 0 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            color: #fff;
            background: rgba(56, 189, 248, 0.85);
            opacity: 0.92;
            transform: scale(0.97) rotate(-1deg);
            box-shadow: 0 10px 28px rgba(0,0,0,0.2);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            will-change: transform, left, top;
        }
        #orgTreePanel .tree-row { touch-action: none; }

        .org-detail-emp__avatar {
            flex-shrink: 0; width: 36px; height: 36px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; font-weight: 700;
        }
        .org-detail-emp__info { flex: 1; min-width: 0; }
        .org-detail-emp__name {
            font-size: 14px; font-weight: 600; color: var(--zm-text-strong);
            display: flex; align-items: center; gap: 6px;
        }
        .org-detail-emp__meta {
            font-size: 12px; color: var(--zm-text-muted); margin-top: 2px;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .org-head-chip {
            display: inline-flex; align-items: center; justify-content: center;
            width: 18px; height: 18px; border-radius: 50%;
            background: linear-gradient(135deg, var(--zm-primary), #7090ff);
            color: white; flex-shrink: 0;
        }
        .org-head-chip svg { width: 10px; height: 10px; }
        .org-pos-badge {
            display: inline-flex; align-items: center;
            padding: 3px 8px; border-radius: 6px;
            font-size: 11px; font-weight: 700;
            letter-spacing: 0.02em;
        }

        .org-head-btn {
            margin-left: auto; flex-shrink: 0;
            display: inline-flex; align-items: center; gap: 5px;
            padding: 5px 12px; border-radius: 8px;
            font-size: 11px; font-weight: 600;
            transition: all 0.2s ease;
            cursor: pointer; border: none; outline: none;
            white-space: nowrap;
        }
        .org-head-btn--set {
            color: var(--zm-text-muted);
            background: var(--zm-surface-3, rgba(100,116,139,0.08));
        }
        .org-head-btn--set:hover {
            color: var(--zm-text-default);
            background: rgba(0,0,0,0.08);
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transform: translateY(-1px);
        }
        .org-head-btn--unset {
            color: var(--zm-text-muted);
            background: var(--zm-surface-3, rgba(100,116,139,0.08));
        }
        .org-head-btn--unset:hover {
            color: #ef4444;
            background: rgba(239,68,68,0.08);
        }

        .org-subdept-chip {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 10px; border-radius: 8px;
            background: var(--zm-surface-2);
            border: 1px solid var(--zm-border) !important;
            font-size: 12px; color: var(--zm-text-default); cursor: pointer;
            transition: all 0.15s;
        }
        .org-subdept-chip:hover {
            border-color: rgba(0,0,0,0.12) !important; background: rgba(0,0,0,0.08);
            color: var(--zm-primary-fg);
        }

        @media (max-width: 767px) {
            #orgChart .chart-node { min-width: 140px; max-width: 180px; padding: 8px 10px; }
            #orgChart .chart-node__name { font-size: 13px; }
            #orgChart li { padding: 20px 6px 0 6px; }
        }
        </style>

    </main>
</div>

<!-- 직원 상세 모달 -->
<div id="employeeModal" class="fixed inset-0 bg-black/40 z-[60] hidden flex items-center justify-center" onclick="if(event.target===this)document.getElementById('employeeModal').classList.add('hidden')">
    <div class="bg-slate-900 rounded-2xl shadow-xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-primary px-6 py-4 flex items-center justify-between">
            <h3 class="text-white font-semibold">직원 정보</h3>
            <button id="closeModal" type="button" class="text-white/80 hover:text-white transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <div id="modalContent" class="p-6"></div>
    </div>
</div>

<?php if ($__orgCanEdit): ?>
<!-- 부서 추가/수정 모달 (두 패널) -->
<div id="deptModal" class="fixed inset-0 bg-black/50 z-[60] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl flex flex-col" style="height:min(88vh,680px)">

        <!-- 헤더 -->
        <div class="bg-primary px-6 py-4 flex items-center justify-between rounded-t-2xl flex-shrink-0">
            <h3 id="deptModalTitle" class="text-white font-semibold text-base">부서 추가</h3>
            <button id="closeDeptModal" type="button" class="text-white/70 hover:text-white transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <!-- 본문: 좌(트리) + 우(폼) -->
        <div class="flex flex-1 min-h-0">

            <!-- ── 왼쪽: 조직도 트리 + 상위 조직 ── -->
            <div class="w-96 flex-shrink-0 border-r border-gray-200 flex flex-col bg-slate-50 rounded-bl-2xl min-h-0 overflow-hidden">
                <div class="px-4 pt-3 pb-2 border-b border-gray-100 flex-shrink-0">
                    <div class="relative">
                        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        <input id="deptTreeSearch" type="text" placeholder="조직 검색..." autocomplete="off"
                               class="w-full pl-8 pr-3 py-1.5 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-300/30 focus:border-gray-300 bg-white">
                    </div>
                </div>
                <div id="orgTreePanel" class="flex-1 overflow-auto p-3 space-y-1.5">
                    <!-- JS로 채움 -->
                </div>
                <div class="px-4 py-2 border-t border-gray-100 flex-shrink-0">
                    <p id="deptTreeHint" class="text-[11px] text-gray-400 leading-tight">클릭으로 부서 전환 · 드래그로 위치 이동</p>
                </div>
            </div>

            <!-- ── 오른쪽: 편집 폼 + 조직 상세 ── -->
            <div class="flex-1 flex flex-col min-w-0">
                <form id="deptForm" class="flex-1 overflow-y-auto px-7 pt-1 pb-3 space-y-5">
                    <input type="hidden" id="deptId"        name="id">
                    <input type="hidden" id="deptParent"    name="parent_id">
                    <input type="hidden" id="deptCode"      name="code"       value="">
                    <input type="hidden" id="deptSortOrder" name="sort_order" value="0">

                    <!-- 부서 정보 -->
                    <div>
                        <h4 class="text-sm font-bold text-gray-800 mb-2.5"><?= htmlspecialchars(getOrgLabel('department')) ?> 정보</h4>
                        <div class="border border-gray-200 rounded-xl bg-white overflow-hidden">
                            <div class="p-4">
                                <label class="block text-xs font-medium text-gray-500 mb-1.5"><?= htmlspecialchars(getOrgLabel('department')) ?>명 <span class="text-red-500">*</span></label>
                                <input type="text" id="deptName" name="name" required placeholder="예) 개발1팀"
                                       class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-300/30 focus:border-gray-300">
                            </div>
                            <div id="deptPathRow" class="border-t border-gray-100 p-4 hidden">
                                <p class="text-xs font-medium text-gray-500 mb-1.5">조직 경로</p>
                                <div id="deptDetailPath" class="text-sm text-gray-700"></div>
                            </div>
                        </div>
                    </div>

                    <!-- 상위 부서 -->
                    <div>
                        <h4 class="text-sm font-bold text-gray-800 mb-2.5">상위 부서</h4>
                        <div class="border border-gray-200 rounded-xl bg-white overflow-hidden">
                            <div class="p-4">
                                <div id="deptParentDisplay" class="flex items-center gap-2 min-h-[2rem]">
                                    <span class="text-sm text-gray-400">좌측 트리에서 상위 부서를 클릭하세요</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 조직 유형 -->
                    <div>
                        <h4 class="text-sm font-bold text-gray-800 mb-2.5">조직 유형</h4>
                        <div class="border border-gray-200 rounded-xl bg-white overflow-hidden">
                            <div class="p-4">
                                <div id="deptLevelDisplay" class="text-sm text-gray-400">상위 부서를 선택하면 자동 결정됩니다</div>
                            </div>
                        </div>
                    </div>

                    <!-- 구성원 배치 (신규 생성 시) -->
                    <div id="deptMemberAssign" class="hidden">
                        <h4 class="text-sm font-bold text-gray-800 mb-2.5">구성원 배치</h4>
                        <div class="border border-gray-200 rounded-xl bg-white overflow-hidden">
                            <div class="px-4 pt-3 pb-2">
                                <div class="relative">
                                    <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                                    <input id="memberSearchInput" type="text" placeholder="직원 이름으로 검색..." autocomplete="off"
                                           class="w-full pl-8 pr-3 py-1.5 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-300/30 focus:border-gray-300 bg-white">
                                </div>
                            </div>
                            <div class="px-4 pb-1">
                                <p id="memberSelectedCount" class="text-[11px] text-gray-400">0명 선택됨</p>
                            </div>
                            <div id="memberCheckboxList" class="max-h-52 overflow-y-auto px-2 pb-3 space-y-0.5">
                                <!-- JS로 채움 -->
                            </div>
                        </div>
                    </div>

                    <!-- 조직 상세 정보 (수정 모드에서만 표시) -->
                    <div id="deptDetailInfo" class="hidden space-y-6">

                        <!-- 구성원 -->
                        <div>
                            <h4 class="text-sm font-bold text-gray-800 mb-2.5">구성원</h4>
                            <div class="border border-gray-200 rounded-xl bg-white overflow-hidden">
                                <div class="p-4">
                                    <p class="text-xs font-medium text-gray-500 mb-2"><?= htmlspecialchars(getOrgHeadTitle('department')) ?> <span class="font-normal text-gray-400">(복수 선택 가능)</span></p>
                                    <div id="deptHeadCheckboxes" class="max-h-40 overflow-y-auto space-y-0.5"></div>
                                </div>
                                <div class="border-t border-gray-100 p-4">
                                    <p class="text-xs font-medium text-gray-500 mb-1.5">직속 직원</p>
                                    <div id="deptDetailEmployees" class="space-y-1"></div>
                                </div>
                            </div>
                        </div>

                        <!-- 하위 조직 -->
                        <div id="deptDetailSubWrap">
                            <h4 class="text-sm font-bold text-gray-800 mb-2.5">하위 조직</h4>
                            <div class="border border-gray-200 rounded-xl bg-white p-4">
                                <div id="deptDetailSubs" class="flex flex-wrap gap-1.5"></div>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- 버튼 (스크롤 영역 밖) -->
                <div class="flex items-center justify-between px-7 py-4 border-t border-gray-100 flex-shrink-0 rounded-br-2xl bg-white">
                    <button type="button" id="deleteDeptBtn" class="hidden px-4 py-2 text-sm text-red-600 border border-red-200 rounded-xl hover:bg-red-50 transition-colors">삭제</button>
                    <div class="flex gap-2 ml-auto">
                        <button type="button" id="cancelDeptForm" class="btn btn-secondary">취소</button>
                        <button type="submit" form="deptForm" class="px-6 py-2 text-sm text-white bg-primary rounded-xl hover:opacity-90 transition-colors font-semibold">저장</button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<?php endif; ?>

<script>
window.ORG_PAGE_CONFIG = {
    rawData: <?= $orgData ?>,
    basePath: '<?= $basePath ?>',
    useDB: <?= $useDB ? 'true' : 'false' ?>,
    canEdit: <?= $__orgCanEdit ? 'true' : 'false' ?>
};
</script>
<script src="<?= $basePath ?>/assets/js/organization.js">
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
