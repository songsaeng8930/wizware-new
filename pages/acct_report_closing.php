<?php
/**
 * 세무리포트 > 결산 탭
 * 상단: 결산준비 요약 (접이식 체크리스트)
 * 하단: 손익계산서 + 계정별 집계 + 마감 (acct_bank_closing.php include)
 */
$fy = intval($_GET['fy'] ?? $_GET['rpt_y'] ?? date('Y'));
$deadlineDate = ($fy + 1) . '-03-31';
$dDay = (int)((strtotime($deadlineDate) - time()) / 86400);

$pdo = getDBConnection();
$hasDB = false;

$cardTotal = 0; $cardUnsettled = 0; $cardUnchecked = 0; $cardAmount = 0;
$bankTotal = 0; $bankUnconfirmed = 0; $bankIncome = 0; $bankExpense = 0;
$invSales = 0; $invSalesTax = 0; $invSalesCnt = 0;
$invPurch = 0; $invPurchTax = 0; $invPurchCnt = 0;
$payMonths = []; $payGross = 0;
$manualChecks = [];

if ($pdo) {
    try {
        $st = $pdo->prepare('SELECT COUNT(*) as total, COALESCE(SUM(is_settled = 0), 0) as unsettled, COALESCE(SUM(compliance_status = ?), 0) as unchecked, COALESCE(SUM(amount), 0) as amt FROM card_expenses WHERE YEAR(usage_date) = ?');
        $st->execute(['미확인', $fy]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        $cardTotal = (int)$r['total']; $cardUnsettled = (int)$r['unsettled'];
        $cardUnchecked = (int)$r['unchecked']; $cardAmount = (int)$r['amt'];
        $hasDB = true;
    } catch (PDOException $e) {}

    try {
        $st = $pdo->prepare('SELECT COUNT(*) as total, COALESCE(SUM(is_confirmed = 0), 0) as unconfirmed, COALESCE(SUM(CASE WHEN tx_type = ? THEN amount ELSE 0 END), 0) as income, COALESCE(SUM(CASE WHEN tx_type = ? THEN amount ELSE 0 END), 0) as expense FROM bank_transactions WHERE YEAR(transaction_date) = ?');
        $st->execute(['입금', '출금', $fy]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        $bankTotal = (int)$r['total']; $bankUnconfirmed = (int)$r['unconfirmed'];
        $bankIncome = (int)$r['income']; $bankExpense = (int)$r['expense'];
    } catch (PDOException $e) {}

    try {
        $st = $pdo->prepare('SELECT invoice_type, COUNT(*) as cnt, COALESCE(SUM(supply_amount), 0) as supply, COALESCE(SUM(tax_amount), 0) as tax FROM tax_invoices WHERE YEAR(issue_date) = ? AND invoice_status = ? GROUP BY invoice_type');
        $st->execute([$fy, '정상']);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
            if ($r['invoice_type'] === '매출') { $invSalesCnt = (int)$r['cnt']; $invSales = (int)$r['supply']; $invSalesTax = (int)$r['tax']; }
            if ($r['invoice_type'] === '매입') { $invPurchCnt = (int)$r['cnt']; $invPurch = (int)$r['supply']; $invPurchTax = (int)$r['tax']; }
        }
    } catch (PDOException $e) {}

    try {
        $st = $pdo->prepare('SELECT DISTINCT month FROM payslips WHERE year = ?');
        $st->execute([$fy]);
        $payMonths = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
        $st2 = $pdo->prepare('SELECT COALESCE(SUM(gross_pay), 0) FROM payslips WHERE year = ?');
        $st2->execute([$fy]);
        $payGross = (int)$st2->fetchColumn();
    } catch (PDOException $e) {}

    try {
        $pdo->query('SELECT 1 FROM closing_checklist LIMIT 1');
        $st = $pdo->prepare('SELECT item_key, is_checked FROM closing_checklist WHERE fiscal_year = ?');
        $st->execute([$fy]);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $manualChecks[$r['item_key']] = (int)$r['is_checked'];
        }
    } catch (PDOException $e) {}
}

