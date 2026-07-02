<?php
/**
 * 세금계산서 > 패턴 탭
 * AI/규칙 학습 패턴 조회 + 매칭 이력
 */
if (!function_exists('getDBConnection')) {
    require_once __DIR__ . '/../config/database.php';
}
$pdo = getDBConnection();
?>

<div class="space-y-5">
    <!-- 필터 바 -->
    <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs text-slate-400 mb-1">거래처</label>
                <input type="text" id="ptFilterPartner" placeholder="거래처명 검색"
                       class="border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm w-48">
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">패턴 유형</label>
                <select id="ptFilterType" class="border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm">
                    <option value="">전체</option>
                    <option value="amount_exact">금액 정확 일치</option>
                    <option value="date_offset">입금 시점 패턴</option>
                    <option value="description_keyword">적요 키워드</option>
                    <option value="aggregate">합산 입금</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">적용 방식</label>
                <select id="ptFilterSource" class="border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm">
                    <option value="">전체</option>
                    <option value="user">확정 (무조건 적용)</option>
                    <option value="recommend">추천 (검토용)</option>
                </select>
            </div>
            <button onclick="loadPatterns()" class="px-4 py-2 bg-slate-700 text-slate-200 text-sm rounded-lg hover:bg-slate-600">
                <i data-lucide="search" class="w-4 h-4 inline"></i> 조회
            </button>
            <div class="flex-1"></div>
            <span class="text-xs text-slate-500" id="ptSummary"></span>
        </div>
    </div>

    <!-- 요약 카드 -->
    <div class="grid grid-cols-4 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-xs text-slate-400 mb-1">전체 패턴</p>
            <p class="text-xl font-bold text-slate-100" id="ptTotal">0</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-xs text-slate-400 mb-1">활성 패턴</p>
            <p class="text-xl font-bold text-green-400" id="ptActive">0</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-xs text-slate-400 mb-1">평균 신뢰도</p>
            <p class="text-xl font-bold text-blue-400" id="ptAvgConf">-</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-xs text-slate-400 mb-1">매칭 이력</p>
            <p class="text-xl font-bold text-slate-100" id="ptHistoryCount">0</p>
        </div>
    </div>

    <!-- 패턴 테이블 -->
    <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
        <div class="list-info-bar">
            <span class="info-text">학습된 패턴 <strong id="ptListCount">0</strong>건</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full emp-table table-fixed">
                <colgroup>
                    <col class="w-[36px]">
                    <col class="w-[13%]">
                    <col class="w-[12%]">
                    <col class="w-[22%]">
                    <col class="w-[11%]">
                    <col class="w-[8%]">
                    <col class="w-[10%]">
                    <col class="w-[9%]">
                    <col class="w-[15%]">
                </colgroup>
                <thead>
                    <tr>
                        <th class="px-2 py-3"></th>
                        <th class="px-4 py-3 text-center">거래처</th>
                        <th class="px-4 py-3 text-center">패턴 유형</th>
                        <th class="px-4 py-3 text-center">규칙 요약</th>
                        <th class="px-4 py-3 text-center">신뢰도</th>
                        <th class="px-4 py-3 text-center">적중/미적중</th>
                        <th class="px-4 py-3 text-center">적용 방식</th>
                        <th class="px-4 py-3 text-center">최근 매칭</th>
                        <th class="px-4 py-3 text-center">액션</th>
                    </tr>
                </thead>
                <tbody id="ptBody">
                    <tr><td colspan="9" class="px-3 py-12 text-center text-slate-500 text-sm">
                        아직 학습된 패턴이 없습니다.<br>
                        <span class="text-xs text-slate-600 mt-1 block">매칭 탭에서 세금계산서와 통장내역을 매칭하고 확정하면 패턴이 자동으로 생성됩니다.</span>
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 매칭 이력 (기본 접힘) -->
    <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3 cursor-pointer hover:bg-slate-800/50 transition-colors" onclick="toggleHistorySection()">
            <div class="flex items-center gap-2">
                <i data-lucide="chevron-right" class="w-4 h-4 text-slate-500 transition-transform" id="historyChevron"></i>
                <h3 class="text-sm font-bold text-slate-200">최근 매칭 활동</h3>
                <span class="text-xs text-slate-500" id="historyBadge"></span>
            </div>
            <div class="flex items-center gap-2" onclick="event.stopPropagation()">
                <input type="date" id="historyDateFrom" class="border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-2 py-1 text-xs" onchange="loadHistory()">
                <span class="text-slate-500 text-xs">~</span>
                <input type="date" id="historyDateTo" class="border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-2 py-1 text-xs" onchange="loadHistory()">
                <select id="historyFilter" class="border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-2 py-1 text-xs" onchange="loadHistory()">
                    <option value="">전체</option>
                    <option value="confirm">확정</option>
                    <option value="modify">수정</option>
                    <option value="remove">해제</option>
                    <option value="unconfirm">확정해제</option>
                    <option value="auto_match">자동매칭</option>
                    <option value="manual_match">수동매칭</option>
                    <option value="pattern_edit">패턴수정</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto hidden" id="historyContent">
            <table class="w-full emp-table text-sm">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-center">일시</th>
                        <th class="px-4 py-2 text-center">액션</th>
                        <th class="px-4 py-2 text-center">세금계산서</th>
                        <th class="px-4 py-2 text-center">통장내역</th>
                        <th class="px-4 py-2 text-center">적용 패턴</th>
                        <th class="px-4 py-2 text-center">수행자</th>
                        <th class="px-4 py-2 text-center">메모</th>
                    </tr>
                </thead>
                <tbody id="historyBody">
                    <tr><td colspan="7" class="px-3 py-8 text-center text-slate-500 text-sm">매칭 이력이 없습니다.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 패턴 수정 모달 -->
