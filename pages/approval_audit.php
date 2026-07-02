<?php
$pageTitle = '결재 감사로그';
$currentPage = 'approval_admin';
require_once __DIR__ . '/../config/database.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$userRole = $_SESSION['user']['role'] ?? '';
if ($userRole !== 'admin') {
    echo '<div class="ml-60 mt-14 p-8"><p style="color:var(--zm-text-muted)">관리자만 접근할 수 있습니다.</p></div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6 min-h-screen" style="background:var(--zm-surface-1)">
        <!-- 헤더 -->
        <div class="rounded-2xl p-4 mb-4" style="background:var(--zm-surface-0); border:1px solid var(--zm-border)">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:rgba(79,106,255,0.12)">
                    <i data-lucide="shield-check" class="w-4 h-4" style="color:var(--zm-primary)"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold" style="color:var(--zm-text-strong)">결재 감사로그</h2>
                    <span class="text-xs" style="color:var(--zm-text-muted)">관리자 전용 · 결재 시스템의 모든 변경 이력을 추적</span>
                </div>
            </div>
        </div>

        <!-- 통계 카드 -->
        <div class="grid grid-cols-4 gap-4 mb-5">
            <div class="rounded-xl p-5" style="background:var(--zm-surface-0); border:1px solid var(--zm-border)">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:rgba(79,106,255,0.12)">
                        <i data-lucide="activity" class="w-4 h-4" style="color:var(--zm-primary)"></i>
                    </div>
                    <span class="text-xs font-medium" style="color:var(--zm-text-muted)">오늘</span>
                </div>
                <div class="flex items-baseline gap-1">
                    <span class="text-2xl font-bold" style="color:var(--zm-text-strong)" id="statToday">-</span>
                    <span class="text-xs" style="color:var(--zm-text-muted)">건</span>
                </div>
            </div>
            <div class="rounded-xl p-5" style="background:var(--zm-surface-0); border:1px solid var(--zm-border)">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:rgba(16,185,129,0.12)">
                        <i data-lucide="bar-chart-3" class="w-4 h-4" style="color:#10b981"></i>
                    </div>
                    <span class="text-xs font-medium" style="color:var(--zm-text-muted)">최근 7일</span>
                </div>
                <div class="flex items-baseline gap-1">
                    <span class="text-2xl font-bold" style="color:var(--zm-text-strong)" id="statWeek">-</span>
                    <span class="text-xs" style="color:var(--zm-text-muted)">건</span>
                </div>
            </div>
            <div class="rounded-xl p-5" style="background:var(--zm-surface-0); border:1px solid var(--zm-border)">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:rgba(244,63,94,0.12)">
                        <i data-lucide="shield-alert" class="w-4 h-4" style="color:#f43f5e"></i>
                    </div>
                    <span class="text-xs font-medium" style="color:var(--zm-text-muted)">관리자 조작 (7일)</span>
                </div>
                <div class="flex items-baseline gap-1">
                    <span class="text-2xl font-bold" style="color:var(--zm-text-strong)" id="statAdmin">-</span>
                    <span class="text-xs" style="color:var(--zm-text-muted)">건</span>
                </div>
            </div>
            <div class="rounded-xl p-5" style="background:var(--zm-surface-0); border:1px solid var(--zm-border)">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:rgba(107,114,128,0.12)">
                        <i data-lucide="trending-up" class="w-4 h-4" style="color:var(--zm-text-subtle)"></i>
                    </div>
                    <span class="text-xs font-medium" style="color:var(--zm-text-muted)">주요 이벤트 TOP 3</span>
                </div>
                <div id="topEventsBody"></div>
            </div>
        </div>

        <!-- 탭 -->
        <div class="flex gap-1 mb-0">
            <button data-tab-btn="logs" onclick="switchTab('logs')"
                class="audit-tab audit-tab-active px-5 py-2.5 text-sm font-semibold rounded-t-lg transition-colors"
                style="border:1px solid var(--zm-border); border-bottom:none">
                <i data-lucide="scroll-text" class="w-4 h-4 inline -mt-0.5"></i> 감사로그
            </button>
            <button data-tab-btn="actions" onclick="switchTab('actions')"
                class="audit-tab px-5 py-2.5 text-sm font-semibold rounded-t-lg transition-colors"
                style="border:1px solid var(--zm-border); border-bottom:none">
                <i data-lucide="stamp" class="w-4 h-4 inline -mt-0.5"></i> 결재행위
            </button>
        </div>

        <!-- ===== 감사로그 탭 ===== -->
        <div id="tabLogs">
            <div class="audit-log-wrap rounded-b-xl rounded-tr-xl overflow-hidden" style="background:#fff; border:1px solid var(--zm-border)">
                <!-- 필터 -->
                <div class="p-5" style="border-bottom:1px solid var(--zm-border)">
                    <div class="grid grid-cols-3 gap-x-8 gap-y-4">
                        <div class="flex items-center gap-3">
                            <label class="text-sm font-medium w-16 shrink-0" style="color:var(--zm-text-strong)">기간</label>
                            <div class="flex items-center gap-2 flex-1">
                                <input type="date" id="filterDateFrom" class="reg-input">
                                <span style="color:var(--zm-text-muted)">~</span>
                                <input type="date" id="filterDateTo" class="reg-input">
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="text-sm font-medium w-16 shrink-0" style="color:var(--zm-text-strong)">수행자</label>
                            <input type="text" id="filterActor" class="reg-input" placeholder="이름으로 검색">
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="text-sm font-medium w-16 shrink-0" style="color:var(--zm-text-strong)">카테고리</label>
                            <select id="filterCategory" class="reg-select">
                                <option value="">전체</option>
                                <option value="document">문서</option>
                                <option value="admin">관리자</option>
                                <option value="form">양식</option>
                                <option value="config">설정</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="text-sm font-medium w-16 shrink-0" style="color:var(--zm-text-strong)">이벤트</label>
                            <select id="filterEventType" class="reg-select">
                                <option value="">전체</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="text-sm font-medium w-16 shrink-0" style="color:var(--zm-text-strong)">대상</label>
                            <input type="text" id="filterTarget" class="reg-input" placeholder="문서번호, 양식명 등">
                        </div>
                        <div class="flex items-center justify-end gap-2">
                            <button onclick="loadLogs()" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white rounded-lg hover:opacity-90 transition-colors" style="background:var(--zm-primary)">
                                검색 <i data-lucide="search" class="w-4 h-4"></i>
                            </button>
                            <button onclick="resetLogFilters()" class="btn btn-secondary">초기화 <i data-lucide="rotate-cw" class="w-4 h-4"></i></button>
                        </div>
                    </div>
                </div>

                <!-- 정보바 -->
                <div class="list-info-bar">
                    <span class="info-text">감사 로그 <strong id="totalCount">0</strong></span>
                    <div class="flex items-center gap-2">
                        <button onclick="downloadCsv()" class="btn btn-secondary btn-xs">
                            <i data-lucide="download" class="w-3.5 h-3.5"></i> CSV
                        </button>
                        <select id="perPageSelect" class="list-per-page" onchange="loadLogs()">
                            <option value="20">20</option>
                            <option value="50" selected>50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>

                <!-- 테이블 -->
                <div class="overflow-x-auto">
                    <table class="w-full emp-table">
                        <thead>
                            <tr>
                                <th class="text-left" style="width:125px">시각</th>
                                <th class="text-left" style="width:65px">수행자</th>
                                <th class="text-center" style="width:56px">구분</th>
                                <th class="text-left" style="width:155px;white-space:nowrap">이벤트</th>
                                <th class="text-left">대상</th>
                                <th class="text-left" style="width:180px">변경 내용</th>
                                <th class="text-left" style="width:280px">사유</th>
                            </tr>
                        </thead>
                        <tbody id="logTableBody"></tbody>
                    </table>
                </div>
                <div class="flex items-center justify-center gap-1 py-4" style="border-top:1px solid var(--zm-border)" id="logsPagination"></div>
            </div>
        </div>

        <!-- ===== 결재행위 탭 ===== -->
        <div id="tabActions" class="hidden">
            <div class="audit-log-wrap rounded-b-xl rounded-tr-xl overflow-hidden" style="background:#fff; border:1px solid var(--zm-border)">
                <!-- 필터 -->
                <div class="p-5" style="border-bottom:1px solid var(--zm-border)">
                    <div class="grid grid-cols-3 gap-x-8 gap-y-4">
                        <div class="flex items-center gap-3">
                            <label class="text-sm font-medium w-16 shrink-0" style="color:var(--zm-text-strong)">기간</label>
                            <div class="flex items-center gap-2 flex-1">
                                <input type="date" id="actDateFrom" class="reg-input">
                                <span style="color:var(--zm-text-muted)">~</span>
                                <input type="date" id="actDateTo" class="reg-input">
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="text-sm font-medium w-16 shrink-0" style="color:var(--zm-text-strong)">결재자</label>
                            <input type="text" id="actApprover" class="reg-input" placeholder="이름으로 검색">
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="text-sm font-medium w-16 shrink-0" style="color:var(--zm-text-strong)">처리</label>
                            <select id="actType" class="reg-select">
                                <option value="">전체</option>
                                <option value="승인">승인</option>
                                <option value="반려">반려</option>
                                <option value="협의">협의</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="text-sm font-medium w-16 shrink-0" style="color:var(--zm-text-strong)">대상</label>
                            <input type="text" id="actTarget" class="reg-input" placeholder="문서번호, 제목 등">
                        </div>
                        <div></div>
                        <div class="flex items-center justify-end gap-2">
                            <button onclick="loadActions()" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white rounded-lg hover:opacity-90 transition-colors" style="background:var(--zm-primary)">
                                검색 <i data-lucide="search" class="w-4 h-4"></i>
                            </button>
                            <button onclick="resetActFilters()" class="btn btn-secondary">초기화 <i data-lucide="rotate-cw" class="w-4 h-4"></i></button>
                        </div>
                    </div>
                </div>

                <!-- 정보바 -->
                <div class="list-info-bar">
                    <span class="info-text">결재 행위 <strong id="actTotalCount">0</strong></span>
                    <select id="actPerPage" class="list-per-page" onchange="loadActions()">
                        <option value="20">20</option>
                        <option value="50" selected>50</option>
                        <option value="100">100</option>
                    </select>
                </div>

                <!-- 테이블 -->
                <div class="overflow-x-auto">
                    <table class="w-full emp-table">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left" style="width:120px">처리일시</th>
                                <th class="px-4 py-3 text-left" style="width:130px">결재자</th>
                                <th class="px-4 py-3 text-center" style="width:70px">처리</th>
                                <th class="px-4 py-3 text-left">대상 문서</th>
                                <th class="px-4 py-3 text-left" style="width:160px">의견</th>
                            </tr>
                        </thead>
                        <tbody id="actTableBody"></tbody>
                    </table>
                </div>
                <div class="flex items-center justify-center gap-1 py-4" style="border-top:1px solid var(--zm-border)" id="actPagination"></div>
            </div>
        </div>
    </main>
</div>


<style>
/* 감사로그 탭 */
.audit-tab {
    background: var(--zm-surface-2);
    color: var(--zm-text-muted);
    cursor: pointer;
}
.audit-tab:hover { color: var(--zm-text-strong); }
.audit-tab-active {
    background: var(--zm-surface-0) !important;
    color: var(--zm-primary) !important;
    position: relative;
    z-index: 1;
    margin-bottom: -1px;
}

/* 테이블 헤더·행 배경 순백 */
.audit-log-wrap .emp-table thead th {
    background-color: #fff !important;
}
.audit-log-wrap .emp-table tbody tr:hover {
    background-color: #f0f4ff !important;
}

/* Diff 표시 */
.audit-diff-wrap {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.audit-diff-row {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
}
.audit-diff-label {
    color: var(--zm-text-muted);
    min-width: 40px;
    font-weight: 500;
}
.audit-diff-old {
    color: #f43f5e;
    text-decoration: line-through;
    opacity: 0.8;
}
.audit-diff-new {
    color: #10b981;
    font-weight: 500;
}
.audit-status-pill {
    display: inline-block;
    padding: 1px 8px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 600;
}

/* 행 클릭 가능 커서 */
.audit-log-wrap .emp-table tbody tr[data-log-idx] { cursor: pointer; }
.audit-log-wrap .emp-table tbody tr.audit-row-active {
    background-color: rgba(79, 106, 255, 0.06) !important;
}

/* 아코디언 확장행 */
.audit-expand-row > td {
    padding: 0 !important;
    background: #f8f9fc !important;
    border-bottom: 1px solid var(--zm-border) !important;
    overflow: hidden;
}
.audit-expand-row:hover > td { background: #f8f9fc !important; }
.audit-expand-clip {
    overflow: hidden;
    transition: max-height 0.25s ease-out, opacity 0.2s ease-out;
    opacity: 1;
}
.audit-expand-clip.collapsing {
    max-height: 0 !important; opacity: 0;
    transition: max-height 0.2s ease-in, opacity 0.15s ease-in;
}
.audit-row-active { background-color: #f8f9fc !important; }
.audit-row-active > td { border-bottom-color: transparent !important; }
.audit-expand-inner { padding: 8px 24px 14px; }
.audit-expand-empty { font-size: 13px; color: var(--zm-text-muted); }
.ax-doc {
    display: flex; align-items: center; gap: 8px;
    margin-bottom: 8px; flex-wrap: wrap;
}
.ax-doc-title { font-size: 13px; font-weight: 600; color: var(--zm-text-strong); }
.ax-doc-meta { font-size: 12px; color: var(--zm-text-muted); }
.ax-doc-btn {
    margin-left: auto;
    font-size: 12px; font-weight: 500;
    color: var(--zm-primary);
    text-decoration: none;
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 12px;
    border: 1px solid rgba(79,106,255,0.3);
    border-radius: 6px;
    background: rgba(79,106,255,0.04);
    transition: all 0.15s;
}
.ax-doc-btn:hover {
    background: rgba(79,106,255,0.1);
    border-color: var(--zm-primary);
    color: var(--zm-primary);
}
.ax-flow {
    display: flex; align-items: center; gap: 0; flex-wrap: wrap;
}
.ax-step {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 12px; background: #fff; border-radius: 8px;
    font-size: 12px; border: 1px solid #e8eaed;
}
.ax-dot {
    width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0;
}
.ax-name { font-weight: 500; color: var(--zm-text-default); }
.ax-status { font-size: 11px; }
.ax-arrow { padding: 0 6px; color: #ccc; font-size: 11px; }
.ax-line {
    display: flex; align-items: center; gap: 8px;
    font-size: 13px; color: var(--zm-text-default);
}
html[data-theme="dark"] .audit-expand-row > td,
html[data-theme="dark"] .audit-expand-row:hover > td,
html[data-theme="dark"] .audit-row-active { background: #1c1d22 !important; }
html[data-theme="dark"] .ax-step { background: var(--zm-surface-1); border-color: var(--zm-border); }
</style>

<script>
window.__AUDIT_API = '<?= $basePath ?>/api/approval_audit.php';
</script>
<script src="<?= $basePath ?>/assets/js/approval-audit.js"></script>
<script>lucide.createIcons();</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
