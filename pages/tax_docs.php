<?php
$pageTitle = '서류 요청/알림';
$currentPage = 'tax';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$tab = $_GET['tab'] ?? 'list';

// 더미 서류 요청 데이터
$docRequests = [
    ['id'=>1,'doc'=>'2025년 4분기 통장거래내역', 'desc'=>'국민은행 운영계좌 전체 내역 PDF', 'due'=>'2026-03-10', 'status'=>'요청중',    'requester'=>'이세무', 'files'=>[]],
    ['id'=>2,'doc'=>'2025년 12월 급여대장',       'desc'=>'전직원 급여 지급 내역',           'due'=>'2026-03-07', 'status'=>'업로드완료', 'requester'=>'이세무', 'files'=>['급여대장_2025_12.xlsx']],
    ['id'=>3,'doc'=>'사업자등록증 사본',           'desc'=>'최신 발급본 (3개월 이내)',        'due'=>'2026-03-15', 'status'=>'확인완료',   'requester'=>'이세무', 'files'=>['사업자등록증.pdf']],
    ['id'=>4,'doc'=>'2025년 부가세 매입세금계산서','desc'=>'엑셀 또는 PDF 형식',             'due'=>'2026-03-20', 'status'=>'요청중',    'requester'=>'이세무', 'files'=>[]],
    ['id'=>5,'doc'=>'법인 등기부등본',             'desc'=>'말소사항 포함 전부증명서',        'due'=>'2026-03-25', 'status'=>'요청중',    'requester'=>'이세무', 'files'=>[]],
];

// 더미 알림 데이터
$notifications = [
    ['id'=>1,'type'=>'upload',   'title'=>'서류 업로드 완료', 'msg'=>'2025년 12월 급여대장이 업로드되었습니다.',         'url'=>'?tab=list','time'=>'10분 전','read'=>false],
    ['id'=>2,'type'=>'request',  'title'=>'새 서류 요청',    'msg'=>'세무사가 법인 등기부등본을 요청했습니다.',          'url'=>'?tab=list','time'=>'2시간 전','read'=>false],
    ['id'=>3,'type'=>'confirm',  'title'=>'서류 확인 완료',  'msg'=>'사업자등록증 사본이 확인 완료되었습니다.',          'url'=>'?tab=list','time'=>'1일 전','read'=>true],
    ['id'=>4,'type'=>'request',  'title'=>'새 서류 요청',    'msg'=>'세무사가 부가세 매입세금계산서를 요청했습니다.',    'url'=>'?tab=list','time'=>'2일 전','read'=>true],
];

$unreadCount = count(array_filter($notifications, fn($n)=>!$n['read']));