<div id="editPatternModal" class="fixed inset-0 bg-black/60 z-50 hidden items-center justify-center">
    <div class="bg-slate-900 border border-slate-700 rounded-xl w-full max-w-lg mx-4 shadow-2xl">
        <div class="flex items-center justify-between px-5 py-3 border-b border-slate-800">
            <h3 class="text-sm font-bold text-slate-200">패턴 수정</h3>
            <button onclick="closeEditModal()" class="text-slate-400 hover:text-slate-200 cursor-pointer text-lg">&times;</button>
        </div>
        <div class="px-5 py-4 space-y-4">
            <input type="hidden" id="editPatternId">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs text-slate-400 mb-1">거래처명</label>
                    <input type="text" id="editPartnerName" class="w-full border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">사업자번호</label>
                    <input type="text" id="editPartnerBizno" class="w-full border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs text-slate-400 mb-1">패턴 유형</label>
                    <select id="editPatternType" onchange="updateRuleFields()" class="w-full border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm">
                        <option value="amount_exact">금액 정확 일치</option>
                        <option value="date_offset">입금 시점 패턴</option>
                        <option value="description_keyword">적요 키워드</option>
                        <option value="aggregate">합산 입금</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">적용 방식</label>
                    <select id="editSource" class="w-full border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm">
                        <option value="user">확정 · 이 조건이면 무조건 적용</option>
                        <option value="ai">추천 · 자동 매칭 후보로만 제시</option>
                    </select>
                </div>
            </div>

            <!-- 규칙 상세 (동적 필드) -->
            <div class="border border-slate-700 rounded-lg p-3">
                <p class="text-xs text-slate-400 mb-2 font-medium">규칙 상세</p>
                <div id="ruleFields_amount_exact" class="space-y-2">
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">기준 필드</label>
                        <select id="editRuleField" class="w-full border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm">
                            <option value="total_amount">합계금액</option>
                            <option value="supply_amount">공급가액</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">금액 (원)</label>
                        <input type="number" id="editRuleAmount" class="w-full border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>
                <div id="ruleFields_date_offset" class="space-y-2 hidden">
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">오프셋 일수 (발행일 기준)</label>
                        <input type="number" id="editRuleOffsetDays" class="w-full border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm" min="0" max="365">
                    </div>
                </div>
                <div id="ruleFields_description_keyword" class="space-y-2 hidden">
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">키워드</label>
                        <input type="text" id="editRuleKeyword" class="w-full border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>
                <div id="ruleFields_aggregate" class="space-y-2 hidden">
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">합산 건수</label>
                        <input type="number" id="editRuleInvoiceCount" class="w-full border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm" min="1">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">합계 금액 (원)</label>
                        <input type="number" id="editRuleTotal" class="w-full border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs text-slate-400 mb-1">신뢰도 (%)</label>
                <div class="flex items-center gap-3">
                    <input type="range" id="editConfidenceRange" min="0" max="100" step="1" class="flex-1 accent-blue-500"
                           oninput="document.getElementById('editConfidence').value = this.value">
                    <input type="number" id="editConfidence" min="0" max="100" class="w-20 border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm text-center"
                           oninput="document.getElementById('editConfidenceRange').value = this.value">
                </div>
            </div>
        </div>
        <div class="flex justify-end gap-2 px-5 py-3 border-t border-slate-800">
            <button onclick="closeEditModal()" class="btn btn-secondary">취소</button>
            <button onclick="savePattern()" class="px-4 py-2 text-sm text-white bg-blue-600 hover:bg-blue-500 rounded-lg cursor-pointer">저장</button>
        </div>
    </div>