if (!$hasDB) {
    $cardTotal = 245; $cardUnsettled = 12; $cardUnchecked = 8; $cardAmount = 72000000;
    $bankTotal = 1234; $bankUnconfirmed = 23; $bankIncome = 1860000000; $bankExpense = 720000000;
    $invSalesCnt = 78; $invSales = 1860000000; $invSalesTax = 186000000;
    $invPurchCnt = 156; $invPurch = 540000000; $invPurchTax = 54000000;
    $payMonths = [1,2,3,4,5,6,7,8,9,10,11]; $payGross = 380000000;
}

$autoChecks = [
    ['key'=>'card_settled',  'label'=>'법인카드 전 건 정산 완료', 'ok'=> $cardUnsettled === 0 && $cardTotal > 0, 'detail'=>$cardUnsettled === 0 ? "총 {$cardTotal}건 전액 정산 완료" : "미정산 {$cardUnsettled}건 남음", 'link'=>'acct_card.php?tab=settle', 'icon'=>'credit-card'],
    ['key'=>'card_compliance','label'=>'법인카드 규정준수 확인', 'ok'=> $cardUnchecked === 0 && $cardTotal > 0, 'detail'=>$cardUnchecked === 0 ? "전 건 규정 확인 완료" : "미확인 {$cardUnchecked}건 남음", 'link'=>'acct_card.php?tab=expenses', 'icon'=>'shield-check'],
    ['key'=>'bank_confirmed','label'=>'통장 거래 전 건 확인 완료', 'ok'=> $bankUnconfirmed === 0 && $bankTotal > 0, 'detail'=>$bankUnconfirmed === 0 ? "총 {$bankTotal}건 전 건 확인" : "미확정 {$bankUnconfirmed}건 남음", 'link'=>'acct_bank.php?tab=history', 'icon'=>'landmark'],
    ['key'=>'invoice_collected','label'=>'세금계산서 수집 완료', 'ok'=> $invSalesCnt > 0 && $invPurchCnt > 0, 'detail'=>"매출 {$invSalesCnt}건 / 매입 {$invPurchCnt}건", 'link'=>'acct_invoice.php', 'icon'=>'file-text'],
    ['key'=>'payslip_complete','label'=>'급여대장 12개월 완성', 'ok'=> count($payMonths) >= 12, 'detail'=>count($payMonths) >= 12 ? "12개월 전부 완성" : (12 - count($payMonths)) . "개월 누락", 'link'=>'', 'icon'=>'users'],
];

$manualItems = [
    ['key'=>'insurance_paid',  'label'=>'4대보험 납부 확인',    'desc'=>'국민연금/건강보험/고용보험/산재보험 납부 완료 여부'],
    ['key'=>'fixed_assets',    'label'=>'고정자산 목록 확인',   'desc'=>'감가상각 대상 자산 목록 및 변동사항 확인'],
    ['key'=>'temp_accounts',   'label'=>'가수금/가지급금 정리', 'desc'=>'임시 계정 잔액 확인 및 정리 완료 여부'],
    ['key'=>'lease_contracts', 'label'=>'임대차 계약 확인',    'desc'=>'사무실/장비 임대 계약 현황 확인'],
    ['key'=>'etc_docs',        'label'=>'기타 결산 자료 확인',  'desc'=>'세무사 요청 기타 자료 준비 완료 여부'],
];

$autoCompleted = count(array_filter($autoChecks, fn($a) => $a['ok']));
$manualCompleted = count(array_filter($manualItems, fn($m) => !empty($manualChecks[$m['key']])));
$totalItems = count($autoChecks) + count($manualItems);
$completedItems = $autoCompleted + $manualCompleted;
$progress = $totalItems > 0 ? round($completedItems / $totalItems * 100) : 0;
$incompleteCount = $totalItems - $completedItems;

if (!function_exists('clFmt')) {
    function clFmt($v) {
        if ($v >= 100000000) return number_format($v / 100000000, 1) . '억';
        if ($v >= 10000) return number_format($v / 10000, 0) . '만';
        return number_format($v);
    }
}
if (!function_exists('clFmtWon')) {
    function clFmtWon($v) { return clFmt($v) . '원'; }
}
?>

