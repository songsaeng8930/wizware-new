<?php
$pageTitle = '근태 관리';
$currentPage = 'attendance';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/permissions.php';
requireMenuPermission('attendance.manage', 'view');

// DB에서 직원 데이터 가져오기 (직원관리와 동일한 소스)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/hr_codes.php';
$employees = [];

try {
    $pdo = getDBConnection();
    if ($pdo) {
        $stmt = $pdo->query("
            SELECT e.id, e.name, e.email, e.phone, e.position,
                   e.employment_type, e.employment_status,
                   d.name AS department_name,
                   CASE WHEN pd.parent_id IS NOT NULL THEN pd.name
                        ELSE COALESCE(d.name, '')
                   END AS division_name
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN departments pd ON d.parent_id = pd.id
            WHERE e.is_active = 1
            ORDER BY e.id DESC
        ");
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            $employees[] = [
                'no'     => (int)$row['id'],
                'name'   => $row['name'],
                'org'    => $row['division_name'] ?: '',
                'dept'   => $row['department_name'] ?: '',
                'rank'   => $row['position'] ?: '',
                'type'   => $row['employment_type'] ?: '정규직',
                'status' => $row['employment_status'] ?: '재직',
                'email'  => $row['email'] ?: '',
            ];
        }
    }
} catch (PDOException $e) {
    // DB 연결 실패 시 빈 배열 유지
}
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6">

        <!-- 페이지 제목 -->
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-2">
                <button onclick="history.back()" class="text-slate-400 hover:text-slate-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                <h2 class="text-lg font-bold text-slate-100">근태 관리</h2>
            </div>
            <div class="flex items-center gap-2">
                <a href="dept_attendance.php" class="btn btn-secondary">
                    <i data-lucide="calendar-check" class="w-3 h-3"></i>
                    근태현황
                </a>
                <a href="att_manage.php" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-primary border border-primary rounded-lg transition-colors">
                    <i data-lucide="settings" class="w-3 h-3"></i>
                    근태관리
                </a>
            </div>
        </div>

        <!-- 검색 필터 영역 -->
        <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden mb-5">
            <div class="filter-grid filter-style-chip">
                <!-- 1행: 본부, 부서, 직급 -->
                <div class="filter-row-3">
                    <div class="filter-label <?= isOrgLevelEnabled('division') ? '' : 'hidden' ?>"><?= htmlspecialchars(getOrgLabel('division')) ?></div>
                    <div class="filter-value <?= isOrgLevelEnabled('division') ? '' : 'hidden' ?>">
                        <select id="filterDivision" class="filter-input" style="padding-left:12px;">
                            <option value="">전체 <?= htmlspecialchars(getOrgLabel('division')) ?></option>
                        </select>
                    </div>
                    <div class="filter-label <?= isOrgLevelEnabled('department') ? '' : 'hidden' ?>"><?= htmlspecialchars(getOrgLabel('department')) ?></div>
                    <div class="filter-value <?= isOrgLevelEnabled('department') ? '' : 'hidden' ?>">
                        <select id="filterDepartment" class="filter-input" style="padding-left:12px;">
                            <option value="">전체 <?= htmlspecialchars(getOrgLabel('department')) ?></option>
                        </select>
                    </div>
                    <div class="filter-label">직급</div>
                    <div class="filter-value">
                        <select id="filterPosition" class="filter-input" style="padding-left:12px;">
                            <option value="">전체 직급</option>
                            <?= getHrCodeOptions('직급') ?>
                        </select>
                    </div>
                </div>
                <!-- 2행: 이름, 이메일 -->
                <div class="filter-row">
                    <div class="filter-label">이름</div>
                    <div class="filter-value">
                        <div class="filter-input-wrap">
                            <i data-lucide="search" class="filter-icon"></i>
                            <input type="text" id="filterName" placeholder="이름을 입력" class="filter-input">
                        </div>
                    </div>
                    <div class="filter-label">이메일</div>
                    <div class="filter-value">
                        <div class="filter-input-wrap">
                            <i data-lucide="search" class="filter-icon"></i>
                            <input type="text" id="filterEmail" placeholder="이메일을 입력" class="filter-input">
                        </div>
                    </div>
                </div>
                <!-- 3행: 고용형태, 고용상태 (공통코드 연동) -->
                <?php
                    $empTypes = getHrCodeItems('고용형태');
                    $empStatuses = getHrCodeItems('고용상태');
                    $defaultTypes = ['정규직','계약직','파견직'];
                    $defaultStatuses = ['재직'];
                ?>
                <div class="filter-row">
                    <div class="filter-label">고용형태</div>
                    <div class="filter-value">
                        <div class="flex flex-wrap items-center gap-x-1.5 gap-y-1">
                            <label class="emp-checkbox-label">
                                <input type="checkbox" name="empType" value="" class="emp-checkbox"> <span>전체</span>
                            </label>
                            <?php foreach ($empTypes as $t): ?>
                            <label class="emp-checkbox-label">
                                <input type="checkbox" name="empType" value="<?= htmlspecialchars($t['name']) ?>" <?= in_array($t['name'], $defaultTypes) ? 'checked' : '' ?> class="emp-checkbox"> <span><?= htmlspecialchars($t['name']) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="filter-label">고용상태</div>
                    <div class="filter-value">
                        <div class="flex flex-wrap items-center gap-x-1.5 gap-y-1">
                            <label class="emp-checkbox-label">
                                <input type="checkbox" name="empStatus" value="" class="emp-checkbox"> <span>전체</span>
                            </label>
                            <?php foreach ($empStatuses as $s): ?>
                            <label class="emp-checkbox-label">
                                <input type="checkbox" name="empStatus" value="<?= htmlspecialchars($s['name']) ?>" <?= in_array($s['name'], $defaultStatuses) ? 'checked' : '' ?> class="emp-checkbox"> <span><?= htmlspecialchars($s['name']) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <!-- 하단: 검색/초기화 버튼 -->
            <div class="flex justify-end gap-2 px-5 py-3 bg-slate-950 border-t border-slate-800">
                <button id="btnSearch" class="inline-flex items-center gap-1.5 px-5 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:opacity-90 transition-colors">
                    검색 <i data-lucide="search" class="w-4 h-4"></i>
                </button>
                <button id="btnReset" class="btn btn-secondary">
                    초기화 <i data-lucide="rotate-cw" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        <!-- 결과 테이블 -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm text-slate-200">구성원총 <span id="totalCount" class="text-primary font-bold">0</span> 명</p>
                <div class="flex items-center gap-2">
                    <select id="pageSize" class="border border-slate-800 rounded-lg px-3 py-1.5 text-sm">
                        <option>10</option>
                        <option selected>50</option>
                        <option>100</option>
                    </select>
                    <button onclick="exportAttCsv()" class="btn btn-secondary btn-sm">근태 엑셀 다운로드</button>
                    <button onclick="exportLeaveCsv()" class="btn btn-secondary btn-sm">휴가 엑셀 다운로드</button>
                </div>
            </div>

            <table class="w-full text-sm emp-table">
                <thead>
                    <tr class="border-b-2 border-slate-800">
                        <th class="py-3 px-2 text-center font-medium text-slate-300">No.</th>
                        <th class="py-3 px-2 text-center font-medium text-slate-300">이름</th>
                        <?php if (isOrgLevelEnabled('division')): ?><th class="py-3 px-2 text-center font-medium text-slate-300"><?= htmlspecialchars(getOrgLabel('division')) ?></th><?php endif; ?>
                        <?php if (isOrgLevelEnabled('department')): ?><th class="py-3 px-2 text-center font-medium text-slate-300"><?= htmlspecialchars(getOrgLabel('department')) ?></th><?php endif; ?>
                        <th class="py-3 px-2 text-center font-medium text-slate-300">직급</th>
                        <th class="py-3 px-2 text-center font-medium text-slate-300">고용형태</th>
                        <th class="py-3 px-2 text-center font-medium text-slate-300">고용상태</th>
                        <th class="py-3 px-2 text-center font-medium text-slate-300">이메일</th>
                    </tr>
                </thead>
                <tbody id="empTableBody"></tbody>
            </table>
        </div>

    </main>
