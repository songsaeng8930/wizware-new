<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getDBConnection();

// DB에서 계정과목 로드 (type 포함 · 검색형 드롭다운용)
$accountCategories = [];
$accountCategoriesFull = [];
try {
    $catStmt = $pdo->query("SELECT code, name, type, tax_type FROM account_categories WHERE is_active = 1 AND code NOT LIKE 'G\\_%' ESCAPE '\\\\' ORDER BY FIELD(type, '자산','부채','자본','매출','매입','비용','수익'), sort_order");
    foreach ($catStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $accountCategories[$row['code']] = $row['name'];
        $accountCategoriesFull[] = $row;
    }
} catch (Throwable $e) {
    $accountCategories = [];
    $accountCategoriesFull = [];
}

// DB에서 미분류 거래내역 로드
$transactions = [];
try {
    $txStmt = $pdo->query("
        SELECT id, transaction_date, description, amount, tx_type,
               counterparty, memo,
               account_code, account_name, ai_confidence, is_confirmed
        FROM bank_transactions
        WHERE is_confirmed = 0 OR is_confirmed IS NULL
        ORDER BY transaction_date DESC
        LIMIT 200
    ");
    $transactions = $txStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    // 테이블 미존재 시 빈 배열
}

$totalCount = count($transactions);
$classifiedCount = count(array_filter($transactions, fn($t) => !empty($t['account_code'])));
$highConfCount = count(array_filter($transactions, fn($t) => ($t['ai_confidence'] ?? 0) >= 80));
$lowConfCount = count(array_filter($transactions, fn($t) => !empty($t['account_code']) && ($t['ai_confidence'] ?? 0) >= 40 && ($t['ai_confidence'] ?? 0) < 80));
$dangerCount = count(array_filter($transactions, fn($t) => !empty($t['account_code']) && ($t['ai_confidence'] ?? 0) < 40));
?>

        <!-- 액션바: 분류 실행 + 요약 숫자 + 필터 -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 mb-5">
            <div class="flex items-center gap-3 flex-wrap">
                <!-- 분류 버튼 -->
                <button onclick="startClassify()" id="btnClassify" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-violet-600 to-violet-500 rounded-lg hover:opacity-90 transition-opacity">
                    <i data-lucide="sparkles" class="w-4 h-4"></i>분류 시작
                </button>

                <div class="w-px h-7 bg-slate-700"></div>

                <!-- 요약 숫자 -->
                <div class="flex items-center gap-4 text-sm">
                    <span class="text-slate-400">전체 <strong class="text-slate-100" id="statTotal"><?= $totalCount ?></strong></span>
                    <span class="text-slate-400">분류완료 <strong class="text-emerald-400" id="statHighConf"><?= $highConfCount ?></strong></span>
                    <span class="text-slate-400">확인필요 <strong class="text-amber-400" id="statLowConf"><?= $lowConfCount ?></strong></span>
                    <span class="text-slate-400">위험 <strong class="text-red-400" id="statDanger"><?= $dangerCount ?></strong></span>
                    <span class="text-slate-400">미분류 <strong class="text-slate-300" id="statUnclassified"><?= $totalCount - $classifiedCount ?></strong></span>
                </div>

                <div id="aiStatusBar" class="hidden"></div>
                <span id="aiStatusLabel" class="hidden"></span>
                <a id="aiSettingsLink" class="hidden"></a>
            </div>

            <!-- AI 처리 상태 (숨김) -->
            <div id="classifyProgress" class="hidden mt-3 p-3 bg-violet-500/10 border border-violet-500/20 rounded-xl">
                <div class="flex items-center gap-2 text-sm text-violet-300 mb-2">
                    <span id="classifySpinner"><svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></span>
                    <span id="classifyStatusText">AI가 계정과목을 분류하고 있습니다...</span>
                </div>
                <div class="w-full bg-slate-800 rounded-full h-1.5">
                    <div id="classifyBar" class="bg-violet-500 h-1.5 rounded-full transition-all duration-500" style="width:0%"></div>
                </div>
            </div>
            <div id="classifyError" class="hidden mt-3 p-3 bg-red-500/10 border border-red-500/20 rounded-xl text-sm text-red-300"></div>
        </div>

        <!-- 필터바 + 테이블 -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <!-- 필터 -->
                <div class="flex items-center gap-2">
                    <button onclick="filterClassify('all')" class="cls-filter-chip active" data-filter="all">전체</button>
                    <button onclick="filterClassify('unclassified')" class="cls-filter-chip" data-filter="unclassified">미분류</button>
                    <button onclick="filterClassify('lowconf')" class="cls-filter-chip" data-filter="lowconf">확인필요</button>
                    <button onclick="filterClassify('danger')" class="cls-filter-chip" data-filter="danger">위험</button>
                    <button onclick="filterClassify('classified')" class="cls-filter-chip" data-filter="classified">분류완료</button>
                    <div class="w-px h-6 bg-slate-700 mx-1"></div>
                    <select id="clsTxTypeFilter" onchange="filterClassify(document.querySelector('.cls-filter-chip.active')?.dataset.filter || 'all')" class="border border-slate-700 bg-slate-800 text-slate-300 rounded-lg px-2 py-1.5 text-xs">
                        <option value="">입금+출금</option>
                        <option value="입금">입금</option>
                        <option value="출금">출금</option>
                    </select>
                    <span class="text-xs text-slate-500 ml-2" id="tableSubtitle">
                        <?= $totalCount > 0 ? "총 {$totalCount}건" : "거래내역 없음 · 계좌 조회에서 먼저 조회하세요" ?>
                    </span>
                </div>
                <!-- 액션 -->
                <div class="flex items-center gap-2">
                    <button onclick="confirmAll()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-white bg-amber-500 rounded-lg hover:bg-amber-600 transition-colors">
                        <i data-lucide="check-check" class="w-4 h-4"></i>전체 확정
                    </button>
                    <button onclick="saveResults()" id="btnSave" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-white bg-primary rounded-lg hover:opacity-90">
                        <i data-lucide="save" class="w-4 h-4"></i>저장
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm emp-table" id="classifyTable">
                    <thead>
                        <tr class="border-b-2 border-slate-800">
                            <th class="py-3 px-3 text-center font-medium text-slate-300 w-10">
                                <input type="checkbox" class="emp-checkbox" onchange="toggleAll(this)">
                            </th>
                            <th class="py-3 px-3 text-left font-medium text-slate-300 whitespace-nowrap">거래일</th>
                            <th class="py-3 px-3 text-left font-medium text-slate-300">적요</th>
                            <th class="py-3 px-3 text-left font-medium text-slate-300">거래처</th>
                            <th class="py-3 px-3 text-right font-medium text-slate-300">금액</th>
                            <th class="py-3 px-3 text-center font-medium text-slate-300">구분</th>
                            <th class="py-3 px-3 text-center font-medium text-slate-300">계정과목</th>
                            <th class="py-3 px-3 text-center font-medium text-slate-300">신뢰도</th>
                            <th class="py-3 px-3 text-center font-medium text-slate-300">확정</th>
                        </tr>
                    </thead>
                    <tbody id="txBody">
                        <?php foreach ($transactions as $i => $t):
                            $hasClass = !empty($t['account_code']);
                            $conf = (int)($t['ai_confidence'] ?? 0);
                            $isDanger = $hasClass && $conf < 40;
                            $isLowConf = $hasClass && $conf >= 40 && $conf < 80;
                            $rowBg = $isDanger ? 'cls-row-danger' : ($isLowConf ? 'cls-row-review' : (!$hasClass ? 'cls-row-unclassified' : ''));
                        ?>
                        <tr class="border-b border-slate-800 hover:bg-slate-950 <?= $rowBg ?>"
                            data-row="<?= $i ?>" data-txid="<?= (int)$t['id'] ?>" data-conf="<?= $conf ?>" data-hasclass="<?= $hasClass ? '1' : '0' ?>"
                            data-desc="<?= htmlspecialchars($t['description']) ?>" data-cp="<?= htmlspecialchars($t['counterparty'] ?? '') ?>" data-txtype="<?= htmlspecialchars($t['tx_type']) ?>" data-amount="<?= (int)$t['amount'] ?>">
                            <td class="py-3 px-3 text-center">
                                <input type="checkbox" class="emp-checkbox row-check" <?= !empty($t['is_confirmed']) ? 'checked' : '' ?>>
                            </td>
                            <td class="py-3 px-3 text-slate-300 tabular-nums whitespace-nowrap"><?= htmlspecialchars($t['transaction_date']) ?></td>
                            <td class="py-3 px-3 text-slate-200" <?php if (!empty($t['memo'])): ?>title="<?= htmlspecialchars($t['memo']) ?>"<?php endif; ?>><?= htmlspecialchars($t['description']) ?></td>
                            <td class="py-3 px-3 text-slate-400"><?= htmlspecialchars($t['counterparty'] ?? '') ?></td>
                            <td class="py-3 px-3 text-right font-medium tabular-nums" style="color:var(<?= $t['tx_type']==='입금' ? '--zm-deposit-fg' : '--zm-withdraw-fg' ?>)"><?= number_format((int)$t['amount']) ?>원</td>
                            <td class="py-3 px-3 text-center">
                                <span class="px-2 py-0.5 text-sm rounded-full" style="background:var(<?= $t['tx_type']==='입금' ? '--zm-deposit-bg' : '--zm-withdraw-bg' ?>);color:var(<?= $t['tx_type']==='입금' ? '--zm-deposit-fg' : '--zm-withdraw-fg' ?>)"><?= htmlspecialchars($t['tx_type']) ?></span>
                            </td>
                            <td class="py-3 px-3">
                                <?php
                                $curCode = $t['account_code'] ?? '';
                                $chevron = '<svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
                                if ($curCode):
                                    $curName = $accountCategories[$curCode] ?? $curCode;
                                ?>
                                <div class="flex items-center gap-1.5 justify-center">
                                    <button type="button" onclick="openClsPicker(<?= (int)$t['id'] ?>, this)"
                                        class="flex items-center justify-between gap-1 bg-slate-950 border <?= $isDanger ? 'border-red-500/50' : 'border-slate-700' ?> rounded-lg px-2 py-1.5 text-xs <?= $isDanger ? 'text-red-300' : 'text-slate-100' ?> hover:bg-slate-900 focus:outline-none focus:ring-1 focus:ring-gray-300/30 transition-colors result-code"
                                        style="min-width:140px" data-code="<?= htmlspecialchars($curCode) ?>">
                                        <span class="truncate"><?= htmlspecialchars($curName) ?></span><?= $chevron ?>
                                    </button>
                                    <?php if ($isDanger): ?>
                                    <span class="relative group">
                                        <i data-lucide="alert-circle" class="w-3.5 h-3.5 text-red-400 flex-shrink-0 cursor-help"></i>
                                        <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 text-xs text-red-200 bg-slate-800 border border-red-500/30 rounded-lg shadow-lg whitespace-nowrap opacity-0 pointer-events-none group-hover:opacity-100 transition-opacity z-50">
                                            AI 분류 신뢰도가 매우 낮아요 (<?= $conf ?>%)<br>반드시 직접 확인 후 계정과목을 지정해주세요
                                        </span>
                                    </span>
                                    <?php elseif ($isLowConf): ?>
                                    <span class="relative group">
                                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5 text-amber-500 flex-shrink-0 cursor-help"></i>
                                        <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 text-xs text-amber-200 bg-slate-800 border border-amber-500/30 rounded-lg shadow-lg whitespace-nowrap opacity-0 pointer-events-none group-hover:opacity-100 transition-opacity z-50">
                                            AI 분류 신뢰도가 낮아요 (<?= $conf ?>%)<br>계정과목이 맞는지 한 번 확인해주세요
                                        </span>
                                    </span>
                                    <?php else: ?>
                                    <span class="w-3.5 h-3.5 flex-shrink-0" aria-hidden="true"></span><!-- 아이콘 자리 예약 · 열 정렬 고정 -->
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <div class="flex items-center gap-1.5 justify-center">
                                    <button type="button" onclick="openClsPicker(<?= (int)$t['id'] ?>, this)"
                                        class="flex items-center justify-between gap-1 bg-slate-950 border border-dashed border-amber-500/40 rounded-lg px-2 py-1.5 text-xs text-slate-500 hover:border-gray-400 hover:text-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-300/30 transition-colors result-code animate-pulse-subtle"
                                        style="min-width:140px" data-code="">
                                        <span class="truncate">계정과목 선택</span><?= $chevron ?>
                                    </button>
                                    <span class="relative group">
                                        <i data-lucide="circle-help" class="w-3.5 h-3.5 text-slate-500 flex-shrink-0 cursor-help"></i>
                                        <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 text-xs text-slate-200 bg-slate-800 border border-slate-600 rounded-lg shadow-lg whitespace-nowrap opacity-0 pointer-events-none group-hover:opacity-100 transition-opacity z-50">
                                            아직 분류되지 않은 거래예요<br>클릭해서 계정과목을 지정하거나, AI 분류를 실행해주세요
                                        </span>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-3 text-center">
                                <?php if ($hasClass): ?>
                                <?php
                                    $barColor = $conf >= 80 ? 'bg-emerald-500' : ($conf >= 40 ? 'bg-amber-500' : 'bg-red-500');
                                    $textColor = $conf >= 80 ? 'text-emerald-400' : ($conf >= 40 ? 'text-amber-400' : 'text-red-400');
                                ?>
                                <div class="flex items-center justify-center gap-1.5">
                                    <div class="w-16 bg-slate-700 rounded-full h-1.5">
                                        <div class="<?= $barColor ?> h-1.5 rounded-full" style="width:<?= $conf ?>%"></div>
                                    </div>
                                    <span class="text-sm tabular-nums <?= $textColor ?> conf-val"><?= $conf ?>%</span>
                                </div>
                                <?php else: ?>
                                <span class="text-xs text-slate-500">미분류</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-3 text-center">
                                <button type="button" onclick="createLockRule(<?= (int)$t['id'] ?>, this)"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-semibold text-emerald-400 border border-emerald-500/50 rounded-lg hover:bg-emerald-500/15 hover:border-emerald-500 transition-colors whitespace-nowrap"
                                    title="이 거래 조건을 규칙으로 확정 · 앞으로 같은 거래는 이 계정과목으로 자동 지정">
                                    <i data-lucide="lock" class="w-4 h-4"></i>규칙 확정
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

<!-- ===== 계정과목 검색 드롭다운 (공유) ===== -->
<div id="clsPickerPanel" class="hidden fixed z-50 w-72 bg-slate-950 border border-slate-700 rounded-xl shadow-2xl overflow-hidden" style="max-height:360px">
    <div class="p-2 space-y-1.5 border-b border-slate-800">
        <div class="relative">
            <i data-lucide="search" class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3 h-3 text-slate-500 pointer-events-none"></i>
            <input type="text" id="clsPickerSearch" placeholder="코드 또는 이름 검색"
                oninput="renderClsPickerList()"
                class="w-full bg-slate-900 border border-slate-800 rounded-lg pl-7 pr-2 py-1.5 text-xs text-slate-100 placeholder:text-slate-600 focus:outline-none focus:ring-1 focus:ring-gray-300/30">
        </div>
        <div class="flex flex-nowrap gap-0.5">
            <button type="button" onclick="setClsTypeFilter('all')" data-cpf="all" class="cpf2-btn px-1.5 py-0.5 text-[10px] rounded border border-primary text-primary font-medium whitespace-nowrap">전체</button>
            <button type="button" onclick="setClsTypeFilter('자산')" data-cpf="자산" class="cpf2-btn px-1.5 py-0.5 text-[10px] rounded border border-gray-300 text-gray-500 whitespace-nowrap">자산</button>
            <button type="button" onclick="setClsTypeFilter('부채')" data-cpf="부채" class="cpf2-btn px-1.5 py-0.5 text-[10px] rounded border border-gray-300 text-gray-500 whitespace-nowrap">부채</button>
            <button type="button" onclick="setClsTypeFilter('매출')" data-cpf="매출" class="cpf2-btn px-1.5 py-0.5 text-[10px] rounded border border-gray-300 text-gray-500 whitespace-nowrap">매출</button>
            <button type="button" onclick="setClsTypeFilter('매입')" data-cpf="매입" class="cpf2-btn px-1.5 py-0.5 text-[10px] rounded border border-gray-300 text-gray-500 whitespace-nowrap">매입</button>
            <button type="button" onclick="setClsTypeFilter('비용')" data-cpf="비용" class="cpf2-btn px-1.5 py-0.5 text-[10px] rounded border border-gray-300 text-gray-500 whitespace-nowrap">비용</button>
            <button type="button" onclick="setClsTypeFilter('수익')" data-cpf="수익" class="cpf2-btn px-1.5 py-0.5 text-[10px] rounded border border-gray-300 text-gray-500 whitespace-nowrap">수익</button>
        </div>
    </div>
    <div id="clsPickerList" class="overflow-y-auto" style="max-height:280px"></div>
</div>

<script>
const ACCT_CATEGORIES = <?= json_encode($accountCategories, JSON_UNESCAPED_UNICODE) ?>;
const clsCategories = <?= json_encode($accountCategoriesFull, JSON_UNESCAPED_UNICODE) ?>;
const API_BASE = '<?= $apiBasePath ?>';

// ── AI 연결 상태 확인 ──
(async function checkAiStatus() {
    const bar = document.getElementById('aiStatusBar');
    const label = document.getElementById('aiStatusLabel');
    const link = document.getElementById('aiSettingsLink');
    if (!bar) return;
    try {
        const res = await fetch(API_BASE + '/api/ai.php?action=get_config');
        const json = await res.json();
        if (json.ok && json.data) {
            const provider = json.data.provider;
            const configured = json.data.configured || {};
            const hasAny = Object.values(configured).some(v => v);
            if (provider && hasAny) {
                label.innerHTML = '<span class="inline-flex items-center gap-1 text-xs text-slate-500"><span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>AI 연결됨</span>';
                link.textContent = '설정';
                link.classList.remove('hidden');
            } else {
                label.innerHTML = '<span class="inline-flex items-center gap-1 text-xs text-slate-500"><span class="w-1.5 h-1.5 rounded-full bg-slate-600"></span>AI 미연결</span>';
                link.textContent = '연결하기';
                link.classList.remove('hidden');
            }
        }
    } catch (e) {
        label.textContent = 'AI 상태 확인 실패';
        label.className = 'text-slate-500 text-xs';
    }
})();

// ── 검색형 계정과목 드롭다운 ──
var clsPickerState = { txId: 0, triggerEl: null, typeFilter: 'all' };
var _clsScrollHandler = null;

function positionClsPanel() {
    var trigger = clsPickerState.triggerEl;
    if (!trigger) return;
    var panel = document.getElementById('clsPickerPanel');
    var rect = trigger.getBoundingClientRect();
    var panelH = 360;
    var spaceBelow = window.innerHeight - rect.bottom;
    var top = spaceBelow > panelH ? rect.bottom + 4 : rect.top - panelH - 4;
    panel.style.left = Math.min(rect.left, window.innerWidth - 296) + 'px';
    panel.style.top = Math.max(4, top) + 'px';
}

function openClsPicker(txId, triggerEl) {
    var panel = document.getElementById('clsPickerPanel');
    if (clsPickerState.triggerEl === triggerEl && !panel.classList.contains('hidden')) {
        closeClsPicker(); return;
    }
    clsPickerState.txId = txId;
    clsPickerState.triggerEl = triggerEl;
    clsPickerState.typeFilter = 'all';
    updateClsFilterBtns();
    document.getElementById('clsPickerSearch').value = '';
    renderClsPickerList();
    panel.classList.remove('hidden');
    panel.style.position = 'fixed';
    positionClsPanel();
    var _clsMouseInPanel = false;
    panel.addEventListener('mouseenter', function() { _clsMouseInPanel = true; });
    panel.addEventListener('mouseleave', function() { _clsMouseInPanel = false; });
    _clsScrollHandler = function(e) {
        if (_clsMouseInPanel) return;
        if (document.getElementById('clsPickerPanel').contains(e.target)) return;
        closeClsPicker();
    };
    window.addEventListener('scroll', _clsScrollHandler, true);
    window.addEventListener('resize', _clsScrollHandler);
    setTimeout(function() { document.getElementById('clsPickerSearch').focus(); }, 50);
}

function closeClsPicker() {
    document.getElementById('clsPickerPanel').classList.add('hidden');
    clsPickerState.txId = 0;
    clsPickerState.triggerEl = null;
    if (_clsScrollHandler) {
        window.removeEventListener('scroll', _clsScrollHandler, true);
        window.removeEventListener('resize', _clsScrollHandler);
        _clsScrollHandler = null;
    }
}

function setClsTypeFilter(type) {
    clsPickerState.typeFilter = type;
    updateClsFilterBtns();
    renderClsPickerList();
}

function updateClsFilterBtns() {
    var active = clsPickerState.typeFilter;
    document.querySelectorAll('.cpf2-btn').forEach(function(btn) {
        var f = btn.getAttribute('data-cpf');
        if (f === active) {
            btn.className = 'cpf2-btn px-1.5 py-0.5 text-[10px] rounded border border-primary text-primary font-medium whitespace-nowrap';
        } else {
            btn.className = 'cpf2-btn px-1.5 py-0.5 text-[10px] rounded border border-gray-300 text-gray-500 whitespace-nowrap';
        }
    });
}

function renderClsPickerList() {
    var keyword = (document.getElementById('clsPickerSearch').value || '').toLowerCase();
    var typeFilter = clsPickerState.typeFilter;
    var currentCode = clsPickerState.triggerEl ? (clsPickerState.triggerEl.getAttribute('data-code') || '') : '';
    var list = document.getElementById('clsPickerList');
    var typeColors = {
        '자산':'text-blue-400','부채':'text-rose-400','자본':'text-purple-400',
        '매출':'text-emerald-400','매입':'text-orange-400','비용':'text-amber-400','수익':'text-cyan-400'
    };
    var html = '';
    if (typeFilter === 'all' && !keyword) {
        html += '<div class="px-2 py-1"><button type="button" onclick="selectClsItem(\'\')" ' +
            'class="w-full text-left px-2 py-1.5 text-xs rounded hover:bg-slate-800 ' +
            (!currentCode ? 'text-primary font-medium' : 'text-slate-400') + '">해제 (미지정)</button></div>';
    }
    var lastType = '';
    var matchCount = 0;
    clsCategories.forEach(function(cat) {
        if (typeFilter !== 'all' && cat.type !== typeFilter) return;
        if (keyword) {
            var haystack = (cat.code + ' ' + cat.name).toLowerCase();
            if (haystack.indexOf(keyword) < 0) return;
        }
        if (cat.type !== lastType) {
            lastType = cat.type;
            var tc = typeColors[cat.type] || 'text-slate-400';
            html += '<div class="px-3 pt-2 pb-0.5 text-[10px] font-bold tracking-wider ' + tc + '">' + escHtml(cat.type) + '</div>';
        }
        var isActive = cat.code === currentCode;
        html += '<div class="px-2"><button type="button" onclick="selectClsItem(\'' + escHtml(cat.code) + '\')" ' +
            'class="w-full text-left px-2 py-1.5 text-xs rounded hover:bg-slate-800 truncate ' +
            (isActive ? 'text-primary font-medium bg-primary/5' : 'text-slate-200') + '">' +
            '<span class="text-slate-500 font-mono mr-1.5">' + escHtml(cat.code) + '</span>' +
            escHtml(cat.name) + '</button></div>';
        matchCount++;
    });
    if (matchCount === 0) {
        html += '<div class="px-4 py-6 text-center text-xs text-slate-500">검색 결과 없음</div>';
    }
    list.innerHTML = html;
}

function selectClsItem(code) {
    var txId = clsPickerState.txId;
    var trigger = clsPickerState.triggerEl;
    if (!trigger) return;
    closeClsPicker();

    var span = trigger.querySelector('span');

    // 드롭다운 버튼 형태 유지 · 라벨/코드만 갱신 (.result-code = trigger 자신이라 별도 변조 불필요)
    if (code) {
        var found = clsCategories.find(function(c) { return c.code === code; });
        var label = found ? found.name : code;
        if (span) span.textContent = label;
        trigger.setAttribute('data-code', code);
        trigger.classList.remove('text-slate-500', 'border-dashed', 'animate-pulse-subtle');
        trigger.classList.add('text-slate-100');
        trigger.style.borderStyle = 'solid';
    } else {
        if (span) span.textContent = '계정과목 선택';
        trigger.setAttribute('data-code', '');
        trigger.classList.remove('text-slate-100');
        trigger.classList.add('text-slate-500', 'border-dashed');
    }
}

document.addEventListener('click', function(e) {
    var panel = document.getElementById('clsPickerPanel');
    if (panel && !panel.classList.contains('hidden')) {
        if (!panel.contains(e.target) && !e.target.closest('[onclick*="openClsPicker"]')) {
            closeClsPicker();
        }
    }
});

function handleDrop(event) {
    event.preventDefault();
    event.currentTarget.classList.remove('border-primary','bg-primary/5');
    const file = event.dataTransfer.files[0];
    if (file) showFileInfo(file);
}
function handleFileSelect(input) {
    if (input.files[0]) showFileInfo(input.files[0]);
}
function showFileInfo(file) {
    document.getElementById('fileName').textContent = file.name;
    document.getElementById('fileInfo').classList.remove('hidden');
    document.getElementById('dropZone').style.display = 'none';
}
function clearFile() {
    document.getElementById('fileInput').value = '';
    document.getElementById('fileInfo').classList.add('hidden');
    document.getElementById('dropZone').style.display = '';
}

function getSelectedTxData() {
    const checked = document.querySelectorAll('.row-check:checked');
    if (!checked.length) return null;

    const txList = [];
    checked.forEach(cb => {
        const tr = cb.closest('tr');
        const cells = tr.querySelectorAll('td');
        txList.push({
            id:          parseInt(tr.dataset.txid),
            date:        cells[1].textContent.trim(),
            description: tr.dataset.desc || '',
            amount:      parseInt(tr.dataset.amount) || 0,
            type:        tr.dataset.txtype || '',
        });
    });
    return txList;
}

function setClassifyUI(state, message) {
    const btn  = document.getElementById('btnClassify');
    const prog = document.getElementById('classifyProgress');
    const bar  = document.getElementById('classifyBar');
    const txt  = document.getElementById('classifyStatusText');
    const errEl = document.getElementById('classifyError');

    if (state === 'start') {
        btn.disabled = true;
        btn.innerHTML = '<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> 처리 중...';
        const spinner = document.getElementById('classifySpinner');
        if (spinner) spinner.innerHTML = '<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>';
        bar.style.width = '0%';
        prog.classList.remove('hidden');
        errEl.classList.add('hidden');
    } else if (state === 'progress') {
        txt.textContent = message;
    } else if (state === 'bar') {
        bar.style.width = message + '%';
    } else if (state === 'error') {
        errEl.textContent = message;
        errEl.classList.remove('hidden');
    } else if (state === 'done') {
        txt.textContent = message;
        bar.style.width = '100%';
        const spinner = document.getElementById('classifySpinner');
        if (spinner) spinner.innerHTML = '<i data-lucide="check-circle" class="w-4 h-4 text-emerald-400"></i>';
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="sparkles" class="w-4 h-4"></i> 분류 시작';
        if (window.lucide) lucide.createIcons();
    }
}

async function runPatternMatch(txIds) {
    const res = await fetch(`${API_BASE}/api/ai.php?action=classify_by_patterns`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ transaction_ids: txIds })
    });
    return await res.json();
}

