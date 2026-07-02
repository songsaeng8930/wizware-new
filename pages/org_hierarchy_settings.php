<?php
/**
 * 시스템 관리 > 그룹웨어 관리 > 조직 용어 설정
 * - 조직 계층 이름과 책임자 호칭을 회사 언어에 맞게 설정
 * - 저장소: config/org_hierarchy.json (api/org_hierarchy.php 경유)
 * - admin 전용
 */
$pageTitle = '조직 용어 설정';
$currentPage = 'groupware';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/permissions.php';

requireMenuPermission('groupware.display', 'admin');

$hierarchy = getOrgHierarchy(true);
$levels = $hierarchy['levels'];
$titleSystem = $hierarchy['title_system'] ?? 'rank_and_duty';
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
<main class="p-6">

    <!-- 페이지 헤더 -->
    <div class="mb-5">
        <h2 class="text-lg font-bold" style="color:var(--zm-text-strong)">조직 용어 설정</h2>
        <p class="text-sm mt-0.5" style="color:var(--zm-text-muted)">조직 계층, 직급, 직책, 직위 체계를 회사에 맞게 설정합니다.</p>
    </div>

    <!-- ═══ 영역 1: 조직 계층 (접힘/펼침) ═══ -->
    <div id="hierarchySection" class="bg-white rounded-xl border border-gray-200 shadow-sm mb-5 overflow-hidden">
        <!-- 접힘 모드 -->
        <div id="hierarchyCollapsed" class="flex items-center justify-between px-5 py-3.5 cursor-pointer hover:bg-gray-50/50 transition-colors" onclick="toggleHierarchyEdit(true)">
            <div class="flex items-center gap-3">
                <h3 class="text-sm font-bold text-gray-800">조직 계층</h3>
                <div id="hierarchySummary" class="flex items-center gap-1 text-sm text-gray-500"></div>
            </div>
            <button class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-medium text-gray-500 bg-gray-100 border border-gray-200 rounded-lg hover:text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition-colors">
                <i data-lucide="pencil" class="w-3 h-3"></i>편집
            </button>
        </div>
        <!-- 펼침 모드 -->
        <div id="hierarchyExpanded" class="hidden">
            <div class="px-5 pt-4 pb-1">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-bold text-gray-800">조직 계층</h3>
                        <p class="text-[11px] text-gray-400 mt-0.5 leading-relaxed">조직 단위와 책임자 호칭을 설정하면 결재선·필터·테이블에 바로 반영돼요.</p>
                    </div>
                    <button class="text-xs text-gray-400 hover:text-gray-600 px-2 py-1 rounded-lg hover:bg-gray-100 transition-colors" onclick="toggleHierarchyEdit(false)">접기</button>
                </div>
                <div id="levelList" class="oh-stepper"></div>
                <button id="addLevelBtn" class="mt-2 ml-9 inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-400 border border-dashed border-gray-300 rounded-lg hover:text-gray-600 hover:border-gray-400 hover:bg-gray-50 transition-colors">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    단계 추가
                </button>
            </div>
            <div class="flex items-center justify-end gap-2.5 px-5 py-3 bg-gray-50/60 border-t border-gray-100 mt-3">
                <span id="changeStatus" class="text-[11px] text-gray-400 transition-all duration-300"></span>
                <button id="saveBtn" disabled class="zm-btn zm-btn-hero text-sm py-1.5 px-5 disabled:opacity-30 disabled:cursor-not-allowed transition-opacity">
                    <i data-lucide="save" class="w-3.5 h-3.5"></i> 저장
                </button>
            </div>
        </div>
    </div>

    <!-- ═══ 영역 2: 직함 설정 ═══ -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

        <!-- 좌: 직함 체계 + 직급/직책/직위 컬럼 + 이름 표시 -->
        <div class="xl:col-span-2">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">

                <!-- 직함 체계 선택 -->
                <div class="px-5 py-4 border-b border-gray-100">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                        <div class="flex-1">
                            <h3 class="text-sm font-bold text-gray-800 mb-0.5">직함 체계</h3>
                            <p id="titleSystemDesc" class="text-[11px] text-gray-400 leading-relaxed"></p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <label for="titleSystemSelect" class="text-xs text-gray-500 whitespace-nowrap">사용할 체계</label>
                            <select id="titleSystemSelect" class="w-full sm:w-48 px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300/20 focus:border-gray-300 transition-colors"></select>
                        </div>
                    </div>
                </div>

                <!-- 직급/직책/직위 컬럼 -->
                <div id="hrColumnsArea" class="px-5 py-4">
                    <div id="hrColumns" class="grid gap-4"></div>
                    <!-- 비활성 안내 -->
                    <div id="hrColumnsEmpty" class="hidden py-10 text-center">
                        <p class="text-sm text-gray-400">선택한 직함 체계에서는 별도 관리할 항목이 없어요.</p>
                    </div>
                </div>

                <!-- 구분선 -->
                <div class="h-px bg-gray-100 mx-5"></div>

                <!-- 이름 표시 형식 -->
                <div class="px-5 py-4">
                    <div class="flex flex-col sm:flex-row sm:items-start gap-4">
                        <div class="flex-1">
                            <label class="block text-[11px] font-semibold text-gray-300 uppercase tracking-wider mb-2">이름 표시 형식</label>
                            <div id="displayFormatOptions" class="dc-chip-group"></div>
                        </div>
                        <label class="relative inline-flex items-center gap-2 cursor-pointer group shrink-0 sm:mt-6">
                            <input type="checkbox" id="boardSuffixToggle" class="sr-only peer">
                            <div class="relative w-8 h-[18px] bg-gray-200 rounded-full peer-checked:bg-primary transition-colors after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-[14px] after:w-[14px] after:shadow-sm after:transition-all peer-checked:after:translate-x-[14px]"></div>
                            <span class="text-[11px] text-gray-400 group-hover:text-gray-500 transition-colors">게시판 '님' 호칭</span>
                        </label>
                    </div>
                    <div id="dcSaveWrap" class="flex justify-end mt-3" style="display:none">
                        <button id="dcSaveBtn" class="dc-save-btn" onclick="saveDisplayConfig()">저장</button>
                    </div>
                </div>

            </div>
        </div>

        <!-- 우: 미리보기 -->
        <div>
            <div class="bg-white rounded-xl border border-gray-200 sticky top-20 overflow-hidden shadow-sm">
                <div class="px-4 py-2.5" style="background:linear-gradient(135deg, #f8f9ff 0%, #f1f5f9 100%); border-bottom:1px solid #e5e7eb;">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">미리보기</p>
                </div>
                <!-- 프로필 카드 -->
                <div class="px-4 pt-4 pb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold text-white shrink-0" style="background:linear-gradient(135deg, var(--zm-primary), #7c8aff);">홍</div>
                        <div>
                            <p id="previewDisplayName" class="text-[15px] font-bold text-gray-800 leading-tight tracking-tight"></p>
                            <p id="previewBoardName" class="text-[11px] text-gray-400 mt-0.5" style="display:none"></p>
                            <p id="previewDeptLine" class="text-[10px] text-gray-400 mt-0.5"></p>
                        </div>
                    </div>
                </div>
                <div class="mx-4 h-px bg-gray-100"></div>
                <!-- 결재선 -->
                <div class="px-4 pt-3 pb-3">
                    <p class="text-[9px] font-bold text-gray-300 uppercase tracking-wider mb-2">결재선</p>
                    <div id="previewFlow" class="flex items-center flex-wrap gap-0.5"></div>
                </div>
                <div class="mx-4 h-px bg-gray-100"></div>
                <!-- 직원 목록 -->
                <div class="px-4 pt-3 pb-4">
                    <p class="text-[9px] font-bold text-gray-300 uppercase tracking-wider mb-2">직원 목록</p>
                    <div id="previewFilters" class="flex flex-wrap gap-1 mb-2"></div>
                    <div class="rounded-lg border border-gray-200 overflow-hidden">
                        <div id="previewHeaders" class="flex" style="background:#f8f9fb"></div>
                        <div id="previewRow" class="flex border-t border-gray-100 bg-white"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <style>
        /* ─── 스테퍼(조직 계층) ─── */
        .oh-stepper { display:flex; flex-direction:column; }
        .oh-step { display:flex; gap:0; }
        .oh-step-disabled { opacity:0.45; }
        .oh-step-dot-col { display:flex; flex-direction:column; align-items:center; width:36px; flex-shrink:0; }
        .oh-step-dot { width:24px; height:24px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; flex-shrink:0; box-shadow:0 2px 6px rgba(0,0,0,0.08); transition:all 0.2s; }
        .oh-step-disabled .oh-step-dot { box-shadow:none; }
        .oh-step-line { flex:1; width:2px; min-height:12px; background:linear-gradient(to bottom, var(--zm-primary-tint-20), var(--zm-border)); border-radius:1px; }
        .oh-step-body { flex:1; display:flex; align-items:center; gap:8px; padding:6px 0 6px 8px; min-height:40px; }
        .oh-step-inputs { display:flex; align-items:center; gap:6px; flex:1; }
        .oh-input { padding:5px 10px; font-size:13px; border:1px solid var(--zm-border); border-radius:8px; background:var(--zm-surface-1); color:var(--zm-text-default); outline:none; transition:border-color 0.15s, box-shadow 0.15s; width:0; flex:1; min-width:0; }
        .oh-input:focus { border-color:var(--zm-surface-3); box-shadow:none; }
        .oh-input::placeholder { color:var(--zm-text-subtle); }
        .oh-input-sep { font-size:11px; color:var(--zm-text-subtle); white-space:nowrap; flex-shrink:0; }
        .oh-step-actions { display:flex; align-items:center; gap:4px; flex-shrink:0; }
        .oh-lock { display:inline-flex; align-items:center; gap:3px; padding:2px 8px; border-radius:6px; background:var(--zm-surface-2); font-size:10px; font-weight:600; color:var(--zm-text-subtle); }
        .oh-toggle-label { display:inline-flex; align-items:center; cursor:pointer; }
        .oh-toggle-track { position:relative; width:32px; height:18px; background:var(--zm-surface-3); border-radius:9px; transition:background 0.2s; }
        .oh-toggle-track::after { content:''; position:absolute; top:2px; left:2px; width:14px; height:14px; background:var(--zm-surface-1); border-radius:50%; box-shadow:0 1px 3px rgba(0,0,0,0.15); transition:transform 0.2s; }
        .oh-del { display:flex; align-items:center; justify-content:center; width:24px; height:24px; border-radius:6px; border:none; background:transparent; color:var(--zm-text-subtle); cursor:pointer; transition:all 0.15s; }
        .oh-del:hover { color:var(--zm-danger-fg); background:var(--zm-danger-bg); }

        /* ─── 직급/직책/직위 컬럼 ─── */
        .hr-col { min-width:0; overflow:visible; padding:0 12px; }
        .hr-col:first-child { padding-left:0; }
        .hr-col:last-child { padding-right:0; }
        .hr-col + .hr-col { border-left:1px solid var(--zm-border); }
        .hr-col-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:6px; padding-bottom:6px; border-bottom:1px solid var(--zm-border); }
        .hr-col-title { font-size:13px; font-weight:700; color:var(--zm-text-strong); flex:1; display:flex; align-items:center; gap:6px; }
        .hr-col-count { font-size:10px; font-weight:500; color:var(--zm-text-subtle); }
        .hr-col-add { display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px; border-radius:5px; border:1px dashed var(--zm-border); background:transparent; color:var(--zm-text-subtle); cursor:pointer; transition:all 0.15s; font-size:11px; }
        .hr-col-add:hover { border-color:var(--zm-text-subtle); color:var(--zm-text-default); background:rgba(0,0,0,0.04); }
        .hr-col-loading { padding:20px 0; text-align:center; color:var(--zm-text-subtle); font-size:12px; }
        .hr-col-empty { padding:16px 0; text-align:center; }

        /* ─── 항목 리스트 (드래그 정렬) ─── */
        .hr-list { display:flex; flex-direction:column; gap:2px; padding:3px; border:1px solid var(--zm-border); border-radius:10px; background:var(--zm-surface-0); }
        .hr-item { position:relative; display:flex; align-items:center; gap:4px; padding:0 10px 0 2px; height:36px; transition:background 0.15s, transform 0.2s cubic-bezier(0.2, 0, 0, 1); cursor:default; border-radius:6px; background:var(--zm-surface-1); }
        .hr-num { font-size:10px; color:var(--zm-text-subtle); width:22px; text-align:center; flex-shrink:0; font-variant-numeric:tabular-nums; opacity:0.4; pointer-events:none; }
        .hr-item:hover { background:var(--zm-surface-1); box-shadow:0 1px 3px rgba(0,0,0,0.06); }
        .hr-item-off .hr-name { color:var(--zm-text-subtle); }
        .hr-item-off .hr-grip { opacity:0.08; }
        .hr-item-off .hr-use { opacity:0.35; }
        .hr-grip { display:flex; align-items:center; justify-content:center; width:16px; height:20px; cursor:grab; flex-shrink:0; opacity:0.2; transition:opacity 0.12s; border-radius:3px; color:var(--zm-text-subtle); }
        .hr-item:hover .hr-grip { opacity:0.5; }
        .hr-grip:active { cursor:grabbing; opacity:0.7; }
        .hr-grip svg { width:14px; height:14px; }
        .hr-name { width:0; flex:1; min-width:0; padding:2px 6px; font-size:13px; line-height:1.4; color:var(--zm-text-strong); border:1px solid transparent; border-radius:4px; background:transparent; outline:none; transition:background 0.15s, border-color 0.15s; -webkit-appearance:none; }
        .hr-name:hover { background:var(--zm-surface-2); }
        .hr-name:focus { background:var(--zm-surface-0); border-color:var(--zm-surface-3); }
        .hr-use { font-size:9px; font-weight:500; color:var(--zm-text-muted); background:var(--zm-surface-2); padding:1px 0; border-radius:8px; line-height:14px; flex-shrink:0; width:30px; text-align:center; font-variant-numeric:tabular-nums; }
        .hr-use-zero { opacity:0.4; }
        .hr-menu-btn { display:flex; align-items:center; justify-content:center; width:22px; height:22px; border:none; background:transparent; cursor:pointer; color:var(--zm-text-subtle); font-size:14px; font-weight:700; letter-spacing:-1px; flex-shrink:0; border-radius:4px; opacity:0; transition:all 0.1s; line-height:1; }
        .hr-item:hover .hr-menu-btn { opacity:0.5; }
        .hr-menu-btn:hover { color:var(--zm-text-default); background:var(--zm-surface-2); opacity:1; }
        .hr-dropdown { position:absolute; right:4px; top:calc(100% + 2px); z-index:50; min-width:100px; background:var(--zm-surface-1); border:1px solid var(--zm-border); border-radius:8px; box-shadow:var(--zm-card-shadow); padding:3px; }
        .hr-dropdown-item { display:flex; align-items:center; gap:6px; width:100%; padding:6px 10px; border:none; background:none; cursor:pointer; font-size:12px; color:var(--zm-text-muted); border-radius:5px; transition:background 0.1s; }
        .hr-dropdown-item:hover { background:var(--zm-surface-2); }
        .hr-dropdown-item-danger:hover { background:var(--zm-danger-bg); color:var(--zm-danger-fg); }
        .hr-item-fixed .hr-grip { opacity:0; pointer-events:none; }
        .hr-item-fixed .hr-menu-btn { visibility:hidden; }
        .hr-item.dragging { opacity:0; }
        .hr-drag-ghost { background:var(--zm-surface-1); border:1px solid var(--zm-border); border-radius:6px; box-shadow:0 12px 28px rgba(0,0,0,0.12), 0 4px 8px rgba(0,0,0,0.08); display:flex; align-items:center; gap:4px; padding:0 10px 0 2px; }
        .hr-drag-ghost .hr-num { display:none; }
        .hr-drag-ghost .hr-menu-btn { display:none; }
        .hr-drag-ghost .hr-grip { opacity:0.5; cursor:grabbing; }
        .hr-item.hr-flip { transition:transform 0.28s cubic-bezier(0.22, 0.68, 0, 1.1); }
        .hr-reorder-bar { position:fixed; bottom:0; left:240px; right:0; z-index:50; display:flex; align-items:center; justify-content:flex-end; gap:8px; padding:12px 32px; background:var(--zm-surface-1); border-top:1px solid var(--zm-border); box-shadow:0 -4px 12px rgba(0,0,0,0.08); }
        .hr-reorder-bar button { padding:6px 20px; border-radius:6px; font-size:13px; font-weight:600; border:none; cursor:pointer; transition:all 0.12s; }
        .hr-save-btn { background:var(--zm-primary); color:#fff; }
        .hr-save-btn:hover { opacity:0.85; }
        .hr-cancel-btn { background:var(--zm-surface-2); color:var(--zm-text-muted); }
        .hr-cancel-btn:hover { background:var(--zm-surface-3); }
        .hr-add-row { display:flex; align-items:center; justify-content:center; height:32px; border-radius:6px; cursor:pointer; transition:background 0.15s; border:1px dashed var(--zm-border); background:transparent; }
        .hr-add-row:hover { background:var(--zm-surface-2); border-color:var(--zm-text-subtle); }
        .hr-add-btn { font-size:12px; color:var(--zm-text-subtle); pointer-events:none; border:none; background:none; }
        .hr-add-input { flex:1; padding:2px 6px; font-size:13px; line-height:1.4; border:1px solid var(--zm-border); border-radius:4px; background:var(--zm-surface-1); outline:none; color:var(--zm-text-strong); }

        /* ─── 표시 형식 칩 ─── */
        .dc-chip-group { display:inline-flex; gap:0; padding:3px; background:var(--zm-surface-2); border-radius:10px; }
        .dc-chip { display:inline-flex; align-items:center; padding:5px 14px; border-radius:8px; border:none; background:transparent; cursor:pointer; transition:all 0.18s ease; font-size:12px; font-weight:500; color:var(--zm-text-subtle); white-space:nowrap; }
        .dc-chip:hover { color:var(--zm-text-muted); background:var(--zm-surface-1); box-shadow:0 1px 2px rgba(0,0,0,0.04); }
        .dc-chip-active { color:var(--zm-text-strong); font-weight:600; background:var(--zm-surface-1); box-shadow:0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.04); }
        .dc-save-btn { padding:5px 18px; border-radius:6px; font-size:13px; font-weight:600; border:1px solid transparent; cursor:pointer; transition:all 0.15s; background:var(--zm-primary); color:#fff; }
        .dc-save-btn:hover:not(:disabled) { background:var(--zm-primary-fg); box-shadow:0 1px 3px rgba(0,0,0,0.12); }
        .dc-save-btn:disabled { background:var(--zm-surface-3); color:var(--zm-text-subtle); cursor:default; opacity:0.5; }

        /* ─── 템플릿 카드 (빈 상태) ─── */
        .tpl-card { display:flex; align-items:center; width:100%; padding:14px 16px; border-radius:12px; border:1px solid var(--zm-border); background:var(--zm-surface-1); cursor:pointer; transition:all 0.15s ease; gap:12px; text-align:left; }
        .tpl-card:hover { border-color:var(--zm-text-subtle); background:rgba(0,0,0,0.04); box-shadow:0 1px 3px rgba(0,0,0,0.08); }
        .tpl-card-custom { border-style:dashed; border-color:var(--zm-surface-3); background:var(--zm-surface-0); }
        .tpl-card-custom:hover { border-color:var(--zm-text-subtle); border-style:solid; background:rgba(0,0,0,0.04); }
        .tpl-card-left { display:flex; align-items:center; gap:12px; flex-shrink:0; }
        .tpl-card-icon { width:40px; height:40px; border-radius:10px; background:var(--zm-primary-tint-12); display:flex; align-items:center; justify-content:center; color:var(--zm-primary); flex-shrink:0; }
        .tpl-card-icon-custom { background:var(--zm-surface-2); color:var(--zm-text-subtle); }
        .tpl-card:hover .tpl-card-icon-custom { background:rgba(0,0,0,0.04); color:var(--zm-text-default); }
        .tpl-card-info { display:flex; flex-direction:column; gap:1px; }
        .tpl-card-title { font-size:14px; font-weight:600; color:var(--zm-text-strong); }
        .tpl-card-desc { font-size:12px; color:var(--zm-text-subtle); }
        .tpl-card-tags { display:flex; flex-wrap:wrap; gap:4px; flex:1; justify-content:flex-end; margin-left:auto; }
        .tpl-tag { padding:2px 8px; font-size:11px; border-radius:6px; background:var(--zm-surface-2); color:var(--zm-text-muted); white-space:nowrap; }
        .tpl-tag-more { background:var(--zm-primary-tint-12); color:var(--zm-primary-fg); font-weight:500; }
        .tpl-card-arrow { color:var(--zm-surface-3); flex-shrink:0; transition:color 0.15s; }
        .tpl-card:hover .tpl-card-arrow { color:var(--zm-text-default); }
    </style>

</main>
</div>

<script>
const API = '<?= $basePath ?>/api/org_hierarchy.php';
const INITIAL = <?= json_encode($levels, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const REQUIRED_KEYS = ['company', 'division', 'department'];
const MAX_LEVELS = <?= ORG_HIERARCHY_MAX_LEVELS ?>;
let currentTitleSystem = '<?= htmlspecialchars($titleSystem, ENT_QUOTES) ?>';

let levels = JSON.parse(JSON.stringify(INITIAL));
let dirty = false;

function esc(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
function activeLevels() { return levels.filter(lv => lv.enabled); }

// ═══ 조직 계층: 접힘/펼침 ═══
function toggleHierarchyEdit(expand) {
    document.getElementById('hierarchyCollapsed').classList.toggle('hidden', expand);
    document.getElementById('hierarchyExpanded').classList.toggle('hidden', !expand);
    if (expand) renderLevels();
}

function renderHierarchySummary() {
    const el = document.getElementById('hierarchySummary');
    const enabled = activeLevels();
    el.innerHTML = enabled.map((lv, i) =>
        '<span class="text-gray-700 font-medium">' + esc(lv.label || '(미입력)') + '</span>' +
        (i < enabled.length - 1 ? '<span class="text-gray-300 mx-0.5">→</span>' : '')
    ).join('');
}

function renderLevels() {
    const ct = document.getElementById('levelList');
    const total = levels.length;
    ct.innerHTML = levels.map((lv, i) => {
        const isCompany = (lv.key === 'company');
        const isRequiredKey = REQUIRED_KEYS.includes(lv.key);
        const isLast = i === total - 1;
        const enabledCheck = isCompany
            ? '<span class="oh-lock"><i data-lucide="lock" class="w-2.5 h-2.5"></i>필수</span>'
            : '<label class="oh-toggle-label"><input type="checkbox" class="lv-enabled sr-only peer" data-i="'+i+'" '+(lv.enabled?'checked':'')+' /><div class="oh-toggle-track peer-checked:!bg-primary peer-checked:after:translate-x-[14px]"></div></label>';
        const deleteBtn = (isRequiredKey || total <= 2)
            ? ''
            : '<button class="lv-del oh-del" data-i="'+i+'" title="삭제"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>';
        const disabledClass = lv.enabled ? '' : ' oh-step-disabled';
        const dotStyle = lv.enabled
            ? 'background:linear-gradient(135deg, var(--zm-primary), #7c8aff); color:#fff;'
            : 'background:#e5e7eb; color:#9ca3af;';
        return '<div class="oh-step' + disabledClass + '">' +
            '<div class="oh-step-dot-col">' +
                '<span class="oh-step-dot" style="'+dotStyle+'">' + (i+1) + '</span>' +
                (isLast ? '' : '<span class="oh-step-line"></span>') +
            '</div>' +
            '<div class="oh-step-body">' +
                '<div class="oh-step-inputs">' +
                    '<input type="text" class="lv-label oh-input" data-i="'+i+'" value="'+esc(lv.label)+'" placeholder="조직명 (예: 본부)" />' +
                    '<span class="oh-input-sep">의 책임자</span>' +
                    '<input type="text" class="lv-head oh-input" data-i="'+i+'" value="'+esc(lv.head_title)+'" placeholder="호칭 (예: 본부장)" />' +
                '</div>' +
                '<div class="oh-step-actions">' + enabledCheck + deleteBtn + '</div>' +
            '</div>' +
        '</div>';
    }).join('');
    lucide.createIcons({ nodes: [ct] });
    bindLevelEvents();
    renderPreview();
    updateAddLevelState();
    checkDirty();
}

function bindLevelEvents() {
    document.querySelectorAll('.lv-label').forEach(el => el.addEventListener('input', () => { levels[+el.dataset.i].label = el.value; markDirty(); }));
    document.querySelectorAll('.lv-head').forEach(el => el.addEventListener('input', () => { levels[+el.dataset.i].head_title = el.value; markDirty(); }));
    document.querySelectorAll('.lv-enabled').forEach(el => el.addEventListener('change', () => { levels[+el.dataset.i].enabled = el.checked; renderLevels(); markDirty(); }));
    document.querySelectorAll('.lv-del').forEach(el => el.addEventListener('click', () => { levels.splice(+el.dataset.i, 1); levels.forEach((lv, j) => lv.depth = j); renderLevels(); markDirty(); }));
}

function markDirty() { dirty = true; checkDirty(); renderPreview(); renderHierarchySummary(); }
function updateAddLevelState() {
    const btn = document.getElementById('addLevelBtn');
    const isMax = levels.length >= MAX_LEVELS;
    btn.disabled = isMax;
    btn.classList.toggle('opacity-50', isMax);
    btn.classList.toggle('cursor-not-allowed', isMax);
}
function checkDirty() {
    const btn = document.getElementById('saveBtn');
    const status = document.getElementById('changeStatus');
    btn.disabled = !dirty;
    if (dirty) {
        status.textContent = '변경사항 있음';
        status.className = 'text-[11px] text-amber-500 font-medium transition-all duration-300';
    } else {
        status.textContent = '';
        status.className = 'text-[11px] text-gray-400 transition-all duration-300';
    }
}

// ═══ 미리보기 ═══
function renderPreview() {
    const enabled = activeLevels();
    const nonCompany = enabled.filter(lv => lv.key !== 'company');
    const companyLv = enabled.find(lv => lv.key === 'company');
    const sample = { name: '홍길동', rank: '과장', duty: '팀장', position: '선임연구원', dept: '인사팀' };

    // 이름 표시
    const dispEl = document.getElementById('previewDisplayName');
    const boardEl = document.getElementById('previewBoardName');
    const activeChip = document.querySelector('.dc-chip-active');
    const pattern = activeChip ? activeChip.dataset.pattern : '{name} {rank}';
    const formatted = pattern.replace('{name}', sample.name).replace('{rank}', sample.rank)
        .replace('{duty}', sample.duty).replace('{position}', sample.position)
        .replace('{dept}', sample.dept).replace(/\s+/g,' ').trim();
    dispEl.textContent = formatted;

    const suffixOn = document.getElementById('boardSuffixToggle')?.checked;
    boardEl.textContent = suffixOn ? '게시판: ' + formatted + '님' : '';
    boardEl.style.display = suffixOn ? '' : 'none';

    // 소속 라인
    const deptLine = document.getElementById('previewDeptLine');
    const orgNames = { '본부':'경영지원본부', '부서':'인사팀', '팀':'인사1팀', '실':'기획실', '센터':'기술센터', '그룹':'개발그룹', '파트':'프론트파트', '지사':'서울지사', '사업부':'신사업부' };
    const deptParts = nonCompany.map(lv => orgNames[lv.label] || lv.label || '?');
    deptLine.textContent = deptParts.join(' · ');

    // 결재선
    const flow = document.getElementById('previewFlow');
    const steps = [{label: '기안자', active: false}];
    for (let i = nonCompany.length - 1; i >= 0; i--) {
        steps.push({label: nonCompany[i].head_title || '?', active: true});
    }
    if (companyLv) steps.push({label: companyLv.head_title || '?', active: true});
    flow.innerHTML = steps.map((s, i) => {
        const isLast = i === steps.length - 1;
        const bg = s.active ? 'background:var(--zm-chip-bg, #eef2ff); color:var(--zm-text-strong); font-weight:600;' : 'background:var(--zm-surface-1, #f3f4f6); color:var(--zm-text-muted, #9ca3af);';
        const arrow = !isLast ? '<svg width="12" height="12" viewBox="0 0 12 12" style="color:var(--zm-border, #d1d5db); flex-shrink:0;"><path d="M4.5 2.5L7.5 6L4.5 9.5" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>' : '';
        return '<span style="display:inline-flex; align-items:center; padding:3px 8px; border-radius:4px; font-size:10px; letter-spacing:-0.01em; '+bg+'">' + esc(s.label) + '</span>' + arrow;
    }).join('');

    // 필터
    const filters = document.getElementById('previewFilters');
    filters.innerHTML = nonCompany.map(lv => {
        const label = lv.label || '(미입력)';
        const opacity = lv.label ? '' : ' opacity-40';
        return '<span class="inline-flex items-center gap-0.5 px-2 py-0.5 text-[10px] text-gray-500 bg-white border border-gray-200 rounded-md' + opacity + '" style="box-shadow:0 1px 2px rgba(0,0,0,0.04)"><span>전체 ' + esc(label) + '</span><span class="text-gray-300 text-[8px] ml-0.5">&#9662;</span></span>';
    }).join('');

    // 미니 테이블
    const headers = document.getElementById('previewHeaders');
    const cols = ['이름', ...nonCompany.map(lv => lv.label || '?'), '직급'];
    headers.innerHTML = cols.map(c =>
        '<span style="flex:1; padding:5px 8px; font-size:9px; font-weight:600; color:var(--zm-text-muted, #9ca3af); min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">' + esc(c) + '</span>'
    ).join('');

    const row = document.getElementById('previewRow');
    const sampleOrgs = nonCompany.map(lv => orgNames[lv.label] || lv.label || '—');
    const rowData = [formatted, ...sampleOrgs, sample.rank];
    row.innerHTML = rowData.map((d, i) =>
        '<span style="flex:1; padding:5px 8px; font-size:10px; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; ' +
        (i === 0 ? 'font-weight:600; color:var(--zm-text-strong, #334155);' : 'color:var(--zm-text-subtle, #6b7280);') + '">' + esc(d) + '</span>'
    ).join('');
}

// ═══ 조직 계층 저장 ═══
document.getElementById('addLevelBtn').addEventListener('click', () => {
    if (levels.length >= MAX_LEVELS) {
        alert('조직 계층은 최대 ' + MAX_LEVELS + '단계까지 설정할 수 있습니다.');
        return;
    }
    let n = levels.length;
    let key = 'level_' + n;
    while (levels.some(lv => lv.key === key)) { n++; key = 'level_' + n; }
    levels.push({ depth: levels.length, key, label: '', head_title: '', enabled: true });
    renderLevels();
    markDirty();
    const lastLabel = document.querySelectorAll('.lv-label');
    if (lastLabel.length) lastLabel[lastLabel.length - 1].focus();
});

document.getElementById('saveBtn').addEventListener('click', async () => {
    const btn = document.getElementById('saveBtn');
    for (let i = 0; i < levels.length; i++) {
        if (!levels[i].key) { alert('계층 정보가 올바르지 않습니다.'); return; }
        if (!levels[i].label) { alert((i+1) + '번째 계층의 이름이 비어있습니다.'); return; }
        if (!levels[i].head_title) { alert((i+1) + '번째 계층의 책임자 호칭이 비어있습니다.'); return; }
    }
    const keys = levels.map(lv => lv.key);
    if (new Set(keys).size !== keys.length) { alert('계층 정보가 올바르지 않습니다.'); return; }
    for (const key of REQUIRED_KEYS) {
        if (!keys.includes(key)) { alert('필수 계층은 삭제할 수 없습니다.'); return; }
    }
    if (!(await AppUI.confirm('조직 용어 설정을 저장하면 필터, 테이블 헤더, 결재선에 즉시 적용됩니다. 저장할까요?'))) return;

    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i>저장 중';
    lucide.createIcons({ nodes: [btn] });
    try {
        const res = await fetch(API + '?action=save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ levels })
        });
        const json = await res.json();
        if (!res.ok || !json.ok) throw new Error(json.error?.message || '저장 실패');
        location.reload();
    } catch (e) {
        alert(e.message || '저장 중 오류가 발생했습니다.');
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="save" class="w-3.5 h-3.5"></i>저장';
        lucide.createIcons({ nodes: [btn] });
    }
});

// ═══ 직함 체계 ═══
const TITLE_SYSTEMS = [
    { key: 'rank_and_duty', label: '직급 + 직책', desc: '사원→대리→과장(직급)과 팀장·본부장(직책)을 나눠서 관리해요. 가장 많이 쓰는 방식이에요.', cols: ['ranks','duties'] },
    { key: 'rank_duty_position', label: '직급 + 직책 + 직위', desc: '직급·직책에 더해 명함에 들어갈 직위(수석연구원 등)도 따로 관리해요. 연구소·대기업에 적합해요.', cols: ['ranks','duties','positions'] },
    { key: 'rank_only', label: '직급만', desc: '팀장·부장 같은 역할 구분 없이 직급(사원→과장→부장)만 써요.', cols: ['ranks'] },
    { key: 'duty_only', label: '직책만', desc: '연차 기반 직급 없이, 맡은 역할(팀장·실장 등)로만 표시해요.', cols: ['duties'] },
    { key: 'free_text', label: '자유 입력', desc: '목록에서 고르지 않고, 직원 등록할 때 직접 텍스트를 입력해요.', cols: [] },
    { key: 'none', label: '사용 안 함', desc: '직급·직책 없이 이름만 표시해요.', cols: [] },
];

function getVisibleCols() {
    const sys = TITLE_SYSTEMS.find(s => s.key === currentTitleSystem);
    return sys ? sys.cols : [];
}

function renderTitleSystem() {
    const sel = document.getElementById('titleSystemSelect');
    const desc = document.getElementById('titleSystemDesc');
    sel.innerHTML = TITLE_SYSTEMS.map(opt =>
        `<option value="${opt.key}" ${opt.key === currentTitleSystem ? 'selected' : ''}>${esc(opt.label)}</option>`
    ).join('');

    const cur = TITLE_SYSTEMS.find(s => s.key === currentTitleSystem);
    if (desc && cur) desc.textContent = cur.desc;

    sel.addEventListener('change', async () => {
        const val = sel.value;
        const sys = TITLE_SYSTEMS.find(s => s.key === val);
        if (desc && sys) desc.textContent = sys.desc;

        try {
            const res = await fetch(API + '?action=saveTitleSystem', {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ title_system: val })
            });
            const json = await res.json();
            if (!json.ok) throw new Error(json.error?.message || '저장 실패');
            currentTitleSystem = val;
            renderHrColumns();
            updateDisplayFormatOptions();
        } catch(e) {
            alert(e.message);
        }
    });
}

