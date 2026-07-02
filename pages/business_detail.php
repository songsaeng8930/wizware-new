<?php
$pageTitle = '사업 상세';
$currentPage = 'business';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// $basePath 는 includes/header.php 에서 프로젝트 루트로 이미 설정됨.
// dirname(SCRIPT_NAME) 로 덮으면 `$basePath . '/pages/...'` 가 중복되어 링크가 깨진다.
$projectId = $_GET['id'] ?? 8;

// DB에서 직원 이름 가져오기 (담당자/팀원 할당용)
require_once __DIR__ . '/../config/database.php';
$empList = [];
try {
    $pdo = getDBConnection();
    if ($pdo) {
        $stmt = $pdo->query("
            SELECT e.name, e.position, d.name AS department_name
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            WHERE e.is_active = 1
            ORDER BY FIELD(e.position, '대표이사','이사','부장','차장','과장','대리','주임','사원','인턴'), e.id ASC
            LIMIT 6
        ");
        $empList = $stmt->fetchAll();
    }
} catch (PDOException $e) {}

$members = [];
foreach ($empList as $emp) {
    $members[] = $emp['name'] . ' (' . ($emp['department_name'] ?: '') . ' ' . ($emp['position'] ?: '') . ')';
}

// 시범 사업 상세 데이터 (담당자/팀원은 DB 직원)
$project = [
    'no' => 8, 'name' => 'Zaemit 기업관리 패키지 개발', 'client' => '(주)위즈웨어',
    'manager' => $empList[0]['name'] ?? '미지정', 'dept' => '개발', 'startDate' => '2025-11-01', 'endDate' => '2026-06-30',
    'budget' => '120,000,000', 'status' => '진행중', 'progress' => 65,
    'description' => '기업 관리를 위한 통합 그룹웨어 패키지 개발. 인사, 근태, 전자결재, 노무관리, 법인카드, 일정, 사업, 자원예약, 게시판 등 기업 운영에 필요한 전반적인 기능을 포함한다.',
    'members' => $members ?: ['팀원 미지정'],
];

// 더미 마일스톤
$milestones = [
    ['name' => '요구사항 분석', 'start' => '2025-11-01', 'end' => '2025-11-30', 'status' => '완료', 'progress' => 100],
    ['name' => 'UI/UX 설계', 'start' => '2025-12-01', 'end' => '2026-01-15', 'status' => '완료', 'progress' => 100],
    ['name' => '프론트엔드 개발', 'start' => '2026-01-16', 'end' => '2026-03-31', 'status' => '진행중', 'progress' => 80],
    ['name' => '백엔드 개발', 'start' => '2026-02-01', 'end' => '2026-04-30', 'status' => '진행중', 'progress' => 55],
    ['name' => '통합 테스트', 'start' => '2026-05-01', 'end' => '2026-05-31', 'status' => '대기', 'progress' => 0],
    ['name' => '배포 및 안정화', 'start' => '2026-06-01', 'end' => '2026-06-30', 'status' => '대기', 'progress' => 0],
];

// 비용 집행 내역
$expenses = [
    ['date' => '2026-02-20', 'item' => '클라우드 서버 비용 (2월)', 'category' => '사업수행비', 'amount' => '850,000'],
    ['date' => '2026-02-15', 'item' => '디자인 외주 비용', 'category' => '사업수행비', 'amount' => '3,500,000'],
    ['date' => '2026-01-30', 'item' => '개발 장비 구매', 'category' => '사업수행비', 'amount' => '2,400,000'],
    ['date' => '2026-01-20', 'item' => '클라우드 서버 비용 (1월)', 'category' => '사업수행비', 'amount' => '850,000'],
    ['date' => '2025-12-10', 'item' => '프로젝트 관리 툴 연간 구독', 'category' => '공통비', 'amount' => '1,200,000'],
];
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6">

        <!-- 페이지 제목 -->
        <div class="flex items-center gap-2 mb-5">
            <a href="<?= $basePath ?>/pages/business.php" class="text-slate-400 hover:text-slate-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h2 class="text-lg font-bold text-slate-100">사업 상세</h2>
        </div>

        <!-- 사업 기본 정보 -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 mb-5">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="text-xl font-bold text-slate-100 mb-1"><?= $project['name'] ?></h3>
                    <p class="text-sm text-slate-400"><?= $project['client'] ?></p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block px-3 py-1 text-sm rounded-full bg-primary-light text-primary font-medium"><?= $project['status'] ?></span>
                    <button class="btn btn-secondary">수정</button>
                    <button class="px-4 py-2 text-sm border border-amber-200 text-amber-500 rounded-lg hover:bg-amber-50 transition-colors">삭제</button>
                </div>
            </div>

            <!-- 진행률 -->
            <div class="mb-5">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm text-slate-300">전체 진행률</span>
                    <span class="text-sm font-bold text-primary"><?= $project['progress'] ?>%</span>
                </div>
                <div class="w-full bg-slate-700 rounded-full h-3">
                    <div class="bg-primary h-3 rounded-full transition-all" style="width:<?= $project['progress'] ?>%"></div>
                </div>
            </div>

            <div class="grid grid-cols-4 gap-6 text-sm">
                <div>
                    <p class="text-slate-400 mb-0.5">담당자</p>
                    <p class="font-medium text-slate-100"><?= $project['manager'] ?> (<?= $project['dept'] ?>)</p>
                </div>
                <div>
                    <p class="text-slate-400 mb-0.5">시작일</p>
                    <p class="font-medium text-slate-100"><?= $project['startDate'] ?></p>
                </div>
                <div>
                    <p class="text-slate-400 mb-0.5">종료일</p>
                    <p class="font-medium text-slate-100"><?= $project['endDate'] ?></p>
                </div>
                <div>
                    <p class="text-slate-400 mb-0.5">사업비</p>
                    <p class="font-medium text-slate-100"><?= $project['budget'] ?>원</p>
                </div>
            </div>

            <div class="mt-4 text-sm">
                <p class="text-slate-400 mb-1">사업 내용</p>
                <p class="text-slate-200 leading-relaxed"><?= $project['description'] ?></p>
            </div>

            <div class="mt-4 text-sm">
                <p class="text-slate-400 mb-1">투입 인원</p>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($project['members'] as $member): ?>
                        <span class="inline-block px-2.5 py-1 bg-slate-800 text-slate-200 rounded-full text-sm"><?= $member ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-5">
            <!-- 마일스톤 -->
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                <h3 class="text-sm font-semibold text-slate-100 mb-4">
                    <i data-lucide="flag" class="text-primary mr-1 w-4 h-4"></i> 마일스톤
                </h3>
                <div class="space-y-3">
                    <?php foreach ($milestones as $ms):
                        $msColor = match($ms['status']) {
                            '완료' => 'border-amber-200 bg-amber-50/50',
                            '진행중' => 'border-primary/30 bg-primary-light/50',
                            default => 'border-slate-800',
                        };
                        $barColor = match($ms['status']) {
                            '완료' => 'bg-amber-500',
                            '진행중' => 'bg-primary',
                            default => 'bg-slate-700',
                        };
                        $statusBadge = match($ms['status']) {
                            '완료' => 'bg-amber-100 text-amber-700',
                            '진행중' => 'bg-primary-light text-primary',
                            default => 'bg-slate-800 text-slate-400',
                        };
                    ?>
                    <div class="border <?= $msColor ?> rounded-lg p-3">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-slate-100"><?= $ms['name'] ?></span>
                            <span class="text-sm px-2 py-0.5 rounded-full <?= $statusBadge ?>"><?= $ms['status'] ?></span>
                        </div>
                        <div class="flex items-center gap-2 mb-1">
                            <div class="flex-1 bg-slate-700 rounded-full h-1.5">
                                <div class="<?= $barColor ?> h-1.5 rounded-full" style="width:<?= $ms['progress'] ?>%"></div>
                            </div>
                            <span class="text-sm text-slate-400"><?= $ms['progress'] ?>%</span>
                        </div>
                        <p class="text-sm text-slate-400"><?= $ms['start'] ?> ~ <?= $ms['end'] ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 비용 집행 내역 -->
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-slate-100">
                        <i data-lucide="coins" class="text-primary mr-1 w-4 h-4"></i> 비용 집행 내역
                    </h3>
                    <button class="text-sm text-gray-600 hover:underline">+ 비용 등록</button>
                </div>
                <table class="w-full text-sm emp-table">
                    <thead>
                        <tr class="border-b-2 border-slate-800">
                            <th class="py-2 px-2 text-center font-medium text-slate-300 text-sm">일자</th>
                            <th class="py-2 px-2 text-center font-medium text-slate-300 text-sm">항목</th>
                            <th class="py-2 px-2 text-center font-medium text-slate-300 text-sm">구분</th>
                            <th class="py-2 px-2 text-center font-medium text-slate-300 text-sm">금액</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $exp): ?>
                        <tr class="border-b border-slate-800">
                            <td class="py-2 px-2 text-center text-slate-400 text-sm"><?= $exp['date'] ?></td>
                            <td class="py-2 px-2 text-slate-200 text-sm"><?= $exp['item'] ?></td>
                            <td class="py-2 px-2 text-center text-sm">
                                <span class="px-1.5 py-0.5 rounded bg-slate-800 text-slate-300"><?= $exp['category'] ?></span>
                            </td>
                            <td class="py-2 px-2 text-right text-slate-200 text-sm"><?= $exp['amount'] ?>원</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-slate-800">
                            <td colspan="3" class="py-2 px-2 text-right text-sm font-medium text-slate-200">집행 합계</td>
                            <td class="py-2 px-2 text-right text-sm font-bold text-primary">8,800,000원</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
