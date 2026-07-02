<?php
/**
 * 세금계산서 > 통장매핑 탭
 * 세금계산서와 통장 거래내역을 자동/수동 매핑
 */
if (!function_exists('getDBConnection')) {
    require_once __DIR__ . '/../config/database.php';
}

$curYear  = (int)date('Y');
$curMonth = (int)date('m');
?>

<div class="space-y-5">
    <!-- 필터 바 -->
    <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs text-slate-400 mb-1">연도</label>
                <select id="matchYear" class="border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm">
                    <?php for ($y = $curYear; $y >= 2024; $y--): ?>
                    <option value="<?= $y ?>" <?= $y === $curYear ? 'selected' : '' ?>><?= $y ?>년</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">월</label>
                <select id="matchMonth" class="border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $m === $curMonth ? 'selected' : '' ?>><?= $m ?>월</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">상태</label>
                <select id="matchFilter" class="border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm">
                    <option value="all">전체</option>
                    <option value="matched">매칭완료</option>
                    <option value="unmatched">미매칭</option>
                    <option value="confirmed">확정완료</option>
                    <option value="name_warning">이름 불일치</option>
                </select>
            </div>
            <button onclick="loadMatchData()" class="px-4 py-2 bg-slate-700 text-slate-200 text-sm rounded-lg hover:bg-slate-600">
                <i data-lucide="search" class="w-4 h-4 inline"></i> 조회
            </button>
            <div class="flex-1"></div>
            <button onclick="showBulkFetchModal()" class="px-4 py-2 bg-slate-700 text-slate-200 text-sm rounded-lg hover:bg-slate-600">
                <i data-lucide="download-cloud" class="w-4 h-4 inline"></i> 통장내역 일괄 조회
            </button>
            <button onclick="runAutoMatch()" id="btnAutoMatch" class="px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:opacity-90">
                <i data-lucide="zap" class="w-4 h-4 inline"></i> 자동 매칭 실행
            </button>
            <button onclick="runBulkAiMatch()" id="btnBulkAi" class="px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-500">
                <i data-lucide="sparkles" class="w-4 h-4 inline"></i> AI 일괄 매칭
            </button>
        </div>
    </div>

    <!-- 요약 카드 -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-xs text-slate-400 mb-1">전체 세금계산서</p>
            <p class="text-2xl font-bold text-slate-100" id="sumTotal">-</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-xs text-slate-400 mb-1">매칭완료</p>
            <p class="text-2xl font-bold text-blue-400" id="sumMatched">-</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-xs text-slate-400 mb-1">확정완료</p>
            <p class="text-2xl font-bold text-green-400" id="sumConfirmed">-</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-xs text-slate-400 mb-1">미매칭</p>
            <p class="text-2xl font-bold text-amber-400" id="sumUnmatched">-</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-xs text-slate-400 mb-1">이름 불일치</p>
            <p class="text-2xl font-bold text-yellow-400" id="sumNameWarning">-</p>
        </div>
    </div>

    <!-- 메인 테이블 -->
    <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden">
        <div class="flex items-center justify-between p-4 border-b border-slate-800">
            <h3 class="text-sm font-bold text-slate-100">세금계산서 매핑 현황</h3>
            <div class="flex gap-2">
                <button onclick="unconfirmSelected()" id="btnUnconfirm" class="hidden px-4 py-2 bg-amber-600 text-white text-sm rounded-lg hover:bg-amber-700">
                    <i data-lucide="unlock" class="w-4 h-4 inline"></i> 선택 해제
                </button>
                <button onclick="confirmSelected()" id="btnConfirm" class="hidden px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700">
                    <i data-lucide="check-check" class="w-4 h-4 inline"></i> 선택 확정
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-800 text-slate-300">
                    <tr>
                        <th class="px-3 py-2 w-8"><input type="checkbox" id="checkAll" onchange="toggleCheckAll()" class="accent-primary"></th>
                        <th class="px-3 py-2 text-left">구분</th>
                        <th class="px-3 py-2 text-left">작성일자</th>
                        <th class="px-3 py-2 text-left">거래처</th>
                        <th class="px-3 py-2 text-right">합계금액</th>
                        <th class="px-3 py-2 text-center">매칭상태</th>
                        <th class="px-3 py-2 text-center">신뢰도</th>
                        <th class="px-3 py-2 text-left">매칭된 통장내역</th>
                        <th class="px-3 py-2 text-center">액션</th>
                    </tr>
                </thead>
                <tbody id="matchBody" class="text-slate-300">
                    <tr><td colspan="9" class="px-3 py-8 text-center text-slate-500">조회 버튼을 눌러주세요</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 매칭 후보 모달 -->