</div>

<script>
const PT_API = '<?= $basePath ?? '' ?>/api/tax_invoice.php';
let patterns = [];
let history = [];

function esc(s) {
    const d = document.createElement('div');
    d.textContent = s || '';
    return d.innerHTML;
}

const PATTERN_TYPE_LABELS = {
    amount_exact: '금액 정확 일치',
    date_offset: '입금 시점 패턴',
    description_keyword: '적요 키워드',
    aggregate: '합산 입금',
};

// 적용 방식: 사람이 정한 규칙(user)은 '확정', 시스템이 만든 것(rule/ai)은 '추천'
const SOURCE_LABELS = {
    user: '<span class="zm-status-pill zm-status-confirmed"><i data-lucide="lock"></i>확정적용</span>',
    rule: '<span class="zm-status-pill zm-status-review"><i data-lucide="eye"></i>검토필요</span>',
    ai: '<span class="zm-status-pill zm-status-review"><i data-lucide="eye"></i>검토필요</span>',
};

const ACTION_LABELS = {
    confirm: '<span class="zm-status-pill zm-status-confirmed">확정</span>',
    modify: '<span class="zm-status-pill zm-status-review">수정</span>',
    remove: '<span class="zm-status-pill zm-status-danger">해제</span>',
    auto_match: '<span class="zm-status-pill zm-status-info">자동</span>',
    manual_match: '<span class="zm-status-pill zm-status-muted">수동</span>',
    pattern_edit: '<span class="zm-status-pill zm-status-info">패턴수정</span>',
    unconfirm: '<span class="zm-status-pill zm-status-review">확정해제</span>',
};

async function loadPatterns() {
    const partner = document.getElementById('ptFilterPartner').value;
    const type = document.getElementById('ptFilterType').value;
    const source = document.getElementById('ptFilterSource').value;

    const params = new URLSearchParams({ action: 'getPatterns' });
    if (partner) params.set('partner', partner);
    if (type) params.set('pattern_type', type);
    if (source) params.set('source', source);

    try {
        const res = await fetch(`${PT_API}?${params}`);
        const data = await res.json();
        if (data.error) { alert(data.error); return; }

        patterns = data.patterns || [];
        const summary = data.summary || {};

        document.getElementById('ptTotal').textContent = summary.total ?? 0;
        document.getElementById('ptActive').textContent = summary.active ?? 0;
        document.getElementById('ptAvgConf').textContent = summary.avg_confidence ? summary.avg_confidence + '%' : '-';
        document.getElementById('ptHistoryCount').textContent = summary.history_count ?? 0;
        document.getElementById('ptListCount').textContent = patterns.length;

        renderPatterns(patterns);
    } catch (e) {
        console.error('패턴 로드 실패:', e);
    }
}

