<?php
$pageTitle = '사업';
$currentPage = 'business';
require_once __DIR__ . '/../includes/hr_codes.php';
require_once __DIR__ . '/../includes/permissions.php';
requireMenuPermission('business', 'view'); // 접근권한 관리 연동 (admin 항상 통과)
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// $basePath 는 includes/header.php 에서 프로젝트 루트로 이미 설정됨.
// dirname(SCRIPT_NAME) 로 덮으면 `$basePath . '/pages/...'` 가 중복되어 링크가 깨진다.
$tab = $_GET['tab'] ?? 'list';

$tabs = [
    'list'   => '사업 목록',
    'status' => '사업 현황',
];

// DB에서 직원 이름 가져오기 (담당자 할당용)
require_once __DIR__ . '/../config/database.php';
$empNames = [];
$deptNames = [];
try {
    $pdo = getDBConnection();
    if ($pdo) {
        $stmt = $pdo->query("SELECT name FROM employees WHERE is_active = 1 ORDER BY id ASC LIMIT 5");
        $empNames = array_column($stmt->fetchAll(), 'name');
        $dStmt = $pdo->query("SELECT name FROM departments WHERE is_active = 1 ORDER BY sort_order, name");
        $deptNames = array_column($dStmt->fetchAll(), 'name');
    }
} catch (PDOException $e) {}
// DB 연결 실패 시 기본값
if (empty($empNames)) $empNames = ['담당자1', '담당자2', '담당자3', '담당자4', '담당자5'];
if (empty($deptNames)) $deptNames = ['개발', '기획', '디자인', '영업'];
$m = $empNames;

$statusOptions = getCommonCodeOptions('business', '사업상태');
if ($statusOptions === '') {
    $statusOptions = '<option>영업</option><option>계약</option><option>진행중</option><option>완료</option><option>보류</option>';
}

// 시범 사업 데이터 (담당자는 DB 직원)
$projects = [
    ['no' => 8, 'name' => 'Zaemit 기업관리 패키지 개발', 'client' => '(주)위즈웨어', 'manager' => $m[0] ?? $m[0], 'dept' => '개발', 'startDate' => '2025-11-01', 'endDate' => '2026-06-30', 'budget' => '120,000,000', 'status' => '진행중', 'progress' => 65],
    ['no' => 7, 'name' => '스마트 물류 관리 시스템', 'client' => '(주)한국물류', 'manager' => $m[3] ?? $m[0], 'dept' => '개발', 'startDate' => '2025-10-15', 'endDate' => '2026-04-30', 'budget' => '85,000,000', 'status' => '진행중', 'progress' => 78],
    ['no' => 6, 'name' => '기업 ERP 커스터마이징', 'client' => '(주)대한제조', 'manager' => $m[1] ?? $m[0], 'dept' => '기획', 'startDate' => '2025-09-01', 'endDate' => '2026-03-31', 'budget' => '65,000,000', 'status' => '진행중', 'progress' => 90],
    ['no' => 5, 'name' => '모바일 앱 리뉴얼', 'client' => '(주)모바일코리아', 'manager' => $m[2] ?? $m[0], 'dept' => '디자인', 'startDate' => '2025-08-01', 'endDate' => '2026-01-31', 'budget' => '45,000,000', 'status' => '완료', 'progress' => 100],
    ['no' => 4, 'name' => '클라우드 인프라 구축', 'client' => '(주)테크솔루션', 'manager' => $m[3] ?? $m[0], 'dept' => '개발', 'startDate' => '2025-07-15', 'endDate' => '2025-12-31', 'budget' => '78,000,000', 'status' => '완료', 'progress' => 100],
    ['no' => 3, 'name' => '고객관리 CRM 도입', 'client' => '(주)영업파트너', 'manager' => $m[4] ?? $m[0], 'dept' => '기획', 'startDate' => '2025-06-01', 'endDate' => '2025-11-30', 'budget' => '35,000,000', 'status' => '완료', 'progress' => 100],
    ['no' => 2, 'name' => '사내 그룹웨어 구축', 'client' => '자체', 'manager' => $m[0] ?? $m[0], 'dept' => '개발', 'startDate' => '2025-03-01', 'endDate' => '2025-09-30', 'budget' => '50,000,000', 'status' => '완료', 'progress' => 100],
    ['no' => 1, 'name' => '웹사이트 리뉴얼', 'client' => '(주)디지털미디어', 'manager' => $m[2] ?? $m[0], 'dept' => '디자인', 'startDate' => '2025-01-15', 'endDate' => '2025-06-30', 'budget' => '28,000,000', 'status' => '완료', 'progress' => 100],
];

