<?php
/**
 * 세무리포트 > 조정분개 탭
 * 포함: acct_report.php 쉘에서 include
 * 통장을 거치지 않는 거래 입력 (감가상각, 미지급비용, 대손충당금 등)
 */
$pdo = getDBConnection();

$categories = [];
if ($pdo) {
    try {
        $catStmt = $pdo->query("SELECT code, name, type FROM account_categories WHERE is_active = 1 AND code NOT LIKE 'G\\_%' ESCAPE '\\\\' ORDER BY FIELD(type, '자산','부채','자본','매출','매입','비용','수익'), sort_order, code");
        $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {}
}
?>

<!-- 기간 필터 -->
<div class="bg-slate-900 rounded-xl border border-slate-800 p-4 mb-4">
    <div class="flex items-center gap-3 flex-wrap">
        <label class="text-sm text-slate-400">회계연도</label>
        <select id="jeYear" onchange="loadJournals()" class="bg-slate-950 border border-slate-800 rounded-lg px-3 py-1.5 text-sm text-slate-100 focus:outline-none focus:ring-1 focus:ring-gray-300/30">
            <?php for ($y = (int)date('Y') + 1; $y >= 2020; $y--): ?>
            <option value="<?= $y ?>" <?= $y === (int)date('Y') ? 'selected' : '' ?>><?= $y ?>년</option>
            <?php endfor; ?>
        </select>
        <select id="jeMonth" onchange="loadJournals()" class="bg-slate-950 border border-slate-800 rounded-lg px-3 py-1.5 text-sm text-slate-100 focus:outline-none focus:ring-1 focus:ring-gray-300/30">
            <option value="0">전체</option>
            <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= $m === (int)date('m') ? 'selected' : '' ?>><?= $m ?>월</option>
            <?php endfor; ?>
        </select>
        <div class="flex-1"></div>
        <button onclick="openJeModal()" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:opacity-90 transition-opacity">
            <i data-lucide="plus" class="w-4 h-4"></i>분개 추가
        </button>
    </div>
</div>

<!-- 요약 카드 -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4" id="jeSummaryCards">
    <div class="bg-slate-900 rounded-xl border border-slate-800 p-4">
        <p class="text-xs text-slate-500 mb-1">총 분개 수</p>
        <p class="text-lg font-bold text-slate-100 tabular-nums" id="jeTotalCount">0건</p>
    </div>
    <div class="bg-slate-900 rounded-xl border border-slate-800 p-4">
        <p class="text-xs text-slate-500 mb-1">차변 합계</p>
        <p class="text-lg font-bold text-rose-400 tabular-nums" id="jeTotalDebit">0원</p>
    </div>
    <div class="bg-slate-900 rounded-xl border border-slate-800 p-4">
        <p class="text-xs text-slate-500 mb-1">대변 합계</p>
        <p class="text-lg font-bold text-primary tabular-nums" id="jeTotalCredit">0원</p>
    </div>
</div>

<!-- 분개 테이블 -->
<div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-700 text-slate-400">
                    <th class="py-3 px-4 text-left font-medium whitespace-nowrap">일자</th>
                    <th class="py-3 px-4 text-left font-medium whitespace-nowrap">유형</th>
                    <th class="py-3 px-4 text-left font-medium whitespace-nowrap">적요</th>
                    <th class="py-3 px-4 text-left font-medium whitespace-nowrap">차변 계정</th>
                    <th class="py-3 px-4 text-left font-medium whitespace-nowrap">대변 계정</th>
                    <th class="py-3 px-4 text-right font-medium whitespace-nowrap">금액</th>
                    <th class="py-3 px-4 text-left font-medium whitespace-nowrap">비고</th>
                    <th class="py-3 px-2 w-20"></th>
                </tr>
            </thead>
            <tbody id="jeTableBody">
                <tr><td colspan="8" class="py-12 text-center text-sm text-slate-500">로딩 중...</td></tr>
            </tbody>
        </table>
    </div>
    <div id="jeEmptyState" class="hidden py-12 text-center">
        <i data-lucide="file-text" class="w-10 h-10 text-slate-700 mx-auto mb-3"></i>
        <p class="text-sm text-slate-500">등록된 조정분개가 없습니다.</p>
        <button onclick="openJeModal()" class="mt-3 text-sm text-primary hover:underline">첫 분개 추가하기</button>
    </div>
</div>

<!-- 자주 쓰는 분개 템플릿 -->
<div class="mt-4 bg-slate-900 rounded-xl border border-slate-800 p-4">
    <h4 class="text-sm font-bold text-slate-300 mb-3">자주 쓰는 분개 템플릿</h4>
    <div class="flex flex-wrap gap-2">
        <button onclick="applyTemplate('depreciation')" class="px-3 py-1.5 text-xs bg-slate-800 text-slate-300 rounded-lg hover:bg-slate-700 transition-colors">감가상각비</button>
        <button onclick="applyTemplate('accrued_expense')" class="px-3 py-1.5 text-xs bg-slate-800 text-slate-300 rounded-lg hover:bg-slate-700 transition-colors">미지급비용</button>
        <button onclick="applyTemplate('prepaid')" class="px-3 py-1.5 text-xs bg-slate-800 text-slate-300 rounded-lg hover:bg-slate-700 transition-colors">선급비용 대체</button>
        <button onclick="applyTemplate('bad_debt')" class="px-3 py-1.5 text-xs bg-slate-800 text-slate-300 rounded-lg hover:bg-slate-700 transition-colors">대손충당금</button>
        <button onclick="applyTemplate('closing_revenue')" class="px-3 py-1.5 text-xs bg-slate-800 text-slate-300 rounded-lg hover:bg-slate-700 transition-colors">결산 수익 대체</button>
    </div>
</div>

<!-- 분개 입력/수정 모달 -->
<div id="jeModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeJeModal()"></div>
    <div class="relative bg-slate-900 rounded-2xl shadow-2xl w-full max-w-lg border border-slate-800">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800">
            <h3 class="text-base font-bold text-slate-100" id="jeModalTitle">분개 추가</h3>
            <button onclick="closeJeModal()" class="text-slate-500 hover:text-slate-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <input type="hidden" id="jeEditId" value="0">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">분개 일자 <span class="text-rose-400">*</span></label>
                    <input type="date" id="jeDate" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:ring-1 focus:ring-gray-300/30">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">유형</label>
                    <select id="jeType" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:ring-1 focus:ring-gray-300/30">
                        <option value="adjusting">조정분개</option>
                        <option value="closing">결산분개</option>
                        <option value="opening">개시분개</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">적요 <span class="text-rose-400">*</span></label>
                <input type="text" id="jeDesc" placeholder="예: 12월 감가상각비 인식" maxlength="200" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:ring-1 focus:ring-gray-300/30">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">차변 (Dr) <span class="text-rose-400">*</span></label>
                    <select id="jeDebit" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:ring-1 focus:ring-gray-300/30">
                        <option value="">계정과목 선택</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">대변 (Cr) <span class="text-rose-400">*</span></label>
                    <select id="jeCredit" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:ring-1 focus:ring-gray-300/30">
                        <option value="">계정과목 선택</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">금액 <span class="text-rose-400">*</span></label>
                <input type="number" id="jeAmount" min="1" placeholder="0" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 tabular-nums text-right focus:outline-none focus:ring-1 focus:ring-gray-300/30">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">비고</label>
                <input type="text" id="jeMemo" placeholder="선택사항" maxlength="200" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:ring-1 focus:ring-gray-300/30">
            </div>
            <div id="jeErrorBox" class="hidden p-3 rounded-xl text-sm bg-rose-950/50 border border-rose-800/50 text-rose-300"></div>
        </div>
        <div class="flex gap-2 px-6 pb-5 justify-end border-t border-slate-800 pt-4">
            <button onclick="closeJeModal()" class="btn btn-secondary">취소</button>
            <button onclick="saveJournal()" id="btnSaveJe" class="px-4 py-2 text-sm text-white bg-primary rounded-lg hover:opacity-90 flex items-center gap-1.5">
                <i data-lucide="check" class="w-4 h-4"></i>저장
            </button>
        </div>
    </div>
</div>

<!-- 삭제 확인 모달 -->
<div id="jeDeleteModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeJeDeleteModal()"></div>
    <div class="relative bg-slate-900 rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center border border-slate-800">
        <i data-lucide="alert-triangle" class="w-12 h-12 text-rose-400 mx-auto mb-3"></i>
        <h3 class="text-base font-bold text-slate-100 mb-1">분개 삭제</h3>
        <p class="text-sm text-slate-400 mb-4" id="jeDeleteMsg">이 분개를 삭제하시겠습니까?</p>
        <div class="flex gap-2 justify-center">
            <button onclick="closeJeDeleteModal()" class="btn btn-secondary">취소</button>
            <button onclick="confirmDeleteJe()" id="btnDeleteJe" class="px-4 py-2 text-sm text-white bg-rose-500 rounded-lg hover:bg-rose-600">삭제</button>
        </div>
    </div>
</div>

<script>
(function() {
    var JE_API = '<?= rtrim(str_replace('\\', '/', str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', realpath(__DIR__ . '/..'))), '/') ?>/api/bankapi.php';
    var categories = <?= json_encode($categories, JSON_UNESCAPED_UNICODE) ?>;
    var journals = [];
    var pendingDeleteId = 0;

    var TYPE_LABELS = { adjusting: '조정', closing: '결산', opening: '개시' };
    var TYPE_COLORS = { adjusting: 'bg-amber-500/10 text-amber-400', closing: 'bg-purple-500/10 text-purple-400', opening: 'bg-emerald-500/10 text-emerald-400' };

    var TEMPLATES = {
        depreciation:    { desc: '감가상각비 인식',     debit: 'SGA06', credit: 'A07', type: 'adjusting' },
        accrued_expense: { desc: '미지급비용 인식',     debit: 'SGA01', credit: 'L03', type: 'adjusting' },
        prepaid:         { desc: '선급비용 대체',       debit: 'SGA01', credit: 'A05', type: 'adjusting' },
        bad_debt:        { desc: '대손충당금 설정',     debit: 'SGA07', credit: 'A06', type: 'adjusting' },
        closing_revenue: { desc: '수익 마감 대체',      debit: 'REV01', credit: 'EQ02', type: 'closing' },
    };

    function initCategorySelects() {
        var debitSel = document.getElementById('jeDebit');
        var creditSel = document.getElementById('jeCredit');
        var html = '<option value="">계정과목 선택</option>';
        var lastType = '';
        categories.forEach(function(c) {
            if (c.type !== lastType) {
                if (lastType) html += '</optgroup>';
                html += '<optgroup label="' + esc(c.type) + '">';
                lastType = c.type;
            }
            html += '<option value="' + esc(c.code) + '">' + esc(c.code) + ' ' + esc(c.name) + '</option>';
        });
        if (lastType) html += '</optgroup>';
        debitSel.innerHTML = html;
        creditSel.innerHTML = html;
    }

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    window.loadJournals = async function() {
        var year = document.getElementById('jeYear').value;
        var month = document.getElementById('jeMonth').value;
        try {
            var res = await fetch(JE_API + '?action=list_journals&year=' + year + '&month=' + month);
            var json = await res.json();
            if (json.success && json.data && json.data.journals) {
                journals = json.data.journals;
            } else {
                journals = [];
            }
        } catch (e) {
            journals = [];
        }
        renderJournals();
    };

    function renderJournals() {
        var tbody = document.getElementById('jeTableBody');
        var emptyEl = document.getElementById('jeEmptyState');

        if (journals.length === 0) {
            tbody.innerHTML = '';
            emptyEl.classList.remove('hidden');
            updateSummary(0, 0, 0);
            return;
        }
        emptyEl.classList.add('hidden');

        var totalDebit = 0, totalCredit = 0;
        var html = '';
        journals.forEach(function(je) {
            var amt = parseInt(je.amount) || 0;
            totalDebit += amt;
            totalCredit += amt;
            var typeLabel = TYPE_LABELS[je.entry_type] || je.entry_type;
            var typeColor = TYPE_COLORS[je.entry_type] || 'bg-slate-800 text-slate-400';
            html +=
                '<tr class="border-b border-slate-800 hover:bg-slate-800/50">' +
                '<td class="py-3 px-4 text-slate-300 tabular-nums whitespace-nowrap">' + esc(je.entry_date) + '</td>' +
                '<td class="py-3 px-4"><span class="px-2 py-0.5 text-[10px] font-medium rounded ' + typeColor + '">' + esc(typeLabel) + '</span></td>' +
                '<td class="py-3 px-4 text-slate-200">' + esc(je.description) + '</td>' +
                '<td class="py-3 px-4 text-slate-200 whitespace-nowrap"><span class="text-slate-500 font-mono text-xs mr-1">' + esc(je.debit_code) + '</span>' + esc(je.debit_name) + '</td>' +
                '<td class="py-3 px-4 text-slate-200 whitespace-nowrap"><span class="text-slate-500 font-mono text-xs mr-1">' + esc(je.credit_code) + '</span>' + esc(je.credit_name) + '</td>' +
                '<td class="py-3 px-4 text-right tabular-nums font-medium text-slate-100 whitespace-nowrap">' + amt.toLocaleString() + '원</td>' +
                '<td class="py-3 px-4 text-slate-400 text-xs">' + esc(je.memo || '') + '</td>' +
                '<td class="py-3 px-2 whitespace-nowrap">' +
                    '<button onclick="editJournal(' + je.id + ')" class="p-1.5 text-slate-500 hover:text-gray-900 rounded-lg hover:bg-gray-100 transition-colors" title="수정"><i data-lucide="pencil" class="w-3.5 h-3.5"></i></button>' +
                    '<button onclick="askDeleteJe(' + je.id + ')" class="p-1.5 text-slate-500 hover:text-rose-400 rounded-lg hover:bg-slate-800 transition-colors" title="삭제"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>' +
                '</td>' +
                '</tr>';
        });
        tbody.innerHTML = html;
        updateSummary(journals.length, totalDebit, totalCredit);
        if (window.lucide) lucide.createIcons();
    }

    function updateSummary(count, debit, credit) {
        document.getElementById('jeTotalCount').textContent = count + '건';
        document.getElementById('jeTotalDebit').textContent = debit.toLocaleString() + '원';
        document.getElementById('jeTotalCredit').textContent = credit.toLocaleString() + '원';
    }

    window.openJeModal = function(id) {
        document.getElementById('jeEditId').value = id || 0;
        document.getElementById('jeModalTitle').textContent = id ? '분개 수정' : '분개 추가';
        document.getElementById('jeErrorBox').classList.add('hidden');

        if (id) {
            var je = journals.find(function(j) { return parseInt(j.id) === id; });
            if (je) {
                document.getElementById('jeDate').value = je.entry_date;
                document.getElementById('jeType').value = je.entry_type;
                document.getElementById('jeDesc').value = je.description;
                document.getElementById('jeDebit').value = je.debit_code;
                document.getElementById('jeCredit').value = je.credit_code;
                document.getElementById('jeAmount').value = je.amount;
                document.getElementById('jeMemo').value = je.memo || '';
            }
        } else {
            var today = new Date();
            document.getElementById('jeDate').value = today.toISOString().slice(0, 10);
            document.getElementById('jeType').value = 'adjusting';
            document.getElementById('jeDesc').value = '';
            document.getElementById('jeDebit').value = '';
            document.getElementById('jeCredit').value = '';
            document.getElementById('jeAmount').value = '';
            document.getElementById('jeMemo').value = '';
        }

        document.getElementById('jeModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        if (window.lucide) lucide.createIcons();
    };

    window.closeJeModal = function() {
        document.getElementById('jeModal').classList.add('hidden');
        document.body.style.overflow = '';
    };

    window.editJournal = function(id) { openJeModal(id); };

    window.saveJournal = async function() {
        var errorBox = document.getElementById('jeErrorBox');
        errorBox.classList.add('hidden');

        var payload = {
            id:          parseInt(document.getElementById('jeEditId').value) || 0,
            entry_date:  document.getElementById('jeDate').value,
            entry_type:  document.getElementById('jeType').value,
            description: document.getElementById('jeDesc').value.trim(),
            debit_code:  document.getElementById('jeDebit').value,
            credit_code: document.getElementById('jeCredit').value,
            amount:      parseInt(document.getElementById('jeAmount').value) || 0,
            memo:        document.getElementById('jeMemo').value.trim()
        };

        if (!payload.entry_date || !payload.description || !payload.debit_code || !payload.credit_code || payload.amount <= 0) {
            errorBox.textContent = '일자, 적요, 차변/대변 계정, 금액을 모두 입력하세요.';
            errorBox.classList.remove('hidden');
            return;
        }
        if (payload.debit_code === payload.credit_code) {
            errorBox.textContent = '차변과 대변 계정이 같을 수 없습니다.';
            errorBox.classList.remove('hidden');
            return;
        }

        var btn = document.getElementById('btnSaveJe');
        btn.disabled = true;
        try {
            var res = await fetch(JE_API + '?action=save_journal', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            var json = await res.json();
            if (json.success) {
                closeJeModal();
                loadJournals();
            } else {
                errorBox.textContent = json.message || '저장에 실패했습니다.';
                errorBox.classList.remove('hidden');
            }
        } catch (e) {
            errorBox.textContent = '네트워크 오류가 발생했습니다.';
            errorBox.classList.remove('hidden');
        }
        btn.disabled = false;
    };

    window.askDeleteJe = function(id) {
        pendingDeleteId = id;
        document.getElementById('jeDeleteModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        if (window.lucide) lucide.createIcons();
    };

    window.closeJeDeleteModal = function() {
        document.getElementById('jeDeleteModal').classList.add('hidden');
        document.body.style.overflow = '';
        pendingDeleteId = 0;
    };

    window.confirmDeleteJe = async function() {
        if (!pendingDeleteId) return;
        var btn = document.getElementById('btnDeleteJe');
        btn.disabled = true;
        try {
            var res = await fetch(JE_API + '?action=delete_journal', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: pendingDeleteId })
            });
            var json = await res.json();
            if (json.success) {
                closeJeDeleteModal();
                loadJournals();
            } else {
                alert(json.message || '삭제에 실패했습니다.');
            }
        } catch (e) {
            alert('네트워크 오류가 발생했습니다.');
        }
        btn.disabled = false;
    };

    window.applyTemplate = function(key) {
        var tpl = TEMPLATES[key];
        if (!tpl) return;
        openJeModal();
        setTimeout(function() {
            document.getElementById('jeDesc').value = tpl.desc;
            document.getElementById('jeType').value = tpl.type;
            if (tpl.debit) document.getElementById('jeDebit').value = tpl.debit;
            if (tpl.credit) document.getElementById('jeCredit').value = tpl.credit;
            document.getElementById('jeAmount').value = '';
            document.getElementById('jeAmount').focus();
        }, 50);
    };

    initCategorySelects();
    loadJournals();
})();
</script>
