<?php
$pageTitle = '사내게시판';
$currentPage = 'board';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$boardTypes = [
    'notice'     => ['name' => '공지사항',   'icon' => 'megaphone',      'emptyMsg' => '등록된 공지사항이 없습니다'],
    'free'       => ['name' => '자유게시판', 'icon' => 'message-square', 'emptyMsg' => '등록된 게시글이 없습니다'],
    'archive'    => ['name' => '자료실',     'icon' => 'folder-open',    'emptyMsg' => '등록된 자료가 없습니다'],
    'department' => ['name' => getOrgLabel('department') . '게시판', 'icon' => 'building-2',     'emptyMsg' => '등록된 게시글이 없습니다'],
];
$rawType   = $_GET['type'] ?? 'notice';
$boardType = isset($boardTypes[$rawType]) ? $rawType : 'notice';

$categories = [
    'notice'     => ['전체', '공지', '안내', '중요'],
    'free'       => ['전체', '일반', '건의', '기타'],
    'archive'    => ['전체', '양식', '매뉴얼', '참고자료'],
];

$catColors = [
    '중요'   => 'bg-amber-100 text-amber-700',
    '공지'   => 'bg-primary-light text-primary',
    '안내'   => 'bg-amber-100 text-amber-700',
    '일반'   => 'bg-slate-800 text-slate-300',
    '건의'   => 'bg-amber-100 text-amber-700',
    '기타'   => 'bg-slate-800 text-slate-300',
    '양식'   => 'bg-primary-light text-primary',
    '매뉴얼' => 'bg-primary-light text-primary',
    '참고자료'=> 'bg-primary-light text-primary',
];

require_once __DIR__ . '/../config/database.php';
$sessUser = $_SESSION['user'] ?? null;
$userRole = (string)($sessUser['role'] ?? 'user');
$userDeptId = (int)($sessUser['department_id'] ?? 0);
$isManager = in_array($userRole, ['admin', 'manager'], true);
$isDeptBoard = ($boardType === 'department');

$currentUser = $sessUser
    ? [
        'id'   => (int)($sessUser['id'] ?? 0),
        'name' => (string)($sessUser['name'] ?? ''),
        'dept' => (string)($sessUser['department_name'] ?? $sessUser['dept'] ?? ''),
      ]
    : ['id' => 0, 'name' => '', 'dept' => ''];