// 현황 요약
$statusSummary = [
    'total'    => count($projects),
    'ongoing'  => count(array_filter($projects, fn($p) => $p['status'] === '진행중')),
    'complete' => count(array_filter($projects, fn($p) => $p['status'] === '완료')),
    'totalBudget' => '506,000,000',
];
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
                <h2 class="text-lg font-bold text-slate-100"><?= $tabs[$tab] ?? '사업' ?></h2>
            </div>
            <button onclick="document.getElementById('projectModal').classList.remove('hidden')" class="flex items-center gap-1.5 px-4 py-2 text-sm bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                사업 등록
            </button>
        </div>

        <!-- 탭 -->
        <div class="zm-tab-container mb-5">
            <?php foreach ($tabs as $key => $label): ?>
            <a href="?tab=<?= $key ?>"
               class="px-5 py-2.5 text-sm font-medium border-b-2 transition-colors <?= $tab === $key ? 'approval-tab active' : 'approval-tab' ?>">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>

        <?php if ($tab === 'status'): ?>
        <!-- ===== 사업 현황 탭 ===== -->

        <!-- 요약 카드 -->
        <div class="grid grid-cols-4 gap-4 mb-5">
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-5 text-center">
                <p class="text-sm text-slate-400 mb-1">전체 사업</p>
                <p class="text-2xl font-bold text-slate-100"><?= $statusSummary['total'] ?><span class="text-sm font-normal">건</span></p>
            </div>
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-5 text-center">
                <p class="text-sm text-slate-400 mb-1">진행중</p>
                <p class="text-2xl font-bold text-primary"><?= $statusSummary['ongoing'] ?><span class="text-sm font-normal">건</span></p>
            </div>
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-5 text-center">
                <p class="text-sm text-slate-400 mb-1">완료</p>
                <p class="text-2xl font-bold text-amber-700"><?= $statusSummary['complete'] ?><span class="text-sm font-normal">건</span></p>
            </div>
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-5 text-center">
                <p class="text-sm text-slate-400 mb-1">총 사업비</p>
                <p class="text-2xl font-bold text-slate-100"><span class="text-base"><?= $statusSummary['totalBudget'] ?></span><span class="text-sm font-normal">원</span></p>
            </div>
        </div>

        <!-- 진행중 사업 카드 -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-5 mb-5">
            <h3 class="text-sm font-semibold text-slate-100 mb-4">
                <i data-lucide="loader" class="text-primary mr-1 w-4 h-4"></i> 진행중 사업
            </h3>
            <div class="space-y-4">
                <?php foreach ($projects as $proj):
                    if ($proj['status'] !== '진행중') continue;
                    $barColor = $proj['progress'] >= 80 ? 'bg-amber-500' : ($proj['progress'] >= 50 ? 'bg-primary' : 'bg-amber-500');
                ?>
                <div class="border border-slate-800 rounded-lg p-4 hover:shadow-sm transition-shadow">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <h4 class="text-sm font-semibold text-slate-100"><?= $proj['name'] ?></h4>
                            <p class="text-sm text-slate-400"><?= $proj['client'] ?> | 담당: <?= $proj['manager'] ?> (<?= $proj['dept'] ?>)</p>
                        </div>
                        <span class="text-sm font-bold <?= $proj['progress'] >= 80 ? 'text-amber-700' : 'text-primary' ?>"><?= $proj['progress'] ?>%</span>
                    </div>
                    <div class="w-full bg-slate-700 rounded-full h-2 mb-2">
                        <div class="<?= $barColor ?> h-2 rounded-full transition-all" style="width: <?= $proj['progress'] ?>%"></div>
                    </div>
                    <div class="flex items-center justify-between text-sm text-slate-400">
                        <span><?= $proj['startDate'] ?> ~ <?= $proj['endDate'] ?></span>
                        <span>사업비: <?= $proj['budget'] ?>원</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 완료 사업 테이블 -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
            <h3 class="text-sm font-semibold text-slate-100 mb-4">
                <i data-lucide="circle-check" class="text-amber-500 mr-1 w-4 h-4"></i> 완료 사업
            </h3>
            <table class="w-full text-sm emp-table">
                <thead>
                    <tr class="border-b-2 border-slate-800">
                        <th class="py-3 px-3 text-center font-medium text-slate-300">사업명</th>
                        <th class="py-3 px-3 text-center font-medium text-slate-300 w-[12%]">발주처</th>
                        <th class="py-3 px-3 text-center font-medium text-slate-300 w-[8%]">담당자</th>
                        <th class="py-3 px-3 text-center font-medium text-slate-300 w-[15%]">기간</th>
                        <th class="py-3 px-3 text-center font-medium text-slate-300 w-[12%]">사업비</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projects as $proj):
                        if ($proj['status'] !== '완료') continue;
                    ?>
                    <tr class="border-b border-slate-800 hover:bg-gray-100">
                        <td class="py-3 px-3 text-slate-100"><?= $proj['name'] ?></td>
                        <td class="py-3 px-3 text-center text-slate-300"><?= $proj['client'] ?></td>
                        <td class="py-3 px-3 text-center text-slate-300"><?= $proj['manager'] ?></td>
                        <td class="py-3 px-3 text-center text-slate-400 text-sm"><?= $proj['startDate'] ?> ~ <?= $proj['endDate'] ?></td>
                        <td class="py-3 px-3 text-right text-slate-300"><?= $proj['budget'] ?>원</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php else: ?>
        <!-- ===== 사업 목록 탭 ===== -->

        <!-- 검색 필터 -->
        <div class="bg-slate-900 rounded-xl border border-slate-800 p-5 mb-5">
            <div class="grid grid-cols-2 gap-x-8 gap-y-4">
                <div class="flex items-center gap-4">
                    <label class="text-sm font-medium text-slate-200 w-20 shrink-0">사업명</label>
                    <input type="text" id="filterName" class="reg-input" placeholder="사업명을 입력해주세요">
                </div>
                <div class="flex items-center gap-4">
                    <label class="text-sm font-medium text-slate-200 w-20 shrink-0">발주처</label>
                    <input type="text" id="filterClient" class="reg-input" placeholder="발주처를 입력해주세요">
                </div>
                <div class="flex items-center gap-4">
                    <label class="text-sm font-medium text-slate-200 w-20 shrink-0">담당자</label>
                    <input type="text" id="filterManager" class="reg-input" placeholder="담당자를 입력해주세요">
                </div>
                <div class="flex items-center gap-4">
                    <label class="text-sm font-medium text-slate-200 w-20 shrink-0">상태</label>
                    <select id="filterStatus" class="reg-select">
                        <option>전체</option>
                        <?= $statusOptions ?>
                    </select>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-4">
                <button id="btnSearch" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:opacity-90 transition-colors">검색 <i data-lucide="search" class="w-4 h-4"></i></button>
                <button id="btnReset" class="btn btn-secondary">초기화 <i data-lucide="rotate-cw" class="w-4 h-4"></i></button>
            </div>
        </div>

        <!-- 사업 목록 테이블 -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm text-slate-200">총 <span class="text-primary font-bold"><?= count($projects) ?></span> 건</p>
                <div class="flex items-center gap-2">
                    <select class="border border-slate-800 rounded-lg px-3 py-1.5 text-sm">
                        <option>10</option>
                        <option selected>50</option>
                        <option>100</option>
                    </select>
                    <button onclick="BmsExport.table('#projectsTable', '사업목록_<?= date('Y-m-d') ?>.csv')" class="btn btn-secondary btn-sm">
                        <i data-lucide="download" class="w-3 h-3 mr-1"></i>엑셀 다운로드
                    </button>
                </div>
            </div>

            <table id="projectsTable" class="w-full text-sm emp-table">
                <thead>
                    <tr class="border-b-2 border-slate-800">
                        <th class="py-3 px-2 text-center font-medium text-slate-300 w-[5%]">No.</th>
                        <th class="py-3 px-2 text-center font-medium text-slate-300">사업명</th>
                        <th class="py-3 px-2 text-center font-medium text-slate-300 w-[10%]">발주처</th>
                        <th class="py-3 px-2 text-center font-medium text-slate-300 w-[7%]">담당자</th>
                        <th class="py-3 px-2 text-center font-medium text-slate-300 w-[6%]">부서</th>
                        <th class="py-3 px-2 text-center font-medium text-slate-300 w-[14%]">기간</th>
                        <th class="py-3 px-2 text-center font-medium text-slate-300 w-[10%]">사업비</th>
                        <th class="py-3 px-2 text-center font-medium text-slate-300 w-[7%]">진행률</th>
                        <th class="py-3 px-2 text-center font-medium text-slate-300 w-[7%]">상태</th>
                    </tr>
                </thead>
                <tbody id="projectTableBody">
                    <?php foreach ($projects as $proj): ?>
                    <tr class="border-b border-slate-800 hover:bg-gray-100 cursor-pointer"
                        onclick="location.href='<?= $basePath ?>/pages/business_detail.php?id=<?= $proj['no'] ?>'">
                        <td class="py-3 px-2 text-center text-slate-300"><?= $proj['no'] ?></td>
                        <td class="py-3 px-2 text-slate-100 font-medium"><?= $proj['name'] ?></td>
                        <td class="py-3 px-2 text-center text-slate-300 text-sm"><?= $proj['client'] ?></td>
                        <td class="py-3 px-2 text-center text-slate-300"><?= $proj['manager'] ?></td>
                        <td class="py-3 px-2 text-center text-slate-300"><?= $proj['dept'] ?></td>
                        <td class="py-3 px-2 text-center text-slate-400 text-sm"><?= $proj['startDate'] ?> ~ <?= $proj['endDate'] ?></td>
                        <td class="py-3 px-2 text-right text-slate-300 text-sm"><?= $proj['budget'] ?>원</td>
                        <td class="py-3 px-2 text-center">
                            <div class="flex items-center gap-1">
                                <div class="flex-1 bg-slate-700 rounded-full h-1.5">
                                    <div class="<?= $proj['progress'] === 100 ? 'bg-amber-500' : 'bg-primary' ?> h-1.5 rounded-full" style="width:<?= $proj['progress'] ?>%"></div>
                                </div>
                                <span class="text-sm text-slate-400 w-8"><?= $proj['progress'] ?>%</span>
                            </div>
                        </td>
                        <td class="py-3 px-2 text-center">
                            <?php
                            $sBadge = match($proj['status']) {
                                '영업' => 'bg-blue-100 text-blue-700',
                                '계약' => 'bg-indigo-100 text-indigo-700',
                                '진행중' => 'bg-primary-light text-primary',
                                '완료' => 'bg-amber-100 text-amber-700',
                                '보류' => 'bg-slate-800 text-slate-300',
                                default => 'bg-slate-800 text-slate-300',
                            };
                            ?>
                            <span class="inline-block px-2 py-0.5 text-sm rounded-full <?= $sBadge ?>"><?= $proj['status'] ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    </main>