async function runAiClassify(txList) {
    const BATCH_SIZE = 20;
    const batches = [];
    for (let i = 0; i < txList.length; i += BATCH_SIZE) {
        batches.push(txList.slice(i, i + BATCH_SIZE));
    }

    let allResults = [];
    let completed = 0;

    for (const batch of batches) {
        setClassifyUI('progress', `AI 분류 중... (${completed}/${txList.length}건)`);
        setClassifyUI('bar', String(completed / txList.length * 100));

        const res = await fetch(`${API_BASE}/api/ai.php?action=classify_transactions`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ transactions: batch })
        });
        const data = await res.json();

        if (data.ok && data.data?.results) {
            allResults = allResults.concat(data.data.results);
            completed += batch.length;
        } else {
            throw new Error(data.error?.message || data.message || 'AI 분류 실패');
        }
    }
    return allResults;
}

async function startClassify() {
    const txList = getSelectedTxData();
    if (!txList) { alert('분류할 거래를 선택해주세요.'); return; }

    setClassifyUI('start');
    let allResults = [];

    // ── 1단계: 패턴 매칭 (무료, 즉시) ──
    setClassifyUI('progress', '1단계: 패턴 매칭 중...');
    setClassifyUI('bar', '10');

    try {
        const patternData = await runPatternMatch(txList.map(t => t.id));
        if (patternData.ok) {
            const patternResults = patternData.data.results || [];
            const unmatchedIds = patternData.data.unmatched_ids || [];

            if (patternResults.length > 0) {
                allResults = allResults.concat(patternResults);
                applyClassifyResults(patternResults);
            }

            setClassifyUI('progress',
                `패턴: ${patternResults.length}건 자동분류 → ${unmatchedIds.length}건 AI로 전달`);
            setClassifyUI('bar', '30');

            // ── 2단계: 미매칭 건 AI 분류 ──
            if (unmatchedIds.length > 0) {
                const unmatchedTx = txList.filter(t => unmatchedIds.includes(t.id));

                if (unmatchedTx.length > 0) {
                    setClassifyUI('progress', `2단계: AI 분류 중... (${unmatchedTx.length}건)`);
                    try {
                        const aiResults = await runAiClassify(unmatchedTx);
                        aiResults.forEach(r => r.source = 'ai');
                        allResults = allResults.concat(aiResults);
                        applyClassifyResults(aiResults);
                    } catch (aiErr) {
                        setClassifyUI('error', 'AI 분류 실패: ' + aiErr.message + ` (패턴으로 ${patternResults.length}건은 분류됨)`);
                    }
                }
            }
        }
    } catch (e) {
        setClassifyUI('error', '패턴 매칭 실패: ' + e.message);
    }

    // ── 결과 요약 ──
    const patternCount = allResults.filter(r => r.source === 'pattern').length;
    const aiCount = allResults.filter(r => r.source === 'ai').length;
    const total = txList.length;
    const unmatched = total - allResults.length;

    if (allResults.length > 0) updateStats(allResults);

    setClassifyUI('done',
        `완료: 패턴 ${patternCount}건 + AI ${aiCount}건 = ${allResults.length}건 분류` +
        (unmatched > 0 ? ` · ${unmatched}건 미분류` : ''));
}