$HAS_DB = false;
$departments = [];
$userDeptName = '';
$isDeptHead = false;
try {
    $pdo = getDBConnection();
    if ($pdo) {
        $HAS_DB = true;
        if ($currentUser['id'] > 0 && empty($currentUser['dept'])) {
            $ds = $pdo->prepare("SELECT COALESCE(d.name, '') AS dept_name FROM employees e LEFT JOIN departments d ON e.department_id = d.id WHERE e.id = ? LIMIT 1");
            $ds->execute([$currentUser['id']]);
            $row = $ds->fetch();
            if ($row) $currentUser['dept'] = (string)$row['dept_name'];
        }

        // 부서게시판 + 관리자: 부서 목록 로드
        if ($isDeptBoard) {
            $deptStmt = $pdo->query("SELECT id, name FROM departments WHERE parent_id IS NOT NULL ORDER BY sort_order, name");
            $departments = $deptStmt->fetchAll();

            if ($userDeptId > 0) {
                $dn = $pdo->prepare("SELECT name FROM departments WHERE id = ?");
                $dn->execute([$userDeptId]);
                $r = $dn->fetch();
                if ($r) $userDeptName = $r['name'];

                $empId = (int)($sessUser['id'] ?? 0);
                if ($empId > 0) {
                    $hd = $pdo->prepare("SELECT 1 FROM employees WHERE id = ? AND department_id = ? AND is_dept_head = 1 AND is_active = 1");
                    $hd->execute([$empId, $userDeptId]);
                    $isDeptHead = (bool)$hd->fetchColumn();
                }
            }
        }
    }
} catch (PDOException $e) {
    error_log('[Board] 초기화 실패: ' . $e->getMessage());
}
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6">

        <!-- 상단 헤더: 제목 + 글쓰기 버튼 -->
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-primary flex items-center justify-center">
                    <i data-lucide="<?= $boardTypes[$boardType]['icon'] ?>" class="w-[18px] h-[18px] text-white"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-100">
                        <?= htmlspecialchars($boardTypes[$boardType]['name']) ?>
                        <?php if ($isDeptBoard && !$isManager && $userDeptName): ?>
                            <span class="text-base font-normal text-slate-400 ml-1">— <?= htmlspecialchars($userDeptName) ?></span>
                        <?php endif; ?>
                    </h2>
                    <p class="text-sm text-slate-400 mt-0.5">총 <span id="totalBadge" class="font-semibold text-primary">0</span>건</p>
                </div>
            </div>
            <button id="btnWrite" onclick="openCreateModal()" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-primary rounded-lg hover:opacity-90 transition-colors shadow-sm">
                <i data-lucide="plus" class="w-4 h-4"></i>
                글쓰기
            </button>
        </div>

        <!-- 검색바 + 카테고리/부서 선택 -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 mb-4">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-2 flex-wrap">
                    <?php if ($isDeptBoard && $isManager): ?>
                        <!-- 부서게시판 관리자: 부서 선택 드롭다운 -->
                        <select id="deptFilter" onchange="changeDeptFilter()"
                                class="text-sm border border-slate-700 rounded-lg px-3 py-2 bg-slate-900 text-slate-200 outline-none focus:border-gray-300">
                            <?php if ($userDeptId > 0): ?>
                                <option value="<?= $userDeptId ?>">내 <?= htmlspecialchars(getOrgLabel('department')) ?><?php if ($userDeptName): ?> (<?= htmlspecialchars($userDeptName) ?>)<?php endif; ?></option>
                            <?php endif; ?>
                            <?php foreach ($departments as $dept): ?>
                                <?php if ((int)$dept['id'] === $userDeptId) continue; ?>
                                <option value="<?= (int)$dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif (!$isDeptBoard): ?>
                        <!-- 일반 게시판: 카테고리 칩 -->
                        <?php foreach ($categories[$boardType] as $i => $cat): ?>
                        <button onclick="filterCategory(this, '<?= htmlspecialchars($cat, ENT_QUOTES) ?>')"
                                class="cat-chip zm-tab px-3.5 py-1.5 text-sm rounded-full border transition-colors <?= $i === 0 ? 'zm-tab-active' : 'border-slate-700' ?>">
                            <?= htmlspecialchars($cat) ?>
                        </button>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="flex items-center gap-2">
                    <select id="searchField" class="text-sm border border-slate-700 rounded-lg px-3 py-2 bg-slate-900 text-slate-200 outline-none focus:border-gray-300">
                        <option value="title">제목</option>
                        <option value="author">작성자</option>
                        <option value="content">내용</option>
                    </select>
                    <div class="relative">
                        <input type="text" id="searchInput" placeholder="검색어를 입력하세요"
                               class="w-56 pl-3 pr-9 py-2 text-sm border border-slate-700 rounded-lg outline-none focus:border-gray-300 transition-colors"
                               onkeydown="if(event.key==='Enter') searchPosts()">
                        <button onclick="searchPosts()" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-gray-900 transition-colors">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 게시글 목록 -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden">
            <div class="list-info-bar">
                <p class="info-text">전체<strong id="infoTotal">0</strong>건</p>
                <select id="perPageSelect" class="list-per-page" onchange="changePerPage()">
                    <option value="10">10개씩</option>
                    <option value="20">20개씩</option>
                    <option value="50">50개씩</option>
                </select>
            </div>

            <table class="w-full text-sm emp-table">
                <thead>
                    <tr>
                        <th class="py-3 px-4 text-center w-[6%]">번호</th>
                        <?php if (!$isDeptBoard): ?>
                        <th class="py-3 px-4 text-center w-[10%]">카테고리</th>
                        <?php endif; ?>
                        <th class="py-3 px-4 text-left">제목</th>
                        <th class="py-3 px-4 text-center w-[10%]">작성자</th>
                        <th class="py-3 px-4 text-center w-[10%]">등록일</th>
                        <th class="py-3 px-4 text-center w-[7%]">조회</th>
                    </tr>
                </thead>
                <tbody id="postBody">
                    <tr><td colspan="<?= $isDeptBoard ? 5 : 6 ?>" class="py-20 text-center text-slate-500">로딩 중...</td></tr>
                </tbody>
            </table>

            <!-- 페이지네이션 -->
            <div id="pagination" class="flex items-center justify-center gap-1 py-4 border-t border-slate-800"></div>
        </div>

    </main>
</div>