<!-- 결산준비 요약 바 -->
<div class="bg-slate-900 border border-slate-800 rounded-xl p-4 mb-5">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <select id="fySelect" onchange="location.href='?tab=closing&fy='+this.value" class="border border-slate-800 rounded-lg px-3 py-1.5 text-sm">
                <?php for ($y = (int)date('Y'); $y >= 2023; $y--): ?>
                <option value="<?= $y ?>" <?= $y === $fy ? 'selected' : '' ?>><?= $y ?>년</option>
                <?php endfor; ?>
            </select>
            <div class="flex items-center gap-2">
                <span class="text-sm text-slate-200 font-medium">결산준비</span>
                <span class="text-sm font-bold <?= $progress >= 100 ? 'text-emerald-400' : 'text-primary' ?>"><?= $completedItems ?>/<?= $totalItems ?></span>
                <?php if ($incompleteCount > 0): ?>
                <span class="px-2 py-0.5 text-xs rounded-full bg-amber-500/10 text-amber-400 font-medium">미완료 <?= $incompleteCount ?>건</span>
                <?php else: ?>
                <span class="px-2 py-0.5 text-xs rounded-full bg-emerald-500/10 text-emerald-400 font-medium">준비 완료</span>
                <?php endif; ?>
            </div>
            <div class="w-32 h-1.5 bg-slate-800 rounded-full overflow-hidden">
                <div class="h-full <?= $progress >= 100 ? 'bg-emerald-400' : 'bg-primary' ?> rounded-full transition-all" id="progressBar" style="width: <?= $progress ?>%"></div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <?php if ($dDay > 0): ?>
            <span class="px-2 py-0.5 text-xs rounded-lg font-bold <?= $dDay <= 30 ? 'bg-amber-500/10 text-amber-400' : ($dDay <= 90 ? 'bg-amber-500/10 text-amber-500' : 'bg-slate-800 text-slate-400') ?>">
                D-<?= $dDay ?>
            </span>
            <?php elseif ($dDay === 0): ?>
            <span class="px-2 py-0.5 text-xs rounded-lg font-bold bg-amber-500 text-white">D-Day</span>
            <?php else: ?>
            <span class="px-2 py-0.5 text-xs rounded-lg font-bold bg-slate-800 text-slate-500">기한 경과</span>
            <?php endif; ?>
            <button onclick="togglePrepPanel()" id="prepToggleBtn" class="btn btn-secondary btn-xs">
                <i data-lucide="clipboard-check" class="w-3 h-3"></i>
                <span id="prepToggleText">상세 보기</span>
                <i data-lucide="chevron-down" class="w-3 h-3 transition-transform" id="prepChevron"></i>
            </button>
        </div>
    </div>
</div>