<div id="candidateModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/60" onclick="closeCandidateModal()"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-3xl max-h-[80vh] bg-slate-900 border border-slate-700 rounded-xl shadow-2xl overflow-hidden flex flex-col">
        <div class="flex items-center justify-between p-4 border-b border-slate-800">
            <div>
                <h3 class="text-sm font-bold text-slate-100">매칭 후보 선택</h3>
                <p class="text-xs text-slate-400 mt-1" id="modalInvoiceInfo"></p>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="aiEnhanceMatchInModal()" id="btnAiModal" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-violet-600 rounded-lg hover:bg-violet-700 transition-colors">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i> AI 판단
                </button>
                <button onclick="closeCandidateModal()" class="text-slate-500 hover:text-slate-300">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
        </div>
        <div class="overflow-y-auto flex-1 p-4">
            <div id="candidateList" class="space-y-2">로딩 중...</div>
        </div>
    </div>
</div>

<!-- 통장내역 일괄 조회 모달 -->
<div id="bulkFetchModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/60" onclick="closeBulkFetchModal()"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md bg-slate-900 border border-slate-700 rounded-xl shadow-2xl p-6">
        <h3 class="text-sm font-bold text-slate-100 mb-4">통장내역 일괄 조회 & 저장</h3>
        <p class="text-xs text-slate-400 mb-4">등록된 모든 활성 계좌의 거래내역을 조회해서 DB에 저장합니다.</p>
        <div class="space-y-3 mb-4">
            <div>
                <label class="block text-xs text-slate-400 mb-1">계좌 비밀번호 (4자리)</label>
                <input type="password" id="bulkPassword" maxlength="4" class="w-full border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="****">
            </div>
        </div>
        <div class="flex justify-end gap-2">
            <button onclick="closeBulkFetchModal()" class="px-4 py-2 bg-slate-700 text-slate-300 text-sm rounded-lg">취소</button>
            <button onclick="doBulkFetch()" id="btnBulkFetch" class="px-4 py-2 bg-primary text-white text-sm rounded-lg hover:opacity-90">조회 시작</button>
        </div>
        <div id="bulkFetchResult" class="hidden mt-4 p-3 bg-slate-800 rounded-lg text-sm text-slate-300"></div>
    </div>
</div>

<script>
const INVOICE_API = '<?= $basePath ?? '' ?>/api/tax_invoice.php';
const BANK_API    = '<?= $basePath ?? '' ?>/api/bankapi.php';
let matchData = [];
let currentInvoiceId = null;
let autoMatchAfterFetch = false;

function getDateRange() {
    const y = parseInt(document.getElementById('matchYear').value);
    const m = parseInt(document.getElementById('matchMonth').value);
    const dateFrom = `${y}-${String(m).padStart(2,'0')}-01`;
    const lastDay = new Date(y, m, 0).getDate();
    const dateTo = `${y}-${String(m).padStart(2,'0')}-${lastDay}`;
    return { dateFrom, dateTo };
}

function fmt(n) {
    return Number(n || 0).toLocaleString();
}

function esc(s) {
    const d = document.createElement('div');
    d.textContent = s || '';
    return d.innerHTML;
}

