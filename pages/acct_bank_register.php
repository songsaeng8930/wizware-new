<?php
// $banks and $apiBasePath are set by acct_bank.php shell
if (!defined('BANKAPI_BASE_URL')) {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/bankapi.php';
    $banks = BANKAPI_BANKS;
    $apiBasePath = rtrim(str_replace('\\', '/', str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', realpath(__DIR__ . '/..'))), '/');
}
?>

<!-- 상단 안내 -->
<div class="bg-[var(--zm-surface-1)] border border-[var(--zm-border)] rounded-xl p-5 mb-5 shadow-sm">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-primary/10">
                <i data-lucide="landmark" class="w-4.5 h-4.5 text-primary"></i>
            </span>
            <div>
                <p class="text-sm font-semibold text-[var(--zm-text-strong)]">Bank API 등록 계좌</p>
                <p class="text-xs text-[var(--zm-text-muted)] mt-0.5">bankapi.co.kr에 등록된 계좌를 관리합니다. 등록된 계좌만 거래내역 조회가 가능합니다.</p>
            </div>
        </div>
        <button onclick="openRegisterModal()" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm text-white bg-primary rounded-lg hover:opacity-90">
            <i data-lucide="plus" class="w-4 h-4"></i>계좌 등록
        </button>
    </div>
</div>

<!-- API 정보 카드 -->
<div class="bg-[var(--zm-surface-2)] rounded-xl p-4 border border-[var(--zm-border)] mb-5">
    <div class="flex items-start gap-3">
        <i data-lucide="info" class="w-4 h-4 text-[var(--zm-text-subtle)] flex-shrink-0 mt-0.5"></i>
        <div class="flex-1">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-6 gap-y-1 text-sm">
                <div><span class="text-[var(--zm-text-muted)]">Base URL</span> <code class="text-xs text-[var(--zm-text-default)] bg-[var(--zm-surface-1)] px-1.5 py-0.5 rounded"><?= BANKAPI_BASE_URL ?></code></div>
                <div><span class="text-[var(--zm-text-muted)]">API Key</span> <code class="text-xs text-[var(--zm-text-default)] bg-[var(--zm-surface-1)] px-1.5 py-0.5 rounded"><?= substr(BANKAPI_API_KEY, 0, 8) ?>...</code></div>
                <div><span class="text-[var(--zm-text-muted)]">지원 은행</span> <span class="text-[var(--zm-text-default)]"><?= implode(', ', array_keys(BANKAPI_BANKS)) ?> (<?= count(BANKAPI_BANKS) ?>개)</span></div>
                <div><span class="text-[var(--zm-text-muted)]">상태</span> <span class="text-emerald-600 font-medium" id="apiStatus">확인 중...</span></div>
            </div>
        </div>
    </div>
</div>

<!-- 로딩 -->
<div id="accLoading" class="py-8 text-center">
    <svg class="animate-spin w-6 h-6 text-primary mx-auto mb-2" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
    </svg>
    <p class="text-sm text-slate-400">등록 계좌 조회 중...</p>
</div>

<!-- 에러 -->
<div id="accError" class="hidden mb-5 p-4 bg-amber-50 border border-amber-200 rounded-xl">
    <div class="flex items-start gap-3">
        <i data-lucide="alert-circle" class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5"></i>
        <div>
            <p class="text-sm font-medium text-amber-700">계좌 목록 조회 실패</p>
            <p class="text-sm text-amber-700 mt-0.5" id="accErrorMsg"></p>
            <p class="text-sm text-amber-500 mt-1">config/bankapi.php에서 API Key와 Secret을 확인하세요.</p>
        </div>
    </div>
</div>

<!-- 계좌 목록 -->
<div id="accList" class="hidden space-y-3 mb-6"></div>

<!-- 빈 상태 -->
<div id="accEmpty" class="hidden py-12 text-center text-[var(--zm-text-muted)]">
    <i data-lucide="landmark" class="w-10 h-10 mx-auto mb-3 text-[var(--zm-text-subtle)]"></i>
    <p>등록된 계좌가 없습니다.</p>
    <p class="text-sm mt-1">위의 "계좌 등록" 버튼으로 Bank API에 계좌를 등록하세요.</p>
</div>


<!-- 계좌 등록 모달 -->
<div id="registerModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeRegisterModal()"></div>
    <div class="relative bg-slate-900 rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800">
            <div>
                <h3 class="text-base font-bold text-slate-100">계좌 등록</h3>
                <p class="text-sm text-slate-500 mt-0.5">Bank API에 조회용 계좌를 등록합니다</p>
            </div>
            <button onclick="closeRegisterModal()" class="text-slate-500 hover:text-slate-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1.5">은행 선택 <span class="text-amber-500">*</span></label>
                <select id="regBankCode" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30">
                    <option value="">은행 선택</option>
                    <?php foreach ($banks as $code => $name): ?>
                    <option value="<?= $code ?>"><?= $name ?> (<?= $code ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1.5">계좌번호 <span class="text-amber-500">*</span></label>
                <input type="text" id="regAccountNo" placeholder="숫자만 입력 (- 없이)" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30">
            </div>
            <div id="regResultBox" class="hidden p-3 rounded-xl text-sm"></div>
        </div>
        <div class="flex gap-2 px-6 pb-5 justify-end border-t border-slate-800 pt-4">
            <button onclick="closeRegisterModal()" class="btn btn-secondary">취소</button>
            <button onclick="registerAccount()" id="btnRegisterAcc" class="px-4 py-2 text-sm text-white bg-primary rounded-lg hover:opacity-90 flex items-center gap-1.5">
                <i data-lucide="check" class="w-4 h-4"></i>등록
            </button>
        </div>
    </div>
</div>

<!-- 삭제 확인 모달 -->
<div id="deleteModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeDeleteModal()"></div>
    <div class="relative bg-slate-900 rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
        <i data-lucide="alert-triangle" class="w-12 h-12 text-amber-500 mx-auto mb-3"></i>
        <h3 class="text-base font-bold text-slate-100 mb-1">계좌 삭제</h3>
        <p class="text-sm text-slate-400 mb-4" id="deleteMsg">이 계좌를 Bank API에서 삭제하시겠습니까?</p>
        <div class="flex gap-2 justify-center">
            <button onclick="closeDeleteModal()" class="btn btn-secondary">취소</button>
            <button onclick="confirmDelete()" id="btnDelete" class="px-4 py-2 text-sm text-white bg-amber-500 rounded-lg hover:bg-amber-600">삭제</button>
        </div>
    </div>
</div>

<script src="<?= $apiBasePath ?>/assets/js/bank-brand.js"></script>
<script>
window.BANK_IMG = '<?= $apiBasePath ?>/assets/img/banks';
var API_URL = '<?= $apiBasePath ?>/api/bankapi.php';
var BANKS = <?= json_encode($banks, JSON_UNESCAPED_UNICODE) ?>;
var pendingDeleteBank = '';
var pendingDeleteAccNo = '';

(function() {
    var el = document.getElementById('regAccountNo');
    if (el) el.addEventListener('input', function() {
        var pos = this.selectionStart;
        var before = this.value.length;
        this.value = this.value.replace(/[^0-9]/g, '');
        this.selectionStart = this.selectionEnd = pos - (before - this.value.length);
    });
})();

async function loadAccounts() {
    var loading = document.getElementById('accLoading');
    var error = document.getElementById('accError');
    var list = document.getElementById('accList');
    var empty = document.getElementById('accEmpty');
    var status = document.getElementById('apiStatus');

    loading.classList.remove('hidden');
    error.classList.add('hidden');
    list.classList.add('hidden');
    empty.classList.add('hidden');

    try {
        var res = await fetch(API_URL + '?action=list_accounts');
        var json = await res.json();
        loading.classList.add('hidden');

        if (!json.success) {
            document.getElementById('accErrorMsg').textContent = json.message;
            error.classList.remove('hidden');
            if (status) { status.textContent = '\연\결 \실\패'; status.className = 'text-amber-500 font-medium'; }
            return;
        }

        if (status) { status.textContent = '\정\상 \연\결'; status.className = 'text-amber-700 font-medium'; }

        var accounts = (json.data && json.data.accounts) || json.data || [];
        var registered = (json.data && json.data.registeredCount != null) ? json.data.registeredCount : accounts.length;
        var remaining = (json.data && json.data.remainingSlots != null) ? json.data.remainingSlots : '?';

        if (accounts.length === 0 && registered === 0) { empty.classList.remove('hidden'); return; }

        list.innerHTML = '<div class="flex items-center gap-2 text-sm text-[var(--zm-text-muted)] mb-2"><i data-lucide="database" class="w-4 h-4"></i>\등\록 \계\좌: <strong class="text-[var(--zm-text-strong)]">' + registered + '\개</strong> \· \남\은 \슬\롯: <strong class="text-[var(--zm-text-strong)]">' + remaining + '\개</strong></div>';

        if (Array.isArray(accounts)) {
            accounts.forEach(function(acc) {
                var bankCode = acc.bankCode || '';
                var bankName = acc.bankName || BANKS[bankCode] || bankCode;
                var accNo = acc.accountNumber || '';
                var maskedNo = accNo.length > 4 ? accNo.slice(0,4) + '****' + accNo.slice(-4) : accNo;
                var q = String.fromCharCode(39);

                list.innerHTML +=
                    '<div class="flex items-center gap-4 p-4 bg-[var(--zm-surface-1)] border border-[var(--zm-border)] rounded-xl hover:border-gray-400 transition-colors group shadow-sm">' +
                        bankBadgeHtml(bankName, 'lg') +
                        '<div class="flex-1 min-w-0">' +
                            '<div class="flex items-center gap-2">' +
                                '<span class="font-medium text-[var(--zm-text-strong)]">' + bankName + '</span>' +
                                '<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full bg-emerald-50 text-emerald-600 ring-1 ring-emerald-200"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>\연\결\됨</span>' +
                            '</div>' +
                            '<p class="text-sm text-[var(--zm-text-muted)] mt-0.5 tabular-nums">' + maskedNo + '</p>' +
                        '</div>' +
                        '<div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">' +
                            '<a href="?tab=history" class="text-sm px-3 py-1.5 bg-primary/10 text-primary rounded-lg hover:bg-gray-100 transition-colors flex items-center gap-1">' +
                                '<i data-lucide="search" class="w-3 h-3"></i>\조\회' +
                            '</a>' +
                            '<button onclick="openDeleteModal(' + q + bankCode + q + ',' + q + accNo + q + ')" class="text-slate-500 hover:text-amber-500 transition-colors p-1.5 rounded-lg hover:bg-amber-50">' +
                                '<i data-lucide="trash-2" class="w-4 h-4"></i>' +
                            '</button>' +
                        '</div>' +
                    '</div>';
            });
        }

        list.classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    } catch (e) {
        loading.classList.add('hidden');
        document.getElementById('accErrorMsg').textContent = e.message;
        error.classList.remove('hidden');
        if (status) { status.textContent = '\연\결 \실\패'; status.className = 'text-amber-500 font-medium'; }
    }
}

function openRegisterModal() {
    document.getElementById('regBankCode').value = '';
    document.getElementById('regAccountNo').value = '';
    document.getElementById('regResultBox').classList.add('hidden');
    document.getElementById('registerModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeRegisterModal() {
    document.getElementById('registerModal').classList.add('hidden');
    document.body.style.overflow = '';
}

async function registerAccount() {
    var bankCode = document.getElementById('regBankCode').value;
    var accountNo = document.getElementById('regAccountNo').value.replace(/[^0-9]/g, '');
    var resultBox = document.getElementById('regResultBox');

    if (!bankCode || !accountNo) {
        resultBox.className = 'p-3 rounded-xl text-sm bg-amber-50 text-amber-700';
        resultBox.textContent = '\은\행\과 \계\좌\번\호\를 \입\력\하\세\요.';
        resultBox.classList.remove('hidden');
        return;
    }

    document.getElementById('btnRegisterAcc').disabled = true;
    resultBox.className = 'p-3 rounded-xl text-sm bg-primary-light text-primary';
    resultBox.textContent = 'Bank API\에 \계\좌\를 \등\록\하\는 \중...';
    resultBox.classList.remove('hidden');

    try {
        var res = await fetch(API_URL + '?action=register_account', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ bankCode: bankCode, accountNumber: accountNo })
        });
        var json = await res.json();
        if (json.success) {
            resultBox.className = 'p-3 rounded-xl text-sm bg-amber-50 text-amber-700';
            resultBox.textContent = '\계\좌 \등\록 \완\료! ' + (json.message || '');
            setTimeout(function() { closeRegisterModal(); loadAccounts(); }, 1500);
        } else {
            resultBox.className = 'p-3 rounded-xl text-sm bg-amber-50 text-amber-700';
            resultBox.textContent = json.message || '\등\록 \실\패';
        }
    } catch (e) {
        resultBox.className = 'p-3 rounded-xl text-sm bg-amber-50 text-amber-700';
        resultBox.textContent = '\네\트\워\크 \오\류: ' + e.message;
    } finally {
        document.getElementById('btnRegisterAcc').disabled = false;
    }
}

function openDeleteModal(bankCode, accNo) {
    pendingDeleteBank = bankCode;
    pendingDeleteAccNo = accNo;
    var bankName = BANKS[bankCode] || bankCode;
    var masked = accNo.length > 4 ? accNo.slice(0,4) + '****' + accNo.slice(-4) : accNo;
    document.getElementById('deleteMsg').textContent = bankName + ' ' + masked + ' \계\좌\를 \삭\제\하\시\겠\습\니\까?';
    document.getElementById('deleteModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    document.body.style.overflow = '';
}

async function confirmDelete() {
    document.getElementById('btnDelete').disabled = true;
    try {
        var res = await fetch(API_URL + '?action=delete_account', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ bankCode: pendingDeleteBank, accountNumber: pendingDeleteAccNo })
        });
        var json = await res.json();
        closeDeleteModal();
        if (json.success) loadAccounts(); else alert(json.message || '\삭\제 \실\패');
    } catch (e) {
        closeDeleteModal();
        alert('\네\트\워\크 \오\류: ' + e.message);
    } finally {
        document.getElementById('btnDelete').disabled = false;
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { closeRegisterModal(); closeDeleteModal(); }
});

document.addEventListener('DOMContentLoaded', loadAccounts);
</script>
