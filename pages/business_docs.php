<?php
$pageTitle = '업무자료실/의뢰';
$currentPage = 'business_docs';
require_once __DIR__ . '/../includes/permissions.php';
requireMenuPermission('business_docs', 'view'); // 접근권한 관리 연동 (admin 항상 통과)
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// $basePath 는 includes/header.php 에서 프로젝트 루트로 이미 설정됨.
// dirname(SCRIPT_NAME) 로 덮으면 `$basePath . '/pages/...'` 가 중복되어 링크가 깨진다.
$tab = $_GET['tab'] ?? 'hr_labor';
$doc = $_GET['doc'] ?? null;

// 상세 콘텐츠 데이터 로드
require_once __DIR__ . '/business_docs_content.php';

$tabs = [
    'hr_labor' => '인사/노무',
    'research' => '기업연구소/벤처',
    'patent' => '특허/상표/디자인',
    'appraisal' => '감정평가',
    'legal' => '법무(등기)',
    'shareholder' => '주주총회',
    'tax' => '절세',
    'subsidy' => '고용지원금',
];

// 각 카테고리별 자료 목록 (창업자를 위한 실무 자료)
$docData = [
    'hr_labor' => [
        ['no' => 8, 'title' => '스타트업 첫 직원 채용 시 반드시 알아야 할 10가지', 'type' => '가이드', 'author' => '재밋 노무사', 'date' => '2026-03-25', 'status' => '등록완료'],
        ['no' => 7, 'title' => '2026년 최저임금 적용 가이드 (시급/월급/주휴수당 계산)', 'type' => '자료', 'author' => '재밋 노무사', 'date' => '2026-03-18', 'status' => '등록완료'],
        ['no' => 6, 'title' => '표준 근로계약서 양식 (정규직/계약직/단시간/인턴)', 'type' => '양식', 'author' => '재밋 노무사', 'date' => '2026-03-10', 'status' => '등록완료'],
        ['no' => 5, 'title' => '4대보험 최초 가입 및 취득신고 완전 가이드', 'type' => '가이드', 'author' => '재밋 노무사', 'date' => '2026-02-28', 'status' => '등록완료'],
        ['no' => 4, 'title' => '취업규칙 작성 가이드 (10인 이상 사업장 필수)', 'type' => '가이드', 'author' => '재밋 노무사', 'date' => '2026-02-20', 'status' => '등록완료'],
        ['no' => 3, 'title' => '주 52시간 근무제 핵심 Q&A · 5인 미만 vs 5인 이상 차이', 'type' => '자료', 'author' => '재밋 노무사', 'date' => '2026-02-15', 'status' => '등록완료'],
        ['no' => 2, 'title' => '직장 내 괴롭힘·성희롱 예방 교육 자료 (법정의무교육)', 'type' => '교육자료', 'author' => '재밋 노무사', 'date' => '2026-02-10', 'status' => '등록완료'],
        ['no' => 1, 'title' => '퇴직금 계산법과 중간정산 요건 총정리', 'type' => '가이드', 'author' => '재밋 노무사', 'date' => '2026-01-15', 'status' => '등록완료'],
    ],
    'research' => [
        ['no' => 7, 'title' => '기업부설연구소 vs 연구개발전담부서 · 어떤 걸 설립해야 할까?', 'type' => '가이드', 'author' => '재밋 컨설팅', 'date' => '2026-03-22', 'status' => '등록완료'],
        ['no' => 6, 'title' => '기업부설연구소 설립 요건 체크리스트 (인력/시설/신청)', 'type' => '자료', 'author' => '재밋 컨설팅', 'date' => '2026-03-15', 'status' => '등록완료'],
        ['no' => 5, 'title' => '벤처기업인증 3가지 유형별 요건 비교 (기술평가/연구개발/혁신성장)', 'type' => '가이드', 'author' => '재밋 컨설팅', 'date' => '2026-03-08', 'status' => '등록완료'],
        ['no' => 4, 'title' => '벤처인증 받으면 뭐가 좋을까? 세금·입지·인력 혜택 총정리', 'type' => '자료', 'author' => '재밋 컨설팅', 'date' => '2026-02-25', 'status' => '등록완료'],
        ['no' => 3, 'title' => '이노비즈(Inno-Biz) vs 메인비즈(Main-Biz) 인증 비교', 'type' => '자료', 'author' => '재밋 컨설팅', 'date' => '2026-02-18', 'status' => '등록완료'],
        ['no' => 2, 'title' => '연구개발비 세액공제 신청 실무 가이드 (25% 공제 받는 법)', 'type' => '가이드', 'author' => '재밋 컨설팅', 'date' => '2026-02-05', 'status' => '등록완료'],
        ['no' => 1, 'title' => '정부 R&D 과제 신청 절차 및 주의사항', 'type' => '가이드', 'author' => '재밋 컨설팅', 'date' => '2026-01-20', 'status' => '등록완료'],
    ],
    'patent' => [
        ['no' => 7, 'title' => '창업 전 꼭 해야 할 상표등록 · 왜, 언제, 어떻게?', 'type' => '가이드', 'author' => '재밋 변리사', 'date' => '2026-03-28', 'status' => '등록완료'],
        ['no' => 6, 'title' => '상표등록 출원서 작성 가이드 및 양식', 'type' => '양식', 'author' => '재밋 변리사', 'date' => '2026-03-20', 'status' => '등록완료'],
        ['no' => 5, 'title' => '특허출원 절차·비용·기간 한눈에 보기', 'type' => '가이드', 'author' => '재밋 변리사', 'date' => '2026-03-12', 'status' => '등록완료'],
        ['no' => 4, 'title' => '스타트업을 위한 지식재산(IP) 전략 기초', 'type' => '교육자료', 'author' => '재밋 변리사', 'date' => '2026-03-05', 'status' => '등록완료'],
        ['no' => 3, 'title' => '디자인 등록 vs 상표등록 · 로고 보호는 어디에?', 'type' => '자료', 'author' => '재밋 변리사', 'date' => '2026-02-22', 'status' => '등록완료'],
        ['no' => 2, 'title' => '특허·상표 정부 수수료 감면 제도 (중소기업 70% 감면)', 'type' => '자료', 'author' => '재밋 변리사', 'date' => '2026-02-12', 'status' => '등록완료'],
        ['no' => 1, 'title' => '영업비밀 보호를 위한 비밀유지계약서(NDA) 양식', 'type' => '양식', 'author' => '재밋 변리사', 'date' => '2026-01-25', 'status' => '등록완료'],
    ],
    'appraisal' => [
        ['no' => 5, 'title' => '비상장주식 가치평가 · 투자유치 전 반드시 알아야 할 것', 'type' => '가이드', 'author' => '재밋 감정평가사', 'date' => '2026-03-20', 'status' => '등록완료'],
        ['no' => 4, 'title' => '주식매수선택권(스톡옵션) 행사가격 산정 가이드', 'type' => '가이드', 'author' => '재밋 감정평가사', 'date' => '2026-03-10', 'status' => '등록완료'],
        ['no' => 3, 'title' => '법인 부동산 취득 시 감정평가가 필요한 경우', 'type' => '자료', 'author' => '재밋 감정평가사', 'date' => '2026-02-28', 'status' => '등록완료'],
        ['no' => 2, 'title' => '증여·상속 시 비상장주식 보충적 평가방법 해설', 'type' => '자료', 'author' => '재밋 감정평가사', 'date' => '2026-02-15', 'status' => '등록완료'],
        ['no' => 1, 'title' => '감정평가 의뢰서 양식 (부동산/동산/주식)', 'type' => '양식', 'author' => '재밋 감정평가사', 'date' => '2026-01-30', 'status' => '등록완료'],
    ],
    'legal' => [
        ['no' => 7, 'title' => '법인설립 후 꼭 해야 할 등기 사항 체크리스트', 'type' => '가이드', 'author' => '재밋 법무사', 'date' => '2026-03-25', 'status' => '등록완료'],
        ['no' => 6, 'title' => '본점이전등기 절차 및 필요서류 (관할 내/외 구분)', 'type' => '가이드', 'author' => '재밋 법무사', 'date' => '2026-03-18', 'status' => '등록완료'],
        ['no' => 5, 'title' => '임원(이사/감사) 변경등기 신청 가이드', 'type' => '가이드', 'author' => '재밋 법무사', 'date' => '2026-03-10', 'status' => '등록완료'],
        ['no' => 4, 'title' => '유상증자 등기 절차 · 투자금 납입 후 할 일', 'type' => '가이드', 'author' => '재밋 법무사', 'date' => '2026-02-28', 'status' => '등록완료'],
        ['no' => 3, 'title' => '사업목적 추가/변경 등기 절차 안내', 'type' => '자료', 'author' => '재밋 법무사', 'date' => '2026-02-20', 'status' => '등록완료'],
        ['no' => 2, 'title' => '법인 인감 변경 및 인감증명서 발급 가이드', 'type' => '자료', 'author' => '재밋 법무사', 'date' => '2026-02-08', 'status' => '등록완료'],
        ['no' => 1, 'title' => '등기부등본 보는 법 · 창업자가 확인해야 할 포인트', 'type' => '교육자료', 'author' => '재밋 법무사', 'date' => '2026-01-18', 'status' => '등록완료'],
    ],
    'shareholder' => [
        ['no' => 6, 'title' => '정기주주총회 완전 가이드 · 소집부터 의사록까지', 'type' => '가이드', 'author' => '재밋 법무사', 'date' => '2026-03-20', 'status' => '등록완료'],
        ['no' => 5, 'title' => '주주총회 의사록 양식 (정기/임시)', 'type' => '양식', 'author' => '재밋 법무사', 'date' => '2026-03-12', 'status' => '등록완료'],
        ['no' => 4, 'title' => '이사회 의사록 양식 및 작성 가이드', 'type' => '양식', 'author' => '재밋 법무사', 'date' => '2026-03-05', 'status' => '등록완료'],
        ['no' => 3, 'title' => '주주간 계약서(SHA) 핵심 조항 해설 · 창업자 보호 포인트', 'type' => '가이드', 'author' => '재밋 법무사', 'date' => '2026-02-22', 'status' => '등록완료'],
        ['no' => 2, 'title' => '소규모 회사 서면결의 가이드 (주주총회 생략 가능한 경우)', 'type' => '자료', 'author' => '재밋 법무사', 'date' => '2026-02-10', 'status' => '등록완료'],
        ['no' => 1, 'title' => '배당 결의 절차 및 배당금 지급 시 세금 처리', 'type' => '자료', 'author' => '재밋 법무사', 'date' => '2026-01-22', 'status' => '등록완료'],
    ],
    'tax' => [
        ['no' => 8, 'title' => '창업 첫해 세금 캘린더 · 월별로 해야 할 신고·납부 총정리', 'type' => '가이드', 'author' => '재밋 세무사', 'date' => '2026-03-28', 'status' => '등록완료'],
        ['no' => 7, 'title' => '창업중소기업 세액감면 (5년간 법인세 50~100% 감면)', 'type' => '가이드', 'author' => '재밋 세무사', 'date' => '2026-03-20', 'status' => '등록완료'],
        ['no' => 6, 'title' => '부가가치세 신고 체크포인트 (매입세액 공제 놓치지 않는 법)', 'type' => '가이드', 'author' => '재밋 세무사', 'date' => '2026-03-12', 'status' => '등록완료'],
        ['no' => 5, 'title' => '법인카드 사용 시 비용처리 가능/불가능 항목 구분', 'type' => '자료', 'author' => '재밋 세무사', 'date' => '2026-03-05', 'status' => '등록완료'],
        ['no' => 4, 'title' => '대표이사 급여 설정 가이드 · 절세와 4대보험 최적 금액', 'type' => '가이드', 'author' => '재밋 세무사', 'date' => '2026-02-25', 'status' => '등록완료'],
        ['no' => 3, 'title' => '중소기업 특별세액감면 대상 업종 및 감면율 정리', 'type' => '자료', 'author' => '재밋 세무사', 'date' => '2026-02-18', 'status' => '등록완료'],
        ['no' => 2, 'title' => '가지급금 정리 방법 · 대표 개인 사용분 처리 가이드', 'type' => '가이드', 'author' => '재밋 세무사', 'date' => '2026-02-05', 'status' => '등록완료'],
        ['no' => 1, 'title' => '접대비·복리후생비·광고선전비 구분 기준과 한도', 'type' => '자료', 'author' => '재밋 세무사', 'date' => '2026-01-15', 'status' => '등록완료'],
    ],
    'subsidy' => [
        ['no' => 8, 'title' => '2026년 고용지원금 전체 종류 한눈에 보기', 'type' => '가이드', 'author' => '재밋 노무사', 'date' => '2026-03-28', 'status' => '등록완료'],
        ['no' => 7, 'title' => '청년일자리도약장려금 · 최대 1,200만원 지원 요건', 'type' => '가이드', 'author' => '재밋 노무사', 'date' => '2026-03-20', 'status' => '등록완료'],
        ['no' => 6, 'title' => '일자리안정자금 신청 가이드 (30인 미만 사업장)', 'type' => '가이드', 'author' => '재밋 노무사', 'date' => '2026-03-12', 'status' => '등록완료'],
        ['no' => 5, 'title' => '두루누리 사회보험료 지원 · 10인 미만 사업장 필수 신청', 'type' => '가이드', 'author' => '재밋 노무사', 'date' => '2026-03-05', 'status' => '등록완료'],
        ['no' => 4, 'title' => '고용촉진장려금 신청 요건 및 절차 안내', 'type' => '자료', 'author' => '재밋 노무사', 'date' => '2026-02-25', 'status' => '등록완료'],
        ['no' => 3, 'title' => '워라밸일자리장려금 (근로시간 단축 지원금) 안내', 'type' => '자료', 'author' => '재밋 노무사', 'date' => '2026-02-15', 'status' => '등록완료'],
        ['no' => 2, 'title' => '출산육아기 고용안정장려금 · 대체인력 지원금 받는 법', 'type' => '자료', 'author' => '재밋 노무사', 'date' => '2026-02-05', 'status' => '등록완료'],
        ['no' => 1, 'title' => '고용지원금 신청서 양식 및 구비서류 체크리스트', 'type' => '양식', 'author' => '재밋 노무사', 'date' => '2026-01-28', 'status' => '등록완료'],
    ],
];

