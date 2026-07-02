<?php
/**
 * 계좌 관리 탭
 * 등록된 계좌 목록 조회, 추가, 수정, 삭제, 활성/비활성 토글
 */
if (!isset($banks)) {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/bankapi.php';
    $banks = BANKAPI_BANKS;
    $apiBasePath = rtrim(str_replace('\\', '/', str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', realpath(__DIR__ . '/..'))), '/');
}
?>

<div class="space-y-5">

    <!-- 상단 헤더 -->
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-slate-400">등록된 은행 계좌를 관리합니다. 계좌를 추가하거나 비활성화할 수 있습니다.</p>
        </div>
        <button onclick="openAddModal()" class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium text-white bg-primary rounded-xl hover:opacity-90 transition-opacity">
            <i data-lucide="plus" class="w-4 h-4"></i>계좌 추가
        </button>
    </div>

    <!-- 요약 카드 -->
    <div class="grid grid-cols-4 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-xs text-slate-400 mb-1">총 잔액</p>
            <p class="text-2xl font-bold text-slate-100 tabular-nums" id="sumBalance">-</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-xs text-slate-400 mb-1">당일 입금</p>
            <p class="text-2xl font-bold text-emerald-400 tabular-nums" id="sumDeposit">-</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-xs text-slate-400 mb-1">당일 출금</p>
            <p class="text-2xl font-bold text-red-400 tabular-nums" id="sumWithdraw">-</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-xs text-slate-400 mb-1">계좌</p>
            <p class="text-2xl font-bold text-primary" id="sumCount">-</p>
        </div>
    </div>

    <!-- 필터 -->
    <div id="accountFilters" class="hidden flex items-center gap-2">
        <button onclick="filterAccounts('all')" data-filter="all" class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors bg-primary text-white">전체</button>
        <button onclick="filterAccounts('운영')" data-filter="운영" class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors bg-slate-800 text-slate-400 hover:text-slate-200">운영</button>
        <button onclick="filterAccounts('급여')" data-filter="급여" class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors bg-slate-800 text-slate-400 hover:text-slate-200">급여</button>
        <button onclick="filterAccounts('세금')" data-filter="세금" class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors bg-slate-800 text-slate-400 hover:text-slate-200">세금</button>
        <button onclick="filterAccounts('예비')" data-filter="예비" class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors bg-slate-800 text-slate-400 hover:text-slate-200">예비</button>
        <button onclick="filterAccounts('기타')" data-filter="기타" class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors bg-slate-800 text-slate-400 hover:text-slate-200">기타</button>
    </div>

    <!-- 계좌 카드 목록 -->
    <div id="accountCards" class="space-y-2">
        <div class="py-12 text-center">
            <svg class="animate-spin w-6 h-6 text-primary mx-auto mb-3" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <p class="text-sm text-slate-400">계좌 목록을 불러오는 중...</p>
        </div>
    </div>
</div>

<!-- 계좌 추가 모달 -->
<div id="addModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/60" onclick="closeAddModal()"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md bg-slate-900 border border-slate-700 rounded-xl shadow-2xl p-6">
        <h3 class="text-sm font-bold text-slate-100 mb-4 flex items-center gap-2">
            <i data-lucide="plus-circle" class="w-4 h-4 text-primary"></i>
            계좌 추가
        </h3>
        <div class="space-y-3">
            <div>
                <label class="block text-xs text-slate-400 mb-1">은행 선택</label>
                <select id="addBank" class="w-full border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm">
                    <option value="">은행을 선택하세요</option>
                    <?php foreach ($banks as $code => $name): ?>
                    <option value="<?= htmlspecialchars($code) ?>"><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">계좌번호</label>
                <input type="text" id="addAccountNo" class="w-full border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="숫자만 입력 (하이픈 자동 제거)">
            </div>
        </div>
        <div id="addError" class="hidden mt-3 p-3 bg-red-500/10 border border-red-500/20 rounded-lg text-sm text-red-300"></div>
        <div class="flex justify-end gap-2 mt-4">
            <button onclick="closeAddModal()" class="btn btn-secondary">취소</button>
            <button onclick="doAddAccount()" id="btnAdd" class="px-4 py-2 bg-primary text-white text-sm rounded-lg hover:opacity-90">등록</button>
        </div>
    </div>
</div>

<!-- 계좌 수정 모달 -->
<div id="editModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/60" onclick="closeEditModal()"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md bg-slate-900 border border-slate-700 rounded-xl shadow-2xl p-6">
        <h3 class="text-sm font-bold text-slate-100 mb-4 flex items-center gap-2">
            <i data-lucide="edit-3" class="w-4 h-4 text-primary"></i>
            계좌 정보 수정
        </h3>
        <input type="hidden" id="editAccountId">
        <input type="hidden" id="editType">
        <input type="hidden" id="editSortOrder">
        <input type="hidden" id="editConsent">
        <div class="space-y-3">
            <div>
                <label class="block text-xs text-slate-400 mb-1">은행 / 계좌번호</label>
                <p class="text-sm text-slate-300 bg-slate-800 rounded-lg px-3 py-2" id="editBankInfo">-</p>
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">별칭</label>
                <input type="text" id="editAlias" class="w-full border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="예: 운영계좌, 급여계좌">
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">예금주</label>
                <input type="text" id="editOwner" class="w-full border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="예금주명">
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">계좌 비밀번호</label>
                <div id="pwStatusView" class="flex items-center justify-between bg-slate-800 border border-slate-700 rounded-lg px-3 py-2">
                    <span id="pwStatusText" class="text-sm text-slate-400"></span>
                    <div class="flex items-center gap-2">
                        <button type="button" id="pwDeleteBtn" onclick="deletePassword()" class="hidden text-xs text-rose-400 hover:text-rose-300">삭제</button>
                        <button type="button" id="pwActionBtn" onclick="togglePasswordEdit()" class="text-xs text-primary hover:text-gray-900"></button>
                    </div>
                </div>
                <div id="pwEditView" class="hidden">
                    <input type="password" id="editPassword" maxlength="4" class="w-full border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm tracking-widest" placeholder="4자리 입력" autocomplete="off">
                    <div class="flex gap-2 mt-1.5">
                        <button type="button" onclick="cancelPasswordEdit()" class="text-xs text-slate-500 hover:text-slate-300">취소</button>
                    </div>
                </div>
                <p class="text-xs text-slate-600 mt-1">저장하면 거래내역 조회 시 매번 입력하지 않아도 됩니다</p>
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">메모</label>
                <textarea id="editMemo" rows="2" class="w-full border border-slate-700 bg-slate-800 text-slate-200 rounded-lg px-3 py-2 text-sm resize-none" placeholder="용도, 담당자 등 자유롭게 메모"></textarea>
            </div>
        </div>
        <div class="flex justify-end gap-2 mt-4">
            <button onclick="closeEditModal()" class="btn btn-secondary">취소</button>
            <button onclick="doUpdateAccount()" id="btnEdit" class="px-4 py-2 bg-primary text-white text-sm rounded-lg hover:opacity-90">저장</button>
        </div>
    </div>
</div>

<script src="<?= $apiBasePath ?>/assets/js/bank-brand.js"></script>
<script>
window.BANK_IMG = '<?= $apiBasePath ?>/assets/img/banks';
const BANK_API = '<?= $apiBasePath ?>/api/bankapi.php';
const BANK_NAMES = <?= json_encode($banks, JSON_UNESCAPED_UNICODE) ?>;
let accountList = [];
let currentFilter = 'all';

function esc(s) {
    return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function fmt(n) {
    return Number(n || 0).toLocaleString();
}

function maskAccountNo(no) {
    if (!no || no.length < 6) return no;
    return no.slice(0, 3) + '-' + no.slice(3, 6) + '-' + '*'.repeat(Math.max(0, no.length - 6));
}

function formatSyncTime(dt) {
    if (!dt) return { text: '조회 기록 없음', color: 'text-slate-700' };
    const synced = new Date(dt);
    const now = new Date();
    const diffMs = now - synced;
    const diffMin = Math.floor(diffMs / 60000);
    const diffHour = Math.floor(diffMin / 60);
    const diffDay = Math.floor(diffHour / 24);

    let text;
    if (diffMin < 1) text = '방금 조회';
    else if (diffMin < 60) text = diffMin + '분 전 조회';
    else if (diffHour < 24) text = diffHour + '시간 전 조회';
    else if (diffDay < 7) text = diffDay + '일 전 조회';
    else text = synced.toLocaleDateString('ko-KR', { month: 'short', day: 'numeric' }) + ' 조회';

    const color = diffDay >= 7 ? 'text-amber-500' : diffDay >= 1 ? 'text-slate-500' : 'text-slate-600';
    return { text, color };
}

async function loadAccounts() {
    try {
        const res = await fetch(`${BANK_API}?action=list_accounts_full`);
        const data = await res.json();

        if (!data.success) {
            document.getElementById('accountCards').innerHTML =
                `<div class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 text-sm text-red-300">${esc(data.message)}</div>`;
            return;
        }

        accountList = data.data.accounts || [];
        updateSummary(accountList, data.data);
        showFilters(accountList);
        applyFilter();
    } catch (e) {
        document.getElementById('accountCards').innerHTML =
            `<div class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 text-sm text-red-300">계좌 목록 로드 실패: ${esc(e.message)}</div>`;
    }
}

function updateSummary(accounts, apiData) {
    const active = accounts.filter(a => a.is_active == 1);
    const inactive = accounts.filter(a => a.is_active != 1);

    document.getElementById('sumBalance').textContent = '₩' + fmt(apiData.totalBalance || 0);
    document.getElementById('sumDeposit').textContent = '₩' + fmt(apiData.todayDeposit || 0);
    document.getElementById('sumWithdraw').textContent = '₩' + fmt(apiData.todayWithdraw || 0);
    document.getElementById('sumCount').textContent = active.length + (inactive.length ? ' / ' + inactive.length : '');
}

function renderAccounts(accounts) {
    const container = document.getElementById('accountCards');

    if (!accounts.length) {
        container.innerHTML = `
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-12 text-center">
                <div class="w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="landmark" class="w-7 h-7 text-primary"></i>
                </div>
                <p class="text-sm font-medium text-slate-300 mb-1">등록된 계좌가 없습니다</p>
                <p class="text-xs text-slate-500 mb-4">계좌를 추가하면 거래내역 조회와 AI 분류를 사용할 수 있습니다.</p>
                <button onclick="openAddModal()" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:opacity-90">
                    <i data-lucide="plus" class="w-4 h-4"></i>첫 계좌 추가
                </button>
            </div>`;
        lucide.createIcons();
        return;
    }

    const TYPE_COLORS = {
        '운영': { border: 'border-gray-400/30', text: 'text-gray-400/60' },
        '급여': { border: 'border-emerald-400/30', text: 'text-emerald-400/60' },
        '세금': { border: 'border-amber-400/30', text: 'text-amber-400/60' },
        '예비': { border: 'border-purple-400/30', text: 'text-purple-400/60' },
        '기타': { border: 'border-slate-500/30', text: 'text-slate-500/60' },
    };

    container.innerHTML = accounts.map(a => {
        const isActive = a.is_active == 1;
        const opacity = isActive ? '' : 'opacity-40';
        const typeName = a.account_type || '운영';
        const tc = TYPE_COLORS[typeName] || TYPE_COLORS['기타'];

        const txCount = parseInt(a.tx_count) || 0;
        const balance = parseInt(a.latest_balance) || 0;
        const monthIn = parseInt(a.month_deposit) || 0;
        const monthOut = parseInt(a.month_withdraw) || 0;
        const alias = a.account_alias || '';
        const displayName = alias || typeName + '계좌';
        const maskedNo = maskAccountNo(a.account_no);
        const memoSnippet = a.memo ? esc(a.memo.length > 30 ? a.memo.slice(0, 30) + '…' : a.memo) : '';
        const inactiveTag = !isActive ? '<span class="ml-1.5 text-[11px] px-1.5 py-0.5 rounded bg-slate-800 text-slate-500">비활성</span>' : '';
        const hasPw = a.account_password;
        const syncLabel = formatSyncTime(a.last_synced_at);

        return `
            <div class="bg-slate-900 border border-slate-800 rounded-xl ${opacity} hover:border-slate-700 transition-colors" data-acct-id="${a.id}">
                <a href="?tab=history" class="block cursor-pointer">
                    <div class="px-4 py-2.5 flex items-center gap-3">
                        ${bankBadgeHtml(a.bank_name)}
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5">
                                <span class="text-sm font-bold text-slate-100">${esc(displayName)}</span>
                                <span class="text-[9px] px-1 py-px rounded border ${tc.border} ${tc.text}">${esc(typeName)}</span>
                                ${inactiveTag}
                            </div>
                            <div class="flex items-center gap-1.5 text-[11px] text-slate-500 mt-0.5">
                                <span>${esc(a.bank_name)} ${esc(maskedNo)}</span>
                                ${a.owner_name ? `<span>·</span><span>${esc(a.owner_name)}</span>` : ''}
                                <span>·</span><span>거래 ${fmt(txCount)}건</span>
                                ${hasPw ? '<span class="text-emerald-600">● 비밀번호 저장됨</span>' : ''}
                                <span class="${syncLabel.color}" title="${esc(a.last_synced_at || '')}">· ${syncLabel.text}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-5 shrink-0">
                            <div class="text-right">
                                <p class="text-[10px] text-slate-500">현재 잔액</p>
                                <p class="text-sm font-bold tabular-nums ${balance > 0 ? 'text-white' : 'text-slate-600'}">₩${fmt(balance)}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] text-slate-500">당월 입금</p>
                                <p class="text-sm font-semibold tabular-nums ${monthIn > 0 ? 'text-emerald-400' : 'text-slate-600'}">₩${fmt(monthIn)}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] text-slate-500">당월 출금</p>
                                <p class="text-sm font-semibold tabular-nums ${monthOut > 0 ? 'text-red-400' : 'text-slate-600'}">₩${fmt(monthOut)}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-0.5 shrink-0 ml-1">
                            <button onclick="event.preventDefault(); event.stopPropagation(); openEditModal(${a.id})" class="p-1.5 rounded text-slate-600 hover:text-slate-300 hover:bg-slate-800 transition-colors" title="수정">
                                <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                            </button>
                            <button onclick="event.preventDefault(); event.stopPropagation(); toggleAccount(${a.id})" class="p-1.5 rounded ${isActive ? 'text-slate-600 hover:text-amber-400 hover:bg-amber-500/10' : 'text-slate-600 hover:text-emerald-400 hover:bg-emerald-500/10'} transition-colors" title="${isActive ? '비활성화' : '활성화'}">
                                <i data-lucide="${isActive ? 'pause' : 'play'}" class="w-3.5 h-3.5"></i>
                            </button>
                            <button onclick="event.preventDefault(); event.stopPropagation(); deleteAccount(${a.id})" class="p-1.5 rounded text-slate-600 hover:text-red-400 hover:bg-red-500/10 transition-colors" title="삭제">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </div>
                </a>
            </div>`;
    }).join('');

    lucide.createIcons();
}

// ─── 필터링 ───
function showFilters(accounts) {
    const types = new Set(accounts.map(a => a.account_type || '운영'));
    const filterWrap = document.getElementById('accountFilters');
    if (accounts.length > 1) {
        filterWrap.classList.remove('hidden');
        filterWrap.querySelectorAll('[data-filter]').forEach(btn => {
            const f = btn.getAttribute('data-filter');
            if (f === 'all') return;
            btn.classList.toggle('hidden', !types.has(f));
        });
    } else {
        filterWrap.classList.add('hidden');
    }
}

function filterAccounts(type) {
    currentFilter = type;
    document.querySelectorAll('#accountFilters [data-filter]').forEach(btn => {
        const isActive = btn.getAttribute('data-filter') === type;
        btn.className = 'px-3 py-1.5 text-xs font-medium rounded-lg transition-colors ' +
            (isActive ? 'bg-primary text-white' : 'bg-slate-800 text-slate-400 hover:text-slate-200');
    });
    applyFilter();
}

function applyFilter() {
    const filtered = currentFilter === 'all'
        ? accountList
        : accountList.filter(a => (a.account_type || '운영') === currentFilter);
    renderAccounts(filtered);
}

// ─── 계좌 추가 ───
function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
    document.getElementById('addBank').value = '';
    document.getElementById('addAccountNo').value = '';
    document.getElementById('addError').classList.add('hidden');
    lucide.createIcons();
}
function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
}

async function doAddAccount() {
    const bankCode = document.getElementById('addBank').value;
    const accountNo = document.getElementById('addAccountNo').value.replace(/[^0-9]/g, '');
    const errEl = document.getElementById('addError');

    if (!bankCode) { errEl.textContent = '은행을 선택하세요.'; errEl.classList.remove('hidden'); return; }
    if (!accountNo) { errEl.textContent = '계좌번호를 입력하세요.'; errEl.classList.remove('hidden'); return; }

    const btn = document.getElementById('btnAdd');
    btn.disabled = true;
    btn.textContent = '등록 중...';
    errEl.classList.add('hidden');

    try {
        const res = await fetch(`${BANK_API}?action=register_account`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ bankCode, accountNumber: accountNo }),
        });
        const data = await res.json();

        if (data.success) {
            closeAddModal();
            loadAccounts();
        } else {
            errEl.textContent = data.message || '등록 실패';
            errEl.classList.remove('hidden');
        }
    } catch (e) {
        errEl.textContent = '오류: ' + e.message;
        errEl.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        btn.textContent = '등록';
    }
}