// ═══ 직급/직책/직위 컬럼 ═══
let hrRanks = [];
let hrDuties = [];
let hrPositions = [];

const HR_COL_CONFIG = {
    ranks: { label: '직급', addLabel: '직급 추가' },
    duties: { label: '직책', addLabel: '직책 추가' },
    positions: { label: '직위', addLabel: '직위 추가' },
};

function renderHrColumns() {
    const cols = getVisibleCols();
    const container = document.getElementById('hrColumns');
    const emptyMsg = document.getElementById('hrColumnsEmpty');

    if (!cols.length) {
        container.innerHTML = '';
        container.style.display = 'none';
        emptyMsg.classList.remove('hidden');
        return;
    }

    emptyMsg.classList.add('hidden');
    container.style.display = '';
    container.style.gridTemplateColumns = `repeat(${cols.length}, minmax(0, 1fr))`;

    container.innerHTML = cols.map(type => {
        const cfg = HR_COL_CONFIG[type];
        return `<div class="hr-col" id="hrCol_${type}">
            <div class="hr-col-header">
                <span class="hr-col-title">${cfg.label}<span class="hr-col-count" id="hrCount_${type}"></span></span>
                <button class="hr-col-add" onclick="inlineAddToCol('${type}', this)" title="${cfg.addLabel}">+</button>
            </div>
            <div class="hr-col-loading"><i data-lucide="loader-2" class="w-4 h-4 animate-spin inline-block mb-1"></i><br>불러오는 중...</div>
        </div>`;
    }).join('');

    lucide.createIcons({ nodes: [container] });
    cols.forEach(type => loadHrItems(type));
}