function applyClassifyResults(results) {
    results.forEach(r => {
        const tr = document.querySelector(`tr[data-txid="${r.id}"]`);
        if (!tr) return;

        const confCell = tr.querySelectorAll('td')[7];
        if (confCell && r.confidence > 0) {
            const barColor = r.confidence >= 80 ? 'bg-emerald-500' : (r.confidence >= 40 ? 'bg-amber-500' : 'bg-red-500');
            const textColor = r.confidence >= 80 ? 'text-emerald-400' : (r.confidence >= 40 ? 'text-amber-400' : 'text-red-400');
            confCell.innerHTML = `
                <div class="flex items-center justify-center gap-1.5">
                    <div class="w-16 bg-slate-700 rounded-full h-1.5">
                        <div class="${barColor} h-1.5 rounded-full" style="width:${r.confidence}%"></div>
                    </div>
                    <span class="text-sm tabular-nums ${textColor} conf-val">${r.confidence}%</span>
                </div>`;
        }

        var pickerBtn = tr.querySelectorAll('td')[6]?.querySelector('button[onclick*="openClsPicker"]');
        if (pickerBtn && r.account_code) {
            pickerBtn.setAttribute('data-code', r.account_code);
            var btnSpan = pickerBtn.querySelector('span');
            if (btnSpan) btnSpan.textContent = r.account_name || r.account_code;
            pickerBtn.classList.remove('text-slate-500', 'border-dashed', 'border-red-500/50', 'text-red-300');
            if (r.confidence < 40) {
                pickerBtn.classList.add('text-red-300', 'border-red-500/50');
            } else {
                pickerBtn.classList.add('text-slate-100');
            }
            pickerBtn.style.borderStyle = 'solid';
        }

        tr.classList.remove('bg-amber-500/5', 'bg-red-500/10', 'cls-row-unclassified', 'cls-row-review', 'cls-row-danger');
        if (r.account_code && r.confidence < 40) {
            tr.classList.add('cls-row-danger');
        } else if (r.account_code && r.confidence < 80) {
            tr.classList.add('cls-row-review');
        }

        tr.dataset.reason = r.reason || '';
    });
}