// ─── 계좌 수정 ───
let editingPasswordMode = false;
let editingAccountHasPassword = false;
let passwordMarkedForDeletion = false;

function openEditModal(accountId) {
    const acct = accountList.find(a => a.id == accountId);
    if (!acct) return;

    document.getElementById('editAccountId').value = accountId;
    document.getElementById('editBankInfo').textContent = `${acct.bank_name} ${maskAccountNo(acct.account_no)}`;
    document.getElementById('editAlias').value = acct.account_alias || '';
    document.getElementById('editType').value = acct.account_type || '운영';
    document.getElementById('editOwner').value = acct.owner_name || '';
    document.getElementById('editMemo').value = acct.memo || '';
    document.getElementById('editConsent').value = acct.consent_agreed == 1 ? '1' : '0';
    document.getElementById('editSortOrder').value = acct.sort_order || 0;

    editingAccountHasPassword = acct.account_password === true || acct.account_password === 1 || acct.account_password === '1';
    editingPasswordMode = false;
    passwordMarkedForDeletion = false;
    updatePasswordUI();

    document.getElementById('editModal').classList.remove('hidden');
    lucide.createIcons();
}

function updatePasswordUI() {
    const statusView = document.getElementById('pwStatusView');
    const editView = document.getElementById('pwEditView');
    const statusText = document.getElementById('pwStatusText');
    const actionBtn = document.getElementById('pwActionBtn');

    if (editingPasswordMode) {
        statusView.classList.add('hidden');
        editView.classList.remove('hidden');
        document.getElementById('editPassword').value = '';
        document.getElementById('editPassword').focus();
    } else {
        statusView.classList.remove('hidden');
        editView.classList.add('hidden');
        if (editingAccountHasPassword) {
            statusText.textContent = '●●●● 저장됨';
            statusText.className = 'text-sm text-emerald-400';
            actionBtn.textContent = '변경';
            document.getElementById('pwDeleteBtn').classList.remove('hidden');
        } else {
            statusText.textContent = '미등록';
            statusText.className = 'text-sm text-slate-500';
            actionBtn.textContent = '등록';
            document.getElementById('pwDeleteBtn').classList.add('hidden');
        }
    }
}