<!-- ========== 글쓰기 모달 ========== -->
<div id="createModal" class="fixed inset-0 z-[9999] hidden">
    <div class="absolute inset-0 bg-black/40" onclick="closeModals()"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-xl bg-slate-900 rounded-2xl shadow-2xl overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800 bg-slate-950">
            <h3 class="text-base font-bold text-slate-100">글쓰기</h3>
            <button onclick="closeModals()" class="p-2 -mr-2 rounded-lg text-slate-500 hover:text-slate-300 hover:bg-slate-700 transition-colors"><i data-lucide="x" class="w-5 h-5 pointer-events-none"></i></button>
        </div>
        <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
            <?php if (!$isDeptBoard && isset($categories[$boardType])): ?>
            <div>
                <label class="block text-sm font-semibold text-slate-200 mb-1">카테고리</label>
                <select id="createCat" class="w-full border border-slate-700 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-gray-300">
                    <?php foreach (array_slice($categories[$boardType], 1) as $cat): ?>
                    <option value="<?= htmlspecialchars($cat, ENT_QUOTES) ?>"><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div>
                <label class="block text-sm font-semibold text-slate-200 mb-1">제목</label>
                <input type="text" id="createTitle" placeholder="제목을 입력하세요" maxlength="300"
                       class="w-full border border-slate-700 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-gray-300">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-200 mb-1">내용</label>
                <textarea id="createContent" rows="8" placeholder="내용을 입력하세요"
                          class="w-full border border-slate-700 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-gray-300 resize-y"></textarea>
            </div>
            <?php if ($isManager || ($isDeptBoard && $isDeptHead)): ?>
            <div class="flex items-center gap-2">
                <input type="checkbox" id="createPinned" class="w-4 h-4 text-primary border-slate-700 rounded focus:ring-gray-300">
                <label for="createPinned" class="text-sm text-slate-200">공지글로 등록 (상단 고정)</label>
            </div>
            <?php endif; ?>
        </div>
        <!-- 첨부파일 영역 -->
        <div class="p-6 pt-0 space-y-2">
            <div class="flex items-center justify-between">
                <label class="block text-sm font-semibold text-slate-200">첨부파일</label>
                <button onclick="addCreateFile()" class="inline-flex items-center gap-1 text-sm text-primary hover:opacity-80">
                    <i data-lucide="plus" class="w-4 h-4"></i> 파일 추가
                </button>
            </div>
            <div id="createFileList" class="space-y-1.5">
                <p class="text-sm text-slate-500">선택된 파일이 없습니다</p>
            </div>
            <p class="text-xs text-slate-500">최대 5개, 파일당 10MB (pdf, doc, hwp, xls, ppt, txt, zip, jpg, png, gif)</p>
        </div>
        <div class="flex justify-end gap-2 px-6 py-4 border-t border-slate-800 bg-slate-950">
            <button onclick="closeModals()" class="btn btn-secondary">취소</button>
            <button onclick="submitCreate()" class="px-5 py-2.5 text-sm font-semibold text-white bg-primary rounded-lg hover:opacity-90">등록</button>
        </div>
    </div>
</div>