const TEMPLATES = {
    ranks: {
        standard: { label: '일반 기업', desc: '대표이사부터 인턴까지 9단계', icon: 'building-2',
            items: [{name:'대표이사',tier:1},{name:'이사',tier:2},{name:'부장',tier:3},{name:'차장',tier:4},{name:'과장',tier:5},{name:'대리',tier:6},{name:'주임',tier:7},{name:'사원',tier:8},{name:'인턴',tier:9}]},
        startup: { label: '스타트업', desc: 'C레벨 + 리드/시니어/주니어 5단계', icon: 'rocket',
            items: [{name:'C-Level',tier:1},{name:'Lead',tier:2},{name:'Senior',tier:3},{name:'Junior',tier:4},{name:'Intern',tier:5}]},
    },
    duties: {
        standard: { label: '일반 기업', desc: 'CEO부터 파트장까지 4단계 7개 직책', icon: 'building-2',
            items: [{name:'CEO',tier:1},{name:'CTO',tier:1},{name:'COO',tier:1},{name:'본부장',tier:2},{name:'팀장',tier:3},{name:'실장',tier:3},{name:'파트장',tier:4}]},
        startup: { label: '스타트업', desc: 'CEO + Head/Lead 3단계', icon: 'rocket',
            items: [{name:'CEO',tier:1},{name:'CTO',tier:1},{name:'Head',tier:2},{name:'Lead',tier:3}]},
    },
    positions: {
        research: { label: '연구소', desc: '수석연구원부터 연구원까지 4단계', icon: 'flask-conical',
            items: [{name:'수석연구원',tier:1},{name:'책임연구원',tier:2},{name:'선임연구원',tier:3},{name:'연구원',tier:4}]},
        consulting: { label: '컨설팅', desc: '파트너부터 어소시에이트까지 4단계', icon: 'briefcase',
            items: [{name:'파트너',tier:1},{name:'시니어 컨설턴트',tier:2},{name:'컨설턴트',tier:3},{name:'어소시에이트',tier:4}]},
    }
};

