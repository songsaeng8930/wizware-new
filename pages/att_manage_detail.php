<?php
$pageTitle = '근태 현황';
$currentPage = 'attendance';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// 직원 정보 (쿼리 파라미터)
$empId = $_GET['id'] ?? 0;
$empName = $_GET['name'] ?? '위진희';
$empDept = $_GET['dept'] ?? 'Zaemit';
$empRank = $_GET['rank'] ?? '대리';
$remainVac = 0.5;

// 더미 휴가 사용 내역
$vacUsage = [
    ['period' => '2026-02-25 09:00 ~ 2026-02-25 14:00', 'change' => '-0.5일', 'accum' => '-45일', 'content' => '연차사용', 'note' => '연차사용', 'regDate' => '2026-02-25 08:38:21'],
    ['period' => '2026-01-20 09:00 ~ 2026-01-20 14:00', 'change' => '-0.5일', 'accum' => '-44.5일', 'content' => '연차사용', 'note' => '연차사용', 'regDate' => '2026-01-20 18:05:14'],
    ['period' => '2026-01-15 09:00 ~ 2026-01-16 18:00', 'change' => '-2일',   'accum' => '-44일',  'content' => '연차사용', 'note' => '연차사용', 'regDate' => '2025-12-26 13:20:19'],
    ['period' => '2025-12-24 14:00 ~ 2025-12-24 18:00', 'change' => '-0.5일', 'accum' => '-41.5일', 'content' => '연차사용', 'note' => '연차사용', 'regDate' => '2025-11-27 16:57:42'],
    ['period' => '2025-12-17 14:00 ~ 2025-12-17 18:00', 'change' => '-0.5일', 'accum' => '-41일',  'content' => '연차사용', 'note' => '연차사용', 'regDate' => '2025-11-27 16:57:25'],
    ['period' => '2025-12-12 14:00 ~ 2025-12-12 18:00', 'change' => '-0.5일', 'accum' => '-42일',  'content' => '연차사용', 'note' => '연차사용', 'regDate' => '2025-11-27 16:58:06'],
    ['period' => '2025-11-21 14:00 ~ 2025-11-21 18:00', 'change' => '-0.5일', 'accum' => '-40.5일', 'content' => '연차사용', 'note' => '연차사용', 'regDate' => '2025-11-18 14:59:49'],
    ['period' => '2025-10-30 09:00 ~ 2025-10-30 18:00', 'change' => '-1일',   'accum' => '-40일',  'content' => '연차사용', 'note' => '연차사용', 'regDate' => '2025-10-28 15:01:01'],
];
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6">

        <!-- 페이지 제목 -->
        <div class="flex items-center gap-2 mb-5">
            <button onclick="history.back()" class="text-slate-400 hover:text-slate-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <h2 class="text-lg font-bold text-slate-100">근태 현황</h2>
        </div>

        <!-- 직원 정보 카드 -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 mb-6">
            <div class="flex items-start justify-between">
                <div class="space-y-1 text-sm text-slate-200">
                    <p>이름 : <strong><?= htmlspecialchars($empName) ?> <?= htmlspecialchars($empRank) ?></strong></p>
                    <p><?= htmlspecialchars(getOrgLabel('department')) ?> : <strong><?= htmlspecialchars($empDept) ?></strong></p>
                    <p>잔여 휴가 일수 : <strong><?= $remainVac ?></strong> 일</p>
                </div>
                <div class="flex items-center gap-3">
                    <button class="btn btn-secondary">
                        휴가 촉진
                    </button>
                    <button class="btn btn-secondary">
                        휴가 추가
                    </button>
                    <button onclick="BmsExport.table('#vacUsageTable', '휴가사용내역_<?= htmlspecialchars($empName) ?>_<?= date('Y-m-d') ?>.csv')" class="btn btn-secondary">
                        근태 엑셀 다운로드
                    </button>
                </div>
            </div>
        </div>

        <!-- 휴가 생성 내역 -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 mb-6">
            <h3 class="text-sm font-semibold text-slate-100 mb-4">휴가 생성 내역</h3>
            <table class="w-full text-sm emp-table">
                <thead>
                    <tr class="border-b-2 border-slate-800">
                        <th class="py-3 px-4 text-center font-medium text-slate-300">생성일</th>
                        <th class="py-3 px-4 text-center font-medium text-slate-300">변경일 수</th>
                        <th class="py-3 px-4 text-center font-medium text-slate-300">누적 휴가수</th>
                        <th class="py-3 px-4 text-center font-medium text-slate-300">내용</th>
                        <th class="py-3 px-4 text-center font-medium text-slate-300">비고</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="5" class="py-12 text-center text-slate-400">
                            <div class="flex items-center justify-center gap-2">
                                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>There are no search results</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- 휴가 사용 내역 -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6">
            <h3 class="text-sm font-semibold text-slate-100 mb-4">휴가 사용 내역</h3>
            <table id="vacUsageTable" class="w-full text-sm emp-table">
                <thead>
                    <tr class="border-b-2 border-slate-800">
                        <th class="py-3 px-3 text-center font-medium text-slate-300">사용일</th>
                        <th class="py-3 px-3 text-center font-medium text-slate-300">변경일 수</th>
                        <th class="py-3 px-3 text-center font-medium text-slate-300">누적 휴가 사용일</th>
                        <th class="py-3 px-3 text-center font-medium text-slate-300">내용</th>
                        <th class="py-3 px-3 text-center font-medium text-slate-300">비고</th>
                        <th class="py-3 px-3 text-center font-medium text-slate-300">등록일</th>
                        <th class="py-3 px-3 text-center font-medium text-slate-300" data-export-skip>생성</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vacUsage as $row): ?>
                    <tr class="border-b border-slate-800 hover:bg-slate-950">
                        <td class="py-3 px-3 text-center text-slate-300"><?= $row['period'] ?></td>
                        <td class="py-3 px-3 text-center text-slate-300"><?= $row['change'] ?></td>
                        <td class="py-3 px-3 text-center text-slate-300"><?= $row['accum'] ?></td>
                        <td class="py-3 px-3 text-center text-slate-300"><?= $row['content'] ?></td>
                        <td class="py-3 px-3 text-center text-slate-300"><?= $row['note'] ?></td>
                        <td class="py-3 px-3 text-center text-slate-300"><?= $row['regDate'] ?></td>
                        <td class="py-3 px-3 text-center" data-export-skip>
                            <button onclick='showVacDetail(<?= htmlspecialchars(json_encode($row, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>)' class="btn btn-secondary btn-xs">상세</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </main>