function updateStats(results) {
    const classified = results.filter(r => r.account_code);
    const highConf = classified.filter(r => r.confidence >= 80).length;
    const lowConf = classified.filter(r => r.confidence >= 40 && r.confidence < 80).length;
    const danger = classified.filter(r => r.confidence < 40).length;
    const unmatched = results.length - classified.length;

    document.getElementById('statHighConf').textContent = highConf;
    document.getElementById('statLowConf').textContent = lowConf;
    const dangerEl = document.getElementById('statDanger');
    if (dangerEl) dangerEl.textContent = danger;
    const unclEl = document.getElementById('statUnclassified');
    if (unclEl) unclEl.textContent = unmatched;

    const grouped = {};
    results.forEach(r => {
        if (!r.account_name) return;
        if (!grouped[r.account_name]) grouped[r.account_name] = { count: 0, total: 0 };
        grouped[r.account_name].count++;
    });

    const chartEl = document.getElementById('categoryChart');
    if (!chartEl) return;
    const entries = Object.entries(grouped).sort((a, b) => b[1].count - a[1].count);
    if (!entries.length) return;

    const maxCount = Math.max(...entries.map(e => e[1].count));
    chartEl.innerHTML = entries.map(([name, g]) => `
        <div class="flex items-center gap-3 py-1.5">
            <span class="text-sm text-slate-300 w-24 shrink-0">${escHtml(name)}</span>
            <div class="flex-1 bg-slate-800 rounded-full h-2">
                <div class="bg-violet-500 h-2 rounded-full" style="width:${Math.round(g.count/maxCount*100)}%"></div>
            </div>
            <span class="text-sm text-slate-400 w-8 text-right">${g.count}건</span>
        </div>
    `).join('');
}