async function loadMatchData() {
    const { dateFrom, dateTo } = getDateRange();
    const status = document.getElementById('matchFilter').value;

    try {
        const res = await fetch(`${INVOICE_API}?action=getMatchStatus&date_from=${dateFrom}&date_to=${dateTo}&status=${status}`);
        const data = await res.json();

        if (data.error) { alert(data.error); return; }

        matchData = data.items || [];
        const sum = data.summary || {};

        document.getElementById('sumTotal').textContent = sum.total ?? 0;
        document.getElementById('sumMatched').textContent = sum.matched ?? 0;
        document.getElementById('sumConfirmed').textContent = sum.confirmed ?? 0;
        document.getElementById('sumUnmatched').textContent = sum.unmatched ?? 0;
        document.getElementById('sumNameWarning').textContent = sum.name_warning ?? 0;

        renderMatchTable(matchData);
    } catch (e) {
        alert('데이터 로드 실패: ' + e.message);
    }
}

function renderMatchTable(items) {
    const body = document.getElementById('matchBody');

    if (!items.length) {
        body.innerHTML = '<tr><td colspan="9" class="px-3 py-8 text-center text-slate-500">데이터가 없습니다. 세금계산서를 먼저 업로드해주세요.</td></tr>';
        document.getElementById('btnConfirm').classList.add('hidden');
        document.getElementById('btnUnconfirm').classList.add('hidden');
        return;
    }

    body.innerHTML = items.map(r => {
        const isMatched = r.mapping_id !== null;
        const isConfirmed = r.is_confirmed == 1;
        const hasNameWarning = r.name_warning == 1;
        const partnerName = r.invoice_type === '매출' ? r.buyer_name : r.supplier_name;
        const typeLabel = r.invoice_type === '매출'
            ? '<span class="px-2 py-0.5 bg-blue-900/30 text-blue-400 rounded-full text-xs">매출</span>'
            : '<span class="px-2 py-0.5 bg-orange-900/30 text-orange-400 rounded-full text-xs">매입</span>';

        const modBadge = r.invoice_status === '수정'
            ? ' <span class="px-1.5 py-0.5 bg-red-900/30 text-red-400 rounded text-xs">수정</span>' : '';

        let statusBadge, confidenceBar, txInfo, actions;

        if (isConfirmed) {
            statusBadge = '<span class="px-2 py-0.5 bg-green-900/30 text-green-400 rounded-full text-xs">확정</span>';
        } else if (isMatched) {
            statusBadge = '<span class="px-2 py-0.5 bg-blue-900/30 text-blue-400 rounded-full text-xs">매칭</span>';
        } else {
            statusBadge = '<span class="px-2 py-0.5 bg-slate-700 text-slate-400 rounded-full text-xs">미매칭</span>';
        }

        if (hasNameWarning && isMatched) {
            statusBadge += ' <span class="text-yellow-400 cursor-help" title="거래처명이 정확히 일치하지 않습니다. 확인이 필요합니다."><i data-lucide="alert-triangle" class="w-3.5 h-3.5 inline"></i></span>';
        }

        if (isMatched) {
            const conf = r.confidence || 0;
            const color = conf >= 90 ? 'bg-green-500' : conf >= 80 ? 'bg-blue-500' : conf >= 60 ? 'bg-amber-500' : 'bg-red-500';
            const label = conf >= 90 ? '높음' : conf >= 80 ? '양호' : conf >= 60 ? '보통' : '낮음';
            const reason = r.match_reason || '';
            confidenceBar = `<div class="flex flex-col items-center gap-0.5">
                <div class="flex items-center gap-1"><div class="w-12 h-2 bg-slate-700 rounded-full overflow-hidden"><div class="${color} h-full rounded-full" style="width:${conf}%"></div></div><span class="text-xs">${conf}% ${label}</span></div>
                ${reason ? '<span class="text-xs text-slate-500 max-w-[120px] truncate" title="'+esc(reason)+'">' + esc(reason) + '</span>' : ''}
            </div>`;
            txInfo = `<span class="text-xs">${esc(r.tx_date || '')} ${esc(r.bank_name || '')} ${esc(r.tx_desc || '')}</span><br><span class="text-xs text-slate-500">${fmt(r.tx_amount)}원 ${r.match_type === 'manual' ? '(수동)' : '(자동)'}</span>`;
        } else {
            confidenceBar = '<span class="text-xs text-slate-500">-</span>';
            txInfo = '<span class="text-xs text-slate-500">-</span>';
        }

        if (isConfirmed) {
            actions = `<button onclick="unconfirmSingle(${r.mapping_id})" class="text-xs text-amber-400 hover:underline">해제</button>`;
        } else if (isMatched) {
            actions = `
                <button onclick="confirmSingle(${r.mapping_id})" class="btn btn-secondary btn-xs mr-1">확정</button>
                <button onclick="removeMatch(${r.mapping_id})" class="btn btn-secondary btn-xs">삭제</button>`;
        } else {
            actions = `<button onclick="openCandidateModal(${r.id})" class="btn btn-secondary btn-xs mr-1">매칭</button>
                       <button onclick="aiEnhanceMatch(${r.id})" class="btn btn-secondary btn-xs">AI추천</button>`;
        }

        const checkValue = isConfirmed ? 'confirmed_' + r.mapping_id : r.id;

        return `
            <tr class="border-b border-slate-800 hover:bg-slate-800/50${hasNameWarning ? ' bg-yellow-900/5' : ''}">
                <td class="px-3 py-3"><input type="checkbox" class="match-check accent-primary" value="${checkValue}" data-confirmed="${isConfirmed ? 1 : 0}" data-mapping="${r.mapping_id || ''}"></td>
                <td class="px-3 py-3">${typeLabel}${modBadge}</td>
                <td class="px-3 py-3">${esc(r.issue_date)}</td>
                <td class="px-3 py-3">${esc(partnerName)}</td>
                <td class="px-3 py-3 text-right font-medium">${fmt(r.total_amount)}</td>
                <td class="px-3 py-3 text-center">${statusBadge}</td>
                <td class="px-3 py-3 text-center">${confidenceBar}</td>
                <td class="px-3 py-3">${txInfo}</td>
                <td class="px-3 py-3 text-center">${actions}</td>
            </tr>`;
    }).join('');

    const hasUnconfirmed = items.some(r => r.mapping_id && !r.is_confirmed);
    const hasConfirmed = items.some(r => r.is_confirmed == 1);
    document.getElementById('btnConfirm').classList.toggle('hidden', !hasUnconfirmed);
    document.getElementById('btnUnconfirm').classList.toggle('hidden', !hasConfirmed);
    lucide.createIcons();
}