async function loadHrItems(type) {
    const col = document.getElementById('hrCol_' + type);
    if (!col) return;

    const actionMap = { ranks: 'getRanks', duties: 'getDuties', positions: 'getPositions' };
    try {
        const res = await fetch(API + '?action=' + actionMap[type]);
        const json = await res.json();
        if (!json.ok) { renderColEmpty(type); return; }

        const items = json.data.items || [];
        if (type === 'ranks') hrRanks = items;
        else if (type === 'duties') hrDuties = items;
        else if (type === 'positions') hrPositions = items;

        const countEl = document.getElementById('hrCount_' + type);
        if (countEl) countEl.textContent = items.length > 0 ? items.length + '개' : '';

        if (!items.length) { renderColEmpty(type); return; }
        renderColLadder(type, items);
    } catch (e) {
        console.error('loadHrItems error', e);
        renderColEmpty(type);
    }
}

function renderColEmpty(type) {
    const col = document.getElementById('hrCol_' + type);
    if (!col) return;
    const cfg = HR_COL_CONFIG[type];
    const tpls = TEMPLATES[type];
    const loading = col.querySelector('.hr-col-loading');
    if (loading) loading.style.display = 'none';

    const existing = col.querySelector('.hr-col-empty');
    if (existing) existing.remove();
    const ladderWrap = col.querySelector('.ladder-wrap');
    if (ladderWrap) ladderWrap.remove();

    const emptyDiv = document.createElement('div');
    emptyDiv.className = 'hr-col-empty';
    emptyDiv.innerHTML = `
        <p class="text-xs font-medium text-gray-500 mb-3">${cfg.label} 체계를 선택하세요</p>
        <div class="space-y-1.5">
            ${Object.entries(tpls).map(([key, tpl]) => `
            <button class="tpl-pick w-full text-left px-3 py-2 rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition-colors" data-type="${type}" data-tpl="${key}">
                <span class="text-xs font-semibold text-gray-700">${esc(tpl.label)}</span>
                <span class="text-[10px] text-gray-400 ml-1">${esc(tpl.desc)}</span>
            </button>`).join('')}
            <button class="tpl-custom w-full text-left px-3 py-2 rounded-lg border border-dashed border-gray-300 hover:border-gray-400 hover:bg-gray-50 transition-colors text-xs text-gray-400" data-type="${type}">
                + 직접 만들기
            </button>
        </div>
    `;
    col.appendChild(emptyDiv);

    emptyDiv.querySelectorAll('.tpl-pick').forEach(btn => {
        btn.addEventListener('click', () => applyTemplate(btn.dataset.type, btn.dataset.tpl));
    });
    emptyDiv.querySelectorAll('.tpl-custom').forEach(btn => {
        btn.addEventListener('click', () => inlineAddToCol(btn.dataset.type, btn));
    });
}