function togglePasswordEdit() {
    editingPasswordMode = true;
    updatePasswordUI();
}

function cancelPasswordEdit() {
    editingPasswordMode = false;
    document.getElementById('editPassword').value = '';
    updatePasswordUI();
}

function deletePassword() {
    passwordMarkedForDeletion = true;
    editingAccountHasPassword = false;
    editingPasswordMode = false;
    document.getElementById('editPassword').value = '';
    updatePasswordUI();
}
function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

async function doUpdateAccount() {
    const accountId = document.getElementById('editAccountId').value;
    const alias = document.getElementById('editAlias').value;
    const accountType = document.getElementById('editType').value;
    const ownerName = document.getElementById('editOwner').value;
    let accountPassword;
    if (editingPasswordMode) {
        accountPassword = document.getElementById('editPassword').value;
    } else if (passwordMarkedForDeletion) {
        accountPassword = '';
    }
    const memo = document.getElementById('editMemo').value;
    const consentAgreed = document.getElementById('editConsent').value === '1';
    const sortOrder = parseInt(document.getElementById('editSortOrder').value) || 0;

    const btn = document.getElementById('btnEdit');
    btn.disabled = true;
    btn.textContent = '저장 중...';

    try {
        const res = await fetch(`${BANK_API}?action=update_account`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ accountId: parseInt(accountId), alias, accountType, ownerName, accountPassword, memo, consentAgreed, sortOrder }),
        });
        const data = await res.json();

        if (data.success) {
            closeEditModal();
            loadAccounts();
        } else {
            alert('수정 실패: ' + (data.message || ''));
        }
    } catch (e) {
        alert('오류: ' + e.message);
    } finally {
        btn.disabled = false;
        btn.textContent = '저장';
    }
}