$statusConfig = [
    '요청중'    => ['bg'=>'bg-amber-100','text'=>'text-amber-700','dot'=>'bg-amber-100'],
    '업로드완료'=> ['bg'=>'bg-primary-light',  'text'=>'text-primary',  'dot'=>'bg-primary'],
    '확인완료'  => ['bg'=>'bg-amber-100', 'text'=>'text-amber-700', 'dot'=>'bg-amber-100'],
    '취소'      => ['bg'=>'bg-slate-800',  'text'=>'text-slate-400',  'dot'=>'bg-slate-600'],
];
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6">

        <div class="flex items-center gap-2 mb-5">
            <button onclick="history.back()" class="text-slate-400 hover:text-slate-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <h2 class="text-lg font-bold text-slate-100">서류 요청 / 알림</h2>
            <?php if ($unreadCount > 0): ?>
            <span class="ml-1 px-2 py-0.5 text-sm bg-amber-500 text-white rounded-full font-medium"><?= $unreadCount ?>개의 새 알림</span>
            <?php endif; ?>
        </div>

        <!-- 탭 -->
        <div class="zm-tab-container mb-5">
            <a href="?tab=list"   class="px-5 py-2.5 text-sm font-medium border-b-2 transition-colors <?= $tab==='list'   ? 'approval-tab active' : 'approval-tab' ?>">요청 목록</a>
            <a href="?tab=upload" class="px-5 py-2.5 text-sm font-medium border-b-2 transition-colors <?= $tab==='upload' ? 'approval-tab active' : 'approval-tab' ?>">서류 업로드</a>
            <a href="?tab=notify" class="relative px-5 py-2.5 text-sm font-medium border-b-2 transition-colors <?= $tab==='notify' ? 'approval-tab active' : 'approval-tab' ?>">
                알림 이력
                <?php if ($unreadCount > 0): ?>
                <span class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-amber-500 text-white text-sm rounded-full flex items-center justify-center"><?= $unreadCount ?></span>
                <?php endif; ?>
            </a>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6">

        <?php if ($tab === 'list'): ?>
        <!-- ===== 요청 목록 ===== -->
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-3">
                <div class="flex rounded-lg border border-slate-800 overflow-hidden">
                    <button class="px-3 py-1.5 text-sm bg-primary text-white">전체</button>
                    <button class="px-3 py-1.5 text-sm text-slate-300 hover:bg-slate-950 transition-colors">요청중</button>
                    <button class="px-3 py-1.5 text-sm text-slate-300 hover:bg-slate-950 transition-colors">완료</button>
                </div>
            </div>
            <button onclick="openRequestModal()" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm text-white bg-primary rounded-lg hover:opacity-90">
                <i data-lucide="send" class="w-4 h-4"></i>서류 요청
            </button>
        </div>

        <div class="space-y-3">
            <?php foreach ($docRequests as $doc):
                $sc = $statusConfig[$doc['status']];
            ?>
            <div class="border border-slate-800 rounded-xl p-4 hover:border-gray-400 transition-colors">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-xl <?= $sc['bg'] ?> flex items-center justify-center flex-shrink-0">
                        <i data-lucide="file-text" class="w-5 h-5 <?= $sc['text'] ?>"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h4 class="font-medium text-slate-100"><?= $doc['doc'] ?></h4>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-sm rounded-full whitespace-nowrap <?= $sc['bg'] ?> <?= $sc['text'] ?>">
                                <span class="w-1.5 h-1.5 rounded-full <?= $sc['dot'] ?>"></span>
                                <?= $doc['status'] ?>
                            </span>
                        </div>
                        <p class="text-sm text-slate-400 mt-0.5"><?= $doc['desc'] ?></p>
                        <div class="flex items-center gap-4 mt-2 text-sm text-slate-500">
                            <span class="flex items-center gap-1"><i data-lucide="user" class="w-3 h-3"></i>요청: <?= $doc['requester'] ?></span>
                            <span class="flex items-center gap-1"><i data-lucide="calendar" class="w-3 h-3"></i>기한: <?= $doc['due'] ?></span>
                            <?php if (!empty($doc['files'])): ?>
                            <span class="flex items-center gap-1 text-amber-500"><i data-lucide="paperclip" class="w-3 h-3"></i><?= implode(', ', $doc['files']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <?php if ($doc['status'] === '요청중'): ?>
                        <button onclick="openUploadModal(<?= $doc['id'] ?>, '<?= addslashes($doc['doc']) ?>')" class="px-3 py-1.5 text-sm bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors flex items-center gap-1">
                            <i data-lucide="upload" class="w-3 h-3"></i>업로드
                        </button>
                        <?php elseif ($doc['status'] === '업로드완료'): ?>
                        <button class="px-3 py-1.5 text-sm bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-colors flex items-center gap-1">
                            <i data-lucide="check" class="w-3 h-3"></i>확인
                        </button>
                        <?php endif; ?>
                        <button class="text-slate-600 hover:text-amber-500 transition-colors p-2 rounded-lg hover:bg-amber-50">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php elseif ($tab === 'upload'): ?>
        <!-- ===== 서류 업로드 ===== -->
        <p class="text-sm text-slate-400 mb-5">세무사로부터 요청된 서류를 업로드합니다.</p>
        <div class="space-y-3">
            <?php foreach (array_filter($docRequests, fn($d)=>$d['status']==='요청중') as $doc): ?>
            <div class="border-2 border-dashed border-slate-800 rounded-xl p-5 hover:border-gray-400 transition-colors">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h4 class="font-medium text-slate-100"><?= $doc['doc'] ?></h4>
                        <p class="text-sm text-slate-400 mt-0.5"><?= $doc['desc'] ?></p>
                        <p class="text-sm text-amber-500 mt-1">제출 기한: <?= $doc['due'] ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <label class="flex-1 flex items-center justify-center gap-2 px-4 py-3 border border-slate-800 rounded-xl cursor-pointer hover:bg-slate-950 transition-colors">
                        <i data-lucide="upload-cloud" class="w-4 h-4 text-slate-500"></i>
                        <span class="text-sm text-slate-400">파일 선택 또는 드래그</span>
                        <input type="file" class="hidden" multiple>
                    </label>
                    <button onclick="submitUpload(<?= $doc['id'] ?>)" class="px-4 py-3 text-sm text-white bg-primary rounded-xl hover:bg-primary-dark transition-colors whitespace-nowrap flex items-center gap-1.5">
                        <i data-lucide="send" class="w-4 h-4"></i>전송
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php else: ?>
        <!-- ===== 알림 이력 ===== -->
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-slate-400">서류 요청 및 업로드 알림 이력입니다.</p>
            <button class="text-sm text-slate-500 hover:text-gray-900 transition-colors">전체 읽음</button>
        </div>
        <div class="space-y-2">
            <?php
            $typeIcons = ['upload'=>'upload','request'=>'send','confirm'=>'check-circle'];
            $typeBg = ['upload'=>'bg-primary-light text-primary','request'=>'bg-amber-100 text-amber-500','confirm'=>'bg-amber-100 text-amber-500'];
            foreach ($notifications as $n):
            ?>
            <div class="flex items-start gap-3 p-4 rounded-xl <?= !$n['read'] ? 'bg-primary-light border border-primary-light' : 'border border-slate-800 hover:bg-slate-950' ?> transition-colors cursor-pointer">
                <div class="w-9 h-9 rounded-full <?= $typeBg[$n['type']] ?> flex items-center justify-center flex-shrink-0">
                    <i data-lucide="<?= $typeIcons[$n['type']] ?>" class="w-4 h-4"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <p class="text-sm font-medium text-slate-100"><?= $n['title'] ?></p>
                        <?php if (!$n['read']): ?>
                        <span class="w-2 h-2 rounded-full bg-primary"></span>
                        <?php endif; ?>
                    </div>
                    <p class="text-sm text-slate-400 mt-0.5"><?= $n['msg'] ?></p>
                    <p class="text-sm text-slate-500 mt-1"><?= $n['time'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        </div>
    </main>
</div>

<!-- 서류 요청 모달 -->
<div id="requestModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeRequestModal()"></div>
    <div class="relative bg-slate-900 rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800">
            <h3 class="text-base font-bold text-slate-100">서류 요청</h3>
            <button onclick="closeRequestModal()" class="text-slate-500 hover:text-slate-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1.5">서류명 <span class="text-amber-500">*</span></label>
                <input type="text" id="reqDocName" placeholder="예: 2025년 4분기 통장거래내역" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1.5">요청 내용</label>
                <textarea id="reqDocDesc" rows="3" placeholder="제출 형식, 기간 등 상세 안내" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30 resize-none"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1.5">제출 기한</label>
                <input type="date" id="reqDueDate" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30">
            </div>
            <div class="p-3 bg-primary-light rounded-lg text-sm text-primary flex items-center gap-2">
                <i data-lucide="bell" class="w-3.5 h-3.5 flex-shrink-0"></i>
                요청 즉시 담당자에게 알림이 발송됩니다.
            </div>
        </div>
        <div class="flex gap-2 px-6 pb-5 justify-end">
            <button onclick="closeRequestModal()" class="btn btn-secondary">취소</button>
            <button onclick="sendRequest()" class="px-4 py-2 text-sm text-white bg-primary rounded-lg hover:opacity-90 flex items-center gap-1.5">
                <i data-lucide="send" class="w-4 h-4"></i>요청 전송
            </button>
        </div>
    </div>
</div>

<!-- 파일 업로드 모달 -->
<div id="uploadModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeUploadModal()"></div>
    <div class="relative bg-slate-900 rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800">
            <div>
                <h3 class="text-base font-bold text-slate-100">파일 업로드</h3>
                <p class="text-sm text-slate-500 mt-0.5" id="uploadDocName"></p>
            </div>
            <button onclick="closeUploadModal()" class="text-slate-500 hover:text-slate-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6">
            <div class="border-2 border-dashed border-slate-700 rounded-xl p-8 text-center hover:border-gray-400 hover:bg-gray-100 transition-colors cursor-pointer"
                 onclick="document.getElementById('uploadFileInput').click()">
                <i data-lucide="upload-cloud" class="w-10 h-10 text-slate-600 mx-auto mb-3"></i>
                <p class="text-sm text-slate-300">파일을 드래그하거나 클릭하여 선택</p>
                <p class="text-sm text-slate-500 mt-1">PDF, Excel, 이미지 파일 지원</p>
                <input type="file" id="uploadFileInput" class="hidden" multiple>
            </div>
            <div id="uploadFileList" class="mt-3 space-y-2 hidden"></div>
        </div>
        <div class="flex gap-2 px-6 pb-5 justify-end">
            <button onclick="closeUploadModal()" class="btn btn-secondary">취소</button>
            <button onclick="confirmUpload()" class="px-4 py-2 text-sm text-white bg-primary rounded-lg hover:bg-primary-dark flex items-center gap-1.5">
                <i data-lucide="upload" class="w-4 h-4"></i>업로드
            </button>
        </div>
    </div>
</div>

<script>
let currentUploadDocId = null;

function openRequestModal() {
    document.getElementById('requestModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeRequestModal() {
    document.getElementById('requestModal').classList.add('hidden');
    document.body.style.overflow = '';
}
function sendRequest() {
    const name = document.getElementById('reqDocName').value.trim();
    if (!name) { alert('서류명을 입력하세요.'); return; }
    alert(`"${name}" 서류 요청이 전송되었습니다.\n담당자에게 알림이 발송됩니다.`);
    closeRequestModal();
}

function openUploadModal(docId, docName) {
    currentUploadDocId = docId;
    document.getElementById('uploadDocName').textContent = docName;
    document.getElementById('uploadFileList').classList.add('hidden');
    document.getElementById('uploadFileInput').value = '';
    document.getElementById('uploadModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeUploadModal() {
    document.getElementById('uploadModal').classList.add('hidden');
    document.body.style.overflow = '';
}

document.getElementById('uploadFileInput').addEventListener('change', function() {
    const list = document.getElementById('uploadFileList');
    list.innerHTML = '';
    Array.from(this.files).forEach(f => {
        list.innerHTML += `<div class="flex items-center gap-2 p-2 bg-slate-950 rounded-lg text-sm">
            <i data-lucide="file" class="w-4 h-4 text-slate-500"></i>
            <span class="flex-1 truncate text-slate-200">${f.name}</span>
            <span class="text-sm text-slate-500">${(f.size/1024).toFixed(1)}KB</span>
        </div>`;
    });
    if (this.files.length > 0) {
        list.classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }
});

function confirmUpload() {
    alert('파일이 업로드되었습니다.\n세무사에게 알림이 전송됩니다.');
    closeUploadModal();
}

function submitUpload(docId) { openUploadModal(docId, ''); }

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closeRequestModal(); closeUploadModal(); }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