$currentDocs = $docData[$tab] ?? [];

// DB에서 직원 이름 가져오기 (의뢰인 할당용)
require_once __DIR__ . '/../config/database.php';
$empNames = [];
try {
    $pdo = getDBConnection();
    if ($pdo) {
        $stmt = $pdo->query("SELECT name FROM employees WHERE is_active = 1 ORDER BY id ASC LIMIT 3");
        $empNames = array_column($stmt->fetchAll(), 'name');
    }
} catch (PDOException $e) {}
if (empty($empNames)) $empNames = ['관리자', '관리자', '관리자'];

// 의뢰 내역 시범 데이터 (의뢰인은 DB 직원)
$requestData = [
    'hr_labor' => [
        ['no' => 3, 'title' => '신규 입사자 근로계약서 검토 의뢰', 'requester' => $empNames[0], 'date' => '2026-03-22', 'status' => '진행중'],
        ['no' => 2, 'title' => '취업규칙 작성 및 신고 대행 의뢰', 'requester' => $empNames[0], 'date' => '2026-02-20', 'status' => '완료'],
        ['no' => 1, 'title' => '4대보험 최초 가입 신고 의뢰', 'requester' => $empNames[1] ?? $empNames[0], 'date' => '2026-02-10', 'status' => '완료'],
    ],
    'research' => [
        ['no' => 2, 'title' => '벤처기업인증 신청 대행 의뢰', 'requester' => $empNames[0], 'date' => '2026-03-15', 'status' => '진행중'],
        ['no' => 1, 'title' => '기업부설연구소 설립 컨설팅 의뢰', 'requester' => $empNames[2] ?? $empNames[0], 'date' => '2026-02-15', 'status' => '완료'],
    ],
    'patent' => [
        ['no' => 1, 'title' => '서비스명 상표등록 출원 의뢰', 'requester' => $empNames[0], 'date' => '2026-03-10', 'status' => '진행중'],
    ],
    'appraisal' => [
        ['no' => 1, 'title' => '투자유치 전 비상장주식 가치평가 의뢰', 'requester' => $empNames[0], 'date' => '2026-03-18', 'status' => '진행중'],
    ],
    'legal' => [
        ['no' => 2, 'title' => '사업목적 추가 변경등기 의뢰', 'requester' => $empNames[0], 'date' => '2026-03-20', 'status' => '진행중'],
        ['no' => 1, 'title' => '본점이전등기 의뢰', 'requester' => $empNames[1] ?? $empNames[0], 'date' => '2026-02-18', 'status' => '완료'],
    ],
    'shareholder' => [
        ['no' => 1, 'title' => '정기주주총회 의사록 작성 의뢰', 'requester' => $empNames[0], 'date' => '2026-03-05', 'status' => '완료'],
    ],
    'tax' => [
        ['no' => 2, 'title' => '창업중소기업 세액감면 적용 검토 의뢰', 'requester' => $empNames[0], 'date' => '2026-03-22', 'status' => '진행중'],
        ['no' => 1, 'title' => '부가세 신고 대행 의뢰', 'requester' => $empNames[1] ?? $empNames[0], 'date' => '2026-03-10', 'status' => '완료'],
    ],
    'subsidy' => [
        ['no' => 2, 'title' => '두루누리 사회보험료 지원 신청 의뢰', 'requester' => $empNames[0], 'date' => '2026-03-18', 'status' => '진행중'],
        ['no' => 1, 'title' => '청년일자리도약장려금 신청 대행 의뢰', 'requester' => $empNames[0], 'date' => '2026-02-25', 'status' => '완료'],
    ],
];
$currentRequests = $requestData[$tab] ?? [];