// ─── 활성/비활성 토글 ───
async function toggleAccount(accountId) {
    const acct = accountList.find(a => a.id == accountId);
    if (!acct) return;

    const action = acct.is_active == 1 ? '비활성화' : '활성화';
    if (!(await AppUI.confirm(`${acct.bank_name} ${maskAccountNo(acct.account_no)} 계좌를 ${action}하시겠습니까?`))) return;

    try {
        const res = await fetch(`${BANK_API}?action=toggle_account`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ accountId }),
        });
        const data = await res.json();

        if (data.success) {
            loadAccounts();
        } else {
            alert(data.message || '변경 실패');
        }
    } catch (e) {
        alert('오류: ' + e.message);
    }
}

// ─── 계좌 삭제 ───
async function deleteAccount(accountId) {
    const acct = accountList.find(a => a.id == accountId);
    if (!acct) return;

    const txCount = parseInt(acct.tx_count) || 0;
    let msg = `${acct.bank_name} ${maskAccountNo(acct.account_no)} 계좌를 삭제하시겠습니까?`;
    if (txCount > 0) {
        msg += `\n\n이 계좌에 연결된 거래내역 ${fmt(txCount)}건도 함께 삭제됩니다.`;
    }
    if (!(await AppUI.confirm(msg))) return;

    try {
        const res = await fetch(`${BANK_API}?action=delete_account`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ bankCode: acct.bank_code, accountNumber: acct.account_no }),
        });
        const data = await res.json();

        if (data.success) {
            loadAccounts();
        } else {
            alert('삭제 실패: ' + (data.message || ''));
        }
    } catch (e) {
        alert('오류: ' + e.message);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadAccounts();
    lucide.createIcons();
});
</script>