function toggleCheckAll() {
    const checked = document.getElementById('checkAll').checked;
    document.querySelectorAll('.match-check:not(:disabled)').forEach(cb => cb.checked = checked);
}

async function runAutoMatch() {
    const { dateFrom, dateTo } = getDateRange();
    const btn = document.getElementById('btnAutoMatch');
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 inline animate-spin"></i> 매칭 중...';
    lucide.createIcons();

    try {
        const res = await fetch(INVOICE_API + '?action=autoMatch', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ date_from: dateFrom, date_to: dateTo }),
        });
        const data = await res.json();

        if (data.no_bank_data) {
            autoMatchAfterFetch = true;
            showBulkFetchModal();
            const resultEl = document.getElementById('bulkFetchResult');
            resultEl.classList.remove('hidden');
            resultEl.innerHTML = '<p class="text-amber-400 text-sm">해당 월의 통장내역이 없어서 매칭할 수 없습니다.<br>아래에서 비밀번호를 입력하고 조회하면 자동으로 매칭까지 실행됩니다.</p>';
        } else if (data.success) {
            let msg = `자동 매칭 완료\n매칭: ${data.matched}건 / 미매칭: ${data.unmatched}건`;
            const hints = data.aggregate_hints || [];
            if (hints.length) {
                msg += '\n\n[합산 입금 가능성]';
                hints.forEach(h => {
                    msg += `\n- ${h.partner_name}: ${h.invoice_ids.length}건 합계 ≈ 통장 ${h.candidate_txs?.length || 0}건`;
                });
            }
            alert(msg);
            loadMatchData();
        } else {
            alert('자동 매칭 실패: ' + (data.error || ''));
        }
    } catch (e) {
        alert('오류: ' + e.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="zap" class="w-4 h-4 inline"></i> 자동 매칭 실행';
        lucide.createIcons();
    }
}