function updateCategory(rowIdx, code, name) {
    const row = document.querySelector(`tr[data-row="${rowIdx}"]`);
    if (!row) return;
    const btn = row.querySelector('.result-code');
    if (!btn || !code) return;
    // 드롭다운 버튼 유지 · 라벨/코드만 갱신 (pill로 변조하면 재선택 불가 + 열 정렬 깨짐)
    const label = btn.querySelector('span');
    if (label) label.textContent = name; else btn.textContent = name;
    btn.dataset.code = code;
    btn.setAttribute('data-code', code);
    btn.classList.remove('text-slate-500', 'border-dashed', 'animate-pulse-subtle');
    btn.classList.add('text-slate-100');
    btn.style.borderStyle = 'solid';
}

function toggleAll(master) {
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = master.checked);
}

function filterClassify(type) {
    document.querySelectorAll('.cls-filter-chip').forEach(c => c.classList.remove('active'));
    const chip = document.querySelector(`.cls-filter-chip[data-filter="${type}"]`);
    if (chip) chip.classList.add('active');

    const txTypeVal = document.getElementById('clsTxTypeFilter')?.value || '';
    let visibleCount = 0;

    document.querySelectorAll('tr[data-txid]').forEach(tr => {
        const code = tr.querySelector('.result-code')?.dataset.code || '';
        const conf = parseInt(tr.querySelector('.conf-val')?.textContent || '0');
        const txType = tr.querySelector('span.rounded-full')?.textContent?.trim() || '';

        let show = true;
        if (type === 'unclassified') show = !code;
        else if (type === 'danger') show = code && conf < 40;
        else if (type === 'lowconf') show = code && conf >= 40 && conf < 80;
        else if (type === 'classified') show = code && conf >= 80;

        if (show && txTypeVal) show = txType === txTypeVal;

        tr.style.display = show ? '' : 'none';
        if (show) visibleCount++;
    });

    const subtitle = document.getElementById('tableSubtitle');
    if (subtitle) subtitle.textContent = `${visibleCount}건 표시`;
}