<!-- ========== 상세 모달 ========== -->
<div id="detailModal" class="fixed inset-0 z-[9999] hidden">
    <div class="absolute inset-0 bg-black/40" onclick="closeModals()"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-2xl bg-slate-900 rounded-2xl shadow-2xl overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800 bg-slate-950">
            <h3 id="detailModalTitle" class="text-base font-bold text-slate-100">게시글 상세</h3>
            <button onclick="closeModals()" class="p-2 -mr-2 rounded-lg text-slate-500 hover:text-slate-300 hover:bg-slate-700 transition-colors"><i data-lucide="x" class="w-5 h-5 pointer-events-none"></i></button>
        </div>
        <!-- 읽기 모드 -->
        <div id="detailView" class="p-6 max-h-[70vh] overflow-y-auto">
            <div class="flex items-center gap-3 mb-4">
                <span id="detailCatBadge" class="inline-block px-2.5 py-0.5 text-sm font-semibold rounded-full"></span>
                <span id="detailPinBadge" class="hidden inline-flex items-center gap-1 text-sm text-primary font-semibold">
                    <i data-lucide="pin" class="w-3 h-3"></i> 고정
                </span>
            </div>
            <h4 id="detailTitle" class="text-lg font-bold text-slate-100 mb-3"></h4>
            <div class="flex items-center gap-4 text-sm text-slate-400 mb-5 pb-4 border-b border-slate-800">
                <span id="detailAuthor"></span>
                <span id="detailDept" class="text-slate-500"></span>
                <span id="detailDate"></span>
                <span id="detailViews"></span>
            </div>
            <div id="detailContent" class="text-sm text-slate-200 leading-relaxed whitespace-pre-wrap"></div>

            <!-- 첨부파일 -->
            <div id="detailAttachments" class="hidden mt-5 pt-4 border-t border-slate-800">
                <p class="text-sm font-semibold text-slate-300 mb-2 flex items-center gap-1.5">
                    <i data-lucide="paperclip" class="w-4 h-4"></i>
                    첨부파일
                </p>
                <div class="att-list space-y-1"></div>
            </div>

            <!-- 댓글 -->
            <div id="detailComments" class="hidden mt-5 pt-4 border-t border-slate-800">
                <p class="text-sm font-semibold text-slate-300 mb-2 flex items-center gap-1.5">
                    <i data-lucide="message-circle" class="w-4 h-4"></i>
                    댓글 <span class="comment-count text-primary">0</span>
                </p>
                <div class="comment-list"></div>
                <div class="mt-3 flex gap-2">
                    <input type="text" id="commentInput" placeholder="댓글을 입력하세요" maxlength="2000"
                           class="flex-1 border border-slate-700 rounded-lg px-3 py-2 text-sm outline-none focus:border-gray-300"
                           onkeydown="if(event.key==='Enter') submitComment()">
                    <button onclick="submitComment()" class="px-4 py-2 text-sm font-semibold text-white bg-primary rounded-lg hover:opacity-90 flex-shrink-0">등록</button>
                </div>
            </div>
        </div>
        <!-- 수정 모드 -->
        <div id="editView" class="p-6 space-y-4 max-h-[70vh] overflow-y-auto hidden">
            <input type="hidden" id="editId">
            <div>
                <label class="block text-sm font-semibold text-slate-200 mb-1">제목</label>
                <input type="text" id="editTitle" maxlength="300"
                       class="w-full border border-slate-700 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-gray-300">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-200 mb-1">내용</label>
                <textarea id="editContent" rows="8"
                          class="w-full border border-slate-700 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-gray-300 resize-y"></textarea>
            </div>
            <?php if ($isManager || ($isDeptBoard && $isDeptHead)): ?>
            <div class="flex items-center gap-2">
                <input type="checkbox" id="editPinned" class="w-4 h-4 text-primary border-slate-700 rounded focus:ring-gray-300">
                <label for="editPinned" class="text-sm text-slate-200">공지글 (상단 고정)</label>
            </div>
            <?php endif; ?>
            <!-- 수정 모드 첨부파일 -->
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-sm font-semibold text-slate-200">첨부파일</label>
                    <button onclick="addEditFile()" class="inline-flex items-center gap-1 text-sm text-primary hover:opacity-80">
                        <i data-lucide="plus" class="w-4 h-4"></i> 파일 추가
                    </button>
                </div>
                <div id="editExistingFiles" class="space-y-1.5"></div>
                <div id="editNewFiles" class="space-y-1.5"></div>
                <p class="text-xs text-slate-500 mt-1">최대 5개, 파일당 10MB</p>
            </div>
        </div>
        <!-- 하단 버튼 -->
        <div class="flex justify-between px-6 py-4 border-t border-slate-800 bg-slate-950">
            <div id="detailBtnLeft">
                <button id="btnDelete" onclick="submitDelete()" class="hidden px-4 py-2.5 text-sm font-medium text-amber-700 bg-slate-900 border border-amber-200 rounded-lg hover:bg-amber-50">삭제</button>
            </div>
            <div class="flex gap-2">
                <!-- 읽기 모드 버튼 -->
                <div id="detailBtnRead" class="flex gap-2">
                    <button onclick="closeModals()" class="btn btn-secondary">닫기</button>
                    <button id="btnEdit" onclick="switchToEdit()" class="hidden px-5 py-2.5 text-sm font-semibold text-white bg-primary rounded-lg hover:opacity-90">수정</button>
                </div>
                <!-- 수정 모드 버튼 -->
                <div id="detailBtnEdit" class="flex gap-2 hidden">
                    <button onclick="switchToRead()" class="btn btn-secondary">취소</button>
                    <button onclick="submitUpdate()" class="px-5 py-2.5 text-sm font-semibold text-white bg-primary rounded-lg hover:opacity-90">저장</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 토스트 -->
<div id="toast" class="fixed bottom-6 right-6 z-[10000] hidden">
    <div class="flex items-center gap-2 px-5 py-3 rounded-xl shadow-lg text-sm font-medium text-white bg-slate-700">
        <i data-lucide="check-circle" class="w-4 h-4"></i>
        <span id="toastMsg"></span>
    </div>
</div>

<script>
const API_URL   = '<?= $basePath ?>/api/board.php';
const BOARD_TYPE = '<?= $boardType ?>';
const HAS_DB    = <?= $HAS_DB ? 'true' : 'false' ?>;
const CURRENT_USER = <?= json_encode($currentUser, JSON_UNESCAPED_UNICODE) ?>;
const EMPTY_MSG = '<?= addslashes($boardTypes[$boardType]['emptyMsg']) ?>';
const CAT_COLORS = <?= json_encode($catColors, JSON_UNESCAPED_UNICODE) ?>;
const NEW_THRESHOLD = '<?= date('Y-m-d', strtotime('-3 days')) ?>';
const IS_DEPT_BOARD = <?= $isDeptBoard ? 'true' : 'false' ?>;
const IS_MANAGER = <?= $isManager ? 'true' : 'false' ?>;
const COL_SPAN = IS_DEPT_BOARD ? 5 : 6;
window.__BOARD_DEPT_FILTER_ID = <?= $isDeptBoard && $isManager && $userDeptId > 0 ? $userDeptId : 0 ?>;
</script>
<script src="<?= $basePath ?>/assets/js/board.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