async function openCandidateModal(invoiceId) {
    currentInvoiceId = invoiceId;
    document.getElementById('candidateModal').classList.remove('hidden');
    document.getElementById('candidateList').innerHTML = '<p class="text-slate-400 text-sm">후보 조회 중...</p>';

    try {
        const res = await fetch(`${INVOICE_API}?action=getMatchCandidates&invoice_id=${invoiceId}`);
        const data = await res.json();

        if (data.error) { alert(data.error); closeCandidateModal(); return; }

        const inv = data.invoice;
        const partnerName = inv.invoice_type === '매출' ? inv.buyer_name : inv.supplier_name;
        document.getElementById('modalInvoiceInfo').textContent =
            `${inv.invoice_type} | ${inv.issue_date} | ${partnerName} | ${fmt(inv.total_amount)}원`;

        const candidates = data.candidates || [];
        if (!candidates.length) {
            document.getElementById('candidateList').innerHTML =
                '<p class="text-slate-400 text-sm py-4 text-center">매칭 가능한 통장 거래내역이 없습니다.<br><span class="text-xs">통장내역을 먼저 조회/저장해주세요.</span></p>';
            return;
        }

        document.getElementById('candidateList').innerHTML = candidates.map(tx => {
            const conf = tx.confidence || 0;
            const color = conf >= 90 ? 'border-green-600 bg-green-900/10' : conf >= 80 ? 'border-blue-600 bg-blue-900/10' : conf >= 60 ? 'border-amber-600 bg-amber-900/10' : 'border-slate-700 bg-slate-800';
            const barColor = conf >= 90 ? 'bg-green-500' : conf >= 80 ? 'bg-blue-500' : conf >= 60 ? 'bg-amber-500' : 'bg-red-500';
            const disabled = tx.already_confirmed ? 'opacity-50 pointer-events-none' : '';

            return `
                <div class="border ${color} rounded-lg p-3 ${disabled}">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-1">
                                <span class="text-sm font-medium text-slate-200">${esc(tx.transaction_date)}</span>
                                <span class="px-2 py-0.5 rounded-full text-xs ${tx.tx_type === '입금' ? 'bg-blue-900/30 text-blue-400' : 'bg-orange-900/30 text-orange-400'}">${tx.tx_type}</span>
                                <span class="text-sm font-bold text-slate-100">${fmt(tx.amount)}원</span>
                            </div>
                            <p class="text-xs text-slate-400">${esc(tx.description)}</p>
                            <p class="text-xs text-slate-500 mt-1">${esc(tx.match_reason || '')}</p>
                        </div>
                        <div class="flex items-center gap-3 ml-4">
                            <div class="text-center">
                                <div class="w-16 h-2 bg-slate-700 rounded-full overflow-hidden mb-1">
                                    <div class="${barColor} h-full rounded-full" style="width:${conf}%"></div>
                                </div>
                                <span class="text-xs text-slate-400">${conf}%</span>
                            </div>
                            ${tx.already_confirmed
                                ? '<span class="text-xs text-slate-500">이미 확정</span>'
                                : `<button onclick="doManualMatch(${invoiceId}, ${tx.id}, ${conf})" class="px-3 py-1.5 bg-primary text-white text-xs rounded-lg hover:opacity-90">선택</button>`
                            }
                        </div>
                    </div>
                </div>`;
        }).join('');

        lucide.createIcons();
    } catch (e) {
        document.getElementById('candidateList').innerHTML = '<p class="text-red-400 text-sm">후보 조회 실패: ' + e.message + '</p>';
    }
}