function renderPatterns(items) {
    const body = document.getElementById('ptBody');

    if (!items.length) {
        body.innerHTML = '<tr><td colspan="9" class="px-3 py-12 text-center text-slate-500 text-sm">아직 학습된 패턴이 없습니다.<br><span class="text-xs text-slate-600 mt-1 block">매칭 탭에서 세금계산서와 통장내역을 매칭하고 확정하면 패턴이 자동으로 생성됩니다.</span></td></tr>';
        return;
    }

    body.innerHTML = items.map(p => {
        const conf = parseFloat(p.confidence) || 0;
        const confColor = conf >= 80 ? 'text-green-400' : conf >= 60 ? 'text-blue-400' : conf >= 40 ? 'text-amber-400' : 'text-red-400';
        const barColor = conf >= 80 ? 'bg-green-500' : conf >= 60 ? 'bg-blue-500' : conf >= 40 ? 'bg-amber-500' : 'bg-red-500';

        let ruleSummary = '-';
        try {
            const rule = typeof p.pattern_rule === 'string' ? JSON.parse(p.pattern_rule) : p.pattern_rule;
            ruleSummary = summarizeRule(p.pattern_type, rule);
        } catch(e) {}

        return `<tr class="border-b border-slate-800">
            <td class="px-2 py-3 text-center text-slate-500 cursor-pointer hover:text-slate-200" onclick="togglePatternDetail(${p.id})"><i data-lucide="chevron-right" class="w-4 h-4 inline transition-transform" id="ptChevron-${p.id}"></i></td>
            <td class="px-4 py-3 text-sm text-slate-200">${esc(p.partner_name)}</td>
            <td class="px-4 py-3 text-center text-sm text-slate-300">${esc(PATTERN_TYPE_LABELS[p.pattern_type] || p.pattern_type)}</td>
            <td class="px-4 py-3 text-sm text-slate-400 max-w-xs truncate">${esc(ruleSummary)}</td>
            <td class="px-4 py-3 text-center">
                <div class="flex items-center gap-2 justify-center">
                    <div class="w-16 h-1.5 bg-slate-700 rounded-full overflow-hidden">
                        <div class="${barColor} h-full rounded-full" style="width:${conf}%"></div>
                    </div>
                    <span class="text-xs ${confColor} tabular-nums">${conf}%</span>
                </div>
            </td>
            <td class="px-4 py-3 text-center text-sm tabular-nums">
                <span class="text-green-400">${p.hit_count}</span> / <span class="text-red-400">${p.miss_count}</span>
            </td>
            <td class="px-4 py-3 text-center">${SOURCE_LABELS[p.source] || p.source}</td>
            <td class="px-4 py-3 text-center text-xs text-slate-400">${p.last_matched_at ? p.last_matched_at.slice(0,10) : '-'}</td>
            <td class="px-4 py-3 text-center whitespace-nowrap">
                <div class="zm-row-actions">
                    <button onclick="openEditModal(${p.id})" class="zm-action-btn zm-action-primary" title="패턴 수정"><i data-lucide="pencil" class="w-3.5 h-3.5"></i>수정</button>
                    <button onclick="togglePattern(${p.id}, ${p.is_active})" class="zm-action-btn ${p.is_active ? 'zm-action-live' : 'zm-action-paused'}" title="${p.is_active ? '클릭하면 패턴을 중지합니다' : '클릭하면 패턴을 활성화합니다'}">
                        <i data-lucide="${p.is_active ? 'check-circle-2' : 'pause-circle'}" class="w-3.5 h-3.5"></i>${p.is_active ? '사용중' : '중지됨'}
                    </button>
                    <button onclick="deletePattern(${p.id})" class="zm-action-btn zm-action-danger" title="패턴 삭제"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i>삭제</button>
                </div>
            </td>
        </tr>
        <tr id="ptDetail-${p.id}" class="hidden">
            <td colspan="9" class="px-6 py-4 bg-slate-950/50 border-b border-slate-800">
                <div class="text-xs text-slate-400" id="ptDetailContent-${p.id}">이력 로딩 중...</div>
            </td>
        </tr>`;
    }).join('');

    lucide.createIcons();
}

