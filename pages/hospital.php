<?php
$pageTitle = '병원 전용';
$currentPage = 'hospital';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/hr_codes.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$leaveTypeOptions = getCommonCodeOptions('attendance', '휴가유형');
if ($leaveTypeOptions === '') {
    $leaveTypeOptions = '<option>연차</option><option>반차(오전)</option><option>반차(오후)</option><option>병가</option><option>경조사</option><option>공가</option>';
}

$tab = $_GET['tab'] ?? 'dashboard';
$tabs = [
    'dashboard' => ['label' => '운영 대시보드', 'icon' => 'activity'],
    'shifts' => ['label' => '근무표', 'icon' => 'calendar-clock'],
    'checks' => ['label' => '일일점검', 'icon' => 'clipboard-check'],
    'closing' => ['label' => '수납마감', 'icon' => 'receipt-text'],
    'assets' => ['label' => '재고/장비', 'icon' => 'package-check'],
    'training' => ['label' => '교육/자격', 'icon' => 'badge-check'],
];
if (!isset($tabs[$tab])) $tab = 'dashboard';

$hospitalEmployeeOptions = [];
try {
    $pdo = getDBConnection();
    if ($pdo) {
        $hospitalEmployeeOptions = $pdo->query("
            SELECT DISTINCT name FROM (
                SELECT name FROM employees WHERE is_active = 1
                UNION SELECT employee_name AS name FROM hospital_shift_slots
                UNION SELECT employee_name AS name FROM hospital_leave_requests
                UNION SELECT substitute_name AS name FROM hospital_leave_requests WHERE substitute_name IS NOT NULL AND substitute_name <> ''
            ) names
            WHERE name IS NOT NULL AND name <> ''
            ORDER BY name
            LIMIT 50
        ")->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (Throwable $e) {
    $hospitalEmployeeOptions = [];
}
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
<main class="hospital-page p-6 min-h-screen bg-slate-950">
    <div class="hospital-hero flex flex-wrap items-center justify-between gap-4 mb-5">
        <div>
            <p class="hospital-eyebrow">SMALL CLINIC OPERATIONS</p>
            <h2 class="text-xl font-bold text-slate-100">병원 전용 운영관리</h2>
            <p class="text-sm text-slate-400 mt-1">10명 내외 병원의 근무, 점검, 수납마감, 재고/장비, 교육/자격을 한 곳에서 관리합니다.</p>
        </div>
        <div class="hospital-print-hide flex items-center gap-2">
            <input type="date" id="workDate" value="<?= date('Y-m-d') ?>" class="bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100">
            <button type="button" onclick="printHospital()" class="hospital-secondary"><i data-lucide="printer" class="w-4 h-4"></i>출력</button>
        </div>
    </div>

    <div class="zm-tab-container mb-5">
        <?php foreach ($tabs as $key => $info): ?>
        <a href="?tab=<?= $key ?>" class="zm-tab <?= $tab === $key ? 'zm-tab-active' : '' ?>">
            <i data-lucide="<?= $info['icon'] ?>" class="w-4 h-4"></i><?= $info['label'] ?>
        </a>
        <?php endforeach; ?>
    </div>

    <section id="hospitalApp" data-tab="<?= htmlspecialchars($tab) ?>">
        <?php if ($tab === 'dashboard'): ?>
        <div class="hospital-kpi-grid mb-5" id="kpiCards"></div>

        <div class="hospital-dash-main mb-5">
            <div class="hospital-panel p-5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="text-base font-bold text-slate-100">수납·내원 추이</h3>
                        <p class="text-xs text-slate-500 mt-1">최근 14일 수납 금액(결제수단별)과 내원 환자수</p>
                    </div>
                    <div class="hospital-legend" id="revenueLegend"></div>
                </div>
                <div id="revenueChart" class="mt-4"></div>
            </div>
            <div class="hospital-panel p-5">
                <h3 class="text-base font-bold text-slate-100">운영 알림</h3>
                <p class="text-xs text-slate-500 mt-1">조치가 필요한 항목 · 클릭하면 해당 탭으로 이동합니다.</p>
                <div id="alertList" class="mt-3 space-y-2"></div>
            </div>
        </div>

        <div class="hospital-dash-tri mb-5">
            <div class="hospital-panel p-5">
                <h3 class="text-base font-bold text-slate-100">결제수단 구성</h3>
                <p class="text-xs text-slate-500 mt-1">최근 14일 수납 누적</p>
                <div id="paymentDonut" class="mt-2"></div>
            </div>
            <div class="hospital-panel p-5">
                <h3 class="text-base font-bold text-slate-100">오늘 근무</h3>
                <p class="text-xs text-slate-500 mt-1">시간순 근무 배치</p>
                <div id="dashShifts" class="mt-3 space-y-2 text-sm"></div>
            </div>
            <div class="hospital-panel p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-100">오늘 점검</h3>
                        <p class="text-xs text-slate-500 mt-1">오픈/마감 체크리스트</p>
                    </div>
                    <span id="checkProgressBadge"></span>
                </div>
                <div id="checkProgressBar" class="mt-3"></div>
                <div id="dashChecks" class="mt-3 space-y-2 text-sm"></div>
            </div>
        </div>

        <div class="hospital-dash-tri">
            <div class="hospital-panel p-5">
                <h3 class="text-base font-bold text-slate-100">부족 재고</h3>
                <p class="text-xs text-slate-500 mt-1">최소 재고 이하 품목</p>
                <div id="lowStockList" class="mt-3 space-y-2 text-sm"></div>
            </div>
            <div class="hospital-panel p-5">
                <h3 class="text-base font-bold text-slate-100">장비 점검 예정</h3>
                <p class="text-xs text-slate-500 mt-1">30일 이내 점검일 도래</p>
                <div id="dueAssetList" class="mt-3 space-y-2 text-sm"></div>
            </div>
            <div class="hospital-panel p-5">
                <h3 class="text-base font-bold text-slate-100">교육·자격 만료 임박</h3>
                <p class="text-xs text-slate-500 mt-1">30일 이내 만료 · 갱신 필요</p>
                <div id="dueCredList" class="mt-3 space-y-2 text-sm"></div>
            </div>
        </div>
        <?php elseif ($tab === 'shifts'): ?>
        <datalist id="hospitalEmployeeOptions">
            <?php foreach ($hospitalEmployeeOptions as $employeeName): ?>
            <option value="<?= htmlspecialchars($employeeName, ENT_QUOTES) ?>"></option>
            <?php endforeach; ?>
        </datalist>
        <div class="hospital-shift-layout">
            <div class="col-span-4 space-y-5">
            <form id="shiftForm" class="hospital-panel p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-100">근무 등록/수정</h3>
                    <button type="button" onclick="resetShiftForm()" class="hospital-text-btn hospital-print-hide">초기화</button>
                </div>
                <input type="hidden" name="id">
                <label class="hospital-field">근무일<input type="date" name="slot_date" class="hospital-input" required></label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="hospital-field">구분<select name="shift_type" class="hospital-input"><option>오전</option><option>오후</option><option>종일</option><option>야간</option><option>휴무</option></select></label>
                    <label class="hospital-field">상태<select name="status" class="hospital-input"><option>확정</option><option>임시</option><option>변경필요</option><option>취소</option></select></label>
                </div>
                <label class="hospital-field">역할<input name="role_name" list="hospitalRoleOptions" placeholder="예: 원무/간호" class="hospital-input" required></label>
                <datalist id="hospitalRoleOptions">
                    <option value="원무"></option><option value="접수"></option><option value="간호"></option><option value="진료지원"></option><option value="청구/수납"></option>
                </datalist>
                <label class="hospital-field">직원<input name="employee_name" list="hospitalEmployeeOptions" placeholder="직원명" class="hospital-input" required></label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="hospital-field">시작<input type="time" name="start_time" class="hospital-input" required></label>
                    <label class="hospital-field">종료<input type="time" name="end_time" class="hospital-input" required></label>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" onclick="presetShift('morning')" class="hospital-chip">오전</button>
                    <button type="button" onclick="presetShift('afternoon')" class="hospital-chip">오후</button>
                    <button type="button" onclick="presetShift('day')" class="hospital-chip">종일</button>
                </div>
                <input name="note" placeholder="비고" class="hospital-input">
                <button class="hospital-primary w-full">저장</button>
            </form>

            <form id="bulkShiftForm" class="hospital-panel p-5 space-y-3">
                <h3 class="text-sm font-bold text-slate-100">반복 근무 빠른 등록</h3>
                <p class="text-xs text-slate-500">선택한 요일에 같은 역할/직원/시간을 한 번에 등록합니다.</p>
                <div class="grid grid-cols-7 gap-1" id="weekdayPicker">
                    <label class="hospital-day"><input type="checkbox" value="1">월</label>
                    <label class="hospital-day"><input type="checkbox" value="2">화</label>
                    <label class="hospital-day"><input type="checkbox" value="3">수</label>
                    <label class="hospital-day"><input type="checkbox" value="4">목</label>
                    <label class="hospital-day"><input type="checkbox" value="5">금</label>
                    <label class="hospital-day"><input type="checkbox" value="6">토</label>
                    <label class="hospital-day"><input type="checkbox" value="0">일</label>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <input name="role_name" list="hospitalRoleOptions" placeholder="역할" class="hospital-input" required>
                    <input name="employee_name" list="hospitalEmployeeOptions" placeholder="직원" class="hospital-input" required>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <select name="shift_type" class="hospital-input"><option>오전</option><option>오후</option><option>종일</option><option>야간</option></select>
                    <input type="time" name="start_time" class="hospital-input" value="09:00" required>
                    <input type="time" name="end_time" class="hospital-input" value="13:00" required>
                </div>
                <button class="hospital-secondary w-full">선택 요일에 등록</button>
            </form>
            </div>

            <div class="col-span-8 space-y-5">
            <div class="hospital-panel overflow-hidden">
                <div class="p-4 border-b border-slate-800 flex flex-wrap justify-between items-center gap-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-100">주간 근무표</h3>
                        <p id="weekRangeLabel" class="text-xs text-slate-500 mt-1">휴가/대체근무와 인원부족 경고를 함께 확인합니다.</p>
                    </div>
                    <div class="hospital-print-hide flex items-center gap-2">
                        <button type="button" onclick="moveWeek(-1)" class="hospital-icon-btn" aria-label="이전 주"><i data-lucide="chevron-left" class="w-4 h-4"></i></button>
                        <button type="button" onclick="setToday()" class="hospital-secondary">오늘</button>
                        <button type="button" onclick="moveWeek(1)" class="hospital-icon-btn" aria-label="다음 주"><i data-lucide="chevron-right" class="w-4 h-4"></i></button>
                    </div>
                </div>
                <div id="staffingWarnings" class="p-4 border-b border-slate-800 grid grid-cols-1 gap-2"></div>
                <div id="shiftWeekGrid" class="hospital-week-grid"></div>
            </div>

            <div class="hospital-panel overflow-hidden">
                <div class="p-4 border-b border-slate-800 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-100">근무 상세 목록</h3>
                    <span class="text-xs text-slate-500">카드를 누르면 수정할 수 있습니다.</span>
                </div>
                <div id="shiftList" class="divide-y divide-slate-800"></div>
            </div>

            <div class="hospital-form-list-grid">
            <form id="leaveForm" class="hospital-panel p-5 space-y-3">
                <h3 class="text-sm font-bold text-slate-100">휴가/대체근무 신청</h3>
                <input type="hidden" name="id">
                <input name="employee_name" list="hospitalEmployeeOptions" placeholder="휴가 직원명" class="hospital-input" required>
                <select name="leave_type" class="hospital-input"><?= $leaveTypeOptions ?></select>
                <div class="grid grid-cols-2 gap-2">
                    <input type="date" name="start_date" class="hospital-input" required>
                    <input type="date" name="end_date" class="hospital-input" required>
                </div>
                <input name="substitute_name" list="hospitalEmployeeOptions" placeholder="대체근무자" class="hospital-input">
                <input name="reason" placeholder="사유" class="hospital-input">
                <select name="status" class="hospital-input"><option>신청</option><option>승인</option><option>반려</option></select>
                <button class="hospital-primary w-full">신청 저장</button>
            </form>
            <div class="col-span-2 hospital-panel overflow-hidden">
                <div class="p-4 border-b border-slate-800"><h3 class="text-sm font-bold text-slate-100">휴가/대체근무 현황</h3></div>
                <div id="leaveList" class="divide-y divide-slate-800"></div>
            </div>
            </div>
            </div>
        </div>
        <?php elseif ($tab === 'checks'): ?>
        <div class="hospital-panel overflow-hidden">
            <div class="p-4 border-b border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-100">일일 오픈/마감 체크리스트</h3>
                    <p class="text-xs text-slate-500 mt-1">환자 개인정보 없이 병원 운영 점검만 기록합니다.</p>
                </div>
                <button onclick="seedChecks()" class="hospital-secondary">기본 항목 생성</button>
            </div>
            <div id="checkList" class="divide-y divide-slate-800"></div>
        </div>
        <?php elseif ($tab === 'closing'): ?>
        <div class="hospital-closing-layout">
        <form id="closingForm" class="hospital-panel p-5 space-y-4">
            <h3 class="text-sm font-bold text-slate-100">원무 수납 마감</h3>
            <input type="date" name="closing_date" class="hospital-input">
            <div class="grid grid-cols-3 gap-3">
                <label class="hospital-field">현금<input type="number" name="cash_amount" class="hospital-input" min="0"></label>
                <label class="hospital-field">카드<input type="number" name="card_amount" class="hospital-input" min="0"></label>
                <label class="hospital-field">계좌이체<input type="number" name="transfer_amount" class="hospital-input" min="0"></label>
                <label class="hospital-field">환불<input type="number" name="refund_amount" class="hospital-input" min="0"></label>
                <label class="hospital-field">미수<input type="number" name="unpaid_amount" class="hospital-input" min="0"></label>
                <label class="hospital-field">내원 수<input type="number" name="patient_count" class="hospital-input" min="0"></label>
            </div>
            <div class="hospital-mini-row text-sm"><span class="text-slate-400">실수납 합계 (현금+카드+이체-환불)</span><span id="closingTotalPreview" class="font-bold text-slate-100">0원</span></div>
            <textarea name="memo" class="hospital-input" rows="3" placeholder="취소/환불/미수 특이사항"></textarea>
            <select name="status" class="hospital-input"><option>작성중</option><option>마감완료</option><option>승인완료</option></select>
            <div class="flex gap-2">
                <button class="hospital-primary">마감 저장</button>
                <button type="button" onclick="syncClosing()" class="hospital-secondary">재무에 반영</button>
            </div>
            <p id="closingSyncMsg" class="text-sm text-slate-500"></p>
        </form>
        <div class="hospital-panel overflow-hidden">
            <div class="p-4">
                <h3 class="text-sm font-bold text-slate-100">최근 마감 이력</h3>
                <p class="text-xs text-slate-500 mt-1">날짜를 누르면 해당 일자 마감을 불러옵니다.</p>
            </div>
            <div id="closingHistory" class="divide-y divide-slate-800"></div>
        </div>
        </div>
        <?php elseif ($tab === 'assets'): ?>
        <div class="hospital-form-list-grid">
            <form id="assetForm" class="hospital-panel p-5 space-y-3">
                <h3 class="text-sm font-bold text-slate-100">재고/장비 등록</h3>
                <input type="hidden" name="id">
                <select name="asset_type" class="hospital-input"><option>재고</option><option>장비</option></select>
                <input name="name" placeholder="품목/장비명" class="hospital-input" required>
                <input name="category" placeholder="분류" class="hospital-input">
                <div class="grid grid-cols-3 gap-2">
                    <input type="number" name="current_qty" placeholder="현재" class="hospital-input">
                    <input type="number" name="min_qty" placeholder="최소" class="hospital-input">
                    <input name="unit" placeholder="단위" class="hospital-input">
                </div>
                <input type="date" name="expire_date" class="hospital-input" title="유통기한">
                <input type="date" name="next_due_date" class="hospital-input" title="다음 점검일">
                <input name="location" placeholder="위치" class="hospital-input">
                <input name="vendor" placeholder="거래처/AS 업체" class="hospital-input">
                <select name="status" class="hospital-input"><option>정상</option><option>부족</option><option>점검예정</option><option>고장</option></select>
                <button class="hospital-primary w-full">저장</button>
            </form>
            <div class="col-span-2 hospital-panel overflow-hidden">
                <div class="p-4 border-b border-slate-800"><h3 class="text-sm font-bold text-slate-100">재고/장비 목록</h3></div>
                <div id="assetList" class="divide-y divide-slate-800"></div>
            </div>
        </div>
        <div class="hospital-form-list-grid mt-5">
            <form id="purchaseForm" class="hospital-panel p-5 space-y-3">
                <h3 class="text-sm font-bold text-slate-100">발주 요청</h3>
                <input type="hidden" name="id">
                <input type="hidden" name="asset_id">
                <input name="item_name" placeholder="발주 품목" class="hospital-input" required>
                <div class="grid grid-cols-2 gap-2">
                    <input type="number" name="requested_qty" placeholder="수량" class="hospital-input" min="1" value="1">
                    <input name="unit" placeholder="단위" class="hospital-input">
                </div>
                <input name="vendor" placeholder="거래처" class="hospital-input">
                <input name="reason" placeholder="사유" class="hospital-input">
                <select name="status" class="hospital-input"><option>요청</option><option>승인</option><option>발주완료</option><option>취소</option></select>
                <button class="hospital-primary w-full">요청 저장</button>
            </form>
            <div class="col-span-2 hospital-panel overflow-hidden">
                <div class="p-4 border-b border-slate-800"><h3 class="text-sm font-bold text-slate-100">발주 요청 현황</h3></div>
                <div id="purchaseList" class="divide-y divide-slate-800"></div>
            </div>
        </div>
        <?php else: ?>
        <div class="hospital-form-list-grid">
            <form id="credentialForm" class="hospital-panel p-5 space-y-3">
                <h3 class="text-sm font-bold text-slate-100">교육/자격 등록</h3>
                <input type="hidden" name="id">
                <input name="employee_name" placeholder="직원명" class="hospital-input" required>
                <select name="credential_type" class="hospital-input"><option>법정교육</option><option>면허/자격</option><option>내부교육</option></select>
                <input name="credential_name" placeholder="교육/자격명" class="hospital-input" required>
                <input type="date" name="issue_date" class="hospital-input">
                <input type="date" name="expire_date" class="hospital-input">
                <select name="status" class="hospital-input"><option>유효</option><option>만료예정</option><option>만료</option><option>미이수</option></select>
                <input name="memo" placeholder="비고" class="hospital-input">
                <button class="hospital-primary w-full">저장</button>
            </form>
            <div class="col-span-2 hospital-panel overflow-hidden">
                <div class="p-4 border-b border-slate-800"><h3 class="text-sm font-bold text-slate-100">직원 교육/자격 현황</h3></div>
                <div id="credentialList" class="divide-y divide-slate-800"></div>
            </div>
        </div>
        <?php endif; ?>
    </section>
</main>
</div>

<style>
.hospital-hero{background:linear-gradient(135deg,rgba(0,0,0,0.04),var(--zm-surface-2));border:1px solid rgba(0,0,0,0.12);border-radius:1rem;padding:1.25rem 1.35rem}
.hospital-eyebrow{font-size:.68rem;letter-spacing:.08em;color:var(--zm-primary-fg);font-weight:800;margin-bottom:.35rem}
.hospital-panel{background:var(--zm-surface-1);border:1px solid var(--zm-border);border-radius:.75rem}
.hospital-page{overflow-x:hidden}
.hospital-kpi-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem}
.hospital-kpi-card{background:var(--zm-surface-1);border:1px solid var(--zm-border);border-radius:.9rem;padding:1.15rem;box-shadow:var(--zm-card-shadow)}
.hospital-kpi-card .kpi-icon{display:flex;width:2.25rem;height:2.25rem;align-items:center;justify-content:center;border-radius:.65rem;background:rgba(0,0,0,0.08);color:var(--zm-primary-fg)}
.hospital-kpi-card.warn .kpi-icon{background:rgba(245,158,11,.14);color:var(--zm-status-warn-fg)}
.hospital-kpi-card.danger .kpi-icon{background:rgba(239,68,68,.14);color:var(--zm-status-danger-fg)}
.hospital-kpi-card.ok .kpi-icon{background:rgba(16,185,129,.14);color:var(--zm-status-ok-fg)}
.hospital-two-col{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1.25rem}
.hospital-dash-main{display:grid;grid-template-columns:minmax(0,2.1fr) minmax(0,1fr);gap:1.25rem}
.hospital-dash-tri{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1.25rem}
.hospital-legend{display:flex;flex-wrap:wrap;align-items:center;gap:.75rem;font-size:.75rem;color:var(--zm-text-muted)}
.hospital-legend .dot{display:inline-block;width:.6rem;height:.6rem;border-radius:999px;margin-right:.3rem;vertical-align:-1px}
.hospital-chart svg{display:block;width:100%;height:auto}
.hospital-alert-row{display:flex;align-items:center;justify-content:space-between;gap:.6rem;border:1px solid var(--zm-border);border-radius:.6rem;padding:.6rem .75rem;font-size:.85rem;color:var(--zm-text-default);background:var(--zm-surface-2)}
.hospital-alert-row:hover{border-color:rgba(0,0,0,0.12)}
.hospital-alert-count{display:inline-flex;align-items:center;justify-content:center;min-width:1.5rem;height:1.5rem;padding:0 .4rem;border-radius:999px;font-size:.75rem;font-weight:700}
.hospital-progress{height:.5rem;border-radius:999px;background:var(--zm-surface-3);overflow:hidden}
.hospital-progress > span{display:block;height:100%;border-radius:999px;background:var(--zm-primary)}
.hospital-mini-row{display:flex;align-items:center;justify-content:space-between;gap:.6rem;border:1px solid var(--zm-border);border-radius:.6rem;padding:.6rem .75rem;background:var(--zm-surface-2)}
.hospital-closing-layout{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(0,1fr);gap:1.25rem;align-items:start}
.hospital-form-list-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1.25rem}
.hospital-shift-layout{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:1.25rem}
.hospital-input{width:100%;border:1px solid var(--zm-border);background:var(--zm-surface-1);border-radius:.5rem;padding:.55rem .75rem;font-size:.875rem;color:var(--zm-text-strong)}
.hospital-input:focus{outline:2px solid rgba(0,0,0,0.04);outline-offset:0}
.hospital-primary{display:inline-flex;align-items:center;justify-content:center;border-radius:.5rem;background:var(--zm-primary);color:white;padding:.55rem 1rem;font-size:.875rem;font-weight:600}
.hospital-primary:hover{filter:brightness(1.06)}
.hospital-secondary{display:inline-flex;align-items:center;justify-content:center;gap:.35rem;border:1px solid var(--zm-border);border-radius:.5rem;color:var(--zm-text-default);padding:.5rem .85rem;font-size:.875rem}
.hospital-secondary:hover,.hospital-icon-btn:hover,.hospital-chip:hover{background:var(--zm-surface-2)}
.hospital-danger{color:rgb(251 146 60);font-size:.875rem}
.hospital-danger:hover{color:rgb(253 186 116)}
.hospital-icon-btn{display:inline-flex;align-items:center;justify-content:center;width:2.25rem;height:2.25rem;border:1px solid var(--zm-border);border-radius:.5rem;color:var(--zm-text-default)}
.hospital-text-btn{font-size:.75rem;color:var(--zm-text-muted)}
.hospital-text-btn:hover{color:var(--zm-text-strong)}
.hospital-chip{display:inline-flex;align-items:center;justify-content:center;border:1px solid var(--zm-border);border-radius:.5rem;color:var(--zm-text-default);padding:.45rem .5rem;font-size:.75rem}
.hospital-day{display:flex;flex-direction:column;align-items:center;gap:.25rem;border:1px solid var(--zm-border);border-radius:.5rem;padding:.45rem .2rem;font-size:.75rem;color:var(--zm-text-default);cursor:pointer}
.hospital-day:has(input:checked){background:var(--zm-surface-3);border-color:var(--zm-text-strong);color:var(--zm-text-strong)}
.hospital-field{display:block;font-size:.75rem;color:var(--zm-text-muted)}
.hospital-week-grid{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));min-height:380px}
.hospital-day-col{min-height:380px;border-right:1px solid var(--zm-border)}
.hospital-day-col:last-child{border-right:0}
.hospital-day-head{display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--zm-border);padding:.75rem .75rem;font-size:.75rem;color:var(--zm-text-muted)}
.hospital-day-body{padding:.75rem;display:flex;flex-direction:column;gap:.5rem}
.hospital-shift-card{border:1px solid var(--zm-border);border-radius:.625rem;padding:.65rem;background:var(--zm-surface-2);cursor:pointer}
.hospital-shift-card:hover{border-color:rgba(0,0,0,0.12)}
.hospital-status{display:inline-flex;align-items:center;border-radius:999px;padding:.1rem .45rem;font-size:.68rem;background:rgba(0,0,0,0.08);color:var(--zm-primary-fg)}
.hospital-status-ok{background:rgba(16,185,129,.15);color:var(--zm-status-ok-fg)}
.hospital-status-warn{background:rgba(245,158,11,.16);color:var(--zm-status-warn-fg)}
.hospital-status-danger{background:rgba(239,68,68,.16);color:var(--zm-status-danger-fg)}
.hospital-status-muted{background:rgba(100,116,139,.22);color:var(--zm-text-default)}
.hospital-empty{display:flex;min-height:8rem;flex-direction:column;align-items:center;justify-content:center;gap:.55rem;padding:2rem;text-align:center;color:var(--zm-text-subtle)}
.hospital-empty svg{width:1.75rem;height:1.75rem;color:var(--zm-text-subtle)}
@media (max-width: 1180px){
    #mainContent{margin-left:0}
    .hospital-kpi-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    .hospital-dash-main{grid-template-columns:1fr}
    .hospital-dash-tri{grid-template-columns:1fr}
    .hospital-closing-layout{grid-template-columns:1fr}
    .hospital-two-col{grid-template-columns:1fr}
    .hospital-form-list-grid{grid-template-columns:1fr}
    .hospital-form-list-grid > *{grid-column:auto!important}
    .hospital-shift-layout{grid-template-columns:1fr}
    .hospital-shift-layout > *{grid-column:auto!important}
    .hospital-week-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    .hospital-day-col{border-bottom:1px solid var(--zm-border)}
}
@media (max-width: 760px){
    .hospital-page{padding:1rem}
    .hospital-hero{padding:1rem}
    .zm-tab-container{max-width:100%;overflow-x:auto;flex-wrap:nowrap}
    .hospital-kpi-grid{grid-template-columns:1fr}
    .hospital-week-grid{grid-template-columns:1fr}
}
@media print{
    body{background:white!important;color:#111827!important}
    #sidebar,.navbar,.zm-tab-container,.hospital-print-hide{display:none!important}
    #mainContent{margin:0!important}
    .hospital-page{padding:0!important;background:white!important}
    .hospital-panel,.hospital-shift-card{break-inside:avoid;background:white!important;border-color:#d1d5db!important;color:#111827!important}
    .text-slate-100,.text-slate-400,.text-slate-500,.hospital-field{color:#111827!important}
    .hospital-week-grid{grid-template-columns:repeat(7,minmax(0,1fr))!important}
    .hospital-day-col{min-height:auto!important;border-color:#d1d5db!important}
    .hospital-input,.hospital-primary,.hospital-secondary,.hospital-icon-btn,.hospital-chip,.hospital-danger{display:none!important}
}
</style>

<script>
const HOSPITAL_API = '<?= $basePath ?>/api/hospital.php';
const HOSPITAL_PRINT = '<?= $basePath ?>/pages/hospital_print.php';
const tab = document.getElementById('hospitalApp').dataset.tab;
const workDate = document.getElementById('workDate');
let currentShifts = [];
let currentLeaves = [];
let currentAssets = [];
let currentPurchases = [];
let currentCredentials = [];

async function api(action, body, qs = '') {
    const opt = body === undefined ? {} : { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(body) };
    const res = await fetch(`${HOSPITAL_API}?action=${action}${qs}`, opt);
    const json = await res.json();
    if (!res.ok || json.error) throw new Error(json.error || '요청 실패');
    return json;
}
function won(v){ return Number(v||0).toLocaleString('ko-KR') + '원'; }
function row(html){ return `<div class="p-4">${html}</div>`; }
function empty(text, icon = 'inbox'){ return `<div class="hospital-empty text-sm"><i data-lucide="${icon}"></i><span>${esc(text)}</span></div>`; }
function formData(form){ return Object.fromEntries(new FormData(form).entries()); }
function fillForm(form, data){ Object.entries(data).forEach(([key, value]) => { if (form.elements[key]) form.elements[key].value = value ?? ''; }); }
function esc(v){ return String(v ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
function statusClass(status){
    if (['확정','승인','승인완료','마감완료','정상','유효','완료','발주완료'].includes(status)) return 'hospital-status hospital-status-ok';
    if (['신청','요청','임시','작성중','점검예정','만료예정','변경필요','부족'].includes(status)) return 'hospital-status hospital-status-warn';
    if (['반려','취소','고장','만료','미이수'].includes(status)) return 'hospital-status hospital-status-danger';
    return 'hospital-status hospital-status-muted';
}
function printHospital(){ window.open(`${HOSPITAL_PRINT}?type=${encodeURIComponent(tab)}&date=${encodeURIComponent(workDate.value)}`, '_blank'); }
// 로컬 타임존 기준 YYYY-MM-DD · toISOString()은 UTC 변환으로 KST에서 하루 밀리므로 사용 금지
function isoDate(date){ return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`; }
function getWeekRange(){
    const date = new Date(`${workDate.value}T00:00:00`);
    const day = date.getDay();
    const monday = new Date(date);
    monday.setDate(date.getDate() - (day === 0 ? 6 : day - 1));
    const sunday = new Date(monday);
    sunday.setDate(monday.getDate() + 6);
    return {from: isoDate(monday), to: isoDate(sunday), monday, sunday};
}
function daysInWeek(monday){
    return Array.from({length: 7}, (_, i) => {
        const d = new Date(monday);
        d.setDate(monday.getDate() + i);
        return d;
    });
}
function showToast(message, type = 'info'){
    let toast = document.getElementById('hospitalToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'hospitalToast';
        toast.className = 'fixed right-6 bottom-6 z-50 rounded-lg border px-4 py-3 text-sm shadow-xl';
        document.body.appendChild(toast);
    }
    toast.className = `fixed right-6 bottom-6 z-50 rounded-lg border px-4 py-3 text-sm shadow-xl ${type === 'error' ? 'border-red-900 bg-red-950 text-red-100' : 'border-slate-700 bg-slate-900 text-slate-100'}`;
    toast.textContent = message;
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => toast.remove(), 2800);
}
async function guarded(fn){
    try { await fn(); } catch (e) { showToast(e.message || '처리 중 오류가 발생했습니다.', 'error'); }
}

// ───────── 운영 대시보드 ─────────
const CHART_COLORS = { cash: '#10b981', card: '#4F6AFF', transfer: '#06b6d4', patient: '#f59e0b' };

function fmtMan(v){ // 축 라벨용 · 만원 단위 축약
    if (v >= 100000000) return (v / 100000000).toFixed(1).replace(/\.0$/, '') + '억';
    if (v >= 10000) return Math.round(v / 10000).toLocaleString('ko-KR') + '만';
    return v.toLocaleString('ko-KR');
}
function dday(dateStr){
    const diff = Math.ceil((new Date(`${dateStr}T00:00:00`) - new Date(new Date().toDateString())) / 86400000);
    return diff < 0 ? `D+${-diff}` : diff === 0 ? 'D-DAY' : `D-${diff}`;
}

// 14일 수납 스택바 + 내원 환자수 라인 (SVG 직접 렌더링 · 외부 라이브러리 없음)
function renderRevenueChart(el, rows, baseDate){
    if (!rows.length) { el.innerHTML = empty('최근 14일 수납 마감 데이터가 없습니다.', 'bar-chart-3'); refreshIcons(); return; }
    const W = 640, H = 240, padL = 48, padR = 16, padT = 14, padB = 28;
    const plotW = W - padL - padR, plotH = H - padT - padB;
    // 기준일 포함 14일 슬롯을 모두 채움 · 마감 없는 날은 0으로 표시해 날짜 축을 균등하게 유지
    const byDate = Object.fromEntries(rows.map(r => [r.closing_date, r]));
    const end = new Date(`${baseDate}T00:00:00`);
    const days = Array.from({length: 14}, (_, i) => {
        const d = new Date(end);
        d.setDate(end.getDate() - 13 + i);
        const date = isoDate(d);
        const r = byDate[date] || {cash_amount: 0, card_amount: 0, transfer_amount: 0, patient_count: 0};
        return {
            date,
            cash: +r.cash_amount, card: +r.card_amount, transfer: +r.transfer_amount,
            total: +r.cash_amount + +r.card_amount + +r.transfer_amount,
            patients: +r.patient_count,
        };
    });
    const maxY = Math.max(...days.map(d => d.total), 1) * 1.15;
    const maxP = Math.max(...days.map(d => d.patients), 1) * 1.25;
    const slotW = plotW / 14;
    const barW = Math.min(slotW * 0.55, 26);
    const x = i => padL + slotW * i + slotW / 2;
    const y = v => padT + plotH * (1 - v / maxY);
    const yP = v => padT + plotH * (1 - v / maxP);

    let svg = `<svg viewBox="0 0 ${W} ${H}" role="img" aria-label="최근 14일 수납 및 내원 추이">`;
    // y축 그리드 4단
    for (let g = 0; g <= 3; g++) {
        const gy = padT + plotH * g / 3;
        const val = maxY * (1 - g / 3);
        svg += `<line x1="${padL}" y1="${gy}" x2="${W - padR}" y2="${gy}" stroke="var(--zm-border)" stroke-width="1" stroke-dasharray="${g === 3 ? '0' : '3 4'}"/>`;
        svg += `<text x="${padL - 8}" y="${gy + 4}" text-anchor="end" font-size="10" fill="var(--zm-text-subtle)">${g === 3 ? '0' : fmtMan(val)}</text>`;
    }
    days.forEach((d, i) => {
        const bx = x(i) - barW / 2;
        let cy = y(0);
        [['cash', d.cash], ['card', d.card], ['transfer', d.transfer]].forEach(([key, v]) => {
            if (v <= 0) return;
            const h = plotH * v / maxY;
            cy -= h;
            svg += `<rect x="${bx}" y="${cy}" width="${barW}" height="${h}" rx="2" fill="${CHART_COLORS[key]}"><title>${d.date} ${key === 'cash' ? '현금' : key === 'card' ? '카드' : '이체'} ${won(v)}</title></rect>`;
        });
        if (i % 2 === (days.length - 1) % 2) {
            svg += `<text x="${x(i)}" y="${H - 8}" text-anchor="middle" font-size="10" fill="var(--zm-text-muted)">${d.date.slice(5).replace('-', '/')}</text>`;
        }
    });
    // 내원 환자수 라인 (보조 스케일)
    const pts = days.map((d, i) => `${x(i)},${yP(d.patients)}`).join(' ');
    svg += `<polyline points="${pts}" fill="none" stroke="${CHART_COLORS.patient}" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>`;
    days.forEach((d, i) => {
        svg += `<circle cx="${x(i)}" cy="${yP(d.patients)}" r="3" fill="${CHART_COLORS.patient}"><title>${d.date} 내원 ${d.patients}명</title></circle>`;
    });
    svg += '</svg>';
    el.innerHTML = svg;
    el.classList.add('hospital-chart');
}

// 결제수단 구성 도넛
function renderPaymentDonut(el, rows){
    const sum = key => rows.reduce((acc, r) => acc + +r[`${key}_amount`], 0);
    const segs = [
        ['카드', sum('card'), CHART_COLORS.card],
        ['현금', sum('cash'), CHART_COLORS.cash],
        ['계좌이체', sum('transfer'), CHART_COLORS.transfer],
    ].filter(s => s[1] > 0);
    const total = segs.reduce((acc, s) => acc + s[1], 0);
    if (!total) { el.innerHTML = empty('수납 데이터가 없습니다.', 'pie-chart'); refreshIcons(); return; }
    const R = 52, C = 2 * Math.PI * R;
    let offset = 0;
    let svg = `<svg viewBox="0 0 240 150" role="img" aria-label="결제수단 구성"><g transform="translate(75,75)">`;
    segs.forEach(([label, v, color]) => {
        const frac = v / total;
        svg += `<circle r="${R}" fill="none" stroke="${color}" stroke-width="22" stroke-dasharray="${C * frac} ${C * (1 - frac)}" stroke-dashoffset="${-C * offset}" transform="rotate(-90)"><title>${label} ${won(v)} (${Math.round(frac * 100)}%)</title></circle>`;
        offset += frac;
    });
    svg += `<text text-anchor="middle" y="-2" font-size="13" font-weight="700" fill="var(--zm-text-strong)">${fmtMan(total)}원</text>`;
    svg += `<text text-anchor="middle" y="14" font-size="9" fill="var(--zm-text-subtle)">14일 누적</text></g>`;
    segs.forEach(([label, v, color], i) => {
        const ly = 45 + i * 22;
        svg += `<circle cx="158" cy="${ly - 4}" r="4" fill="${color}"/>`;
        svg += `<text x="168" y="${ly}" font-size="11" fill="var(--zm-text-default)">${label} ${Math.round(v / total * 100)}%</text>`;
    });
    svg += '</svg>';
    el.innerHTML = svg;
    el.classList.add('hospital-chart');
}

async function loadDashboard(){
    const data = await api('dashboard', undefined, `&date=${workDate.value}`);
    const k = data.kpi;
    const checkPct = k.open_checks_total > 0 ? Math.round(k.open_checks_done / k.open_checks_total * 100) : 0;

    document.getElementById('kpiCards').innerHTML = [
        ['오늘 근무', `${k.shift_count}명`, 'calendar-clock', k.staffing_warning_count > 0 ? 'danger' : 'ok', k.staffing_warning_count > 0 ? `인력 경고 ${k.staffing_warning_count}건` : '인력 경고 없음'],
        ['점검 진행률', k.open_checks_total > 0 ? `${checkPct}%` : '-', 'clipboard-check', k.open_checks_total > 0 && checkPct >= 100 ? 'ok' : 'warn', `완료 ${k.open_checks_done} / 전체 ${k.open_checks_total}`],
        ['오늘 수납', won(k.closing_total), 'receipt-text', k.closing_total > 0 ? 'ok' : '', k.unpaid_amount > 0 ? `미수 ${won(k.unpaid_amount)}` : '미수 없음'],
        ['내원 환자', `${k.patient_count}명`, 'users', k.patient_count > 0 ? 'ok' : '', '수납 마감 기준'],
    ].map(c => `<div class="hospital-kpi-card ${c[3]}"><div class="flex items-start justify-between gap-3"><div><span class="text-sm text-slate-400">${c[0]}</span><div class="mt-2 text-2xl font-bold text-slate-100">${c[1]}</div><p class="mt-2 text-xs text-slate-500">${c[4]}</p></div><span class="kpi-icon"><i data-lucide="${c[2]}" class="w-4 h-4"></i></span></div></div>`).join('');

    // 수납·내원 차트 + 범례
    renderRevenueChart(document.getElementById('revenueChart'), data.closings, workDate.value);
    document.getElementById('revenueLegend').innerHTML =
        `<span><span class="dot" style="background:${CHART_COLORS.cash}"></span>현금</span>` +
        `<span><span class="dot" style="background:${CHART_COLORS.card}"></span>카드</span>` +
        `<span><span class="dot" style="background:${CHART_COLORS.transfer}"></span>이체</span>` +
        `<span><span class="dot" style="background:${CHART_COLORS.patient}"></span>내원 환자</span>`;

    // 운영 알림
    const alerts = [
        ['triangle-alert', '인력 부족/휴가 충돌', k.staffing_warning_count, 'danger', 'shifts'],
        ['package-x', '부족 재고', k.low_stock_count, 'danger', 'assets'],
        ['wrench', '장비 점검 예정', k.asset_due_count, 'warn', 'assets'],
        ['badge-alert', '교육/자격 만료 임박', k.credential_due_count, 'warn', 'training'],
        ['calendar-x', '휴가 승인 대기', k.pending_leave_count, 'warn', 'shifts'],
        ['shopping-cart', '발주 처리 대기', k.pending_purchase_count, 'warn', 'assets'],
    ].filter(a => a[2] > 0);
    document.getElementById('alertList').innerHTML = alerts.length ? alerts.map(a =>
        `<a href="?tab=${a[4]}" class="hospital-alert-row"><span class="flex items-center gap-2"><i data-lucide="${a[0]}" class="w-4 h-4 ${a[3] === 'danger' ? 'text-red-400' : 'text-amber-400'}"></i>${a[1]}</span><span class="hospital-alert-count ${a[3] === 'danger' ? 'bg-red-950/60 text-red-200' : 'bg-amber-950/60 text-amber-200'}">${a[2]}</span></a>`
    ).join('') : `<div class="hospital-empty text-sm"><i data-lucide="circle-check-big"></i><span>모든 운영 지표가 정상입니다.</span></div>`;

    // 결제수단 도넛
    renderPaymentDonut(document.getElementById('paymentDonut'), data.closings);

    // 오늘 근무 / 오늘 점검
    document.getElementById('dashShifts').innerHTML = data.today_shifts.length ? data.today_shifts.map(s =>
        `<div class="hospital-mini-row"><span class="min-w-0 truncate"><span class="font-semibold text-slate-100">${esc(s.employee_name)}</span> <span class="text-slate-400">${esc(s.role_name)}</span></span><span class="flex items-center gap-2 flex-shrink-0"><span class="${statusClass(s.status || '확정')}">${esc(s.shift_type)}</span><span class="text-xs text-slate-500">${esc((s.start_time || '').slice(0,5))}-${esc((s.end_time || '').slice(0,5))}</span></span></div>`
    ).join('') : empty('오늘 근무가 없습니다.', 'calendar-x');

    document.getElementById('checkProgressBadge').innerHTML = k.open_checks_total > 0 ? `<span class="${checkPct >= 100 ? 'hospital-status hospital-status-ok' : 'hospital-status hospital-status-warn'}">${k.open_checks_done}/${k.open_checks_total} 완료</span>` : '';
    document.getElementById('checkProgressBar').innerHTML = k.open_checks_total > 0 ? `<div class="hospital-progress"><span style="width:${checkPct}%;background:${checkPct >= 100 ? '#10b981' : 'var(--zm-primary)'}"></span></div>` : '';
    document.getElementById('dashChecks').innerHTML = data.today_checks.length ? data.today_checks.map(c =>
        `<div class="hospital-mini-row"><span class="min-w-0 truncate">${esc(c.item_name)} <span class="text-xs text-slate-500">${esc(c.shift_type)} · ${esc(c.category)}</span></span><span class="${statusClass(c.is_done == 1 ? '완료' : '대기')} flex-shrink-0">${c.is_done == 1 ? '완료' : '대기'}</span></div>`
    ).join('') : empty('점검 항목이 없습니다. 일일점검 탭에서 기본 항목을 생성하세요.', 'clipboard-list');

    // 주의 항목 3종
    document.getElementById('lowStockList').innerHTML = data.low_stock.length ? data.low_stock.map(a =>
        `<div class="hospital-mini-row"><span class="min-w-0 truncate"><span class="font-semibold text-slate-100">${esc(a.name)}</span> <span class="text-xs text-slate-500">${esc(a.category || '')}</span></span><span class="hospital-status hospital-status-danger flex-shrink-0">${esc(a.current_qty)}/${esc(a.min_qty)}${esc(a.unit || '')}</span></div>`
    ).join('') : empty('부족 재고가 없습니다.', 'package-check');
    document.getElementById('dueAssetList').innerHTML = data.due_assets.length ? data.due_assets.map(a =>
        `<div class="hospital-mini-row"><span class="min-w-0 truncate"><span class="font-semibold text-slate-100">${esc(a.name)}</span> <span class="text-xs text-slate-500">${esc(a.vendor || '')}</span></span><span class="hospital-status hospital-status-warn flex-shrink-0">${dday(a.next_due_date)} · ${esc(a.next_due_date)}</span></div>`
    ).join('') : empty('점검 예정 장비가 없습니다.', 'wrench');
    document.getElementById('dueCredList').innerHTML = data.due_credentials.length ? data.due_credentials.map(c =>
        `<div class="hospital-mini-row"><span class="min-w-0 truncate"><span class="font-semibold text-slate-100">${esc(c.employee_name)}</span> ${esc(c.credential_name)}</span><span class="hospital-status hospital-status-warn flex-shrink-0">${dday(c.expire_date)}</span></div>`
    ).join('') : empty('만료 임박 교육/자격이 없습니다.', 'badge-check');

    refreshIcons();
}
async function loadShifts(){
    const {from, to, monday, sunday} = getWeekRange();
    const [data, warnings, leaves] = await Promise.all([
        api('shifts', undefined, `&from=${from}&to=${to}`),
        api('staffingWarnings', undefined, `&from=${from}&to=${to}`),
        api('leaveRequests', undefined, `&from=${from}&to=${to}`)
    ]);
    currentShifts = data.shifts;
    currentLeaves = leaves.requests;
    document.getElementById('weekRangeLabel').textContent = `${from} ~ ${to} · 휴가/대체근무와 인원부족 경고를 함께 확인합니다.`;
    document.getElementById('staffingWarnings').innerHTML = warnings.warnings.length ? warnings.warnings.slice(0, 10).map(w => `<div class="text-sm rounded-lg border ${w.level === 'danger' ? 'border-red-900/60 bg-red-950/30 text-red-100' : 'border-amber-900/50 bg-amber-950/30 text-amber-100'} px-3 py-2"><span class="font-medium">${esc(w.date)}</span> ${esc(w.message)}</div>`).join('') : '<div class="text-sm rounded-lg border border-emerald-900/40 bg-emerald-950/20 px-3 py-2 text-emerald-100">이번 주 인원부족/휴가 경고가 없습니다.</div>';
    renderShiftWeek(data.shifts, leaves.requests, monday);
    document.getElementById('shiftList').innerHTML = data.shifts.length ? data.shifts.map(s => row(`<div class="flex items-center justify-between gap-4"><button type="button" onclick="editShift(${s.id})" class="text-left flex-1"><p class="font-medium text-slate-100">${esc(s.slot_date)} ${esc(s.shift_type)} · ${esc(s.employee_name)} <span class="${statusClass(s.status || '확정')}">${esc(s.status || '확정')}</span></p><p class="text-sm text-slate-500">${esc(s.role_name)} · ${esc((s.start_time || '').slice(0,5))}-${esc((s.end_time || '').slice(0,5))} · ${esc(s.note || '')}</p></button><button onclick="deleteShift(${s.id})" class="hospital-danger hospital-print-hide">삭제</button></div>`)).join('') : empty('등록된 근무가 없습니다.', 'calendar-plus');
    document.getElementById('leaveList').innerHTML = leaves.requests.length ? leaves.requests.map(l => row(`<div class="flex justify-between gap-4"><button type="button" onclick="editLeave(${l.id})" class="text-left flex-1"><p class="font-medium text-slate-100">${esc(l.employee_name)} · ${esc(l.leave_type)} <span class="${statusClass(l.status)}">${esc(l.status)}</span></p><p class="text-sm text-slate-500">${esc(l.start_date)} ~ ${esc(l.end_date)} · 대체 ${esc(l.substitute_name || '미지정')} · ${esc(l.reason || '')}</p></button><button onclick="deleteLeave(${l.id})" class="hospital-danger hospital-print-hide">삭제</button></div>`)).join('') : empty('휴가/대체근무 신청이 없습니다.', 'calendar-x');
    refreshIcons();
}
function renderShiftWeek(shifts, leaves, monday){
    const byDate = shifts.reduce((acc, shift) => {
        (acc[shift.slot_date] ||= []).push(shift);
        return acc;
    }, {});
    const weekDays = ['월','화','수','목','금','토','일'];
    document.getElementById('shiftWeekGrid').innerHTML = daysInWeek(monday).map((day, idx) => {
        const date = isoDate(day);
        const dayLeaves = leaves.filter(l => l.start_date <= date && l.end_date >= date);
        const cards = (byDate[date] || []).map(s => `<button type="button" onclick="editShift(${s.id})" class="hospital-shift-card text-left">
            <div class="flex items-center justify-between gap-2"><span class="text-sm font-semibold text-slate-100">${esc(s.employee_name)}</span><span class="${statusClass(s.status || '확정')}">${esc(s.shift_type)}</span></div>
            <div class="mt-1 text-xs text-slate-400">${esc(s.role_name)} · ${esc((s.start_time || '').slice(0,5))}-${esc((s.end_time || '').slice(0,5))}</div>
            ${s.note ? `<div class="mt-1 text-xs text-slate-500">${esc(s.note)}</div>` : ''}
        </button>`).join('');
        const leaveBadges = dayLeaves.map(l => `<div class="rounded-md border border-amber-900/50 bg-amber-950/30 px-2 py-1 text-xs text-amber-100">${esc(l.employee_name)} 휴가 · 대체 ${esc(l.substitute_name || '미지정')}</div>`).join('');
        return `<div class="hospital-day-col">
            <div class="hospital-day-head"><span>${weekDays[idx]}</span><button type="button" onclick="pickShiftDate('${date}')" class="text-slate-300">${date.slice(5)}</button></div>
            <div class="hospital-day-body">${cards || '<div class="rounded-md border border-dashed border-slate-800 px-2 py-4 text-center text-xs text-slate-600">근무 없음</div>'}${leaveBadges}</div>
        </div>`;
    }).join('');
}
function editShift(id){
    const shift = currentShifts.find(item => Number(item.id) === Number(id));
    if (!shift) return;
    const form = document.getElementById('shiftForm');
    fillForm(form, shift);
    form.start_time.value = (shift.start_time || '').slice(0,5);
    form.end_time.value = (shift.end_time || '').slice(0,5);
    form.scrollIntoView({behavior: 'smooth', block: 'start'});
}
function editLeave(id){
    const leave = currentLeaves.find(item => Number(item.id) === Number(id));
    if (!leave) return;
    const form = document.getElementById('leaveForm');
    fillForm(form, leave);
    form.scrollIntoView({behavior: 'smooth', block: 'start'});
}
function resetShiftForm(){
    const form = document.getElementById('shiftForm');
    form.reset();
    form.id.value = '';
    form.slot_date.value = workDate.value;
    form.status.value = '확정';
    presetShift('morning');
}
function presetShift(type){
    const form = document.getElementById('shiftForm');
    const presets = {
        morning: ['오전', '09:00', '13:00'],
        afternoon: ['오후', '14:00', '18:00'],
        day: ['종일', '09:00', '18:00'],
    };
    const preset = presets[type] || presets.morning;
    form.shift_type.value = preset[0];
    form.start_time.value = preset[1];
    form.end_time.value = preset[2];
}
function pickShiftDate(date){
    workDate.value = date;
    document.getElementById('shiftForm').slot_date.value = date;
}
function moveWeek(offset){
    const date = new Date(`${workDate.value}T00:00:00`);
    date.setDate(date.getDate() + offset * 7);
    workDate.value = isoDate(date);
    loadShifts();
}
function setToday(){
    workDate.value = isoDate(new Date());
    document.getElementById('shiftForm').slot_date.value = workDate.value;
    loadShifts();
}
async function deleteShift(id){
    if (!(await AppUI.confirm('이 근무를 삭제할까요?'))) return;
    await guarded(async () => { await api('deleteShift', {id}); await loadShifts(); showToast('근무가 삭제되었습니다.'); });
}
async function deleteLeave(id){
    if (!(await AppUI.confirm('이 휴가 신청을 삭제할까요?'))) return;
    await guarded(async () => { await api('deleteLeaveRequest', {id}); await loadShifts(); showToast('휴가 신청이 삭제되었습니다.'); });
}
async function loadChecks(){
    const data = await api('checks', undefined, `&date=${workDate.value}`);
    document.getElementById('checkList').innerHTML = data.checks.length ? data.checks.map(c => row(`<label class="flex items-center justify-between gap-4"><span><span class="${statusClass(c.is_done == 1 ? '완료' : '대기')}">${c.is_done == 1 ? '완료' : '대기'}</span> <span class="text-xs text-slate-500">${esc(c.shift_type)} · ${esc(c.category)}</span><br>${esc(c.item_name)}<br><span class="text-xs text-slate-500">${esc(c.note || '')}</span></span><input type="checkbox" ${c.is_done == 1 ? 'checked' : ''} onchange="toggleCheck(${c.id}, this.checked)" class="hospital-print-hide w-5 h-5"></label>`)).join('') : empty('점검 항목이 없습니다. 기본 항목 생성을 눌러주세요.', 'clipboard-plus');
}
async function seedChecks(){ await api('seedDailyChecks', {date: workDate.value}); loadChecks(); }
async function toggleCheck(id, done){ await api('toggleCheck', {id, is_done: done ? 1 : 0}); loadChecks(); }
async function loadClosing(){
    const [data, history] = await Promise.all([
        api('closing', undefined, `&date=${workDate.value}`),
        api('closings', undefined, '&limit=14'),
    ]);
    const f = document.getElementById('closingForm'); f.closing_date.value = workDate.value;
    ['cash_amount','card_amount','transfer_amount','refund_amount','unpaid_amount','patient_count','memo'].forEach(k => { if (f.elements[k]) f.elements[k].value = ''; });
    if (f.elements.status) f.elements.status.value = '작성중';
    if (data.closing) Object.entries(data.closing).forEach(([k,v]) => { if (f.elements[k]) f.elements[k].value = v ?? ''; });
    updateClosingPreview();
    const msg = document.getElementById('closingSyncMsg');
    if (msg) msg.textContent = data.closing?.bank_transaction_id ? `재무 반영 완료: 거래 ID ${data.closing.bank_transaction_id}` : '';
    const hist = document.getElementById('closingHistory');
    if (hist) {
        hist.innerHTML = history.closings.length ? history.closings.map(c => {
            const total = (+c.cash_amount) + (+c.card_amount) + (+c.transfer_amount) - (+c.refund_amount);
            return row(`<button type="button" onclick="pickClosingDate('${esc(c.closing_date)}')" class="w-full text-left flex items-center justify-between gap-3">
                <span><span class="font-medium text-slate-100">${esc(c.closing_date)}</span> <span class="${statusClass(c.status)}">${esc(c.status)}</span></span>
                <span class="text-right"><span class="font-semibold text-slate-100">${won(total)}</span><br><span class="text-xs text-slate-500">내원 ${esc(c.patient_count)}명${+c.unpaid_amount > 0 ? ` · 미수 ${won(c.unpaid_amount)}` : ''}</span></span>
            </button>`);
        }).join('') : empty('마감 이력이 없습니다.', 'receipt-text');
        refreshIcons();
    }
}
function pickClosingDate(date){ workDate.value = date; loadClosing(); }
function updateClosingPreview(){
    const f = document.getElementById('closingForm');
    if (!f) return;
    const num = name => Number(f.elements[name]?.value || 0);
    const total = num('cash_amount') + num('card_amount') + num('transfer_amount') - num('refund_amount');
    const previewEl = document.getElementById('closingTotalPreview');
    if (previewEl) previewEl.textContent = won(total);
}
async function syncClosing(){
    const f = document.getElementById('closingForm');
    const data = await api('syncClosing', {closing_date: f.closing_date.value || workDate.value});
    document.getElementById('closingSyncMsg').textContent = `재무 반영 완료: 거래 ID ${data.bank_transaction_id}`;
    loadClosing();
}
async function loadAssets(){
    const [data, purchases] = await Promise.all([api('assets'), api('purchaseRequests')]);
    currentAssets = data.assets;
    currentPurchases = purchases.requests;
    document.getElementById('assetList').innerHTML = data.assets.length ? data.assets.map(a => row(`<div class="flex justify-between gap-4"><button type="button" onclick="editAsset(${a.id})" class="text-left flex-1"><p class="font-medium text-slate-100">${esc(a.asset_type)} · ${esc(a.name)} <span class="${statusClass(a.status)}">${esc(a.status)}</span></p><p class="text-sm text-slate-500">${esc(a.category || '-')} · 재고 ${esc(a.current_qty ?? '-')} / 최소 ${esc(a.min_qty ?? '-')} ${esc(a.unit || '')} · 위치 ${esc(a.location || '-')}</p><p class="text-xs text-slate-500">유통기한 ${esc(a.expire_date || '-')} · 점검일 ${esc(a.next_due_date || '-')}</p></button><button onclick="deleteAsset(${a.id})" class="hospital-danger hospital-print-hide">삭제</button></div>`)).join('') : empty('등록된 재고/장비가 없습니다.', 'package-plus');
    document.getElementById('purchaseList').innerHTML = purchases.requests.length ? purchases.requests.map(p => row(`<div class="flex justify-between gap-4"><button type="button" onclick="editPurchase(${p.id})" class="text-left flex-1"><p class="font-medium text-slate-100">${esc(p.item_name)} ${esc(p.requested_qty)}${esc(p.unit || '')} <span class="${statusClass(p.status)}">${esc(p.status)}</span></p><p class="text-sm text-slate-500">요청자 ${esc(p.requester_name || '-')} · 거래처 ${esc(p.vendor || '-')} · ${esc(p.reason || '')}</p></button><button onclick="deletePurchase(${p.id})" class="hospital-danger hospital-print-hide">삭제</button></div>`)).join('') : empty('발주 요청이 없습니다.', 'shopping-cart');
}
function editAsset(id){
    const asset = currentAssets.find(item => Number(item.id) === Number(id));
    if (!asset) return;
    const form = document.getElementById('assetForm');
    fillForm(form, asset);
    form.scrollIntoView({behavior: 'smooth', block: 'start'});
}
function editPurchase(id){
    const purchase = currentPurchases.find(item => Number(item.id) === Number(id));
    if (!purchase) return;
    const form = document.getElementById('purchaseForm');
    fillForm(form, purchase);
    form.scrollIntoView({behavior: 'smooth', block: 'start'});
}
async function deleteAsset(id){
    if (!(await AppUI.confirm('이 재고/장비를 삭제할까요?'))) return;
    await guarded(async () => { await api('deleteAsset', {id}); await loadAssets(); showToast('재고/장비가 삭제되었습니다.'); });
}
async function deletePurchase(id){
    if (!(await AppUI.confirm('이 발주 요청을 삭제할까요?'))) return;
    await guarded(async () => { await api('deletePurchaseRequest', {id}); await loadAssets(); showToast('발주 요청이 삭제되었습니다.'); });
}
async function loadCredentials(){
    const data = await api('credentials');
    currentCredentials = data.credentials;
    document.getElementById('credentialList').innerHTML = data.credentials.length ? data.credentials.map(c => row(`<div class="flex justify-between gap-4"><button type="button" onclick="editCredential(${c.id})" class="text-left flex-1"><p class="font-medium text-slate-100">${esc(c.employee_name)} · ${esc(c.credential_name)} <span class="${statusClass(c.status)}">${esc(c.status)}</span></p><p class="text-sm text-slate-500">${esc(c.credential_type)} · 발급 ${esc(c.issue_date || '-')} · 만료 ${esc(c.expire_date || '-')}</p></button><button onclick="deleteCredential(${c.id})" class="hospital-danger hospital-print-hide">삭제</button></div>`)).join('') : empty('등록된 교육/자격이 없습니다.', 'badge-plus');
}
function editCredential(id){
    const credential = currentCredentials.find(item => Number(item.id) === Number(id));
    if (!credential) return;
    const form = document.getElementById('credentialForm');
    fillForm(form, credential);
    form.scrollIntoView({behavior: 'smooth', block: 'start'});
}
async function deleteCredential(id){
    if (!(await AppUI.confirm('이 교육/자격 항목을 삭제할까요?'))) return;
    await guarded(async () => { await api('deleteCredential', {id}); await loadCredentials(); showToast('교육/자격 항목이 삭제되었습니다.'); });
}

workDate.addEventListener('change', () => { if(tab==='dashboard')loadDashboard(); if(tab==='checks')loadChecks(); if(tab==='closing')loadClosing(); if(tab==='shifts'){ document.getElementById('shiftForm').slot_date.value = workDate.value; loadShifts(); } });
document.getElementById('shiftForm')?.addEventListener('submit', async e => {
    e.preventDefault();
    await guarded(async () => {
        const d = formData(e.target);
        await api('saveShift', d);
        resetShiftForm();
        await loadShifts();
        showToast('근무표가 저장되었습니다.');
    });
});
document.getElementById('bulkShiftForm')?.addEventListener('submit', async e => {
    e.preventDefault();
    await guarded(async () => {
        const d = formData(e.target);
        const selected = [...document.querySelectorAll('#weekdayPicker input:checked')].map(input => Number(input.value));
        if (selected.length === 0) throw new Error('등록할 요일을 선택해주세요.');
        const {monday} = getWeekRange();
        const weekDates = daysInWeek(monday).filter(day => selected.includes(day.getDay()));
        for (const day of weekDates) {
            await api('saveShift', {...d, slot_date: isoDate(day), id: '', status: '확정'});
        }
        await loadShifts();
        showToast(`${weekDates.length}건의 반복 근무를 등록했습니다.`);
    });
});
document.getElementById('leaveForm')?.addEventListener('submit', async e => {
    e.preventDefault();
    await guarded(async () => {
        const d = formData(e.target);
        await api('saveLeaveRequest', d);
        e.target.reset();
        e.target.start_date.value = workDate.value;
        e.target.end_date.value = workDate.value;
        await loadShifts();
        showToast('휴가/대체근무 신청이 저장되었습니다.');
    });
});
document.getElementById('closingForm')?.addEventListener('submit', async e => { e.preventDefault(); await guarded(async () => { await api('saveClosing', formData(e.target)); await loadClosing(); showToast('수납 마감이 저장되었습니다.'); }); });
document.getElementById('closingForm')?.addEventListener('input', updateClosingPreview);
document.getElementById('assetForm')?.addEventListener('submit', async e => { e.preventDefault(); await guarded(async () => { await api('saveAsset', formData(e.target)); e.target.reset(); await loadAssets(); showToast('재고/장비가 저장되었습니다.'); }); });
document.getElementById('purchaseForm')?.addEventListener('submit', async e => { e.preventDefault(); await guarded(async () => { await api('savePurchaseRequest', formData(e.target)); e.target.reset(); await loadAssets(); showToast('발주 요청이 저장되었습니다.'); }); });
document.getElementById('credentialForm')?.addEventListener('submit', async e => { e.preventDefault(); await guarded(async () => { await api('saveCredential', formData(e.target)); e.target.reset(); await loadCredentials(); showToast('교육/자격이 저장되었습니다.'); }); });
if (document.getElementById('shiftForm')) resetShiftForm();
if (document.getElementById('leaveForm')) { document.getElementById('leaveForm').start_date.value = workDate.value; document.getElementById('leaveForm').end_date.value = workDate.value; }
if(tab==='dashboard') loadDashboard();
if(tab==='shifts') loadShifts();
if(tab==='checks') loadChecks();
if(tab==='closing') loadClosing();
if(tab==='assets') loadAssets();
if(tab==='training') loadCredentials();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