// 분류 화면에서 "이런 건 항상 확정" → 확정 규칙 생성
async function createLockRule(txId, btn) {
    const tr = btn.closest('tr');
    if (!tr) return;
    const desc   = tr.dataset.desc || '';
    const cp     = tr.dataset.cp || '';
    const txType = tr.dataset.txtype || '전체';
    const codeBtn = tr.querySelector('.result-code');
    const code = codeBtn ? (codeBtn.dataset.code || '') : '';
    const name = codeBtn ? (codeBtn.querySelector('span') ? codeBtn.querySelector('span').textContent.trim() : '') : '';

    if (!code) { alert('먼저 계정과목을 지정한 뒤 확정 규칙을 만들 수 있어요.'); return; }

    const kw = await AppUI.prompt(
        '「확정 규칙」을 만듭니다.\n\n' +
        '아래 키워드가 적요에 들어간 ' + txType + ' 거래는\n' +
        '앞으로 무조건 「' + name + '」으로 자동 지정됩니다.\n' +
        '(거래처만 같은 거래는 자동으로 제외돼요)\n\n' +
        '키워드 확인/수정 · 짧고 핵심만 남기면 비슷한 거래까지 잡혀요:',
        { defaultValue: desc }
    );
    if (kw === null) return;
    const keyword = kw.trim();
    if (keyword.length < 2) { alert('키워드는 2자 이상이어야 해요.'); return; }

    try {
        const res = await fetch(`${API_BASE}/api/ai.php?action=create_lock_rule`, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ keyword: keyword, account_code: code, account_name: name, tx_type: txType, counterparty: cp }),
        });
        const data = await res.json();
        if (!data.ok) { alert('규칙 생성 실패: ' + (data.error && data.error.message ? data.error.message : '')); return; }
        alert('확정 규칙이 만들어졌어요.\n앞으로 "' + keyword + '"가 들어간 ' + txType + ' 거래는\n자동으로 「' + name + '」으로 지정됩니다.');
    } catch(e) { alert('오류: ' + e.message); }
}