<!-- 결산준비 상세 (접이식) -->
<div id="prepDetailPanel" class="hidden mb-5 space-y-4">
    <!-- 체크리스트 -->
    <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
        <div class="flex items-center gap-2 mb-4">
            <i data-lucide="clipboard-check" class="w-4 h-4 text-primary"></i>
            <h3 class="text-sm font-bold text-slate-100">결산 점검 체크리스트</h3>
            <span class="text-sm text-slate-400">(자동 <?= count($autoChecks) ?>개 + 수동 <?= count($manualItems) ?>개)</span>
        </div>

        <div class="space-y-2.5" id="checklistArea">
            <p class="text-sm font-semibold text-slate-400 uppercase tracking-wider mt-1 mb-1">시스템 자동 확인</p>
            <?php foreach ($autoChecks as $ac): ?>
            <div class="border <?= $ac['ok'] ? 'border-slate-800' : 'border-amber-200 bg-amber-50/30' ?> rounded-xl p-3 flex items-center gap-3 transition-colors" data-check="<?= $ac['key'] ?>">
                <span class="w-7 h-7 rounded-lg <?= $ac['ok'] ? 'bg-emerald-500/10' : 'bg-amber-500/10' ?> flex items-center justify-center shrink-0">
                    <i data-lucide="<?= $ac['ok'] ? 'check-circle' : 'alert-circle' ?>" class="w-3.5 h-3.5 <?= $ac['ok'] ? 'text-emerald-400' : 'text-amber-500' ?>"></i>
                </span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-100"><?= $ac['label'] ?></p>
                    <p class="text-xs <?= $ac['ok'] ? 'text-slate-400' : 'text-amber-400' ?>"><?= $ac['detail'] ?></p>
                </div>
                <?php if ($ac['ok']): ?>
                <span class="px-2 py-0.5 text-xs rounded-full bg-emerald-500/10 text-emerald-400 font-medium shrink-0">완료</span>
                <?php else: ?>
                <span class="px-2 py-0.5 text-xs rounded-full bg-amber-500/10 text-amber-400 font-medium shrink-0">미완료</span>
                <?php if ($ac['link']): ?>
                <a href="<?= $ac['link'] ?>" class="text-xs text-primary hover:underline shrink-0">바로가기</a>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

            <p class="text-sm font-semibold text-slate-400 uppercase tracking-wider mt-4 mb-1">담당자 수동 확인</p>
            <?php foreach ($manualItems as $mi):
                $checked = !empty($manualChecks[$mi['key']]);
            ?>
            <div class="border border-slate-800 rounded-xl p-3 flex items-center gap-3 transition-colors" data-check="<?= $mi['key'] ?>">
                <label class="relative flex items-center shrink-0 cursor-pointer">
                    <input type="checkbox" <?= $checked ? 'checked' : '' ?>
                        onchange="toggleCheck('<?= $mi['key'] ?>', this.checked)"
                        class="w-4 h-4 rounded border-slate-700 text-primary focus:ring-gray-300/20 cursor-pointer">
                </label>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-100 <?= $checked ? 'line-through text-slate-500' : '' ?>"><?= $mi['label'] ?></p>
                    <p class="text-xs text-slate-400"><?= $mi['desc'] ?></p>
                </div>
                <?php if ($checked): ?>
                <span class="px-2 py-0.5 text-xs rounded-full bg-emerald-500/10 text-emerald-400 font-medium shrink-0">확인됨</span>
                <?php else: ?>
                <span class="px-2 py-0.5 text-xs rounded-full bg-slate-800 text-slate-400 font-medium shrink-0">미확인</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 세무사 자료 패키지 (간략) -->
    <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <i data-lucide="package" class="w-4 h-4 text-primary"></i>
                <h3 class="text-sm font-bold text-slate-100">세무사 자료 패키지</h3>
                <span class="text-sm text-slate-400"><?= $fy ?>년</span>
            </div>
            <button onclick="downloadClosingZip()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-primary rounded-lg hover:bg-primary-dark transition-colors">
                <i data-lucide="download" class="w-3 h-3"></i>ZIP 다운로드
            </button>
        </div>
        <div class="flex flex-wrap gap-3 text-xs text-slate-400">
            <span>카드내역 <?= number_format($cardTotal) ?>건</span>
            <span>통장내역 <?= number_format($bankTotal) ?>건</span>
            <span>세금계산서 <?= $invSalesCnt + $invPurchCnt ?>건</span>
            <span>급여대장 <?= count($payMonths) ?>개월</span>
        </div>
        <div class="border border-dashed border-slate-700 rounded-lg p-3 mt-3">
            <div id="fileList" class="space-y-1.5 mb-2"></div>
            <label class="btn btn-secondary btn-xs cursor-pointer">
                <i data-lucide="paperclip" class="w-3 h-3"></i>추가 첨부
                <input type="file" class="hidden" onchange="uploadFile(this)" multiple>
            </label>
        </div>
    </div>
</div>

<!-- 손익계산서 + 계정별 집계 + 마감 -->
<?php include __DIR__ . '/acct_bank_closing.php'; ?>