// DB의 실제 의뢰 내역을 추가로 병합 · 샘플과 같은 테이블에 함께 표시
require_once __DIR__ . '/../config/database.php';
$dbPdo = getDBConnection();
if ($dbPdo) {
    try {
        // category 컬럼 유무 확인
        $hasCat = false;
        try { $dbPdo->query("SELECT category FROM doc_requests LIMIT 1"); $hasCat = true; } catch (PDOException $e) {}
        if ($hasCat) {
            $q = $dbPdo->prepare("SELECT id, doc_name, description, due_date, status, requested_at FROM doc_requests WHERE category = ? ORDER BY requested_at DESC, id DESC");
            $q->execute([$tab]);
        } else {
            $q = $dbPdo->prepare("SELECT id, doc_name, description, due_date, status, requested_at FROM doc_requests WHERE description LIKE ? ORDER BY requested_at DESC, id DESC");
            $q->execute(['[' . $tab . ']%']);
        }
        $dbRows = $q->fetchAll(PDO::FETCH_ASSOC);
        $dbNames = $empNames;
        $dbMerged = array_map(function ($r) use ($dbNames) {
            return [
                'no'        => 'DB-' . $r['id'],
                'title'     => $r['doc_name'],
                'requester' => $dbNames[0] ?? '내부',
                'date'      => substr((string)$r['requested_at'], 0, 10),
                'status'    => $r['status'],
                '_db_id'    => (int)$r['id'],
                '_desc'     => $r['description'] ?? '',
                '_due'      => $r['due_date'] ?? '',
            ];
        }, $dbRows);
        // DB 항목을 상단에 표시
        $currentRequests = array_merge($dbMerged, $currentRequests);
    } catch (PDOException $e) {
        error_log('[business_docs] DB 조회 실패: ' . $e->getMessage());
    }
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
                <h2 class="text-lg font-bold text-slate-100">업무자료실/의뢰</h2>
            </div>
            <button onclick="openDocRequestModal('<?= $tab ?>')" class="flex items-center gap-1.5 px-4 py-2 text-sm bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                의뢰 신청
            </button>
        </div>

        <!-- 카테고리 탭 -->
        <div class="zm-tab-container mb-5 overflow-x-auto">
            <?php foreach ($tabs as $key => $label): ?>
            <a href="?tab=<?= $key ?>"
               class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors whitespace-nowrap <?= $tab === $key ? 'approval-tab active' : 'approval-tab' ?>">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>

        <?php if ($doc !== null):
            // ── 상세보기 모드 ──
            $docKey = $tab . '_' . $doc;
            $docInfo = null;
            foreach ($currentDocs as $d) {
                if ((string)$d['no'] === (string)$doc) { $docInfo = $d; break; }
            }
            $content = $docContent[$docKey] ?? null;
        ?>
            <?php if ($docInfo && $content): ?>
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 mb-5">
                <!-- 상단 네비게이션 -->
                <div class="flex items-center justify-between mb-6 no-print">
                    <a href="?tab=<?= $tab ?>" class="flex items-center gap-1.5 text-sm text-slate-400 hover:text-primary transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <?= $tabs[$tab] ?? '' ?> 목록으로
                    </a>
                    <button onclick="window.print()" class="btn btn-secondary btn-sm">
                        <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                        인쇄
                    </button>
                </div>

                <!-- 문서 메타정보 -->
                <div class="border-b border-slate-800 pb-4 mb-6">
                    <div class="flex items-center gap-2 mb-2">
                        <?php
                        $typeBadge = match($docInfo['type']) {
                            '양식' => 'bg-gray-50 text-gray-600',
                            '가이드' => 'bg-amber-100 text-amber-700',
                            '교육자료' => 'bg-gray-50 text-gray-600',
                            default => 'bg-slate-800 text-slate-300',
                        };
                        ?>
                        <span class="inline-block px-2.5 py-0.5 text-sm font-medium rounded-full <?= $typeBadge ?>"><?= $docInfo['type'] ?></span>
                        <span class="text-sm text-slate-500"><?= $tabs[$tab] ?? '' ?></span>
                    </div>
                    <h3 class="text-lg font-bold text-slate-100 mb-3"><?= htmlspecialchars($docInfo['title']) ?></h3>
                    <div class="flex items-center gap-4 text-sm text-slate-400">
                        <span class="flex items-center gap-1">
                            <i data-lucide="user" class="w-3 h-3"></i>
                            <?= htmlspecialchars($docInfo['author']) ?>
                        </span>
                        <span class="flex items-center gap-1">
                            <i data-lucide="calendar" class="w-3 h-3"></i>
                            <?= $docInfo['date'] ?>
                        </span>
                    </div>
                </div>

                <!-- 문서 본문 -->
                <div class="doc-content prose prose-sm max-w-none">
                    <?= $content ?>
                </div>

                <!-- 하단 네비게이션 -->
                <div class="border-t border-slate-800 mt-8 pt-4 no-print">
                    <a href="?tab=<?= $tab ?>" class="inline-flex items-center gap-1.5 text-sm text-slate-400 hover:text-primary transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        목록으로 돌아가기
                    </a>
                </div>
            </div>

            <?php else: ?>
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-12 text-center text-slate-400">
                <svg class="w-10 h-10 mx-auto mb-3 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="mb-3">해당 자료를 찾을 수 없습니다.</p>
                <a href="?tab=<?= $tab ?>" class="text-primary hover:underline text-sm">목록으로 돌아가기</a>
            </div>
            <?php endif; ?>

        <!-- 상세보기 스타일 -->
        <style>
            .doc-content h3 { font-size: 1.25rem; font-weight: 700; color: #1f2937; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #e5e7eb; }
            .doc-content h4 { font-size: 0.95rem; font-weight: 600; color: #374151; margin-top: 1.5rem; margin-bottom: 0.5rem; }
            .doc-content p { color: #4b5563; line-height: 1.75; margin-bottom: 0.75rem; }
            .doc-content ul, .doc-content ol { color: #4b5563; padding-left: 1.5rem; margin-bottom: 1rem; }
            .doc-content li { margin-bottom: 0.4rem; line-height: 1.6; }
            .doc-content strong { color: #1f2937; }
            .doc-content .info-table { width: 100%; border-collapse: collapse; margin: 0.75rem 0 1.25rem; font-size: 0.875rem; }
            .doc-content .info-table th { background: #f8fafc; color: #475569; font-weight: 600; padding: 0.6rem 0.75rem; border: 1px solid #e2e8f0; text-align: left; }
            .doc-content .info-table td { padding: 0.55rem 0.75rem; border: 1px solid #e2e8f0; color: #4b5563; }
            .doc-content .info-table tr:hover td { background: #f0f9ff; }
            .doc-content .tip { background: #f0f9ff; border-left: 3px solid #3b82f6; padding: 0.75rem 1rem; margin: 1rem 0; border-radius: 0 0.375rem 0.375rem 0; font-size: 0.875rem; color: #1e40af; }
            .doc-content .doc-link { color: #2563eb; text-decoration: underline; font-weight: 500; }
            .doc-content .doc-link:hover { color: #1d4ed8; }

            /* 다크 테마 오버라이드 */
            html[data-theme="dark"] .doc-content h3 { color: #e2e8f0; border-bottom-color: #334155; }
            html[data-theme="dark"] .doc-content h4 { color: #e2e8f0; }
            html[data-theme="dark"] .doc-content p { color: #cbd5e1; }
            html[data-theme="dark"] .doc-content ul, html[data-theme="dark"] .doc-content ol { color: #cbd5e1; }
            html[data-theme="dark"] .doc-content strong { color: #e2e8f0; }
            html[data-theme="dark"] .doc-content .info-table th { background: #1e293b; color: #cbd5e1; border-color: #334155; }
            html[data-theme="dark"] .doc-content .info-table td { border-color: #334155; color: #cbd5e1; }
            html[data-theme="dark"] .doc-content .info-table tr:hover td { background: rgba(30,41,59,0.5); }
            html[data-theme="dark"] .doc-content .tip { background: rgba(30,58,138,0.2); border-color: #3b82f6; color: #93c5fd; }
            html[data-theme="dark"] .doc-content .doc-link { color: #60a5fa; }
            html[data-theme="dark"] .doc-content .doc-link:hover { color: #93c5fd; }

            @media print {
                #sidebar, #header, .no-print, button[onclick="history.back()"] { display: none !important; }
                body { padding: 0 !important; }
                #mainContent { margin: 0 !important; padding: 0 !important; }
                main { padding: 0 !important; }
                .doc-content .info-table th { background: #f1f5f9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                .doc-content .tip { background: #f8fafc !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                @page { size: A4; margin: 0; }
                .doc-content { padding: 15mm !important; }
            }
        </style>

        <script>
        // 본문 내 URL 텍스트를 자동으로 클릭 가능한 링크로 변환
        document.addEventListener('DOMContentLoaded', function() {
            const docContent = document.querySelector('.doc-content');
            if (!docContent) return;

            const walker = document.createTreeWalker(docContent, NodeFilter.SHOW_TEXT);
            const urlPattern = /(?:https?:\/\/)?(?:www\.)?([a-zA-Z0-9-]+(?:\.[a-zA-Z]{2,})+)(?:\/[^\s<)]*)?/g;
            const textNodes = [];

            while (walker.nextNode()) {
                if (walker.currentNode.parentElement.tagName === 'A') continue;
                if (urlPattern.test(walker.currentNode.textContent)) {
                    textNodes.push(walker.currentNode);
                }
                urlPattern.lastIndex = 0;
            }

            textNodes.forEach(function(node) {
                const fragment = document.createDocumentFragment();
                let lastIndex = 0;
                let match;
                urlPattern.lastIndex = 0;

                while ((match = urlPattern.exec(node.textContent)) !== null) {
                    if (lastIndex < match.index) {
                        fragment.appendChild(document.createTextNode(node.textContent.slice(lastIndex, match.index)));
                    }
                    const link = document.createElement('a');
                    const url = match[0].startsWith('http') ? match[0] : 'https://' + match[0];
                    link.href = url;
                    link.target = '_blank';
                    link.className = 'doc-link';
                    link.textContent = match[0];
                    fragment.appendChild(link);
                    lastIndex = match.index + match[0].length;
                }

                if (lastIndex < node.textContent.length) {
                    fragment.appendChild(document.createTextNode(node.textContent.slice(lastIndex)));
                }
                node.parentNode.replaceChild(fragment, node);
            });
        });
        </script>

        <?php else: ?>
        <!-- ── 목록 모드 ── -->

        <!-- 업무 자료 목록 -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-5 mb-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-slate-100 inline-flex items-center gap-1.5 whitespace-nowrap">
                    <i data-lucide="folder-open" class="text-primary w-4 h-4 shrink-0"></i>
                    <span><?= $tabs[$tab] ?? '' ?> 자료</span>
                </h3>
                <div class="flex items-center gap-2">
                    <input type="text" class="w-64 border border-slate-800 rounded-lg px-3 py-1.5 text-sm" placeholder="자료 검색...">
                    <button class="btn btn-secondary btn-sm">
                        <svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </button>
                </div>
            </div>

            <table class="w-full text-sm emp-table">
                <thead>
                    <tr class="border-b-2 border-slate-800">
                        <th class="py-3 px-3 text-center font-medium text-slate-300 w-[6%]">No.</th>
                        <th class="py-3 px-3 text-center font-medium text-slate-300">제목</th>
                        <th class="py-3 px-3 text-center font-medium text-slate-300 w-[10%]">유형</th>
                        <th class="py-3 px-3 text-center font-medium text-slate-300 w-[14%]">작성자</th>
                        <th class="py-3 px-3 text-center font-medium text-slate-300 w-[12%]">등록일</th>
                        <th class="py-3 px-3 text-center font-medium text-slate-300 w-[10%]">상태</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($currentDocs)): ?>
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-400">
                            <div class="flex items-center justify-center gap-2">
                                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>등록된 자료가 없습니다</span>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($currentDocs as $item): ?>
                        <tr class="border-b border-slate-800 hover:bg-gray-100 cursor-pointer" onclick="location.href='?tab=<?= $tab ?>&doc=<?= $item['no'] ?>'">
                            <td class="py-3 px-3 text-center text-slate-300"><?= $item['no'] ?></td>
                            <td class="py-3 px-3 text-slate-100">
                                <div class="flex items-center gap-2">
                                    <?php
                                    $typeIcon = match($item['type']) {
                                        '양식' => 'file-text',
                                        '가이드' => 'book-open',
                                        '교육자료' => 'graduation-cap',
                                        default => 'file',
                                    };
                                    $typeColor = match($item['type']) {
                                        '양식' => 'text-primary',
                                        '가이드' => 'text-amber-500',
                                        '교육자료' => 'text-primary',
                                        default => 'text-slate-500',
                                    };
                                    ?>
                                    <i data-lucide="<?= $typeIcon ?>" class="w-3.5 h-3.5 <?= $typeColor ?>"></i>
                                    <?= $item['title'] ?>
                                </div>
                            </td>
                            <td class="py-3 px-3 text-center">
                                <?php
                                $typeBadge = match($item['type']) {
                                    '양식' => 'bg-gray-50 text-gray-600',
                                    '가이드' => 'bg-amber-100 text-amber-700',
                                    '교육자료' => 'bg-gray-50 text-gray-600',
                                    default => 'bg-slate-800 text-slate-300',
                                };
                                ?>
                                <span class="inline-block px-2 py-0.5 text-sm rounded-full <?= $typeBadge ?>"><?= $item['type'] ?></span>
                            </td>
                            <td class="py-3 px-3 text-center text-slate-300"><?= $item['author'] ?></td>
                            <td class="py-3 px-3 text-center text-slate-300"><?= $item['date'] ?></td>
                            <td class="py-3 px-3 text-center">
                                <span class="inline-block px-2 py-0.5 text-sm rounded-full bg-amber-100 text-amber-700"><?= $item['status'] ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- 의뢰 내역 -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-slate-100 inline-flex items-center gap-1.5 whitespace-nowrap">
                    <i data-lucide="send" class="text-primary w-4 h-4 shrink-0"></i>
                    <span><?= $tabs[$tab] ?? '' ?> 의뢰 내역</span>
                </h3>
                <button onclick="openDocRequestModal('<?= $tab ?>')" class="btn btn-secondary btn-sm">
                    + 신규 의뢰
                </button>
            </div>

            <table class="w-full text-sm emp-table">
                <thead>
                    <tr class="border-b-2 border-slate-800">
                        <th class="py-3 px-3 text-center font-medium text-slate-300 w-[6%]">No.</th>
                        <th class="py-3 px-3 text-center font-medium text-slate-300">의뢰 제목</th>
                        <th class="py-3 px-3 text-center font-medium text-slate-300 w-[12%]">의뢰자</th>
                        <th class="py-3 px-3 text-center font-medium text-slate-300 w-[12%]">의뢰일</th>
                        <th class="py-3 px-3 text-center font-medium text-slate-300 w-[10%]">처리상태</th>
                        <th class="py-3 px-3 text-center font-medium text-slate-300 w-[8%]">상세</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($currentRequests)): ?>
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-400">
                            <div class="flex items-center justify-center gap-2">
                                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>의뢰 내역이 없습니다</span>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($currentRequests as $req): ?>
                        <tr class="border-b border-slate-800 hover:bg-gray-100 cursor-pointer">
                            <td class="py-3 px-3 text-center text-slate-300"><?= htmlspecialchars((string)$req['no']) ?></td>
                            <td class="py-3 px-3 text-slate-100"><?= htmlspecialchars($req['title']) ?></td>
                            <td class="py-3 px-3 text-center text-slate-300"><?= htmlspecialchars($req['requester']) ?></td>
                            <td class="py-3 px-3 text-center text-slate-300"><?= htmlspecialchars($req['date']) ?></td>
                            <td class="py-3 px-3 text-center">
                                <?php
                                $statusBadge = match($req['status']) {
                                    '진행중', '요청중'       => 'bg-amber-100 text-amber-700',
                                    '완료', '확인완료'        => 'bg-amber-100 text-amber-700',
                                    '업로드완료'             => 'bg-gray-50 text-gray-600',
                                    '취소'                  => 'bg-rose-900/40 text-rose-300',
                                    '대기'                  => 'bg-slate-800 text-slate-300',
                                    default                  => 'bg-slate-800 text-slate-300',
                                };
                                ?>
                                <span class="inline-block px-2 py-0.5 text-sm rounded-full <?= $statusBadge ?>"><?= htmlspecialchars($req['status']) ?></span>
                            </td>
                            <td class="py-3 px-3 text-center">
                                <?php if (!empty($req['_db_id'])): ?>
                                    <button onclick='openDocDetailModal(<?= (int)$req['_db_id'] ?>)' class="btn btn-secondary btn-xs">상세</button>
                                <?php else: ?>
                                    <button onclick='openDocDetailSample(<?= htmlspecialchars(json_encode($req, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>)' class="btn btn-secondary btn-xs">상세</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php endif; ?>

    </main>
</div>

<!-- 의뢰 신청 / 상세 모달 -->
<div id="docModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="closeDocModal()"></div>
    <div class="relative bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-800">
            <h3 id="docModalTitle" class="text-base font-bold text-slate-100">의뢰 신청</h3>
            <button onclick="closeDocModal()" class="text-slate-400 hover:text-slate-200" aria-label="닫기">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div id="docModalBody" class="p-5 space-y-3 text-sm text-slate-200"></div>
        <div id="docModalFooter" class="flex gap-2 justify-end px-5 pb-5"></div>
    </div>
</div>

<script>
(function () {
    const BASE = '<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') ?>';
    const API  = BASE + '/../api/docs.php';
    const modal  = document.getElementById('docModal');
    const title  = document.getElementById('docModalTitle');
    const body   = document.getElementById('docModalBody');
    const footer = document.getElementById('docModalFooter');

    window.closeDocModal = function () { modal.classList.add('hidden'); };

    window.openDocRequestModal = function (category) {
        title.textContent = '신규 의뢰 신청';
        body.innerHTML = `
            <div>
                <label class="block text-sm text-slate-300 mb-1">카테고리</label>
                <input class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-200" value="${category}" disabled>
            </div>
            <div>
                <label class="block text-sm text-slate-300 mb-1">의뢰 제목 <span class="text-rose-400">*</span></label>
                <input id="docInDocName" class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-200" placeholder="예) 투자유치 실사 자료 준비 의뢰">
            </div>
            <div>
                <label class="block text-sm text-slate-300 mb-1">상세 설명</label>
                <textarea id="docInDesc" rows="4" class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-200" placeholder="의뢰 배경, 필요 자료, 특이사항 등"></textarea>
            </div>
            <div>
                <label class="block text-sm text-slate-300 mb-1">제출 기한</label>
                <input id="docInDue" type="date" class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-200">
            </div>
        `;
        footer.innerHTML = `
            <button onclick="closeDocModal()" class="btn btn-secondary">취소</button>
            <button id="docSubmitBtn" class="px-4 py-2 text-sm bg-primary text-white rounded-lg hover:bg-primary-dark">제출</button>
        `;
        modal.classList.remove('hidden');
        document.getElementById('docSubmitBtn').onclick = function () { submitDocRequest(category); };
        setTimeout(() => document.getElementById('docInDocName').focus(), 10);
    };

    function submitDocRequest(category) {
        const docName = document.getElementById('docInDocName').value.trim();
        const desc    = document.getElementById('docInDesc').value.trim();
        const due     = document.getElementById('docInDue').value || '';
        if (!docName) { alert('의뢰 제목을 입력해주세요.'); return; }
        const btn = document.getElementById('docSubmitBtn');
        btn.disabled = true; btn.textContent = '제출 중...';
        fetch(API + '?action=createRequest', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ category, doc_name: docName, description: desc, due_date: due })
        })
        .then(r => r.json())
        .then(j => {
            if (j && j.success) {
                alert('의뢰가 접수되었습니다.');
                location.reload();
            } else {
                alert((j && j.error) || '의뢰 저장 실패');
                btn.disabled = false; btn.textContent = '제출';
            }
        })
        .catch(() => { alert('네트워크 오류'); btn.disabled = false; btn.textContent = '제출'; });
    }

    window.openDocDetailModal = function (id) {
        title.textContent = '의뢰 상세';
        body.innerHTML = '<p class="text-slate-400">불러오는 중...</p>';
        footer.innerHTML = '';
        modal.classList.remove('hidden');
        fetch(API + '?action=getRequest&id=' + encodeURIComponent(id))
            .then(r => r.json())
            .then(j => {
                if (!j || !j.request) { body.innerHTML = '<p class="text-rose-400">의뢰 정보를 불러올 수 없습니다.</p>'; return; }
                const r = j.request;
                body.innerHTML = `
                    <dl class="grid grid-cols-3 gap-y-2 text-sm">
                        <dt class="text-slate-400">제목</dt><dd class="col-span-2 text-slate-100">${escapeHtml(r.doc_name)}</dd>
                        <dt class="text-slate-400">카테고리</dt><dd class="col-span-2">${escapeHtml(r.category || '')}</dd>
                        <dt class="text-slate-400">상태</dt><dd class="col-span-2">${escapeHtml(r.status)}</dd>
                        <dt class="text-slate-400">요청일</dt><dd class="col-span-2">${escapeHtml((r.requested_at||'').slice(0,19))}</dd>
                        <dt class="text-slate-400">제출기한</dt><dd class="col-span-2">${escapeHtml(r.due_date || '-')}</dd>
                        <dt class="text-slate-400">설명</dt><dd class="col-span-2 whitespace-pre-wrap">${escapeHtml(r.description || '')}</dd>
                    </dl>`;
                const canCancel = (r.status !== '취소' && r.status !== '확인완료');
                footer.innerHTML = `
                    <button onclick="closeDocModal()" class="btn btn-secondary">닫기</button>
                    ${canCancel ? `<button id="docCancelBtn" class="px-4 py-2 text-sm border border-rose-600 text-rose-300 rounded-lg hover:bg-rose-950">취소 처리</button>` : ''}
                `;
                if (canCancel) {
                    document.getElementById('docCancelBtn').onclick = async function () {
                        if (!(await AppUI.confirm('이 의뢰를 취소 처리할까요?'))) return;
                        fetch(API + '?action=cancelRequest', {
                            method: 'POST', headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id: r.id })
                        })
                        .then(rs => rs.json())
                        .then(jr => {
                            if (jr && jr.success) { alert('취소되었습니다.'); location.reload(); }
                            else alert((jr && jr.error) || '취소 실패');
                        });
                    };
                }
            })
            .catch(() => { body.innerHTML = '<p class="text-rose-400">네트워크 오류</p>'; });
    };

    window.openDocDetailSample = function (req) {
        title.textContent = '의뢰 상세 (샘플)';
        body.innerHTML = `
            <p class="text-xs text-slate-500 mb-2">이 의뢰는 데모 데이터입니다. 실제 저장된 의뢰는 '상세' 버튼에서 서버 정보를 확인할 수 있습니다.</p>
            <dl class="grid grid-cols-3 gap-y-2 text-sm">
                <dt class="text-slate-400">No.</dt><dd class="col-span-2">${escapeHtml(String(req.no))}</dd>
                <dt class="text-slate-400">제목</dt><dd class="col-span-2">${escapeHtml(req.title)}</dd>
                <dt class="text-slate-400">의뢰자</dt><dd class="col-span-2">${escapeHtml(req.requester)}</dd>
                <dt class="text-slate-400">일자</dt><dd class="col-span-2">${escapeHtml(req.date)}</dd>
                <dt class="text-slate-400">상태</dt><dd class="col-span-2">${escapeHtml(req.status)}</dd>
            </dl>`;
        footer.innerHTML = `<button onclick="closeDocModal()" class="btn btn-secondary">닫기</button>`;
        modal.classList.remove('hidden');
    };

    function escapeHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' }[c]));
    }
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