function renderColLadder(type, items) {
    const col = document.getElementById('hrCol_' + type);
    if (!col) return;
    const loading = col.querySelector('.hr-col-loading');
    if (loading) loading.style.display = 'none';
    const existing = col.querySelector('.hr-col-empty');
    if (existing) existing.remove();
    let wrap = col.querySelector('.ladder-wrap');
    if (!wrap) { wrap = document.createElement('div'); wrap.className = 'ladder-wrap'; col.appendChild(wrap); }

    const sorted = [...items].sort((a, b) => (+a.tier || 1) - (+b.tier || 1) || (+a.sort_order || 0) - (+b.sort_order || 0));

    wrap.innerHTML = '<div class="hr-list">' +
        sorted.map((item, idx) => {
            const off = item.is_active != 1;
            const usage = +item.usage_count > 0 ? item.usage_count : 0;
            return `<div class="hr-item${off ? ' hr-item-off' : ''}" data-id="${item.id}" data-type="${type}">
                <span class="hr-num">${idx + 1}</span>
                <span class="hr-grip" title="드래그하여 순서 변경"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg></span>
                <input class="hr-name" value="${esc(item.name)}" data-id="${item.id}" data-type="${type}" data-orig="${esc(item.name)}" />
                <span class="hr-use${usage === 0 ? ' hr-use-zero' : ''}">${usage}명</span>
                <button class="hr-menu-btn" data-id="${item.id}" data-type="${type}" data-active="${item.is_active}">⋮</button>
            </div>`;
        }).join('') +
        `<div class="hr-add-row"><button class="hr-add-btn" data-type="${type}">+ 추가</button></div>` +
    '</div>';

    // 인라인 편집
    wrap.querySelectorAll('.hr-name').forEach(input => {
        input.addEventListener('blur', () => inlineSave(input));
        input.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); input.blur(); } if (e.key === 'Escape') { input.value = input.dataset.orig; input.blur(); } });
    });

    // kebab 메뉴
    wrap.querySelectorAll('.hr-menu-btn').forEach(btn => {
        btn.addEventListener('click', e => {
            e.stopPropagation();
            closeAllDropdowns();
            btn.closest('.hr-item').style.zIndex = '60';
            const dd = document.createElement('div');
            dd.className = 'hr-dropdown';
            const isActive = btn.dataset.active == 1;
            dd.innerHTML = `
                <button class="hr-dropdown-item" data-action="toggle">${isActive ? '비활성화' : '활성화'}</button>
                <button class="hr-dropdown-item hr-dropdown-item-danger" data-action="delete">삭제</button>
            `;
            btn.closest('.hr-item').appendChild(dd);
            dd.querySelector('[data-action="toggle"]').addEventListener('click', () => { closeAllDropdowns(); toggleHrActive(btn.dataset.type, +btn.dataset.id, +btn.dataset.active); });
            dd.querySelector('[data-action="delete"]').addEventListener('click', () => { closeAllDropdowns(); deleteHrItem(btn.dataset.type, +btn.dataset.id); });
        });
    });

    // 드래그 앤 드롭 — 이벤트 위임 + 매 드래그마다 fresh DOM 읽기 (1번 고정)
    const hrList = wrap.querySelector('.hr-list');
    let dragState = null;

    // 첫 번째 아이템(대표이사 등) 고정
    const firstItem = wrap.querySelector('.hr-item');
    if (firstItem) firstItem.classList.add('hr-item-fixed');

    hrList.addEventListener('pointerdown', e => {
        const grip = e.target.closest('.hr-grip');
        if (!grip) return;
        const row = grip.closest('.hr-item');
        if (!row || row.classList.contains('hr-item-fixed')) return;

        const items = [...hrList.querySelectorAll('.hr-item')];
        const idx = items.indexOf(row);
        if (idx < 0) return;

        e.preventDefault();
        const rect = row.getBoundingClientRect();
        const offsetX = e.clientX - rect.left, offsetY = e.clientY - rect.top;
        const origTops = items.map(el => el.getBoundingClientRect().top);
        const itemH = items.length > 1 ? origTops[1] - origTops[0] : rect.height;

        const ghost = row.cloneNode(true);
        ghost.classList.add('hr-drag-ghost');
        ghost.style.cssText = `position:fixed;left:${rect.left}px;top:${rect.top}px;width:${rect.width}px;height:${rect.height}px;z-index:9999;pointer-events:none;transition:none;`;
        document.body.appendChild(ghost);
        row.classList.add('dragging');
        hrList.classList.add('zm-drag-active');

        dragState = { fromIdx: idx, gapIdx: idx, origTops, itemH, ghost, row, items, offsetX, offsetY };

        function onMove(ev) {
            if (!dragState) return;
            ghost.style.left = (ev.clientX - offsetX) + 'px';
            ghost.style.top = (ev.clientY - offsetY) + 'px';
            let newGap = items.length;
            for (let i = 0; i < items.length; i++) {
                if (ev.clientY < origTops[i] + itemH / 2) { newGap = i; break; }
            }
            if (newGap < 1) newGap = 1;
            if (newGap === dragState.gapIdx) return;
            dragState.gapIdx = newGap;
            items.forEach((el, i) => {
                if (i === idx) return;
                let shift = 0;
                if (idx < newGap && i > idx && i < newGap) shift = -itemH;
                else if (idx > newGap && i >= newGap && i < idx) shift = itemH;
                el.style.transform = shift ? `translateY(${shift}px)` : '';
            });
        }
        function onUp() {
            document.removeEventListener('pointermove', onMove);
            document.removeEventListener('pointerup', onUp);
            document.removeEventListener('pointercancel', onUp);
            if (!dragState) return;
            const { fromIdx: dFromIdx, gapIdx: dGapIdx } = dragState;
            if (ghost.isConnected) ghost.remove();
            row.classList.remove('dragging');
            hrList.classList.remove('zm-drag-active');
            items.forEach(el => el.style.transform = '');

            if (dGapIdx === dFromIdx || dGapIdx === dFromIdx + 1) {
                dragState = null;
                return;
            }
            const ids = items.map(el => el.dataset.id);
            const movedId = ids.splice(dFromIdx, 1)[0];
            const insertAt = dGapIdx > dFromIdx ? dGapIdx - 1 : dGapIdx;
            ids.splice(insertAt, 0, movedId);

            const visualRects = new Map();
            items.forEach(el => visualRects.set(el.dataset.id, el.getBoundingClientRect()));

            const addRow = hrList.querySelector('.hr-add-row');
            ids.forEach(id => { const el = items.find(e => e.dataset.id === id); if (el) hrList.insertBefore(el, addRow); });

            const reordered = [...hrList.querySelectorAll('.hr-item[data-id]')];
            reordered.forEach(el => {
                const oldR = visualRects.get(el.dataset.id);
                if (!oldR) return;
                const dy = oldR.top - el.getBoundingClientRect().top;
                if (Math.abs(dy) < 1) return;
                el.style.transform = `translateY(${dy}px)`;
            });
            requestAnimationFrame(() => {
                reordered.forEach(el => { el.classList.add('hr-flip'); el.style.transform = ''; });
                setTimeout(() => {
                    reordered.forEach(el => el.classList.remove('hr-flip'));
                    reordered.forEach((el, i) => { const n = el.querySelector('.hr-num'); if (n) n.textContent = i + 1; });
                }, 300);
            });
            showReorderBar(type, wrap, ids.map(Number));
            dragState = null;
        }
        document.addEventListener('pointermove', onMove);
        document.addEventListener('pointerup', onUp);
        document.addEventListener('pointercancel', onUp);
    });

    // 추가 버튼 (행 전체 클릭)
    wrap.querySelectorAll('.hr-add-row').forEach(row => {
        const btn = row.querySelector('.hr-add-btn');
        row.addEventListener('click', () => {
            const itemsMap = { ranks: hrRanks, duties: hrDuties, positions: hrPositions };
            const its = itemsMap[btn.dataset.type] || [];
            const maxTier = its.length ? Math.max(...its.map(i => +i.tier || 1)) + 1 : 1;
            inlineAdd(btn.dataset.type, maxTier, btn);
        });
    });
}