<script>
/* ── 결산준비 패널 토글 ── */
function togglePrepPanel() {
    const panel = document.getElementById('prepDetailPanel');
    const chevron = document.getElementById('prepChevron');
    const text = document.getElementById('prepToggleText');
    const isHidden = panel.classList.contains('hidden');
    panel.classList.toggle('hidden');
    chevron.style.transform = isHidden ? 'rotate(180deg)' : '';
    text.textContent = isHidden ? '접기' : '상세 보기';
    if (isHidden && typeof lucide !== 'undefined') lucide.createIcons();
}

/* ── 체크리스트 AJAX ── */
const CL_API = '<?= $basePath ?? '' ?>/api/closing.php';
const CL_FY = <?= $fy ?>;
const CL_TOTAL = <?= $totalItems ?>;
const CL_AUTO_DONE = <?= $autoCompleted ?>;
let clManualDone = <?= $manualCompleted ?>;

function toggleCheck(itemKey, checked) {
    fetch(`${CL_API}?action=toggleCheck`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({fiscal_year: CL_FY, item_key: itemKey, is_checked: checked ? 1 : 0})
    })
    .then(r => r.json())
    .then(res => {
        if (res.error) { alert(res.error); return; }
        clManualDone += checked ? 1 : -1;
        clUpdateProgress();
        clUpdateCheckUI(itemKey, checked);
    });
}

function clUpdateProgress() {
    const done = CL_AUTO_DONE + clManualDone;
    const pct = Math.round(done / CL_TOTAL * 100);
    document.getElementById('progressBar').style.width = pct + '%';
}

function clUpdateCheckUI(itemKey, checked) {
    const card = document.querySelector(`[data-check="${itemKey}"]`);
    if (!card) return;
    const label = card.querySelector('.font-medium');
    const badge = card.querySelector('.rounded-full');
    if (label) {
        if (checked) label.classList.add('line-through', 'text-slate-500');
        else label.classList.remove('line-through', 'text-slate-500');
    }
    if (badge) {
        if (checked) {
            badge.className = 'px-2 py-0.5 text-xs rounded-full bg-emerald-500/10 text-emerald-400 font-medium shrink-0';
            badge.textContent = '확인됨';
        } else {
            badge.className = 'px-2 py-0.5 text-xs rounded-full bg-slate-800 text-slate-400 font-medium shrink-0';
            badge.textContent = '미확인';
        }
    }
}

/* ── 세무사 패키지 ── */
function downloadClosingZip() {
    window.location.href = `${CL_API}?action=downloadZip&fy=${CL_FY}`;
}

function uploadFile(input) {
    Array.from(input.files).forEach(file => {
        const fd = new FormData();
        fd.append('file', file);
        fd.append('fiscal_year', CL_FY);
        fetch(`${CL_API}?action=uploadFile`, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.error) { alert(res.error); return; }
            addFileToList(res.id, res.file_name);
        });
    });
    input.value = '';
}

function addFileToList(id, name) {
    const list = document.getElementById('fileList');
    const div = document.createElement('div');
    div.className = 'flex items-center justify-between py-1.5 px-3 bg-slate-950 rounded-lg';
    div.id = `file-${id}`;
    div.innerHTML = `<div class="flex items-center gap-2">
        <i data-lucide="file" class="w-3 h-3 text-slate-500"></i>
        <span class="text-xs text-slate-200">${clEsc(name)}</span>
    </div>
    <button onclick="deleteFile(${id})" class="text-xs text-red-400 hover:text-red-300">삭제</button>`;
    list.appendChild(div);
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

async function deleteFile(id) {
    if (!(await AppUI.confirm('이 파일을 삭제할까요?'))) return;
    fetch(`${CL_API}?action=deleteFile`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id})
    })
    .then(r => r.json())
    .then(res => {
        if (res.error) { alert(res.error); return; }
        document.getElementById(`file-${id}`)?.remove();
    });
}

function clEsc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

(function() {
    fetch(`${CL_API}?action=getFiles&fy=${CL_FY}`)
    .then(r => r.json())
    .then(res => { if (res.files) res.files.forEach(f => addFileToList(f.id, f.file_name)); })
    .catch(() => {});
})();

if (window.AcctReportPeriod) AcctReportPeriod.save('year', <?= $fy ?>, 0);
</script>