</div>

<!-- 직원 연차 상세 드로어 -->
<div id="leaveDrawerOverlay" class="fixed inset-0 z-40 bg-black/30 hidden" onclick="closeLeaveDrawer()"></div>
<div id="leaveDrawer" class="fixed top-14 right-0 bottom-0 z-40 w-[520px] bg-white border-l border-gray-200 transform translate-x-full transition-transform duration-300 flex flex-col">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 shrink-0">
        <div class="flex items-center gap-3">
            <button onclick="navigateEmployee(-1)" id="drawerPrev" class="p-1 rounded hover:bg-gray-100 text-gray-400 hover:text-gray-600" title="이전 직원">
                <i data-lucide="chevron-left" class="w-5 h-5"></i>
            </button>
            <div>
                <h3 id="drawerEmpName" class="text-base font-bold text-gray-800"></h3>
                <p id="drawerEmpInfo" class="text-sm text-gray-500"></p>
            </div>
            <button onclick="navigateEmployee(1)" id="drawerNext" class="p-1 rounded hover:bg-gray-100 text-gray-400 hover:text-gray-600" title="다음 직원">
                <i data-lucide="chevron-right" class="w-5 h-5"></i>
            </button>
        </div>
        <button onclick="closeLeaveDrawer()" class="p-1 rounded hover:bg-gray-100 text-gray-400 hover:text-gray-600">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>
    <div class="px-6 py-5 border-b border-gray-200 shrink-0">
        <div class="flex items-end gap-2 mb-3">
            <span class="text-3xl font-bold" id="drawerRemaining">-</span>
            <span class="text-sm text-gray-500 pb-1">일 남음</span>
        </div>
        <div class="flex gap-6 text-sm mb-3">
            <div><span class="text-gray-500">부여</span> <span id="drawerTotal" class="font-medium text-gray-700">-</span></div>
            <div><span class="text-gray-500">사용</span> <span id="drawerUsed" class="font-medium text-gray-700">-</span></div>
            <div><span class="text-gray-500">잔여</span> <span id="drawerRemain2" class="font-medium text-gray-700">-</span></div>
        </div>
        <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
            <div id="drawerProgressBar" class="h-full rounded-full transition-all duration-500" style="width:0%"></div>
        </div>
    </div>
    <div class="flex border-b border-gray-200 shrink-0">
        <button class="drawer-tab flex-1 py-3 text-sm font-medium text-center border-b-2 border-primary text-primary" data-tab="history" onclick="switchDrawerTab('history')">사용 내역</button>
        <button class="drawer-tab flex-1 py-3 text-sm font-medium text-center border-b-2 border-transparent text-gray-400 hover:text-gray-600" data-tab="adjustments" onclick="switchDrawerTab('adjustments')">조정 내역</button>
    </div>
    <div class="flex-1 overflow-y-auto px-6 py-4" id="drawerContent">
        <div id="drawerHistoryTab"></div>
        <div id="drawerAdjustTab" class="hidden"></div>
    </div>
    <div class="flex gap-2 px-6 py-4 border-t border-gray-200 shrink-0">
        <button onclick="openLeaveAddModal()" class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:opacity-90">
            <i data-lucide="plus" class="w-4 h-4"></i> 휴가 추가
        </button>
        <button onclick="openAdjustModal()" class="flex-1 btn btn-secondary text-sm inline-flex items-center justify-center gap-1.5">
            <i data-lucide="sliders-horizontal" class="w-4 h-4"></i> 연차 조정
        </button>
    </div>