function confirmAll() {
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = true);
    alert('전체 항목이 확정 선택됩니다. 저장 버튼을 눌러 완료하세요.');
}

async function saveResults() {
    const items = [];
    document.querySelectorAll('tr[data-txid]').forEach(tr => {
        const cb = tr.querySelector('.row-check');
        const codeEl = tr.querySelector('.result-code');
        if (!cb || !cb.checked || !codeEl) return;

        const code = codeEl.dataset.code;
        if (!code) return;

        const confEl = tr.querySelector('.conf-val');
        items.push({
            id:           parseInt(tr.dataset.txid),
            account_code: code,
            account_name: codeEl.textContent.trim(),
            confidence:   confEl ? parseInt(confEl.textContent) : 0,
            confirmed:    1,
        });
    });

    if (!items.length) {
        alert('확정할 항목을 선택해주세요. (계정과목이 지정된 항목만 저장 가능)');
        return;
    }

    const btn = document.getElementById('btnSave');
    btn.disabled = true;
    btn.innerHTML = '<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> 저장 중...';

    try {
        const res = await fetch(`${API_BASE}/api/ai.php?action=save_classification`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ items })
        });
        const data = await res.json();
        if (data.ok) {
            alert(`${data.data.saved}건이 확정 저장되었습니다.`);
            location.reload();
        } else {
            alert('저장 실패: ' + (data.error?.message || '알 수 없는 오류'));
        }
    } catch (e) {
        alert('저장 중 오류: ' + e.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="save" class="w-4 h-4"></i>저장';
        if (window.lucide) lucide.createIcons();
    }
}

function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

</script>