function summarizeRule(type, rule) {
    switch (type) {
        case 'amount_exact':
            return `합계금액 ${(rule.amount || 0).toLocaleString()}원 정확 일치`;
        case 'date_offset':
            return `발행일 대비 ${rule.offset_days || '?'}일 후 입금`;
        case 'description_keyword':
            return `적요에 "${rule.keyword || '?'}" 포함`;
        case 'aggregate':
            return `${rule.invoice_count || '?'}건 합산 입금 (${(rule.total || 0).toLocaleString()}원)`;
        default:
            return JSON.stringify(rule).slice(0, 60);
    }
}

async function togglePatternDetail(id) {
    const row = document.getElementById(`ptDetail-${id}`);
    if (!row) return;
    const chevron = document.getElementById(`ptChevron-${id}`);

    if (row.classList.contains('hidden')) {
        row.classList.remove('hidden');
        if (chevron) chevron.style.transform = 'rotate(90deg)';
        const content = document.getElementById(`ptDetailContent-${id}`);
        try {
            const res = await fetch(`${PT_API}?action=getPatternHistory&pattern_id=${id}`);
            const data = await res.json();
            const items = data.history || [];
            if (!items.length) {
                content.innerHTML = '<p class="text-slate-500">이 패턴과 관련된 매칭 이력이 없습니다.</p>';
                return;
            }
            content.innerHTML = '<table class="w-full text-xs"><thead><tr class="text-slate-500">' +
                '<th class="py-1 text-left">일시</th><th class="py-1 text-left">액션</th>' +
                '<th class="py-1 text-left">세금계산서</th><th class="py-1 text-right">계산서 금액</th>' +
                '<th class="py-1 px-3 text-center">→</th>' +
                '<th class="py-1 text-left">통장내역</th><th class="py-1 text-right">입출금액</th>' +
                '</tr></thead><tbody>' +
                items.map(h => {
                    const isPatternEdit = h.action === 'pattern_edit';
                    const invInfo = isPatternEdit ? '<span class="text-cyan-400">패턴 수정</span>'
                        : `${esc(h.partner_name || '-')} <span class="text-slate-500">${h.issue_date || ''}</span>`;
                    const invAmt = isPatternEdit ? '-' : (h.total_amount || 0).toLocaleString() + '원';
                    const txInfo = isPatternEdit ? esc(h.memo || '')
                        : (h.tx_date ? `<span class="text-slate-300">${h.tx_date}</span> ${esc(h.tx_desc || '')}` + (h.bank_name ? ` <span class="text-slate-600">(${esc(h.bank_name)})</span>` : '') : '<span class="text-slate-600">-</span>');
                    const txAmt = isPatternEdit ? '-' : (h.tx_amount ? h.tx_amount.toLocaleString() + '원' : '-');
                    return `<tr class="border-t border-slate-800">
                        <td class="py-1.5 text-slate-400 whitespace-nowrap">${h.created_at?.slice(0,16) || '-'}</td>
                        <td class="py-1.5">${ACTION_LABELS[h.action] || esc(h.action)}</td>
                        <td class="py-1.5 text-slate-300">${invInfo}</td>
                        <td class="py-1.5 text-right text-slate-300 tabular-nums">${invAmt}</td>
                        <td class="py-1.5 px-3 text-center text-slate-600">${isPatternEdit ? '' : '→'}</td>
                        <td class="py-1.5 text-slate-400">${txInfo}</td>
                        <td class="py-1.5 text-right text-slate-300 tabular-nums">${txAmt}</td>
                    </tr>`;
                }).join('') +
                '</tbody></table>';
        } catch(e) {
            content.innerHTML = '<p class="text-red-400">이력 로드 실패</p>';
        }
    } else {
        row.classList.add('hidden');
        if (chevron) chevron.style.transform = '';
    }
}