function closeCandidateModal() {
    document.getElementById('candidateModal').classList.add('hidden');
    currentInvoiceId = null;
}

async function doManualMatch(invoiceId, transactionId, confidence) {
    try {
        const res = await fetch(INVOICE_API + '?action=saveMatch', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ invoice_id: invoiceId, transaction_id: transactionId, match_type: 'manual', confidence }),
        });
        const data = await res.json();
        if (data.success) {
            closeCandidateModal();
            loadMatchData();
        } else {
            alert('매칭 저장 실패: ' + (data.error || ''));
        }
    } catch (e) {
        alert('오류: ' + e.message);
    }
}

async function removeMatch(mappingId) {
    if (!(await AppUI.confirm('이 매칭을 해제하시겠습니까?'))) return;
    try {
        const res = await fetch(INVOICE_API + '?action=removeMatch', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mapping_id: mappingId }),
        });
        const data = await res.json();
        if (data.success) {
            loadMatchData();
        } else {
            alert(data.error || '해제 실패');
        }
    } catch (e) {
        alert('오류: ' + e.message);
    }
}

async function confirmSingle(mappingId) {
    try {
        const res = await fetch(INVOICE_API + '?action=confirmMatches', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mapping_ids: [mappingId] }),
        });
        const data = await res.json();
        if (data.success) loadMatchData();
        else alert(data.error || '확정 실패');
    } catch (e) {
        alert('오류: ' + e.message);
    }
}

async function confirmSelected() {
    const checkedInvoiceIds = [...document.querySelectorAll('.match-check:checked:not(:disabled)')]
        .map(cb => parseInt(cb.value))
        .filter(id => id > 0);

    if (!checkedInvoiceIds.length) { alert('항목을 선택해주세요.'); return; }

    const mappingIds = matchData
        .filter(r => checkedInvoiceIds.includes(r.id) && r.mapping_id && !r.is_confirmed)
        .map(r => r.mapping_id);

    if (!mappingIds.length) { alert('선택한 항목 중 확정할 수 있는 매칭 건이 없습니다.'); return; }
    if (!(await AppUI.confirm(mappingIds.length + '건을 확정하시겠습니까?'))) return;
    const ids = mappingIds;

    try {
        const res = await fetch(INVOICE_API + '?action=confirmMatches', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mapping_ids: ids }),
        });
        const data = await res.json();
        if (data.success) {
            alert(data.message);
            loadMatchData();
        } else {
            alert(data.error || '확정 실패');
        }
    } catch (e) {
        alert('오류: ' + e.message);
    }
}

async function unconfirmSingle(mappingId) {
    if (!(await AppUI.confirm('이 매칭의 확정을 해제하시겠습니까? 해제 후 수정/삭제가 가능해집니다.'))) return;
    try {
        const res = await fetch(INVOICE_API + '?action=unconfirmMatch', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mapping_ids: [mappingId] }),
        });
        const data = await res.json();
        if (data.success) loadMatchData();
        else alert(data.error || '해제 실패');
    } catch (e) {
        alert('오류: ' + e.message);
    }
}

