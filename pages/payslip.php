<?php
$pageTitle = '내 급여';
$currentPage = 'my_payslip';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../config/database.php';

$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
if ($year < 2020 || $year > 2099) $year = (int)date('Y');
if ($month < 1 || $month > 12) $month = (int)date('m');

$showDepartment = isOrgLevelEnabled('department');
$currentUser = getCurrentUser();
$currentEmployeeId = (int)($_SESSION['user_id'] ?? ($currentUser['id'] ?? 0));
$employee = null;
$payslip = null;

try {
    $pdo = getDBConnection();
    if ($pdo && $currentEmployeeId > 0) {
        $employeeStmt = $pdo->prepare("
            SELECT e.id, e.name, e.position, d.name AS department_name
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            WHERE e.id = ?
              AND e.is_active = 1
              AND (e.employment_status IS NULL OR e.employment_status <> '퇴사')
            LIMIT 1
        ");
        $employeeStmt->execute([$currentEmployeeId]);
        $employee = $employeeStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $payslipStmt = $pdo->prepare("
            SELECT p.*, e.position, d.name AS department_name
            FROM payslips p
            LEFT JOIN employees e ON p.employee_id = e.id
            LEFT JOIN departments d ON e.department_id = d.id
            WHERE p.year = ? AND p.month = ? AND p.employee_id = ?
            LIMIT 1
        ");
        $payslipStmt->execute([$year, $month, $currentEmployeeId]);
        $payslip = $payslipStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
} catch (Throwable $e) {
    error_log('[Payslip] personal load failed: ' . $e->getMessage());
}

$displayName = (string)($payslip['employee_name'] ?? $employee['name'] ?? $currentUser['name'] ?? '사용자');
$displayDept = (string)($payslip['department_name'] ?? $employee['department_name'] ?? '');
$displayPosition = (string)($payslip['position'] ?? $employee['position'] ?? '');
$hasPayslip = is_array($payslip);

$payData = [];
if ($hasPayslip) {
    $payData[] = [
        'id' => (int)$payslip['employee_id'],
        'name' => $displayName,
        'dept' => $displayDept,
        'position' => $displayPosition,
        'base' => (int)$payslip['base_salary'],
        'meal' => (int)$payslip['meal_allowance'],
        'car' => (int)$payslip['car_allowance'],
        'child' => (int)$payslip['child_allowance'],
        'overtime_h' => (float)$payslip['overtime_hours'],
        'overtimePay' => (int)$payslip['overtime_pay'],
        'gross' => (int)$payslip['gross_pay'],
        'nationalPension' => (int)$payslip['national_pension'],
        'healthInsurance' => (int)$payslip['health_insurance'],
        'empInsurance' => (int)$payslip['emp_insurance'],
        'incomeTax' => (int)$payslip['income_tax'],
        'totalDeduction' => (int)$payslip['total_deduction'],
        'netPay' => (int)$payslip['net_pay'],
    ];
}

$currentPay = $payData[0] ?? null;
$grossPay = $currentPay['gross'] ?? 0;
$netPay = $currentPay['netPay'] ?? 0;
$totalDeduction = $currentPay['totalDeduction'] ?? 0;
$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 mb-5">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                <div>
                    <button onclick="history.back()" class="inline-flex items-center gap-1.5 text-sm text-slate-400 hover:text-slate-200 mb-3">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i> 이전으로
                    </button>
                    <p class="text-[11px] font-semibold tracking-[0.18em] text-primary uppercase mb-2">MY PAYSLIP</p>
                    <h2 class="text-xl font-bold text-slate-100">내 급여</h2>
                    <p class="text-sm text-slate-400 mt-2"><?= $year ?>년 <?= $month ?>월 확정 급여 명세를 확인합니다.</p>
                </div>
                <div class="rounded-xl border border-slate-800 bg-slate-950/60 px-4 py-3 min-w-[220px]">
                    <p class="text-xs text-slate-500">조회 대상</p>
                    <p class="mt-1 text-lg font-bold text-slate-100"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="mt-1 text-xs text-slate-500">
                        <?= $showDepartment && $displayDept !== '' ? htmlspecialchars($displayDept, ENT_QUOTES, 'UTF-8') . ' · ' : '' ?><?= htmlspecialchars($displayPosition, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 mb-5 flex flex-col sm:flex-row sm:items-center gap-3">
            <select class="border border-slate-800 rounded-lg px-3 py-2 text-sm min-h-[44px]" aria-label="급여 조회 연도" onchange="updateYear(this.value)">
                <?php for ($y = 2024; $y <= 2026; $y++): ?>
                <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?>년</option>
                <?php endfor; ?>
            </select>
            <select class="border border-slate-800 rounded-lg px-3 py-2 text-sm min-h-[44px]" aria-label="급여 조회 월" onchange="updateMonth(this.value)">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>><?= $m ?>월</option>
                <?php endfor; ?>
            </select>
            <span class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs <?= $hasPayslip ? 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-400' : 'bg-amber-500/10 border border-amber-500/20 text-amber-400' ?>">
                <i data-lucide="<?= $hasPayslip ? 'check-circle-2' : 'alert-circle' ?>" class="w-3.5 h-3.5"></i><?= $hasPayslip ? '확정됨' : '미확정' ?>
            </span>
            <?php if ($hasPayslip): ?>
            <button onclick="openPayslipModal(<?= (int)$currentPay['id'] ?>)" class="sm:ml-auto inline-flex items-center justify-center gap-1.5 min-h-[44px] px-4 py-2 text-sm bg-primary text-white rounded-lg hover:bg-primary-dark">
                <i data-lucide="receipt-text" class="w-4 h-4"></i>명세서 보기
            </button>
            <?php endif; ?>
        </div>

        <?php if ($hasPayslip): ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
                <p class="text-sm text-slate-400 mb-1">총 지급액</p>
                <p class="text-xl font-bold text-slate-100"><?= number_format($grossPay) ?><span class="text-sm font-normal text-slate-500 ml-1">원</span></p>
            </div>
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
                <p class="text-sm text-slate-400 mb-1">총 공제액</p>
                <p class="text-xl font-bold text-amber-500"><?= number_format($totalDeduction) ?><span class="text-sm font-normal text-slate-500 ml-1">원</span></p>
            </div>
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
                <p class="text-sm text-slate-400 mb-1">실수령액</p>
                <p class="text-xl font-bold text-amber-700"><?= number_format($netPay) ?><span class="text-sm font-normal text-slate-500 ml-1">원</span></p>
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                <div>
                    <h3 class="text-sm font-semibold text-slate-200"><?= $year ?>년 <?= $month ?>월 급여 내역</h3>
                    <p class="text-xs text-slate-500 mt-1">금액 단위: 원</p>
                </div>
            </div>
            <div class="payslip-table-wrap overflow-x-auto">
                <table id="payslipTable" class="w-full text-sm emp-table">
                    <thead>
                        <tr class="border-b-2 border-slate-800">
                            <th class="py-3 px-3 text-left font-medium text-slate-300">이름</th>
                            <th class="py-3 px-3 text-center font-medium text-slate-300"><?= $showDepartment ? htmlspecialchars(getOrgLabel('department'), ENT_QUOTES, 'UTF-8') . '/직위' : '직위' ?></th>
                            <th class="py-3 px-3 text-right font-medium text-slate-300">기본급</th>
                            <th class="py-3 px-3 text-right font-medium text-slate-300 whitespace-nowrap">초과근무(h)</th>
                            <th class="py-3 px-3 text-right font-medium text-slate-300">초과수당</th>
                            <th class="py-3 px-3 text-right font-medium text-slate-300">총 지급액</th>
                            <th class="py-3 px-3 text-right font-medium text-slate-300">총 공제액</th>
                            <th class="py-3 px-3 text-right font-medium text-slate-300 whitespace-nowrap">실수령액</th>
                            <th class="py-3 px-3 text-center font-medium text-slate-300">명세서</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-slate-800 hover:bg-slate-950">
                            <td class="py-3 px-3 font-medium text-slate-100"><?= htmlspecialchars($currentPay['name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="py-3 px-3 text-center text-slate-400 text-sm">
                                <?= $showDepartment && $currentPay['dept'] !== '' ? htmlspecialchars($currentPay['dept'], ENT_QUOTES, 'UTF-8') . ' · ' : '' ?><?= htmlspecialchars($currentPay['position'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="py-3 px-3 text-right text-slate-300 tabular-nums"><?= number_format($currentPay['base']) ?></td>
                            <td class="py-3 px-3 text-right tabular-nums <?= $currentPay['overtime_h'] > 0 ? 'text-amber-500 font-medium' : 'text-slate-500' ?>"><?= $currentPay['overtime_h'] > 0 ? htmlspecialchars((string)$currentPay['overtime_h'], ENT_QUOTES, 'UTF-8') . 'h' : '-' ?></td>
                            <td class="py-3 px-3 text-right tabular-nums <?= $currentPay['overtimePay'] > 0 ? 'text-amber-500' : 'text-slate-500' ?>"><?= $currentPay['overtimePay'] > 0 ? number_format($currentPay['overtimePay']) : '-' ?></td>
                            <td class="py-3 px-3 text-right font-medium text-slate-100 tabular-nums"><?= number_format($currentPay['gross']) ?></td>
                            <td class="py-3 px-3 text-right text-amber-500 tabular-nums"><?= number_format($currentPay['totalDeduction']) ?></td>
                            <td class="py-3 px-3 text-right font-bold text-amber-700 tabular-nums"><?= number_format($currentPay['netPay']) ?></td>
                            <td class="py-3 px-3 text-center">
                                <button onclick="openPayslipModal(<?= (int)$currentPay['id'] ?>)" class="min-h-[44px] px-3 py-1 text-sm bg-primary/10 text-primary rounded-lg hover:bg-primary/20 transition-colors">
                                    보기
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="payslip-mobile-list">
                <article class="payslip-mobile-card" data-pay-card="<?= (int)$currentPay['id'] ?>">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h4 class="text-sm font-semibold text-slate-100"><?= htmlspecialchars($currentPay['name'], ENT_QUOTES, 'UTF-8') ?></h4>
                            <p class="text-xs text-slate-500 mt-1">
                                <?= $showDepartment && $currentPay['dept'] !== '' ? htmlspecialchars($currentPay['dept'], ENT_QUOTES, 'UTF-8') . ' · ' : '' ?><?= htmlspecialchars($currentPay['position'], ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        </div>
                        <button onclick="openPayslipModal(<?= (int)$currentPay['id'] ?>)" class="shrink-0 min-h-[44px] px-3 py-1.5 text-xs bg-primary/10 text-primary rounded-lg hover:bg-primary/20 transition-colors">명세 보기</button>
                    </div>
                    <div class="grid grid-cols-2 gap-3 mt-4 text-xs">
                        <div>
                            <p class="text-slate-500">총 지급액</p>
                            <p class="mt-1 text-slate-100 font-semibold tabular-nums"><?= number_format($currentPay['gross']) ?></p>
                        </div>
                        <div>
                            <p class="text-slate-500">총 공제액</p>
                            <p class="mt-1 text-amber-500 font-semibold tabular-nums"><?= number_format($currentPay['totalDeduction']) ?></p>
                        </div>
                        <div>
                            <p class="text-slate-500">초과근무</p>
                            <p class="mt-1 tabular-nums <?= $currentPay['overtime_h'] > 0 ? 'text-amber-500' : 'text-slate-500' ?>"><?= $currentPay['overtime_h'] > 0 ? htmlspecialchars((string)$currentPay['overtime_h'], ENT_QUOTES, 'UTF-8') . 'h' : '-' ?></p>
                        </div>
                        <div>
                            <p class="text-slate-500">실수령액</p>
                            <p class="mt-1 text-amber-700 font-bold tabular-nums"><?= number_format($currentPay['netPay']) ?></p>
                        </div>
                    </div>
                </article>
            </div>
        </div>
        <?php else: ?>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-8 text-center">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-slate-950 text-slate-400">
                <i data-lucide="receipt-text" class="w-6 h-6"></i>
            </div>
            <h3 class="text-base font-bold text-slate-100">확정된 급여 명세가 없습니다</h3>
            <p class="text-sm text-slate-400 mt-2"><?= $year ?>년 <?= $month ?>월 급여 명세가 확정되면 이 화면에서 확인할 수 있습니다.</p>
        </div>
        <?php endif; ?>
    </main>
</div>

<style>
.payslip-mobile-list { display: none; }
.payslip-mobile-card { border: 1px solid var(--zm-border); border-radius: 14px; background: var(--zm-surface-1); padding: 14px; }
@media (max-width: 767px) {
    .payslip-table-wrap { display: none; }
    .payslip-mobile-list { display: grid; gap: 12px; }
}
</style>

<?php if ($hasPayslip): ?>
<div id="payslipModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closePayslipModal()"></div>
    <div class="relative bg-slate-900 rounded-2xl shadow-2xl w-full max-w-lg">
        <div class="flex items-center justify-between px-6 py-4">
            <h3 class="text-base font-bold text-slate-100" id="psModalTitle">급여 명세서</h3>
            <button onclick="closePayslipModal()" class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center text-slate-500 hover:text-slate-300" aria-label="급여 명세서 닫기">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="px-6 pb-6" id="psContent"></div>
        <div class="flex gap-2 px-6 pb-5 justify-end">
            <button onclick="window.print()" class="btn btn-secondary min-h-[44px]">
                <i data-lucide="printer" class="w-4 h-4"></i>인쇄
            </button>
            <button onclick="closePayslipModal()" class="min-h-[44px] px-4 py-2 text-sm text-white bg-primary rounded-lg hover:opacity-90">닫기</button>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
const payData = <?= json_encode($payData, $jsonFlags) ?>;
const YEAR = <?= $year ?>;
const MONTH = <?= $month ?>;

function updateYear(y) { location.href = `?year=${y}&month=${MONTH}`; }
function updateMonth(m) { location.href = `?year=${YEAR}&month=${m}`; }

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatWon(value) {
    return Number(value || 0).toLocaleString();
}

function openPayslipModal(id) {
    const p = payData.find(e => e.id === id);
    if (!p) return;

    const personMeta = [p.dept, p.position].filter(Boolean).map(escapeHtml).join(' · ');
    document.getElementById('psModalTitle').textContent = `${p.name} 급여명세서 · ${YEAR}년 ${MONTH}월`;
    document.getElementById('psContent').innerHTML = `
        <div class="border border-slate-800 rounded-xl overflow-hidden text-sm">
            <div class="bg-primary/5 px-4 py-3 flex justify-between gap-4">
                <div>
                    <p class="font-bold text-slate-100 text-base">${escapeHtml(p.name)} <span class="text-sm font-normal text-slate-400">${personMeta}</span></p>
                    <p class="text-sm text-slate-500 mt-0.5">지급년월: ${YEAR}년 ${MONTH}월</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-slate-500">실수령액</p>
                    <p class="text-xl font-bold text-amber-700">${formatWon(p.netPay)}원</p>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2">
                <div class="p-4">
                    <p class="text-sm font-semibold text-slate-400 mb-2">지급 항목</p>
                    ${payRow('기본급', p.base)}
                    ${p.overtimePay > 0 ? payRow(`초과수당 (${p.overtime_h}h)`, p.overtimePay, 'text-amber-500') : ''}
                    ${p.meal > 0 ? payRow('식대', p.meal) : ''}
                    ${p.car > 0 ? payRow('차량지원', p.car) : ''}
                    ${p.child > 0 ? payRow('육아수당', p.child) : ''}
                    <div class="mt-3 rounded-lg bg-slate-950 px-3 py-2 flex justify-between font-semibold">
                        <span>총 지급액</span><span class="tabular-nums">${formatWon(p.gross)}</span>
                    </div>
                </div>
                <div class="p-4">
                    <p class="text-sm font-semibold text-slate-400 mb-2">공제 항목</p>
                    ${payRow('국민연금 (4.5%)', p.nationalPension)}
                    ${payRow('건강보험 (3.545%)', p.healthInsurance)}
                    ${payRow('고용보험 (0.9%)', p.empInsurance)}
                    ${payRow('소득세 (간이)', p.incomeTax)}
                    <div class="mt-3 rounded-lg bg-slate-950 px-3 py-2 flex justify-between font-semibold text-amber-500">
                        <span>총 공제액</span><span class="tabular-nums">${formatWon(p.totalDeduction)}</span>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.getElementById('payslipModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    if (window.lucide) lucide.createIcons();
}

function payRow(label, amount, cls = 'text-slate-300') {
    return `<div class="flex justify-between py-1"><span class="text-slate-400">${escapeHtml(label)}</span><span class="tabular-nums ${cls}">${formatWon(amount)}</span></div>`;
}

function closePayslipModal() {
    const modal = document.getElementById('payslipModal');
    if (!modal) return;
    modal.classList.add('hidden');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closePayslipModal(); });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