</div>

<!-- 휴가 추가 모달 -->
<div id="leaveAddModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40" onclick="closeLeaveAddModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md border border-gray-200">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
            <h3 class="text-base font-bold text-gray-800">휴가 추가</h3>
            <button onclick="closeLeaveAddModal()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <div class="p-5 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">휴가 유형</label>
                <select id="leaveType" onchange="onLeaveTypeChange()" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="AL" data-deduct="1">연차</option>
                    <option value="HAM" data-deduct="1" data-half="1">오전반차</option>
                    <option value="HAP" data-deduct="1" data-half="1">오후반차</option>
                    <option value="SL" data-deduct="1">병가</option>
                    <option value="FL" data-deduct="1">경조사</option>
                    <option value="OL" data-deduct="0">공가</option>
                    <option value="SP" data-deduct="0">특별휴가</option>
                    <option value="OT" data-deduct="0">기타</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">시작일</label>
                    <input type="date" id="leaveStart" onchange="updateLeaveAddPreview()" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div id="leaveEndWrap">
                    <label class="block text-sm font-medium text-gray-700 mb-1">종료일</label>
                    <input type="date" id="leaveEnd" onchange="updateLeaveAddPreview()" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">사유 <span class="text-gray-400 font-normal">(선택)</span></label>
                <input type="text" id="leaveReason" placeholder="사유를 입력하세요" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div id="leaveAddPreview" class="bg-blue-50 border border-blue-100 rounded-lg p-3 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-[var(--zm-text-muted)]">사용일수</span>
                    <span id="previewDays" class="font-bold text-[var(--zm-primary)]">1일</span>
                </div>
                <div class="flex items-center justify-between mt-1">
                    <span class="text-[var(--zm-text-muted)]">등록 후 잔여</span>
                    <span id="previewRemaining" class="font-bold text-emerald-600">-</span>
                </div>
            </div>
        </div>
        <div class="flex gap-2 justify-end px-5 pb-5">
            <button onclick="closeLeaveAddModal()" class="btn btn-secondary">취소</button>
            <button onclick="submitLeaveAdd()" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:opacity-90">신청</button>
        </div>
    </div>
</div>