</div>

<!-- 휴가 사용 상세 모달 -->
<div id="vacDetailModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="closeVacDetail()"></div>
    <div class="relative bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-800">
            <h3 class="text-base font-bold text-slate-100">휴가 사용 상세</h3>
            <button onclick="closeVacDetail()" class="text-slate-400 hover:text-slate-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-5" id="vacDetailBody"></div>
        <div class="flex gap-2 justify-end px-5 pb-5">
            <button onclick="closeVacDetail()" class="btn btn-secondary">닫기</button>
        </div>
    </div>
</div>
<script>
function showVacDetail(row) {
    const body = document.getElementById('vacDetailBody');
    body.innerHTML = `
        <dl class="grid grid-cols-3 gap-y-2 text-sm text-slate-200">
            <dt class="text-slate-400">사용 기간</dt><dd class="col-span-2">${escapeHtml(row.period)}</dd>
            <dt class="text-slate-400">변경일수</dt><dd class="col-span-2">${escapeHtml(row.change)}</dd>
            <dt class="text-slate-400">누적 사용일</dt><dd class="col-span-2">${escapeHtml(row.accum)}</dd>
            <dt class="text-slate-400">내용</dt><dd class="col-span-2">${escapeHtml(row.content)}</dd>
            <dt class="text-slate-400">비고</dt><dd class="col-span-2">${escapeHtml(row.note)}</dd>
            <dt class="text-slate-400">등록일</dt><dd class="col-span-2">${escapeHtml(row.regDate)}</dd>
        </dl>`;
    document.getElementById('vacDetailModal').classList.remove('hidden');
}
function closeVacDetail() { document.getElementById('vacDetailModal').classList.add('hidden'); }
function escapeHtml(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' }[c])); }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