async function togglePattern(id, currentActive) {
    try {
        const res = await fetch(`${PT_API}?action=togglePattern`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, is_active: currentActive ? 0 : 1 }),
        });
        const data = await res.json();
        if (!data.success) { alert('변경 실패: ' + (data.error || '알 수 없는 오류')); return; }
        loadPatterns();
    } catch(e) { alert('오류: ' + e.message); }
}

async function deletePattern(id) {
    if (!(await AppUI.confirm('이 패턴을 삭제할까요? 삭제 후 복구할 수 없습니다.'))) return;
    try {
        const res = await fetch(`${PT_API}?action=deletePattern`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id }),
        });
        const data = await res.json();
        if (!data.success) { alert('삭제 실패: ' + (data.error || '알 수 없는 오류')); return; }
        loadPatterns();
    } catch(e) { alert('오류: ' + e.message); }
}

function toggleHistorySection() {
    const content = document.getElementById('historyContent');
    const chevron = document.getElementById('historyChevron');
    const isHidden = content.classList.contains('hidden');
    content.classList.toggle('hidden');
    chevron.style.transform = isHidden ? 'rotate(90deg)' : '';
    if (isHidden && !history.length) loadHistory();
}

async function loadHistory() {
    const action = document.getElementById('historyFilter').value;
    const dateFrom = document.getElementById('historyDateFrom').value;
    const dateTo = document.getElementById('historyDateTo').value;
    const params = new URLSearchParams({ action: 'getMatchHistory' });
    if (action) params.set('filter_action', action);
    if (dateFrom) params.set('date_from', dateFrom);
    if (dateTo) params.set('date_to', dateTo);

    try {
        const res = await fetch(`${PT_API}?${params}`);
        const data = await res.json();
        history = data.history || [];
        document.getElementById('historyBadge').textContent = history.length ? history.length + '건' : '';
        renderHistory(history);
    } catch(e) {
        console.error('이력 로드 실패:', e);
    }
}

function renderHistory(items) {
    const body = document.getElementById('historyBody');
    if (!items.length) {
        body.innerHTML = '<tr><td colspan="7" class="px-3 py-8 text-center text-slate-500 text-sm">매칭 이력이 없습니다.</td></tr>';
        return;
    }

    body.innerHTML = items.map(h => `<tr class="border-b border-slate-800 hover:bg-slate-950">
        <td class="px-4 py-2 text-center text-xs text-slate-400 tabular-nums">${h.created_at?.slice(0,16) || '-'}</td>
        <td class="px-4 py-2 text-center">${ACTION_LABELS[h.action] || esc(h.action)}</td>
        <td class="px-4 py-2 text-sm text-slate-300">${h.action === 'pattern_edit' ? '<span class="text-cyan-400">패턴 #' + (h.pattern_id || '?') + '</span>' : esc(h.partner_name || '-') + ' <span class="text-xs text-slate-500">' + (h.total_amount || 0).toLocaleString() + '원</span>'}</td>
        <td class="px-4 py-2 text-sm text-slate-400">${h.action === 'pattern_edit' ? esc(h.memo || '') : (h.tx_date ? h.tx_date + ' ' + esc(h.tx_desc || '') : '-') + ' <span class="text-xs">' + (h.tx_amount ? h.tx_amount.toLocaleString() + '원' : '') + '</span>'}</td>
        <td class="px-4 py-2 text-center text-xs text-slate-500">${h.pattern_id ? '#' + h.pattern_id : '-'}</td>
        <td class="px-4 py-2 text-center text-xs text-slate-400">${esc(h.actor || '-')}</td>
        <td class="px-4 py-2 text-xs text-slate-500 max-w-[200px] truncate">${h.action === 'pattern_edit' ? '-' : esc(h.memo || '')}</td>
    </tr>`).join('');
}