</div>

<!-- 사업 등록 모달 -->
<div id="projectModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40" onclick="if(event.target===this)document.getElementById('projectModal').classList.add('hidden')">
    <div class="bg-slate-900 rounded-xl shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800 sticky top-0 bg-slate-900 z-10">
            <h3 class="text-lg font-bold text-slate-100">사업 등록</h3>
            <button onclick="document.getElementById('projectModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="px-6 py-5 space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1">사업명 <span class="text-amber-500">*</span></label>
                <input type="text" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm" placeholder="사업명을 입력해주세요">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1">발주처 <span class="text-amber-500">*</span></label>
                    <input type="text" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm" placeholder="발주처">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1">사업비</label>
                    <input type="text" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm" placeholder="사업비 (원)">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1">시작일 <span class="text-amber-500">*</span></label>
                    <input type="date" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1">종료일 <span class="text-amber-500">*</span></label>
                    <input type="date" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1">담당자</label>
                    <input type="text" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm" placeholder="담당자">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1">부서</label>
                    <select class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm">
                        <option value="">부서 선택</option>
                        <?php foreach ($deptNames as $dn): ?>
                        <option><?= htmlspecialchars($dn) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1">사업 내용</label>
                <textarea rows="4" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm" placeholder="사업 내용을 입력해주세요"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1">첨부파일</label>
                <input type="file" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm" multiple>
            </div>
        </div>
        <div class="flex justify-end gap-2 px-6 py-4 border-t border-slate-800 sticky bottom-0 bg-slate-900">
            <button onclick="document.getElementById('projectModal').classList.add('hidden')" class="btn btn-secondary">취소</button>
            <button class="px-4 py-2 text-sm bg-primary text-white rounded-lg hover:bg-primary/90">등록</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var btnSearch = document.getElementById('btnSearch');
    var btnReset = document.getElementById('btnReset');
    if (!btnSearch || !btnReset) return; // 사업 현황 탭에서는 필터 없음

    btnSearch.addEventListener('click', function() {
        var nameVal = document.getElementById('filterName').value.trim().toLowerCase();
        var clientVal = document.getElementById('filterClient').value.trim().toLowerCase();
        var managerVal = document.getElementById('filterManager').value.trim().toLowerCase();
        var statusVal = document.getElementById('filterStatus').value;

        var rows = document.querySelectorAll('#projectTableBody tr');
        var visibleCount = 0;

        rows.forEach(function(row) {
            var cells = row.querySelectorAll('td');
            if (cells.length < 9) return;

            var rowName = cells[1].textContent.toLowerCase();
            var rowClient = cells[2].textContent.toLowerCase();
            var rowManager = cells[3].textContent.toLowerCase();
            var rowStatus = cells[8].textContent.trim();

            var matchName = !nameVal || rowName.indexOf(nameVal) !== -1;
            var matchClient = !clientVal || rowClient.indexOf(clientVal) !== -1;
            var matchManager = !managerVal || rowManager.indexOf(managerVal) !== -1;
            var matchStatus = statusVal === '전체' || rowStatus === statusVal;

            if (matchName && matchClient && matchManager && matchStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
    });

    btnReset.addEventListener('click', function() {
        document.getElementById('filterName').value = '';
        document.getElementById('filterClient').value = '';
        document.getElementById('filterManager').value = '';
        document.getElementById('filterStatus').value = '전체';

        var rows = document.querySelectorAll('#projectTableBody tr');
        rows.forEach(function(row) {
            row.style.display = '';
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