function showReorderBar(type, wrap, pendingIds) {
    let bar = wrap.querySelector('.hr-reorder-bar');
    if (!bar) {
        bar = document.createElement('div');
        bar.className = 'hr-reorder-bar';
        bar.innerHTML = '<button class="hr-cancel-btn">취소</button><button class="hr-save-btn">저장</button>';
        wrap.appendChild(bar);
        bar.querySelector('.hr-cancel-btn').addEventListener('click', () => { bar.remove(); loadHrItems(type); });
    }
    const saveBtn = bar.querySelector('.hr-save-btn');
    const newSave = saveBtn.cloneNode(true);
    saveBtn.replaceWith(newSave);
    newSave.addEventListener('click', () => { bar.remove(); reorderHrItems(type, pendingIds, true); });
}

function closeAllDropdowns() {
    document.querySelectorAll('.hr-dropdown').forEach(dd => { dd.closest('.hr-item').style.zIndex = ''; dd.remove(); });
}
document.addEventListener('click', closeAllDropdowns);

async function reorderHrItems(type, ids, rerender = true) {
    const actionMap = { ranks: 'reorderRanks', duties: 'reorderDuties', positions: 'reorderPositions' };
    try {
        const res = await fetch(API + '?action=' + actionMap[type], {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids })
        });
        const json = await res.json();
        if (!json.ok) throw new Error(json.error?.message || '순서 변경 실패');
        if (rerender) loadHrItems(type);
        else {
            const itemsMap = { ranks: hrRanks, duties: hrDuties, positions: hrPositions };
            const arr = itemsMap[type] || [];
            ids.forEach((id, i) => {
                const it = arr.find(x => x.id == id);
                if (it) { it.sort_order = i + 1; it.tier = i + 1; }
            });
        }
    } catch (e) { alert(e.message); loadHrItems(type); }
}