function openEditModal(id) {
    const p = patterns.find(x => x.id == id);
    if (!p) return;

    document.getElementById('editPatternId').value = p.id;
    document.getElementById('editPartnerName').value = p.partner_name || '';
    document.getElementById('editPartnerBizno').value = p.partner_bizno || '';
    document.getElementById('editPatternType').value = p.pattern_type;
    document.getElementById('editSource').value = (p.source === 'user') ? 'user' : 'ai';

    const conf = parseFloat(p.confidence) || 0;
    document.getElementById('editConfidence').value = conf;
    document.getElementById('editConfidenceRange').value = conf;

    let rule = {};
    try {
        rule = typeof p.pattern_rule === 'string' ? JSON.parse(p.pattern_rule) : (p.pattern_rule || {});
    } catch(e) {}

    document.getElementById('editRuleField').value = rule.field || 'total_amount';
    document.getElementById('editRuleAmount').value = rule.amount || '';
    document.getElementById('editRuleOffsetDays').value = rule.offset_days || '';
    document.getElementById('editRuleKeyword').value = rule.keyword || '';
    document.getElementById('editRuleInvoiceCount').value = rule.invoice_count || '';
    document.getElementById('editRuleTotal').value = rule.total || '';

    updateRuleFields();

    const modal = document.getElementById('editPatternModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeEditModal() {
    const modal = document.getElementById('editPatternModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function updateRuleFields() {
    const type = document.getElementById('editPatternType').value;
    const ALL_TYPES = ['amount_exact', 'date_offset', 'description_keyword', 'aggregate'];
    ALL_TYPES.forEach(t => {
        const el = document.getElementById('ruleFields_' + t);
        if (el) el.classList.toggle('hidden', t !== type);
    });
}

function buildRuleFromForm() {
    const type = document.getElementById('editPatternType').value;
    switch (type) {
        case 'amount_exact':
            return {
                field: document.getElementById('editRuleField').value,
                amount: parseInt(document.getElementById('editRuleAmount').value) || 0,
            };
        case 'date_offset':
            return { offset_days: parseInt(document.getElementById('editRuleOffsetDays').value) || 0 };
        case 'description_keyword':
            return { keyword: document.getElementById('editRuleKeyword').value.trim() };
        case 'aggregate':
            return {
                invoice_count: parseInt(document.getElementById('editRuleInvoiceCount').value) || 1,
                total: parseInt(document.getElementById('editRuleTotal').value) || 0,
            };
        default:
            return {};
    }
}

async function savePattern() {
    const id = document.getElementById('editPatternId').value;
    const body = {
        id: parseInt(id),
        partner_name: document.getElementById('editPartnerName').value.trim(),
        partner_bizno: document.getElementById('editPartnerBizno').value.trim(),
        pattern_type: document.getElementById('editPatternType').value,
        pattern_rule: buildRuleFromForm(),
        confidence: parseFloat(document.getElementById('editConfidence').value) || 0,
        source: document.getElementById('editSource').value,
    };

    if (!body.partner_name) { alert('거래처명을 입력하세요.'); return; }

    try {
        const res = await fetch(`${PT_API}?action=updatePattern`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        });
        const data = await res.json();
        if (!data.success) { alert('수정 실패: ' + (data.error || '알 수 없는 오류')); return; }
        closeEditModal();
        loadPatterns();
    } catch(e) { alert('오류: ' + e.message); }
}

document.addEventListener('DOMContentLoaded', () => {
    loadPatterns();
    loadHistory();
    lucide.createIcons();
});
</script>