async function unconfirmSelected() {
    const checkedBoxes = [...document.querySelectorAll('.match-check:checked')]
        .filter(cb => cb.dataset.confirmed === '1' && cb.dataset.mapping);
    const mappingIds = checkedBoxes.map(cb => parseInt(cb.dataset.mapping)).filter(id => id > 0);

    if (!mappingIds.length) { alert('해제할 확정 항목을 선택해주세요.'); return; }
    if (!(await AppUI.confirm(mappingIds.length + '건의 확정을 해제하시겠습니까?'))) return;

    try {
        const res = await fetch(INVOICE_API + '?action=unconfirmMatch', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mapping_ids: mappingIds }),
        });
        const data = await res.json();
        if (data.success) {
            alert(data.message);
            loadMatchData();
        } else {
            alert(data.error || '해제 실패');
        }
    } catch (e) {
        alert('오류: ' + e.message);
    }
}

async function runBulkAiMatch() {
    const btn = document.getElementById('btnBulkAi');
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 inline animate-spin"></i> AI 매칭 중...';
    lucide.createIcons();

    try {
        const res = await fetch('/api/ai.php?action=bulk_enhance_match', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({}),
        });
        const data = await res.json();

        const payload = data.data || data;
        if (data.ok !== false && payload.results) {
            const matched = payload.matched_count || 0;
            const total = payload.total_count || 0;
            let msg = `AI 일괄 매칭 완료: ${total}건 분석, ${matched}건 매칭 추천`;

            for (const r of payload.results) {
                if (r.best_match_id && r.confidence >= 60) {
                    await fetch(INVOICE_API + '?action=saveMatch', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            invoice_id: r.invoice_id,
                            transaction_id: r.best_match_id,
                            match_type: 'auto',
                            confidence: r.confidence,
                        }),
                    });
                }
            }

            alert(msg);
            loadMatchData();
        } else {
            alert(data.error?.message || data.message || 'AI 매칭 실패');
        }
    } catch (e) {
        alert('오류: ' + e.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="sparkles" class="w-4 h-4 inline"></i> AI 일괄 매칭';
        lucide.createIcons();
    }
}

// 통장내역 일괄 조회
function showBulkFetchModal() {
    document.getElementById('bulkFetchModal').classList.remove('hidden');
    document.getElementById('bulkFetchResult').classList.add('hidden');
    document.getElementById('bulkPassword').value = '';
    lucide.createIcons();
}

function closeBulkFetchModal() {
    document.getElementById('bulkFetchModal').classList.add('hidden');
}

async function doBulkFetch() {
    const pw = document.getElementById('bulkPassword').value;
    if (!pw || pw.length !== 4) { alert('계좌 비밀번호 4자리를 입력해주세요.'); return; }

    const { dateFrom } = getDateRange();
    const btn = document.getElementById('btnBulkFetch');
    btn.disabled = true;

    const base = new Date(dateFrom);
    const months = [];
    for (let offset = -1; offset <= 1; offset++) {
        const d = new Date(base.getFullYear(), base.getMonth() + offset, 1);
        const y = d.getFullYear();
        const m = d.getMonth();
        const first = `${y}${String(m + 1).padStart(2, '0')}01`;
        const last = `${y}${String(m + 1).padStart(2, '0')}${new Date(y, m + 1, 0).getDate()}`;
        months.push({ startDate: first, endDate: last });
    }

    const resultEl = document.getElementById('bulkFetchResult');
    resultEl.classList.remove('hidden');
    let grandTotal = 0;

    try {
        for (let i = 0; i < months.length; i++) {
            const range = months[i];
            const label = range.startDate.slice(0, 6);
            btn.textContent = `조회 중... (${i + 1}/${months.length})`;
            resultEl.innerHTML = `<p class="text-slate-400 text-sm">${label.slice(0,4)}년 ${parseInt(label.slice(4))}월 조회 중...</p>`;

            const res = await fetch(BANK_API + '?action=bulk_fetch', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    startDate: range.startDate,
                    endDate: range.endDate,
                    accountPassword: pw,
                }),
            });
            const data = await res.json();
            if (data.success && data.data) {
                grandTotal += data.data.transactions_saved || 0;
            }
        }

        resultEl.innerHTML = `<p class="font-medium text-green-400 mb-2">조회 완료</p><p>3개월(전월~다음월) 총 ${grandTotal}건 저장</p>`;

        if (autoMatchAfterFetch) {
            autoMatchAfterFetch = false;
            closeBulkFetchModal();
            runAutoMatch();
        }
    } catch (e) {
        resultEl.innerHTML = `<p class="text-red-400">오류: ${esc(e.message)}</p>`;
    } finally {
        btn.disabled = false;
        btn.textContent = '조회 시작';
    }
}