async function toggleHrActive(type, id, currentActive) {
    const itemsMap = { ranks: hrRanks, duties: hrDuties, positions: hrPositions };
    const item = (itemsMap[type] || []).find(i => i.id == id);
    if (!item) return;
    const saveActionMap = { ranks: 'saveRank', duties: 'saveDuty', positions: 'savePosition' };
    try {
        const res = await fetch(API + '?action=' + saveActionMap[type], {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, name: item.name, tier: +item.tier, sort_order: item.sort_order, is_active: currentActive == 1 ? 0 : 1 })
        });
        const json = await res.json();
        if (!json.ok) throw new Error(json.error?.message || '변경 실패');
        loadHrItems(type);
    } catch (e) { alert(e.message); }
}

async function inlineSave(input) {
    const name = input.value.trim();
    const orig = input.dataset.orig;
    if (!name || name === orig) { input.value = orig; return; }
    const type = input.dataset.type;
    const id = +input.dataset.id;
    const itemsMap = { ranks: hrRanks, duties: hrDuties, positions: hrPositions };
    const item = (itemsMap[type] || []).find(i => i.id == id);
    if (!item) return;
    const saveActionMap = { ranks: 'saveRank', duties: 'saveDuty', positions: 'savePosition' };
    try {
        const res = await fetch(API + '?action=' + saveActionMap[type], {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, name, tier: +item.tier, sort_order: item.sort_order, is_active: +item.is_active })
        });
        const json = await res.json();
        if (!json.ok) throw new Error(json.error?.message || '저장 실패');
        input.dataset.orig = name;
        loadHrItems(type);
    } catch (e) { alert(e.message); input.value = orig; }
}


function inlineAdd(type, tier, anchorBtn) {
    const row = anchorBtn.closest('.hr-add-row') || anchorBtn.parentElement;
    const existing = row.querySelector('.hr-add-input');
    if (existing) { existing.focus(); return; }
    const input = document.createElement('input');
    input.className = 'hr-add-input';
    input.placeholder = { ranks: '직급명', duties: '직책명', positions: '직위명' }[type] || '이름';
    row.insertBefore(input, anchorBtn);
    anchorBtn.style.display = 'none';
    input.focus();

    const save = async () => {
        const name = input.value.trim();
        if (!name) { input.remove(); anchorBtn.style.display = ''; return; }
        const itemsMap = { ranks: hrRanks, duties: hrDuties, positions: hrPositions };
        const items = itemsMap[type] || [];
        const nextOrder = items.length ? Math.max(...items.map(i => +i.sort_order)) + 1 : 1;
        const saveActionMap = { ranks: 'saveRank', duties: 'saveDuty', positions: 'savePosition' };
        try {
            const res = await fetch(API + '?action=' + saveActionMap[type], {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, tier, sort_order: nextOrder, is_active: 1 })
            });
            const json = await res.json();
            if (!json.ok) throw new Error(json.error?.message || '추가 실패');
            loadHrItems(type);
        } catch (e) { alert(e.message); input.remove(); }
    };
    input.addEventListener('blur', save);
    input.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); input.blur(); } if (e.key === 'Escape') { input.value = ''; input.remove(); anchorBtn.style.display = ''; } });
}

