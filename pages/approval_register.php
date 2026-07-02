<?php
$pageTitle = '기안작성';
$currentPage = 'approval';
require_once __DIR__ . '/../config/database.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$pdo = getDBConnection();
$hasDB = false;
$docTypes = [];
$forms = [];

if ($pdo) {
    try {
        $pdo->query('SELECT 1 FROM approval_forms LIMIT 1');
        $hasDB = true;
        $docTypes = $pdo->query("SELECT DISTINCT doc_type FROM approval_forms WHERE is_active = 1 ORDER BY doc_type")->fetchAll(PDO::FETCH_COLUMN);
        $forms = $pdo->query("SELECT id, doc_type, title, content_template FROM approval_forms WHERE is_active = 1 ORDER BY doc_type, title")->fetchAll();
    } catch (PDOException $e) { $hasDB = false; }
}
if (!$hasDB) {
    $docTypes = ['품의서','휴가신청서','출장신청서','외근신청서','야근신청서','법인카드 지출','경비청구서'];
}

// 결재선 로드
$sampleLines = [
    ['id' => 1, 'name' => '개발1팀 기본결재선', 'route' => [
        ['role' => '결재', 'id' => 8, 'name' => '강부장', 'position' => '부장'],
        ['role' => '결재', 'id' => 3, 'name' => '박이사', 'position' => '이사'],
        ['role' => '전결', 'id' => 1, 'name' => '김대표', 'position' => '대표이사'],
    ]],
    ['id' => 2, 'name' => '경영지원 결재선', 'route' => [
        ['role' => '결재', 'id' => 5, 'name' => '정부장', 'position' => '부장'],
        ['role' => '전결', 'id' => 2, 'name' => '이이사', 'position' => '이사'],
    ]],
];

if ($hasDB) {
    try {
        $myId = (int)($_SESSION['user_id'] ?? 0);
        $lineStmt = $pdo->prepare('SELECT id, name, doc_type, scope, line_data FROM approval_lines WHERE scope = "global" OR created_by = ? ORDER BY scope DESC, id');
        $lineStmt->execute([$myId]);
        $lineRows = $lineStmt->fetchAll();
        if ($lineRows) {
            $sampleLines = [];
            foreach ($lineRows as $lr) {
                $lineData = json_decode($lr['line_data'], true) ?: [];
                $label = $lr['name'];
                if (($lr['scope'] ?? 'global') === 'personal') $label = '⭐ ' . $label;
                $sampleLines[] = ['id' => (int)$lr['id'], 'name' => $label, 'doc_type' => $lr['doc_type'] ?? '', 'route' => $lineData];
            }
        }
    } catch (PDOException $e) {}
}