const AI_API = '<?= $basePath ?? '' ?>/api/ai.php';

async function aiEnhanceMatch(invoiceId) {
    const btn = event.target;
    const origText = btn.textContent;
    btn.disabled = true;
    btn.textContent = '분석중...';

    try {
        const res = await fetch(`${AI_API}?action=enhance_match`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ invoice_id: invoiceId })
        });
        const data = await res.json();

        if (data.ok && data.data) {
            const r = data.data;
            if (r.best_match_id) {
                const msg = `AI 추천 결과:\n\n매칭 거래: ${r.best_match_description || ''}\n금액: ${Number(r.best_match_amount || 0).toLocaleString()}원\n신뢰도: ${r.confidence}%\n근거: ${r.reason}\n\n이 매칭을 적용하시겠습니까?`;
                if ((await AppUI.confirm(msg))) {
                    await doManualMatch(invoiceId, r.best_match_id, r.confidence);
                }
            } else {
                alert('AI 분석 결과: 적합한 매칭 거래를 찾지 못했습니다.\n' + (r.reason || ''));
            }
        } else {
            alert('AI 분석 실패: ' + (data.error?.message || data.message || ''));
        }
    } catch (e) {
        alert('AI 요청 오류: ' + e.message);
    } finally {
        btn.disabled = false;
        btn.textContent = origText;
    }
}

async function aiEnhanceMatchInModal() {
    if (!currentInvoiceId) return;
    const btn = document.getElementById('btnAiModal');
    btn.disabled = true;
    btn.innerHTML = '<svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> 분석중...';

    try {
        const res = await fetch(`${AI_API}?action=enhance_match`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ invoice_id: currentInvoiceId })
        });
        const data = await res.json();

        if (data.ok && data.data) {
            const r = data.data;
            if (r.best_match_id) {
                const aiBox = document.createElement('div');
                aiBox.className = 'mb-3 p-3 border border-violet-600 bg-violet-900/20 rounded-lg';
                aiBox.innerHTML = `
                    <div class="flex items-center gap-2 mb-2">
                        <i data-lucide="sparkles" class="w-4 h-4 text-violet-400"></i>
                        <span class="text-sm font-medium text-violet-300">AI 추천</span>
                        <span class="text-xs text-violet-400">(신뢰도 ${r.confidence}%)</span>
                    </div>
                    <p class="text-xs text-slate-300 mb-1">${esc(r.reason)}</p>
                    <p class="text-xs text-slate-500">추천 거래 ID: ${r.best_match_id}</p>
                `;
                const listEl = document.getElementById('candidateList');
                listEl.insertBefore(aiBox, listEl.firstChild);

                const targetCard = listEl.querySelector(`button[onclick*="doManualMatch(${currentInvoiceId}, ${r.best_match_id}"]`);
                if (targetCard) {
                    const card = targetCard.closest('.border');
                    card.classList.add('ring-2', 'ring-violet-500');
                }

                lucide.createIcons();
            } else {
                alert('AI: 적합한 매칭을 찾지 못했습니다.\n' + (r.reason || ''));
            }
        } else {
            alert('AI 분석 실패: ' + (data.error?.message || data.message || ''));
        }
    } catch (e) {
        alert('AI 요청 오류: ' + e.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="sparkles" class="w-3.5 h-3.5"></i> AI 판단';
        lucide.createIcons();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadMatchData();
    lucide.createIcons();
});
</script>