function inlineAddToCol(type, btn) {
    const itemsMap = { ranks: hrRanks, duties: hrDuties, positions: hrPositions };
    const items = itemsMap[type] || [];
    const maxTier = items.length ? Math.max(...items.map(i => +i.tier || 1)) : 1;
    const col = document.getElementById('hrCol_' + type);
    if (!col) return;
    const addBtn = col.querySelector('.hr-add-btn');
    if (addBtn) inlineAdd(type, maxTier, addBtn);
    else inlineAdd(type, 1, btn);
}

async function applyTemplate(type, tplKey) {
    const tpl = TEMPLATES[type][tplKey];
    if (!tpl) return;
    const saveActionMap = { ranks:'saveRank', duties:'saveDuty', positions:'savePosition' };
    const resetActionMap = { ranks:'resetRanks', duties:'resetDuties', positions:'resetPositions' };

    try {
        await fetch(API + '?action=' + resetActionMap[type], { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: '{}' });
    } catch (e) { /* ignore */ }

    for (let i = 0; i < tpl.items.length; i++) {
        const it = tpl.items[i];
        try {
            await fetch(API + '?action=' + saveActionMap[type], {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name: it.name, tier: it.tier, sort_order: i + 1, is_active: 1 })
            });
        } catch (e) { break; }
    }
    loadHrItems(type);
}




async function deleteHrItem(type, id) {
    const labelMap = { ranks: '직급', duties: '직책', positions: '직위' };
    const label = labelMap[type] || type;
    const itemsMap = { ranks: hrRanks, duties: hrDuties, positions: hrPositions };
    const item = (itemsMap[type] || []).find(i => i.id == id);
    const usage = item ? +item.usage_count : 0;
    const msg = usage > 0
        ? `"${item.name}" ${label}을(를) 사용 중인 직원이 ${usage}명 있습니다.\n삭제하면 해당 직원의 ${label}이 비워집니다. 삭제할까요?`
        : `"${item?.name || ''}" ${label}을(를) 삭제하시겠습니까?`;
    if (!(await AppUI.confirm(msg))) return;

    const deleteActionMap = { ranks: 'deleteRank', duties: 'deleteDuty', positions: 'deletePosition' };
    try {
        const res = await fetch(API + '?action=' + deleteActionMap[type], {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const json = await res.json();
        if (!json.ok) throw new Error(json.error?.message || '삭제 실패');
        loadHrItems(type);
    } catch (e) {
        alert(e.message);
    }
}

// ═══ 이름 표시 형식 ═══
const DISPLAY_CONTEXTS = ['default', 'org_chart', 'approval', 'board', 'profile'];
let FORMAT_OPTIONS = [
    { pattern: '{name}', label: '이름만', preview: '홍길동' },
    { pattern: '{name} {rank}', label: '이름 + 직급', preview: '홍길동 과장' },
    { pattern: '{name} {duty}', label: '이름 + 직책', preview: '홍길동 팀장' },
    { pattern: '{name} {rank} ({duty})', label: '이름 + 직급 + 직책', preview: '홍길동 과장 (팀장)' },
];
let displayConfigs = [];
let displayLoaded = false;

function updateDisplayFormatOptions() {
    const sys = currentTitleSystem;
    const hasRank = ['rank_and_duty','rank_duty_position','rank_only'].includes(sys);
    const hasDuty = ['rank_and_duty','rank_duty_position','duty_only'].includes(sys);
    const hasPosition = sys === 'rank_duty_position';

    FORMAT_OPTIONS.length = 0;
    FORMAT_OPTIONS.push({ pattern: '{name}', label: '이름만', preview: '홍길동' });
    if (hasRank) FORMAT_OPTIONS.push({ pattern: '{name} {rank}', label: '이름 + 직급', preview: '홍길동 과장' });
    if (hasDuty) FORMAT_OPTIONS.push({ pattern: '{name} {duty}', label: '이름 + 직책', preview: '홍길동 팀장' });
    if (hasRank && hasDuty) FORMAT_OPTIONS.push({ pattern: '{name} {rank} ({duty})', label: '이름 + 직급 + 직책', preview: '홍길동 과장 (팀장)' });
    if (hasPosition) FORMAT_OPTIONS.push({ pattern: '{name} {position}', label: '이름 + 직위', preview: '홍길동 수석연구원' });
    if (hasPosition && hasRank) FORMAT_OPTIONS.push({ pattern: '{name} {rank} ({position})', label: '이름 + 직급 + 직위', preview: '홍길동 과장 (수석연구원)' });

    displayLoaded = false;
    loadDisplayConfig();
}

async function loadDisplayConfig() {
    if (displayLoaded) return;
    try {
        const res = await fetch(API + '?action=getDisplayConfig');
        const json = await res.json();
        if (json.ok) {
            displayConfigs = json.data.items || [];
            displayLoaded = true;
            renderDisplayConfig();
        }
    } catch (e) {
        console.error('display config load error:', e);
    }
}

function renderDisplayConfig() {
    const wrap = document.getElementById('displayFormatOptions');
    if (!wrap) return;

    const defaultCfg = displayConfigs.find(c => c.context_key === 'default') || { format_pattern: '{name} {rank}', suffix: '' };
    const boardCfg = displayConfigs.find(c => c.context_key === 'board') || { format_pattern: '{name} {rank}', suffix: '' };
    const currentPattern = defaultCfg.format_pattern || '{name} {rank}';
    const hasSuffix = !!(boardCfg.suffix);

    wrap.innerHTML = FORMAT_OPTIONS.map(opt => {
        const selected = opt.pattern === currentPattern;
        return `<label class="dc-chip${selected ? ' dc-chip-active' : ''}" data-pattern="${esc(opt.pattern)}">
            <input type="radio" name="displayFormat" value="${esc(opt.pattern)}" ${selected ? 'checked' : ''} class="sr-only">
            ${esc(opt.preview)}
        </label>`;
    }).join('');

    const toggle = document.getElementById('boardSuffixToggle');
    if (toggle) toggle.checked = hasSuffix;

    const savedPattern = currentPattern;
    const savedSuffix = hasSuffix;

    function checkDcDirty() {
        const curPat = document.querySelector('input[name="displayFormat"]:checked')?.value || '';
        const curSuf = document.getElementById('boardSuffixToggle')?.checked || false;
        const dirty = curPat !== savedPattern || curSuf !== savedSuffix;
        const wrap = document.getElementById('dcSaveWrap');
        if (wrap) wrap.style.display = dirty ? '' : 'none';
    }

    wrap.querySelectorAll('input[name="displayFormat"]').forEach(radio => {
        radio.addEventListener('change', () => {
            wrap.querySelectorAll('.dc-chip').forEach(c => c.classList.remove('dc-chip-active'));
            radio.closest('.dc-chip').classList.add('dc-chip-active');
            renderPreview();
            checkDcDirty();
        });
    });
    if (toggle) toggle.addEventListener('change', () => { renderPreview(); checkDcDirty(); });
}

async function saveDisplayConfig() {
    const btn = document.getElementById('dcSaveBtn');
    if (btn?.disabled) return;
    const pattern = document.querySelector('input[name="displayFormat"]:checked')?.value || '{name} {rank}';
    const hasSuffix = document.getElementById('boardSuffixToggle')?.checked;

    const items = DISPLAY_CONTEXTS.map(ctx => ({
        context_key: ctx,
        format_pattern: ctx === 'board' && hasSuffix ? pattern + '{suffix}' : pattern,
        suffix: ctx === 'board' && hasSuffix ? '님' : null,
    }));

    try {
        const res = await fetch(API + '?action=saveDisplayConfig', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ items })
        });
        const json = await res.json();
        if (!json.ok) throw new Error(json.error?.message || '저장 실패');
        displayLoaded = false;
        loadDisplayConfig();
    } catch (e) {
        alert(e.message);
    }
}

// ═══ 초기화 ═══
renderHierarchySummary();
renderTitleSystem();
renderHrColumns();
loadDisplayConfig();
renderPreview();
lucide.createIcons();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