// 직원 목록 (결재자 검색용)
$employees = [];
if ($hasDB) {
    try {
        $employees = $pdo->query("
            SELECT e.id, e.name, e.position, e.title, e.department_id, d.name AS dept
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            WHERE e.is_active = 1 AND (e.employment_status IS NULL OR e.employment_status <> '퇴사')
            ORDER BY d.sort_order, d.name,
                     FIELD(e.position, '대표이사','이사','부장','차장','과장','대리','주임','사원','인턴'),
                     e.name
        ")->fetchAll();
    } catch (PDOException $e) {}
}

$deptTree = [];
if ($pdo) {
    try {
        $deptTree = $pdo->query("SELECT id, CAST(parent_id AS UNSIGNED) as parent_id, name, sort_order FROM departments WHERE is_active = 1 ORDER BY sort_order, name")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}

$user = getCurrentUser();
$drafterName = $user['name'] ?? '';
$drafterDept = '';
if ($hasDB && !empty($user['department_id'])) {
    $deptStmt = $pdo->prepare('SELECT name FROM departments WHERE id = ?');
    $deptStmt->execute([$user['department_id']]);
    $drafterDept = $deptStmt->fetchColumn() ?: '';
}
$draftDate = date('Y-m-d');
$initType = isset($_GET['type']) ? trim($_GET['type']) : '';
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6 min-h-screen bg-slate-950 max-w-[1100px]">
        <!-- 헤더 -->
        <div class="flex items-center gap-3 mb-6">
            <button onclick="location.href='approval_draft.php'" class="p-1.5 rounded-lg hover:bg-slate-700 transition-colors" title="목록으로">
                <i data-lucide="arrow-left" class="w-5 h-5 text-slate-300"></i>
            </button>
            <h2 class="text-lg font-bold text-slate-100">기안작성</h2>
        </div>

        <!-- 문서정보 -->
        <div class="bg-slate-900 rounded-xl border border-slate-800 mb-5 overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-800 bg-slate-950">
                <span class="form-section-title">문서정보</span>
            </div>
            <table class="form-table">
                <tr>
                    <th class="form-th w-[120px]">문서종류 <span class="text-amber-500">*</span></th>
                    <td class="form-td">
                        <select id="docType" class="reg-select max-w-[240px]" onchange="onDocTypeChange()">
                            <option value="">선택</option>
                            <?php foreach ($docTypes as $dt): ?>
                            <option value="<?= htmlspecialchars($dt) ?>"><?= htmlspecialchars($dt) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <th class="form-th w-[120px]">기안자</th>
                    <td class="form-td">
                        <span class="text-sm text-slate-100"><?= htmlspecialchars($drafterName) ?></span>
                    </td>
                </tr>
                <tr>
                    <th class="form-th">기안부서</th>
                    <td class="form-td">
                        <span class="text-sm text-slate-100"><?= htmlspecialchars($drafterDept) ?></span>
                    </td>
                    <th class="form-th">기안일</th>
                    <td class="form-td">
                        <span class="text-sm text-slate-100"><?= date('Y.m.d') ?></span>
                    </td>
                </tr>
            </table>
        </div>

        <!-- 결재선 -->
        <div class="bg-slate-900 rounded-xl border border-slate-800 mb-5">
            <div class="px-5 py-3 border-b border-slate-800 bg-slate-950 rounded-t-xl flex items-center justify-between">
                <div class="flex items-baseline gap-2">
                    <span class="form-section-title">결재선</span>
                    <span id="autoRouteHint" class="hidden text-[11px] text-slate-400" style="position:relative;top:-1px">자동 지정 · 조직도 기준</span>
                    <span id="routeChangedBadge" class="hidden inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-amber-100 text-amber-700">
                        <i data-lucide="pencil" class="w-3 h-3"></i> 직접 수정됨
                    </span>
                </div>
                <div id="routeActions" class="hidden flex items-center gap-2">
                    <button type="button" onclick="restoreAutoRoute()" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[12px] text-slate-400 hover:text-slate-200 hover:bg-slate-800 transition-colors">
                        <i data-lucide="undo-2" class="w-3 h-3"></i> 되돌리기
                    </button>
                    <button type="button" onclick="saveAsApprovalLine()" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[12px] text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
                        <i data-lucide="bookmark" class="w-3 h-3"></i> 이 결재선 저장
                    </button>
                </div>
            </div>
            <div class="p-5">
                <div class="flex gap-5">
                    <!-- 왼쪽: 결재자 검색 -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 flex-wrap mb-3">
                            <div class="relative flex-1 min-w-[200px]">
                                <i data-lucide="search" class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none z-10"></i>
                                <input type="text" id="approverSearch" class="w-full rounded-lg border border-gray-300 bg-white text-sm text-gray-800 placeholder-gray-400 pl-8 pr-3 py-2 focus:outline-none focus:border-gray-300 focus:ring-2 focus:ring-gray-300/10" placeholder="이름, 부서, 직책으로 검색" autocomplete="off" oninput="renderApproverCards()">
                            </div>
                            <div id="lineSelectRow" class="hidden">
                                <select id="approvalLineSelect" class="reg-select !py-2 !text-sm" onchange="onLineChange()">
                                    <option value="">기본 결재선</option>
                                </select>
                            </div>
                        </div>
                        <div id="deptFilterRow" class="flex items-center gap-1.5 mb-2.5 flex-wrap"></div>
                        <div id="approverCardGrid" class="max-h-[320px] overflow-y-auto rounded-lg border border-gray-200 bg-gray-50/50 p-3" style="scrollbar-gutter:stable"></div>
                    </div>
                    <!-- 오른쪽: 결재 순서 -->
                    <div class="w-[300px] shrink-0 self-start" style="position:sticky;top:16px">
                        <div class="rounded-lg border border-gray-200 bg-gray-50/40 p-4">
                            <div id="approvalFlow" class="min-h-[80px]">
                                <div class="text-sm text-slate-400 py-6 text-center">결재자를 추가해주세요.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 결재내용 -->
        <div class="bg-slate-900 rounded-xl border border-slate-800 mb-5 overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-800 bg-slate-950">
                <span class="form-section-title">결재내용</span>
            </div>
            <div class="p-5">
                <div class="mb-4">
                    <label class="reg-label">제목 <span class="text-amber-500">*</span></label>
                    <input type="text" id="docTitle" class="reg-input" placeholder="결재 문서 제목을 입력해주세요">
                </div>
                <div>
                    <label class="reg-label">내용</label>
                    <!-- 에디터 툴바 -->
                    <div class="border border-slate-700 rounded-lg overflow-hidden">
                        <div class="flex items-center gap-0.5 px-2 py-1.5 bg-slate-950 border-b border-slate-700 flex-wrap">
                            <button type="button" onclick="execCmd('bold')" class="ed-btn" title="굵게"><b>B</b></button>
                            <button type="button" onclick="execCmd('italic')" class="ed-btn" title="기울임"><i>I</i></button>
                            <button type="button" onclick="execCmd('underline')" class="ed-btn" title="밑줄"><u>U</u></button>
                            <button type="button" onclick="execCmd('strikeThrough')" class="ed-btn" title="취소선"><s>S</s></button>
                            <span class="w-px h-5 bg-slate-700 mx-1"></span>
                            <select onchange="execCmd('fontSize', this.value); this.value='';" class="ed-select" title="글꼴 크기">
                                <option value="">크기</option>
                                <option value="1">작게</option>
                                <option value="3">보통</option>
                                <option value="5">크게</option>
                                <option value="7">아주 크게</option>
                            </select>
                            <span class="w-px h-5 bg-slate-700 mx-1"></span>
                            <button type="button" onclick="execCmd('justifyLeft')" class="ed-btn" title="왼쪽 정렬"><i data-lucide="align-left" class="w-3.5 h-3.5"></i></button>
                            <button type="button" onclick="execCmd('justifyCenter')" class="ed-btn" title="가운데 정렬"><i data-lucide="align-center" class="w-3.5 h-3.5"></i></button>
                            <button type="button" onclick="execCmd('justifyRight')" class="ed-btn" title="오른쪽 정렬"><i data-lucide="align-right" class="w-3.5 h-3.5"></i></button>
                            <span class="w-px h-5 bg-slate-700 mx-1"></span>
                            <button type="button" onclick="execCmd('insertUnorderedList')" class="ed-btn" title="목록"><i data-lucide="list" class="w-3.5 h-3.5"></i></button>
                            <button type="button" onclick="execCmd('insertOrderedList')" class="ed-btn" title="번호 목록"><i data-lucide="list-ordered" class="w-3.5 h-3.5"></i></button>
                            <span class="w-px h-5 bg-slate-700 mx-1"></span>
                            <button type="button" onclick="insertLink()" class="ed-btn" title="링크"><i data-lucide="link" class="w-3.5 h-3.5"></i></button>
                        </div>
                        <div id="editorContent" contenteditable="true" class="p-4 min-h-[250px] text-sm text-slate-100 focus:outline-none" style="line-height:1.8;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 첨부 및 일정 -->
        <div class="bg-slate-900 rounded-xl border border-slate-800 mb-6 overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-800 bg-slate-950">
                <span class="form-section-title">첨부 및 일정</span>
            </div>
            <table class="form-table">
                <tr>
                    <th class="form-th w-[120px]">결재문서 첨부</th>
                    <td class="form-td">
                        <div class="flex items-center gap-3">
                            <input type="file" id="attachFile" class="reg-file-sm flex-1" multiple>
                            <button type="button" onclick="openTemplateSelect()" class="px-3 py-2 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors shrink-0">
                                <i data-lucide="file-text" class="mr-1 w-3.5 h-3.5"></i> 양식선택
                            </button>
                        </div>
                        <div id="attachList" class="mt-2 space-y-1"></div>
                    </td>
                </tr>
                <tr>
                    <th class="form-th">진행일정</th>
                    <td class="form-td">
                        <input type="date" id="scheduleDate" class="reg-input max-w-[200px]" value="<?= date('Y-m-d') ?>">
                    </td>
                </tr>
            </table>
        </div>

        <!-- 하단 버튼 -->
        <div class="flex items-center justify-end gap-2">
            <button onclick="location.href='approval_draft.php'" class="btn btn-secondary">취소</button>
            <button onclick="saveDraft('draft')" class="btn btn-secondary">임시저장</button>
            <button onclick="saveDraft('submit')" class="px-5 py-2.5 text-sm font-medium text-white bg-primary rounded-lg hover:opacity-90 transition-colors">진행</button>
        </div>
    </main>
</div>

<!-- 양식선택 모달 -->
<div id="templateModal" class="fixed inset-0 bg-black/40 z-50 hidden flex items-center justify-center" onclick="if(event.target===this)closeTemplateSelect()">
    <div class="bg-slate-900 rounded-xl w-full max-w-md mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800">
            <h3 class="text-lg font-bold text-slate-100">양식 선택</h3>
            <button onclick="closeTemplateSelect()" class="text-slate-400 hover:text-slate-200 p-2 -mr-2 rounded-lg hover:bg-slate-800"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <div class="p-4 max-h-[360px] overflow-y-auto">
            <?php if (empty($forms)): ?>
            <div class="text-center text-sm text-slate-400 py-8">등록된 양식이 없습니다.<br>양식관리 페이지에서 먼저 등록해주세요.</div>
            <?php else: foreach ($forms as $f): ?>
            <button type="button" onclick="selectTemplate(<?= (int)$f['id'] ?>)" class="w-full text-left px-4 py-3 text-sm text-slate-200 hover:bg-gray-50 hover:text-gray-700 rounded-lg transition-colors flex items-center">
                <i data-lucide="file-text" class="mr-2 w-4 h-4 text-slate-500 shrink-0"></i>
                <span class="flex-1 truncate"><?= htmlspecialchars($f['title'] ?: $f['doc_type']) ?></span>
                <span class="text-sm text-slate-500 ml-2"><?= htmlspecialchars($f['doc_type']) ?></span>
            </button>
            <?php endforeach; endif; ?>
        </div>
        <div class="px-6 py-3 border-t border-slate-800 flex justify-end">
            <button onclick="closeTemplateSelect()" class="btn btn-secondary">닫기</button>
        </div>
    </div>
</div>

<!-- 결재 UI 스타일은 custom.css .apv-* 클래스로 통합됨 -->
<style>
#approverCardGrid::-webkit-scrollbar { width: 6px; }
#approverCardGrid::-webkit-scrollbar-track { background: transparent; }
#approverCardGrid::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
.dept-chip {
    display: inline-flex; align-items: center;
    padding: 4px 12px; border-radius: 999px;
    font-size: 12px; font-weight: 600; line-height: 20px;
    white-space: nowrap; cursor: pointer;
    border: 1.5px solid #e5e7eb; color: #6b7280;
    background: white; transition: all 0.15s;
}
.dept-chip:hover { border-color: var(--zm-surface-3); color: var(--zm-text-default); background: #f8f9ff; }
.dept-chip--active { border-color: var(--zm-text-strong); background: #EEF1FF; color: var(--zm-text-strong); box-shadow: 0 1px 4px rgba(0,0,0,0.04); }
.apv-person-card {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 14px; border-radius: 10px;
    border: 1.5px solid #eef0f4; background: white;
    cursor: pointer; transition: all 0.15s;
    position: relative;
}
.apv-person-card:hover { border-color: var(--zm-surface-3); background: #fafbff; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.apv-person-card--added {
    cursor: default; border-color: #f0f1f3; background: #fafafa;
}
.apv-person-card--added:hover { border-color: #f0f1f3; background: #fafafa; box-shadow: none; }
.apv-person-card__avatar {
    width: 36px; height: 36px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 700; flex-shrink: 0;
}
.apv-person-card__info { flex: 1; min-width: 0; }
.apv-person-card__name { font-size: 13px; font-weight: 700; color: #1f2937; }
.apv-person-card__pos { font-size: 11px; color: #9ca3af; margin-left: 4px; font-weight: 400; }
.apv-person-card__title {
    display: inline-block; font-size: 10px; font-weight: 600;
    color: var(--zm-text-strong); background: #EEF1FF; padding: 1px 6px;
    border-radius: 4px; margin-top: 2px; line-height: 16px;
}
.apv-person-card__action {
    font-size: 11px; font-weight: 600; color: var(--zm-text-strong);
    flex-shrink: 0; white-space: nowrap;
}
.apv-person-card--added .apv-person-card__avatar { opacity: 0.4; }
.apv-person-card--added .apv-person-card__name,
.apv-person-card--added .apv-person-card__pos { opacity: 0.4; }
.apv-person-card--added .apv-person-card__title { opacity: 0.3; }
.apv-person-card--added .apv-person-card__action { color: #10b981; }
.apv-flow-section { margin-top: 0; padding-top: 0; }
.apv-flow-label { font-size: 12px; font-weight: 600; color: #9ca3af; margin-bottom: 10px; display: flex; align-items: center; gap: 6px; }
.apv-flow-chip {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 8px 14px; border-radius: 12px;
    border: 1.5px solid; background: white;
    transition: all 0.15s; position: relative;
}
.apv-flow-draggable.dragging { opacity: 0; }
.apv-flow-drag-ghost { position: fixed; z-index: 9999; pointer-events: none;
    background: white; border: 1.5px solid #4F6AFF; border-radius: 12px;
    box-shadow: 0 12px 28px rgba(0,0,0,0.12), 0 4px 8px rgba(0,0,0,0.08);
    display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px;
}
.apv-flow-drag-ghost select, .apv-flow-drag-ghost button { display: none; }
.apv-flow-flip { transition: transform 0.28s cubic-bezier(0.22, 0.68, 0, 1.1); }
.apv-flow-chip__avatar {
    width: 30px; height: 30px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700; flex-shrink: 0;
}
.apv-flow-chip select {
    font-size: 11px; font-weight: 700; border: 0; padding: 0; padding-right: 14px;
    background: transparent; cursor: pointer; outline: none;
}
.apv-flow-chip__remove {
    width: 18px; height: 18px; border-radius: 50%; border: none;
    display: inline-flex; align-items: center; justify-content: center;
    background: transparent; color: #d1d5db; cursor: pointer; transition: all 0.15s;
}
.apv-flow-chip__remove:hover { color: #ef4444; background: #fef2f2; }
.ed-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 30px; height: 30px; font-size: 13px;
    color: var(--zm-text-default); border-radius: 4px;
    cursor: pointer; transition: background-color 0.1s;
    border: none; background: transparent;
}
.ed-btn:hover { background-color: var(--zm-surface-3); }
.ed-select {
    min-height: 30px; padding: 4px 8px; font-size: 12px; line-height: 1.5;
    border: 1px solid var(--zm-border); border-radius: 4px;
    color: var(--zm-text-default); cursor: pointer; outline: none;
    background: var(--zm-surface-1);
}
#editorContent table tr,
#editorContent table th,
#editorContent table td,
.form-table tr,
.form-table th,
.form-table td {
    background: transparent !important;
    background-color: transparent !important;
}

/* ── 다크 테마 오버라이드 ── */
html[data-theme="dark"] .dept-chip {
    background: var(--zm-surface-1); border-color: var(--zm-border); color: var(--zm-text-subtle);
}
html[data-theme="dark"] .dept-chip:hover {
    background: var(--zm-surface-2); border-color: var(--zm-surface-3); color: var(--zm-text-default);
}
html[data-theme="dark"] .dept-chip--active {
    background: rgba(79,106,255,0.15); border-color: var(--zm-text-strong); color: var(--zm-text-strong);
}
html[data-theme="dark"] .apv-person-card {
    background: var(--zm-surface-1); border-color: var(--zm-border);
}
html[data-theme="dark"] .apv-person-card:hover {
    background: var(--zm-surface-2); border-color: var(--zm-surface-3);
}
html[data-theme="dark"] .apv-person-card--added {
    background: var(--zm-surface-0); border-color: var(--zm-border);
}
html[data-theme="dark"] .apv-person-card--added:hover {
    background: var(--zm-surface-0); border-color: var(--zm-border);
}
html[data-theme="dark"] .apv-person-card__name { color: #e2e8f0; }
html[data-theme="dark"] .apv-person-card__pos { color: #94a3b8; }
html[data-theme="dark"] .apv-person-card__title {
    background: rgba(79,106,255,0.15); color: var(--zm-text-strong);
}
html[data-theme="dark"] .apv-person-card--added .apv-person-card__action { color: #34d399; }
html[data-theme="dark"] .apv-flow-label { color: #94a3b8; }
html[data-theme="dark"] .apv-flow-chip {
    background: var(--zm-surface-1);
}
html[data-theme="dark"] .apv-flow-drag-ghost {
    background: var(--zm-surface-1); border-color: #4F6AFF;
    box-shadow: 0 12px 28px rgba(0,0,0,0.3), 0 4px 8px rgba(0,0,0,0.2);
}
html[data-theme="dark"] .apv-flow-chip__remove { color: var(--zm-text-subtle); }
html[data-theme="dark"] .apv-flow-chip__remove:hover { color: #ef4444; background: rgba(239,68,68,0.15); }
</style>

<script>
const API_BASE = '<?= $basePath ?>/api/approval.php';
const HAS_DB = <?= $hasDB ? 'true' : 'false' ?>;
const SAMPLE_LINES = <?= json_encode($sampleLines, JSON_UNESCAPED_UNICODE) ?>;
const EMPLOYEES = <?= json_encode($employees, JSON_UNESCAPED_UNICODE) ?>;
const DEPT_TREE = <?= json_encode($deptTree, JSON_UNESCAPED_UNICODE) ?>;
const FORMS = <?= json_encode($forms, JSON_UNESCAPED_UNICODE) ?>;
const INIT_TYPE = <?= json_encode($initType, JSON_UNESCAPED_UNICODE) ?>;
const MY_DEPT_ID = <?= json_encode($user['department_id'] ?? null) ?>;
const DRAFTER_NAME = <?= json_encode($drafterName, JSON_UNESCAPED_UNICODE) ?>;
const DRAFTER_POS = <?= json_encode($user['position'] ?? '', JSON_UNESCAPED_UNICODE) ?>;
let currentRoute = [];
let autoRouteSig = '';
let autoRouteData = [];
let currentDeptFilter = '';

function routeSignature(route) {
    return route.map(s => `${s.id || s.name}:${s.role}`).join('|');
}

// 문서종류 기준으로 조직도 결재 경로를 자동 해소해 채움
async function applyAutoRoute(forceDocType) {
    const dt = forceDocType !== undefined ? forceDocType : document.getElementById('docType').value;
    const hint = document.getElementById('autoRouteHint');
    if (!HAS_DB) { autoRouteData = []; autoRouteSig = ''; hint?.classList.add('hidden'); updateChangedBadge(); return; }
    try {
        const res = await fetch(`${API_BASE}?action=getResolvedRoute&doc_type=${encodeURIComponent(dt || '')}`);
        const data = await res.json();
        const route = (data.route || []).map(s => ({
            role: s.role || '결재',
            id: s.employee_id || null,
            name: s.name || '',
            position: s.position || '',
        }));
        currentRoute = route;
        document.getElementById('approvalLineSelect').value = '';
        renderFlow();                               // enforceLastAsFinal로 역할 정규화
        autoRouteData = JSON.parse(JSON.stringify(currentRoute));
        autoRouteSig  = routeSignature(currentRoute);
        hint?.classList.toggle('hidden', currentRoute.length === 0);
        updateChangedBadge();
    } catch (e) { /* 자동 채움 실패 시 수동 입력 유지 */ }
}

// 자동 채움 baseline으로 되돌림
function restoreAutoRoute() {
    currentRoute = JSON.parse(JSON.stringify(autoRouteData));
    document.getElementById('approvalLineSelect').value = '';
    renderFlow();
}

// currentRoute가 자동 baseline과 다르면 '변경됨' 배지/복원 노출
function updateChangedBadge() {
    const badge = document.getElementById('routeChangedBadge');
    const actions = document.getElementById('routeActions');
    const changed = !!autoRouteSig && routeSignature(currentRoute) !== autoRouteSig;
    badge?.classList.toggle('hidden', !changed);
    actions?.classList.toggle('hidden', !changed);
}

async function saveAsApprovalLine() {
    if (!currentRoute.length) return;
    const docType = document.getElementById('docType')?.value || '';
    const defaultName = (docType ? docType + ' ' : '') + '결재선';
    const lineName = await AppUI.prompt('저장할 결재선 이름을 입력하세요:', { defaultValue: defaultName });
    if (!lineName) return;
    const lineData = currentRoute.map(s => ({
        type: 'person', role: s.role, employee_id: s.id, name: s.name, position: s.position || ''
    }));
    fetch('../api/approval.php?action=saveLine', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''},
        body: JSON.stringify({ name: lineName, doc_type: docType, department: '', line_data: lineData, scope: 'personal' })
    })
    .then(r => r.json())
    .then(res => {
        if (res.id) {
            alert('결재선 "' + lineName + '"이 저장됐어요.');
            SAMPLE_LINES.push({ id: res.id, name: lineName, doc_type: docType, route: lineData });
            filterLineSelect(docType);
        } else {
            alert('저장 실패: ' + (res.error || '알 수 없는 오류'));
        }
    })
    .catch(() => alert('저장 중 오류가 발생했어요.'));
}

function onDocTypeChange() {
    const dt = document.getElementById('docType').value;
    if (!dt) return;
    document.getElementById('docTitle').placeholder = dt + ' 제목을 입력해주세요';
    filterLineSelect(dt);
    applyAutoRoute();
}

function filterLineSelect(docType) {
    var sel = document.getElementById('approvalLineSelect');
    var row = document.getElementById('lineSelectRow');
    while (sel.options.length > 1) sel.remove(1);
    var matched = SAMPLE_LINES.filter(function(l) {
        if (!docType) return true;
        return l.doc_type === docType || !l.doc_type;
    });
    matched.forEach(function(l) {
        var opt = document.createElement('option');
        opt.value = l.id;
        opt.textContent = l.name;
        sel.appendChild(opt);
    });
    row.classList.toggle('hidden', matched.length === 0);
}

function onLineChange() {
    const lineId = parseInt(document.getElementById('approvalLineSelect').value);
    if (!lineId) { currentRoute = []; renderFlow(); return; }
    const line = SAMPLE_LINES.find(l => l.id === lineId);
    if (!line) return;
    // DB 결재선은 line_data 형태일 수 있음
    currentRoute = (line.route || []).map(s => ({
        role: s.role || '결재',
        name: s.name || '',
        position: s.position || '',
    }));
    renderFlow();
}

function getDeptDescendantIds(parentId) {
    const ids = [parentId];
    DEPT_TREE.filter(d => d.parent_id == parentId).forEach(c => {
        getDeptDescendantIds(c.id).forEach(id => ids.push(id));
    });
    return ids;
}

function getDivisions() {
    const root = DEPT_TREE.find(d => !d.parent_id);
    if (!root) return [];
    return DEPT_TREE.filter(d => d.parent_id == root.id)
        .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
}

function getMyDivisionId() {
    if (!MY_DEPT_ID) return null;
    let deptId = parseInt(MY_DEPT_ID);
    const divisions = getDivisions();
    const divIds = divisions.map(d => d.id);
    while (deptId) {
        if (divIds.includes(deptId)) return deptId;
        const node = DEPT_TREE.find(d => d.id === deptId);
        if (!node || !node.parent_id) break;
        deptId = parseInt(node.parent_id);
    }
    return null;
}

function renderDeptFilters() {
    const row = document.getElementById('deptFilterRow');
    if (!row) return;
    const divisions = getDivisions();
    if (!divisions.length) { row.style.display = 'none'; return; }
    const allCount = EMPLOYEES.length;
    let html = '';
    divisions.forEach(d => {
        const descIds = getDeptDescendantIds(d.id);
        const count = EMPLOYEES.filter(e => descIds.includes(parseInt(e.department_id))).length;
        if (!count) return;
        html += '<button type="button" onclick="setDeptFilter(' + d.id + ')" class="dept-chip ' +
            (currentDeptFilter == d.id ? 'dept-chip--active' : '') + '">' + esc(d.name) + ' ' + count + '</button>';
    });
    row.innerHTML = html;
}

function setDeptFilter(deptId) {
    currentDeptFilter = (currentDeptFilter == deptId) ? '' : deptId;
    renderDeptFilters();
    renderApproverCards();
}

function renderApproverCards() {
    const grid = document.getElementById('approverCardGrid');
    if (!grid) return;
    renderDeptFilters();
    const q = (document.getElementById('approverSearch')?.value || '').toLowerCase().trim();
    const filterDeptIds = currentDeptFilter ? getDeptDescendantIds(parseInt(currentDeptFilter)) : null;

    if (!q && !currentDeptFilter) {
        grid.innerHTML = '<div class="text-center py-4"><p class="text-[12px] text-gray-400">부서를 선택하거나 이름을 검색하세요</p></div>';
        return;
    }

    const grouped = {};
    const deptOrder = [];
    EMPLOYEES.forEach(e => {
        if (q && !(e.name || '').toLowerCase().includes(q) && !(e.dept || '').toLowerCase().includes(q) && !(e.title || '').toLowerCase().includes(q) && !(e.position || '').toLowerCase().includes(q)) return;
        if (filterDeptIds && !filterDeptIds.includes(parseInt(e.department_id))) return;
        const dept = e.dept || '기타';
        if (!grouped[dept]) { grouped[dept] = []; deptOrder.push(dept); }
        grouped[dept].push(e);
    });
    if (!deptOrder.length) {
        grid.innerHTML = '<div class="flex flex-col items-center py-6 text-center">' +
            '<i data-lucide="search-x" class="w-5 h-5 text-gray-300 mb-1"></i>' +
            '<p class="text-[12px] text-gray-400">' + (q ? '검색 결과가 없어요' : '직원 데이터가 없어요') + '</p></div>';
        if (window.lucide) lucide.createIcons();
        return;
    }
    const AVATAR_COLORS = ['#4F6AFF','#10b981','#f59e0b','#8b5cf6','#ec4899','#06b6d4','#ef4444'];
    function avatarColor(name) {
        let h = 0;
        for (let i = 0; i < (name||'').length; i++) h = name.charCodeAt(i) + ((h << 5) - h);
        return AVATAR_COLORS[Math.abs(h) % AVATAR_COLORS.length];
    }
    grid.innerHTML = deptOrder.map(dept => {
        const emps = grouped[dept];
        return '<div class="mb-3 last:mb-0">' +
            '<div class="text-[12px] font-semibold text-gray-500 mb-2 px-2 py-1">' + esc(dept) + ' <span class="text-gray-400 font-normal">' + emps.length + '명</span></div>' +
            '<div class="grid gap-2" style="grid-template-columns:repeat(auto-fill,minmax(200px,1fr))">' +
            emps.map(e => {
                const already = currentRoute.some(r => r.id == e.id);
                const initial = (e.name || '?').charAt(0);
                const ac = avatarColor(e.name);
                return '<div class="apv-person-card' + (already ? ' apv-person-card--added' : '') + '"' +
                    (already ? '' : ' onclick="addApproverFromCard(' + e.id + ')"') + '>' +
                    '<span class="apv-person-card__avatar" style="background:' + ac + '15;color:' + ac + '">' + esc(initial) + '</span>' +
                    '<div class="apv-person-card__info">' +
                        '<div><span class="apv-person-card__name">' + esc(e.name) + '</span><span class="apv-person-card__pos">' + esc(e.position || '') + '</span></div>' +
                        (e.title ? '<div class="apv-person-card__title">' + esc(e.title) + '</div>' : '') +
                    '</div>' +
                    '<span class="apv-person-card__action">' + (already ? '추가됨 ✓' : '+ 추가') + '</span>' +
                '</div>';
            }).join('') +
            '</div></div>';
    }).join('');
    if (window.lucide) lucide.createIcons();
}

function addApproverFromCard(id) {
    const e = EMPLOYEES.find(x => x.id == id);
    if (!e) return;
    if (currentRoute.some(r => r.id == e.id)) return;
    currentRoute.push({ role: '결재', id: e.id, name: e.name, position: e.position || '' });
    document.getElementById('approvalLineSelect').value = '';
    renderFlow();
}

function removeApprover(idx) {
    currentRoute.splice(idx, 1);
    document.getElementById('approvalLineSelect').value = '';
    renderFlow();
}

function changeRole(idx) {
    const current = currentRoute[idx].role;
    // 결재/전결 ↔ 참조 토글 (결재/전결 순서는 enforceLastAsFinal이 자동 관리)
    currentRoute[idx].role = (current === '참조') ? '결재' : '참조';
    renderFlow();
}

// 마지막 결재자(참조 제외)를 자동으로 전결로 설정
function enforceLastAsFinal() {
    const approvers = currentRoute.filter(r => r.role !== '참조');
    if (!approvers.length) return;
    // 결재/전결 역할인 사람들: 마지막만 전결, 나머지는 결재
    approvers.forEach((step, i) => {
        step.role = (i === approvers.length - 1) ? '전결' : '결재';
    });
}

function renderFlow() {
    enforceLastAsFinal();
    const container = document.getElementById('approvalFlow');
    if (!currentRoute.length) {
        container.innerHTML = '<div class="text-sm text-slate-500 py-6 text-center">결재선을 선택하거나<br>결재자를 추가해주세요.</div>';
        updateChangedBadge();
        renderApproverCards();
        return;
    }
    const roleColors = {'기안':'#6b7280','결재':'#4F6AFF','전결':'#f59e0b','협조':'#10b981','참조':'#8b5cf6'};
    let html = '<div>' +
        '<div class="apv-flow-label"><i data-lucide="git-branch" class="w-3.5 h-3.5"></i> 결재 순서</div>' +
        '<div class="flex flex-col gap-0">';
    // 기안자 (본인) 최상단 고정
    const di = (DRAFTER_NAME || '?').charAt(0);
    html += '<div class="apv-flow-chip" style="border-color:var(--zm-border);background:var(--zm-surface-0)">' +
        '<span class="apv-flow-chip__avatar" style="background:var(--zm-surface-2);color:var(--zm-text-subtle)">' + esc(di) + '</span>' +
        '<div style="min-width:0;flex:1">' +
            '<div class="text-[13px] font-bold text-gray-800 truncate">' + esc(DRAFTER_NAME) + ' <span class="text-[11px] text-gray-400 font-normal">' + esc(DRAFTER_POS) + '</span></div>' +
            '<span class="text-[11px] font-semibold text-gray-400">기안</span>' +
        '</div>' +
    '</div>';
    html += '<div id="flowSortable" class="flex flex-col gap-0">';
    currentRoute.forEach((s, i) => {
        const initial = (s.name || '?').charAt(0);
        const rc = roleColors[s.role] || '#6b7280';
        html += '<div class="apv-flow-arrow flex justify-center py-0.5"><i data-lucide="arrow-down" class="w-3.5 h-3.5 text-gray-300"></i></div>';
        html += '<div class="apv-flow-chip apv-flow-draggable" data-idx="' + i + '" style="border-color:' + rc + '40">' +
            '<span class="flow-grip cursor-grab active:cursor-grabbing text-gray-300 hover:text-gray-500 flex-shrink-0" title="드래그하여 순서 변경">' +
                '<i data-lucide="grip-vertical" class="w-3.5 h-3.5"></i>' +
            '</span>' +
            '<span class="apv-flow-chip__avatar" style="background:' + rc + '18;color:' + rc + '">' + esc(initial) + '</span>' +
            '<div style="min-width:0;flex:1">' +
                '<div class="text-[13px] font-bold text-gray-800 truncate">' + esc(s.name) + ' <span class="text-[11px] text-gray-400 font-normal">' + esc(s.position || '') + '</span></div>' +
                '<select class="apv-flow-chip select" style="color:' + rc + '" onchange="setRole(' + i + ',this.value)">' +
                    '<option value="결재"' + (s.role === '결재' ? ' selected' : '') + '>결재</option>' +
                    '<option value="전결"' + (s.role === '전결' ? ' selected' : '') + '>전결</option>' +
                    '<option value="참조"' + (s.role === '참조' ? ' selected' : '') + '>참조</option>' +
                '</select>' +
            '</div>' +
            '<button type="button" onclick="removeApprover(' + i + ')" class="apv-flow-chip__remove" title="제거">' +
                '<i data-lucide="x" class="w-3 h-3"></i>' +
            '</button>' +
        '</div>';
    });
    html += '</div></div>';
    container.innerHTML = html;
    if (typeof lucide !== 'undefined') lucide.createIcons();
    initFlowDragDrop();
    renderApproverCards();
    updateChangedBadge();
}

let preFlowSnapshot = null;

function showFlowReorderBar() {
    const sortable = document.getElementById('flowSortable');
    if (!sortable || sortable.parentElement.querySelector('.zm-reorder-bar')) return;
    const bar = document.createElement('div');
    bar.className = 'zm-reorder-bar';
    bar.innerHTML = '<button class="zm-cancel-btn">취소</button>';
    sortable.parentElement.appendChild(bar);
    bar.querySelector('.zm-cancel-btn').addEventListener('click', () => {
        if (preFlowSnapshot) { currentRoute = JSON.parse(JSON.stringify(preFlowSnapshot)); preFlowSnapshot = null; }
        bar.remove();
        renderFlow();
    });
}

let _flowDragDs = null;
function initFlowDragDrop() {
    const sortable = document.getElementById('flowSortable');
    if (!sortable) return;
    if (_flowDragDs && _flowDragDs.ghost?.isConnected) _flowDragDs.ghost.remove();
    _flowDragDs = null;

    const OLD_KEY = '__flowDragHandler';
    if (sortable[OLD_KEY]) sortable.removeEventListener('pointerdown', sortable[OLD_KEY]);
    const handler = e => {
        const grip = e.target.closest('.flow-grip');
        if (!grip) return;
        const chip = grip.closest('.apv-flow-draggable');
        if (!chip) return;

        const chips = [...sortable.querySelectorAll('.apv-flow-draggable')];
        const idx = chips.indexOf(chip);
        if (idx < 0 || chips.length < 2) return;

        e.preventDefault();
        if (!preFlowSnapshot) preFlowSnapshot = JSON.parse(JSON.stringify(currentRoute));
        const rect = chip.getBoundingClientRect();
        const ox = e.clientX - rect.left, oy = e.clientY - rect.top;
        const origTops = chips.map(c => c.getBoundingClientRect().top);
        const itemH = chips.length > 1 ? origTops[1] - origTops[0] : rect.height;

        const ghost = chip.cloneNode(true);
        ghost.className = 'apv-flow-drag-ghost';
        ghost.style.cssText = `position:fixed;left:${rect.left}px;top:${rect.top}px;width:${rect.width}px;height:${rect.height}px;z-index:9999;pointer-events:none;`;
        document.body.appendChild(ghost);
        chip.classList.add('dragging');
        sortable.classList.add('zm-drag-active');
        const dragArrow = chip.previousElementSibling;
        if (dragArrow?.classList.contains('apv-flow-arrow')) dragArrow.style.opacity = '0';

        _flowDragDs = { fromIdx: idx, gapIdx: idx, origTops, itemH, ghost, chip, ox, oy, dragArrow, chips };

        function onMove(ev) {
            if (!_flowDragDs) return;
            ghost.style.left = (ev.clientX - ox) + 'px';
            ghost.style.top = (ev.clientY - oy) + 'px';
            let newGap = chips.length;
            for (let i = 0; i < chips.length; i++) {
                if (ev.clientY < origTops[i] + itemH / 2) { newGap = i; break; }
            }
            if (newGap === _flowDragDs.gapIdx) return;
            _flowDragDs.gapIdx = newGap;
            chips.forEach((c, i) => {
                if (i === idx) return;
                let shift = 0;
                if (idx < newGap && i > idx && i < newGap) shift = -itemH;
                else if (idx > newGap && i >= newGap && i < idx) shift = itemH;
                const t = shift ? `translateY(${shift}px)` : '';
                c.style.transform = t;
                const arrow = c.previousElementSibling;
                if (arrow?.classList.contains('apv-flow-arrow')) arrow.style.transform = t;
            });
        }
        function onUp() {
            document.removeEventListener('pointermove', onMove);
            document.removeEventListener('pointerup', onUp);
            document.removeEventListener('pointercancel', onUp);
            if (!_flowDragDs) return;
            if (ghost.isConnected) ghost.remove();
            chip.classList.remove('dragging');
            sortable.classList.remove('zm-drag-active');
            if (_flowDragDs.dragArrow) _flowDragDs.dragArrow.style.opacity = '';
            chips.forEach(c => {
                c.style.transform = '';
                const arrow = c.previousElementSibling;
                if (arrow?.classList.contains('apv-flow-arrow')) { arrow.style.transform = ''; arrow.style.opacity = ''; }
            });

            const { fromIdx, gapIdx } = _flowDragDs;
            _flowDragDs = null;
            if (gapIdx === fromIdx || gapIdx === fromIdx + 1) return;
            const item = currentRoute.splice(fromIdx, 1)[0];
            const insertAt = gapIdx > fromIdx ? gapIdx - 1 : gapIdx;
            currentRoute.splice(insertAt, 0, item);
            renderFlow();
            showFlowReorderBar();
        }
        document.addEventListener('pointermove', onMove);
        document.addEventListener('pointerup', onUp);
        document.addEventListener('pointercancel', onUp);
    };
    sortable[OLD_KEY] = handler;
    sortable.addEventListener('pointerdown', handler);
}

function setRole(idx, role) {
    currentRoute[idx].role = role;
    renderFlow();
}

function execCmd(cmd, val) {
    document.getElementById('editorContent').focus();
    document.execCommand(cmd, false, val || null);
}

async function insertLink() {
    const url = await AppUI.prompt('링크 URL을 입력해주세요:', { defaultValue: 'https://' });
    if (url) execCmd('createLink', url);
}

let selectedFormId = null;

function openTemplateSelect() { document.getElementById('templateModal').classList.remove('hidden'); }
function closeTemplateSelect() { document.getElementById('templateModal').classList.add('hidden'); }

async function selectTemplate(formId) {
    selectedFormId = formId;
    const f = FORMS.find(x => x.id == formId);
    if (!f) { alert('양식을 찾을 수 없습니다.'); return; }

    // 문서종류 드롭다운 동기화 (일치하는 option이 있으면 선택)
    const docTypeSel = document.getElementById('docType');
    const match = Array.from(docTypeSel.options).find(o => o.value === f.doc_type);
    if (match) docTypeSel.value = f.doc_type;
    onDocTypeChange();

    // 제목이 비어있을 때만 양식 제목으로 채움 (사용자가 입력한 값은 보존)
    const titleInput = document.getElementById('docTitle');
    if (!titleInput.value.trim()) titleInput.value = f.title || f.doc_type;

    // 본문 에디터에 삽입
    const editor = document.getElementById('editorContent');
    const tpl = (f.content_template || '').trim();
    if (!tpl) {
        alert('선택한 양식의 본문이 비어 있습니다.\n양식관리 페이지에서 본문을 먼저 등록해주세요.');
        closeTemplateSelect();
        return;
    }
    // 기존 내용이 있으면 덮어쓸지 확인
    if (editor.innerHTML.trim() && !(await AppUI.confirm('현재 작성 중인 내용이 있습니다. 양식으로 덮어쓸까요?'))) {
        closeTemplateSelect();
        return;
    }
    editor.innerHTML = tpl;

    closeTemplateSelect();
}

function saveDraft(mode) {
    const docType = document.getElementById('docType').value;
    const title = document.getElementById('docTitle').value.trim();
    const content = document.getElementById('editorContent').innerHTML;
    const schedule = document.getElementById('scheduleDate').value;

    if (!docType) { alert('문서종류를 선택해주세요.'); return; }
    if (!title) { alert('제목을 입력해주세요.'); return; }
    if (mode === 'submit' && !currentRoute.length) { alert('결재선을 지정해주세요.'); return; }

    const data = {
        doc_type: docType,
        title: title,
        content: content,
        schedule_date: schedule,
        form_id: selectedFormId,
        approval_route: currentRoute,
        status: mode === 'submit' ? '진행' : '임시저장',
        drafter_name: '<?= htmlspecialchars($drafterName) ?>',
        drafter_dept: '<?= htmlspecialchars($drafterDept) ?>',
        draft_date: '<?= $draftDate ?>',
    };

    // 상신: 결재대기함으로, 임시저장: 문서보관함 > 임시저장함으로
    const redirectUrl = mode === 'submit' ? 'approval_draft.php?tab=progress' : 'approval_archive.php?tab=temp';

    if (HAS_DB) {
        fetch(`${API_BASE}?action=saveDraft`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(res => {
            if (res.error) { alert(res.error); return; }
            alert(mode === 'submit' ? '결재가 상신되었습니다.' : '임시저장되었습니다.');
            location.href = redirectUrl;
        });
    } else {
        alert(mode === 'submit' ? '결재가 상신되었습니다.' : '임시저장되었습니다.');
        location.href = redirectUrl;
    }
}

const esc = ApvUI.esc;

document.addEventListener('DOMContentLoaded', function() {
    var myDiv = getMyDivisionId();
    if (myDiv) {
        currentDeptFilter = myDiv;
    }
    renderDeptFilters();
    renderApproverCards();
    filterLineSelect('');

    if (INIT_TYPE) {
        var docTypeSel = document.getElementById('docType');
        var match = Array.from(docTypeSel.options).find(function(o) { return o.value === INIT_TYPE; });
        if (match) {
            docTypeSel.value = INIT_TYPE;
            onDocTypeChange();
            var form = FORMS.find(function(f) { return f.doc_type === INIT_TYPE; });
            if (form) selectTemplate(form.id);
        }
    }

    if (!currentRoute.length) {
        applyAutoRoute('');
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