<!-- 연차 조정 모달 -->
<div id="adjustModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40" onclick="closeAdjustModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg border border-gray-200">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                <i data-lucide="sliders-horizontal" class="w-4 h-4 text-[var(--zm-primary)]"></i>
                연차 조정
            </h3>
            <button onclick="closeAdjustModal()" class="p-1 hover:bg-gray-100 rounded-lg"><i data-lucide="x" class="w-4 h-4 text-gray-500"></i></button>
        </div>
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">조정 유형 <span class="text-red-500">*</span></label>
                    <div class="flex gap-2">
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="adjTypeRadio" value="add" class="peer sr-only" checked onchange="updateAdjustPreview()">
                            <div class="peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700 border border-gray-300 rounded-lg py-2 text-center text-sm font-medium transition-colors hover:bg-gray-50">+ 추가</div>
                        </label>
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="adjTypeRadio" value="deduct" class="peer sr-only" onchange="updateAdjustPreview()">
                            <div class="peer-checked:border-red-500 peer-checked:bg-red-50 peer-checked:text-red-700 border border-gray-300 rounded-lg py-2 text-center text-sm font-medium transition-colors hover:bg-gray-50">- 차감</div>
                        </label>
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">조정일수 <span class="text-red-500">*</span></label>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="adjDaysDelta(-0.5)" class="w-8 h-8 rounded-lg border border-gray-300 flex items-center justify-center hover:bg-gray-100 text-gray-600 font-bold">-</button>
                        <input type="number" id="adjustDays" value="1" min="0.5" max="30" step="0.5" onchange="updateAdjustPreview()"
                               class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm text-center font-medium">
                        <button type="button" onclick="adjDaysDelta(0.5)" class="w-8 h-8 rounded-lg border border-gray-300 flex items-center justify-center hover:bg-gray-100 text-gray-600 font-bold">+</button>
                        <span class="text-sm text-gray-500">일</span>
                    </div>
                </div>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700 block mb-1.5">분류</label>
                <div class="adj-cat-chips" id="adjCatWrap">
                    <label class="adj-chip" data-color="amber"><input type="radio" name="adjCatRadio" value="포상"><span>포상</span></label>
                    <label class="adj-chip" data-color="blue"><input type="radio" name="adjCatRadio" value="이월"><span>이월</span></label>
                    <label class="adj-chip" data-color="emerald"><input type="radio" name="adjCatRadio" value="보정"><span>보정</span></label>
                    <label class="adj-chip" data-color="slate" ><input type="radio" name="adjCatRadio" value="기타" checked><span>기타</span></label>
                </div>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700 block mb-1">조정 사유 <span class="text-red-500">*</span></label>
                <textarea id="adjustReason" rows="2" maxlength="200" placeholder="예: 포상 연차 2일 부여 (사내 공모전 수상)"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-[var(--zm-primary)] focus:ring-1 focus:ring-[var(--zm-primary)] resize-none" oninput="document.getElementById('adjReasonCount').textContent=this.value.length"></textarea>
                <p class="text-xs text-gray-400 mt-0.5 text-right"><span id="adjReasonCount">0</span>/200</p>
            </div>
            <div id="adjustPreview" class="hidden border border-dashed border-gray-300 rounded-lg p-3 bg-gray-50 text-sm">
                <div class="flex items-center gap-2 text-gray-600">
                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                    <span>변경 미리보기</span>
                </div>
                <div class="mt-1.5 flex items-center gap-3">
                    <span class="text-gray-500">현재 <span id="adjPrevBefore" class="font-medium text-gray-900">-</span>일</span>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5 text-gray-400"></i>
                    <span id="adjPrevAfter" class="font-bold text-[var(--zm-primary)]">-</span>
                    <span class="text-gray-500">일</span>
                    <span id="adjPrevDelta" class="text-xs px-1.5 py-0.5 rounded font-medium"></span>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-2">
            <button onclick="closeAdjustModal()" class="btn btn-secondary">취소</button>
            <button onclick="submitAdjust()" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-[var(--zm-primary)] rounded-lg hover:opacity-90">
                <i data-lucide="check" class="w-3.5 h-3.5"></i> 적용
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    const basePath = '<?= $basePath ?>';
    const allEmployees = <?= json_encode($employees, JSON_UNESCAPED_UNICODE) ?>;
    let filteredEmployees = [...allEmployees];
    const SHOW_DIVISION = ((window.ORG_LABELS || {}).division || {}).enabled !== false;
    const SHOW_DEPARTMENT = ((window.ORG_LABELS || {}).department || {}).enabled !== false;

    // 본부/부서 드롭다운: 직원 데이터에서 추출
    (function loadFilterOptions() {
        const divSet = new Set();
        const deptSet = new Set();
        allEmployees.forEach(emp => {
            if (emp.org) divSet.add(emp.org);
            if (emp.dept) deptSet.add(emp.dept);
        });
        const divSel = document.getElementById('filterDivision');
        [...divSet].sort().forEach(name => {
            const opt = document.createElement('option');
            opt.value = name;
            opt.textContent = name;
            divSel.appendChild(opt);
        });
        const deptSel = document.getElementById('filterDepartment');
        [...deptSet].sort().forEach(name => {
            const opt = document.createElement('option');
            opt.value = name;
            opt.textContent = name;
            deptSel.appendChild(opt);
        });
    })();

    // 초기 렌더링
    renderTable();
    bindEvents();

    function renderTable() {
        const tbody = document.getElementById('empTableBody');
        document.getElementById('totalCount').textContent = filteredEmployees.length;

        if (filteredEmployees.length === 0) {
            const colCount = 6 + (SHOW_DIVISION ? 1 : 0) + (SHOW_DEPARTMENT ? 1 : 0);
            tbody.innerHTML = `<tr><td colspan="${colCount}" class="py-16 text-center text-slate-400">
                <div class="flex flex-col items-center gap-2">
                    <i data-lucide="search" class="w-8 h-8 text-slate-600"></i>
                    <p>검색 결과가 없습니다.</p>
                </div>
            </td></tr>`;
            lucide.createIcons();
            return;
        }

        const pageSize = parseInt(document.getElementById('pageSize').value) || 50;
        const pageData = filteredEmployees.slice(0, pageSize);

        tbody.innerHTML = pageData.map((emp, i) => `
            <tr class="border-b border-slate-800 hover:bg-gray-100 cursor-pointer"
                onclick="openLeaveDrawer(${emp.no})">
                <td class="py-3.5 px-2 text-center text-slate-300">${i + 1}</td>
                <td class="py-3.5 px-2 text-center text-slate-200 font-medium">${emp.name}</td>
                ${SHOW_DIVISION ? `<td class="py-3.5 px-2 text-center text-slate-300">${emp.org}</td>` : ''}
                ${SHOW_DEPARTMENT ? `<td class="py-3.5 px-2 text-center text-slate-300">${emp.dept}</td>` : ''}
                <td class="py-3.5 px-2 text-center text-slate-300">${emp.rank}</td>
                <td class="py-3.5 px-2 text-center text-slate-300">${emp.type}</td>
                <td class="py-3.5 px-2 text-center text-slate-300">${emp.status}</td>
                <td class="py-3.5 px-2 text-center text-slate-300">${emp.email}</td>
            </tr>
        `).join('');
    }

    function applyFilters() {
        const division = document.getElementById('filterDivision').value;
        const department = document.getElementById('filterDepartment').value;
        const rank = document.getElementById('filterPosition').value;
        const name = document.getElementById('filterName').value.trim().toLowerCase();
        const email = document.getElementById('filterEmail').value.trim().toLowerCase();

        // 고용형태 체크박스
        const typeChecked = document.querySelectorAll('input[name="empType"]:checked');
        const selectedTypes = [...typeChecked].map(cb => cb.value).filter(Boolean);
        const typeAll = [...typeChecked].some(cb => cb.value === '');

        // 고용상태 체크박스
        const statusChecked = document.querySelectorAll('input[name="empStatus"]:checked');
        const selectedStatuses = [...statusChecked].map(cb => cb.value).filter(Boolean);
        const statusAll = [...statusChecked].some(cb => cb.value === '');

        filteredEmployees = allEmployees.filter(emp => {
            if (SHOW_DIVISION && division && emp.org !== division) return false;
            if (SHOW_DEPARTMENT && department && emp.dept !== department) return false;
            if (rank && emp.rank !== rank) return false;
            if (name && !emp.name.toLowerCase().includes(name)) return false;
            if (email && !emp.email.toLowerCase().includes(email)) return false;
            if (!typeAll && selectedTypes.length > 0 && !selectedTypes.includes(emp.type)) return false;
            if (!statusAll && selectedStatuses.length > 0 && !selectedStatuses.includes(emp.status)) return false;
            return true;
        });

        renderTable();
    }

    function resetFilters() {
        document.getElementById('filterDivision').value = '';
        document.getElementById('filterDepartment').value = '';
        document.getElementById('filterPosition').value = '';
        document.getElementById('filterName').value = '';
        document.getElementById('filterEmail').value = '';
        const defaultTypes = <?= json_encode($defaultTypes, JSON_UNESCAPED_UNICODE) ?>;
        const defaultStatuses = <?= json_encode($defaultStatuses, JSON_UNESCAPED_UNICODE) ?>;
        document.querySelectorAll('input[name="empType"]').forEach(cb => {
            cb.checked = cb.value === '' || defaultTypes.includes(cb.value);
        });
        document.querySelectorAll('input[name="empStatus"]').forEach(cb => {
            cb.checked = defaultStatuses.includes(cb.value);
        });
        filteredEmployees = [...allEmployees];
        renderTable();
    }

    function bindEvents() {
        document.getElementById('btnSearch').addEventListener('click', applyFilters);
        document.getElementById('btnReset').addEventListener('click', resetFilters);
        document.getElementById('pageSize').addEventListener('change', renderTable);

        // 엔터키 검색
        document.querySelectorAll('#filterName, #filterEmail').forEach(input => {
            input.addEventListener('keydown', e => {
                if (e.key === 'Enter') { e.preventDefault(); applyFilters(); }
            });
        });

        // select 변경 시 즉시 검색
        document.querySelectorAll('#filterDivision, #filterDepartment, #filterPosition').forEach(sel => {
            sel.addEventListener('change', applyFilters);
        });

        // 고용형태·고용상태: "전체"와 개별 항목 상호배타 처리
        ['empType', 'empStatus'].forEach(wireEmpAllToggle);
    }

    // "전체" 체크 시 개별 해제, 개별 체크 시 "전체" 해제, 아무것도 없으면 "전체" 자동 선택
    function wireEmpAllToggle(groupName) {
        const boxes = [...document.querySelectorAll(`input[name="${groupName}"]`)];
        const allBox = boxes.find(cb => cb.value === '');
        const specifics = boxes.filter(cb => cb.value !== '');
        boxes.forEach(cb => cb.addEventListener('change', function () {
            if (this.value === '') {
                if (this.checked) specifics.forEach(c => (c.checked = false));
                else if (!specifics.some(c => c.checked)) this.checked = true; // 최소 하나 유지
            } else {
                if (this.checked && allBox) allBox.checked = false;
                if (allBox && !specifics.some(c => c.checked)) allBox.checked = true;
            }
        }));
    }

    // 외부 onclick에서 호출 가능하도록 window에 노출 ─ IIFE 스코프 회피
    window.exportAttCsv = function () {
        const today = new Date().toISOString().slice(0, 10);
        const rows = [['No.', '이름']];
        if (SHOW_DIVISION) rows[0].push((ORG_LABELS.division||{}).label||'본부');
        if (SHOW_DEPARTMENT) rows[0].push((ORG_LABELS.department||{}).label||'부서');
        rows[0].push('직급', '고용형태', '고용상태');
        rows[0].push('이메일');
        filteredEmployees.forEach((e, i) => {
            const row = [i + 1, e.name];
            if (SHOW_DIVISION) row.push(e.org);
            if (SHOW_DEPARTMENT) row.push(e.dept);
            row.push(e.rank, e.type, e.status);
            row.push(e.email);
            rows.push(row);
        });
        if (rows.length <= 1) { alert('내보낼 데이터가 없습니다.'); return; }
        BmsExport.rows(rows, `근태관리_${today}.csv`);
    };

    window.exportLeaveCsv = function () {
        // 현재 목록의 연차 현황을 annual_leave API에서 가져와 CSV로 내보낸다.
        fetch(basePath + '/api/annual_leave.php?action=getAll&year=' + new Date().getFullYear())
            .then(r => r.json())
            .then(j => {
                const list = (j && j.employees) || [];
                if (!list.length) { alert('조회된 연차 데이터가 없습니다.'); return; }
                const rows = [['No.', '이름']];
                if (SHOW_DIVISION) rows[0].push((ORG_LABELS.division||{}).label||'본부');
                if (SHOW_DEPARTMENT) rows[0].push((ORG_LABELS.department||{}).label||'부서');
                rows[0].push('부여일수', '사용일수', '잔여일수', '일급', '연차수당');
                list.forEach((e, i) => {
                    const row = [i + 1, e.name];
                    if (SHOW_DIVISION) row.push(e.org);
                    if (SHOW_DEPARTMENT) row.push(e.dept);
                    row.push(e.total, e.used, e.remaining, e.daily, e.compensation);
                    rows.push(row);
                });
                const today = new Date().toISOString().slice(0, 10);
                BmsExport.rows(rows, `연차현황_${today}.csv`);
            })
            .catch(() => alert('연차 데이터를 불러오지 못했습니다.'));
    };

    // === 연차 상세 드로어 ===
    let currentDrawerEmpId = null;
    let leaveBalanceCache = null;
    let _drawerLeaveBalance = null;
    const currentYear = new Date().getFullYear();

    const LEAVE_LABELS = {
        AL:'연차', HAM:'오전반차', HAP:'오후반차', SP:'특별휴가',
        SL:'병가', ML:'출산휴가', PL:'육아휴직', MP:'배우자출산',
        FC:'가족돌봄', FL:'경조사', OL:'공가', OT:'기타'
    };
    const STATUS_CLS = {
        '승인':'bg-emerald-100 text-emerald-700', '대기':'bg-amber-100 text-amber-700',
        '반려':'bg-red-100 text-red-700', '취소':'bg-gray-100 text-gray-500'
    };

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
    }

    async function ensureLeaveBalance() {
        if (leaveBalanceCache) return leaveBalanceCache;
        try {
            const res = await fetch(basePath + '/api/annual_leave.php?action=getAll&year=' + currentYear);
            const data = await res.json();
            leaveBalanceCache = data.employees || [];
        } catch { leaveBalanceCache = []; }
        return leaveBalanceCache;
    }

    window.openLeaveDrawer = async function(empId) {
        currentDrawerEmpId = empId;
        const emp = filteredEmployees.find(e => e.no === empId) || allEmployees.find(e => e.no === empId);
        if (!emp) return;

        document.getElementById('drawerEmpName').textContent = emp.name + (emp.rank ? ' ' + emp.rank : '');
        document.getElementById('drawerEmpInfo').textContent = [emp.org, emp.dept].filter(Boolean).join(' · ');

        document.getElementById('leaveDrawer').classList.remove('translate-x-full');
        document.getElementById('leaveDrawerOverlay').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        lucide.createIcons();

        updateNavButtons();
        switchDrawerTab('history');
        document.getElementById('drawerHistoryTab').innerHTML = '<p class="py-8 text-center text-gray-400 text-sm">로딩 중...</p>';

        const [allBal, histRes] = await Promise.all([
            ensureLeaveBalance(),
            fetch(basePath + '/api/annual_leave.php?action=getHistory&employee_id=' + empId + '&year=' + currentYear).then(r => r.json()).catch(() => ({}))
        ]);
        const bal = allBal.find(e => e.no === empId);
        _drawerLeaveBalance = bal || null;
        renderBalance(bal);
        renderHistory(histRes.history || []);
    };

    window.closeLeaveDrawer = function() {
        document.getElementById('leaveDrawer').classList.add('translate-x-full');
        document.getElementById('leaveDrawerOverlay').classList.add('hidden');
        document.body.style.overflow = '';
        currentDrawerEmpId = null;
    };

    window.navigateEmployee = function(dir) {
        if (!currentDrawerEmpId) return;
        const list = filteredEmployees.length ? filteredEmployees : allEmployees;
        const idx = list.findIndex(e => e.no === currentDrawerEmpId);
        const next = idx + dir;
        if (next < 0 || next >= list.length) return;
        openLeaveDrawer(list[next].no);
    };

    function updateNavButtons() {
        const list = filteredEmployees.length ? filteredEmployees : allEmployees;
        const idx = list.findIndex(e => e.no === currentDrawerEmpId);
        document.getElementById('drawerPrev').disabled = idx <= 0;
        document.getElementById('drawerPrev').classList.toggle('opacity-30', idx <= 0);
        document.getElementById('drawerNext').disabled = idx >= list.length - 1;
        document.getElementById('drawerNext').classList.toggle('opacity-30', idx >= list.length - 1);
    }

    function renderBalance(bal) {
        const total = bal ? bal.total : 0;
        const used = bal ? bal.used : 0;
        const remaining = bal ? bal.remaining : 0;
        const pct = total > 0 ? Math.round((used / total) * 100) : 0;
        const remainPct = total > 0 ? (remaining / total) * 100 : 100;

        document.getElementById('drawerRemaining').textContent = remaining;
        document.getElementById('drawerTotal').textContent = total + '일';
        document.getElementById('drawerUsed').textContent = used + '일';
        document.getElementById('drawerRemain2').textContent = remaining + '일';

        const bar = document.getElementById('drawerProgressBar');
        bar.style.width = pct + '%';

        let barColor, numColor;
        if (remainPct > 50) { barColor = 'bg-emerald-500'; numColor = 'text-3xl font-bold text-emerald-600'; }
        else if (remainPct > 20) { barColor = 'bg-amber-500'; numColor = 'text-3xl font-bold text-amber-600'; }
        else { barColor = 'bg-red-500'; numColor = 'text-3xl font-bold text-red-600'; }
        bar.className = 'h-full rounded-full transition-all duration-500 ' + barColor;
        document.getElementById('drawerRemaining').className = numColor;
    }

    function renderHistory(history) {
        const el = document.getElementById('drawerHistoryTab');
        if (!history.length) { el.innerHTML = '<p class="py-8 text-center text-gray-400 text-sm">사용 내역이 없습니다.</p>'; return; }
        el.innerHTML = '<table class="w-full text-sm"><thead><tr class="border-b border-gray-200">' +
            '<th class="py-2 px-2 text-left text-gray-500 font-medium">날짜</th>' +
            '<th class="py-2 px-2 text-left text-gray-500 font-medium">유형</th>' +
            '<th class="py-2 px-2 text-center text-gray-500 font-medium">일수</th>' +
            '<th class="py-2 px-2 text-center text-gray-500 font-medium">상태</th>' +
            '</tr></thead><tbody>' +
            history.map(h => {
                const date = esc(h.start_date) + (h.end_date !== h.start_date ? ' ~ ' + esc(h.end_date) : '');
                const cls = STATUS_CLS[h.status] || 'bg-gray-100 text-gray-500';
                return '<tr class="border-b border-gray-100 hover:bg-gray-50">' +
                    '<td class="py-2.5 px-2 text-gray-700">' + date + '</td>' +
                    '<td class="py-2.5 px-2 text-gray-700">' + (LEAVE_LABELS[h.leave_type] || h.leave_type) + '</td>' +
                    '<td class="py-2.5 px-2 text-center text-gray-700">' + parseFloat(h.days_used) + '</td>' +
                    '<td class="py-2.5 px-2 text-center"><span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full ' + cls + '">' + esc(h.status) + '</span></td>' +
                    '</tr>';
            }).join('') + '</tbody></table>';
    }

    function renderAdjustments(list) {
        const el = document.getElementById('drawerAdjustTab');
        if (!list.length) { el.innerHTML = '<p class="py-8 text-center text-gray-400 text-sm">조정 내역이 없습니다.</p>'; return; }
        el.innerHTML = '<table class="w-full text-sm"><thead><tr class="border-b border-gray-200">' +
            '<th class="py-2 px-2 text-left text-gray-500 font-medium">날짜</th>' +
            '<th class="py-2 px-2 text-center text-gray-500 font-medium">유형</th>' +
            '<th class="py-2 px-2 text-center text-gray-500 font-medium">일수</th>' +
            '<th class="py-2 px-2 text-left text-gray-500 font-medium">사유</th>' +
            '</tr></thead><tbody>' +
            list.map(a => {
                const isAdd = a.adjust_type === 'add';
                const cls = isAdd ? 'bg-blue-100 text-blue-700' : 'bg-orange-100 text-orange-700';
                return '<tr class="border-b border-gray-100 hover:bg-gray-50">' +
                    '<td class="py-2.5 px-2 text-gray-700">' + esc((a.created_at || '').slice(0,10)) + '</td>' +
                    '<td class="py-2.5 px-2 text-center"><span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full ' + cls + '">' + (isAdd ? '추가' : '차감') + '</span></td>' +
                    '<td class="py-2.5 px-2 text-center text-gray-700">' + (isAdd ? '+' : '-') + parseFloat(a.adjust_days) + '</td>' +
                    '<td class="py-2.5 px-2 text-gray-600">' + esc(a.reason) + '</td></tr>';
            }).join('') + '</tbody></table>';
    }

    window.switchDrawerTab = function(tab) {
        document.querySelectorAll('.drawer-tab').forEach(btn => {
            const active = btn.dataset.tab === tab;
            btn.className = 'drawer-tab flex-1 py-3 text-sm font-medium text-center border-b-2 ' +
                (active ? 'border-primary text-primary' : 'border-transparent text-gray-400 hover:text-gray-600');
        });
        document.getElementById('drawerHistoryTab').classList.toggle('hidden', tab !== 'history');
        document.getElementById('drawerAdjustTab').classList.toggle('hidden', tab !== 'adjustments');
        if (tab === 'adjustments' && currentDrawerEmpId) {
            fetch(basePath + '/api/annual_leave.php?action=getAdjustments&employee_id=' + currentDrawerEmpId + '&year=' + currentYear)
                .then(r => r.json()).then(d => renderAdjustments(d.adjustments || [])).catch(() => renderAdjustments([]));
        }
    };

    // 휴가 추가 모달
    window.openLeaveAddModal = function() {
        const today = new Date().toISOString().slice(0, 10);
        document.getElementById('leaveStart').value = today;
        document.getElementById('leaveEnd').value = today;
        document.getElementById('leaveReason').value = '';
        document.getElementById('leaveType').value = 'AL';
        document.getElementById('leaveEndWrap').classList.remove('hidden');
        document.getElementById('leaveAddModal').classList.remove('hidden');
        lucide.createIcons();
        updateLeaveAddPreview();
    };
    window.closeLeaveAddModal = function() { document.getElementById('leaveAddModal').classList.add('hidden'); };

    window.onLeaveTypeChange = function() {
        const isHalf = ['HAM','HAP'].includes(document.getElementById('leaveType').value);
        document.getElementById('leaveEndWrap').classList.toggle('hidden', isHalf);
        if (isHalf) document.getElementById('leaveEnd').value = '';
        updateLeaveAddPreview();
    };

    window.updateLeaveAddPreview = function() {
        const sel = document.getElementById('leaveType');
        const opt = sel.options[sel.selectedIndex];
        const isHalf = opt.dataset.half === '1';
        const isDeduct = opt.dataset.deduct === '1';

        let daysUsed = 1;
        if (isHalf) {
            daysUsed = 0.5;
        } else if (isDeduct) {
            const s = document.getElementById('leaveStart').value;
            const e = document.getElementById('leaveEnd').value;
            if (s && e) {
                const diff = Math.ceil((new Date(e) - new Date(s)) / 86400000) + 1;
                daysUsed = Math.max(1, diff);
            }
        }

        document.getElementById('previewDays').textContent = daysUsed + '일' + (isDeduct ? '' : ' (차감 안 함)');

        const empData = _drawerLeaveBalance;
        const remaining = empData ? empData.remaining : null;
        const remEl = document.getElementById('previewRemaining');
        if (remaining !== null) {
            const newRemaining = isDeduct ? remaining - daysUsed : remaining;
            remEl.textContent = newRemaining + '일';
            remEl.className = 'font-bold ' + (newRemaining <= 3 ? 'text-red-600' : newRemaining <= 5 ? 'text-amber-600' : 'text-emerald-600');
        } else {
            remEl.textContent = '-';
            remEl.className = 'font-bold text-emerald-600';
        }
    };

    window.submitLeaveAdd = async function() {
        const startDate = document.getElementById('leaveStart').value;
        if (!startDate) { AppUI.toast('시작일을 선택하세요.', 'error'); return; }
        const leaveType = document.getElementById('leaveType').value;
        const isHalf = ['HAM','HAP'].includes(leaveType);
        const endDate = isHalf ? startDate : (document.getElementById('leaveEnd').value || startDate);
        if (!isHalf && endDate < startDate) { AppUI.toast('종료일이 시작일보다 빠릅니다.', 'error'); return; }
        const body = {
            employee_id: currentDrawerEmpId,
            leave_type: leaveType,
            start_date: startDate,
            end_date: endDate,
            reason: document.getElementById('leaveReason').value
        };
        try {
            const res = await fetch(basePath + '/api/annual_leave.php?action=applyLeave', {
                method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(body)
            });
            const data = await res.json();
            if (data.success) { closeLeaveAddModal(); leaveBalanceCache = null; openLeaveDrawer(currentDrawerEmpId); AppUI.toast(data.message || '휴가 등록 완료', 'success'); }
            else AppUI.toast(data.error || '오류가 발생했습니다.', 'error');
        } catch { AppUI.toast('서버 연결에 실패했습니다.', 'error'); }
    };

    // 연차 조정 모달
    window.openAdjustModal = function() {
        document.getElementById('adjustDays').value = '1';
        document.getElementById('adjustReason').value = '';
        document.getElementById('adjReasonCount').textContent = '0';
        document.querySelector('input[name="adjTypeRadio"][value="add"]').checked = true;
        document.querySelector('input[name="adjCatRadio"][value="기타"]').checked = true;
        document.getElementById('adjustModal').classList.remove('hidden');
        lucide.createIcons();
        updateAdjustPreview();
    };
    window.closeAdjustModal = function() { document.getElementById('adjustModal').classList.add('hidden'); };

    window.adjDaysDelta = function(delta) {
        const inp = document.getElementById('adjustDays');
        const v = Math.max(0.5, Math.min(30, parseFloat(inp.value || 1) + delta));
        inp.value = v;
        updateAdjustPreview();
    };

    window.updateAdjustPreview = function() {
        const bal = _drawerLeaveBalance;
        const preview = document.getElementById('adjustPreview');
        if (!bal) { preview.classList.add('hidden'); return; }
        preview.classList.remove('hidden');

        const days = parseFloat(document.getElementById('adjustDays').value) || 0;
        const type = document.querySelector('input[name="adjTypeRadio"]:checked')?.value || 'add';
        const before = bal.remaining;
        const after = type === 'add' ? before + days : before - days;
        const delta = type === 'add' ? days : -days;

        document.getElementById('adjPrevBefore').textContent = before;
        document.getElementById('adjPrevAfter').textContent = after;
        const deltaEl = document.getElementById('adjPrevDelta');
        deltaEl.textContent = (delta > 0 ? '+' : '') + delta + '일';
        deltaEl.className = 'text-xs px-1.5 py-0.5 rounded font-medium ' + (delta > 0 ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700');

        lucide.createIcons({nodes: [preview]});
    };

    window.submitAdjust = async function() {
        const days = parseFloat(document.getElementById('adjustDays').value);
        if (!days || days <= 0) { AppUI.toast('조정일수를 입력하세요.', 'error'); return; }
        const reason = document.getElementById('adjustReason').value.trim();
        if (!reason) { AppUI.toast('사유를 입력하세요.', 'error'); return; }
        const adjustType = document.querySelector('input[name="adjTypeRadio"]:checked')?.value || 'add';
        const category = document.querySelector('input[name="adjCatRadio"]:checked')?.value || '기타';
        const empName = document.getElementById('drawerEmpName')?.textContent || '';
        const label = adjustType === 'add' ? '추가' : '차감';
        if (!(await AppUI.confirm(empName + '의 연차를 ' + days + '일 ' + label + '하시겠습니까?\n사유: ' + reason))) return;
        const body = {
            employee_id: currentDrawerEmpId,
            year: currentYear,
            adjust_type: adjustType,
            adjust_days: days,
            reason: reason,
            category: category
        };
        try {
            const res = await fetch(basePath + '/api/annual_leave.php?action=adjustLeave', {
                method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(body)
            });
            const data = await res.json();
            if (data.success) { closeAdjustModal(); leaveBalanceCache = null; openLeaveDrawer(currentDrawerEmpId); AppUI.toast(data.message || '조정 완료', 'success'); }
            else AppUI.toast(data.error || '오류가 발생했습니다.', 'error');
        } catch { AppUI.toast('서버 연결에 실패했습니다.', 'error'); }
    };

    // ESC 키로 드로어/모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key !== 'Escape') return;
        if (!document.getElementById('leaveAddModal').classList.contains('hidden')) { closeLeaveAddModal(); return; }
        if (!document.getElementById('adjustModal').classList.contains('hidden')) { closeAdjustModal(); return; }
        if (currentDrawerEmpId) closeLeaveDrawer();
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
