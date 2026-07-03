<?php
$pageTitle = '공통코드 관리';
$currentPage = 'groupware';
require_once __DIR__ . '/../includes/permissions.php';
requireMenuPermission('groupware', 'view'); // 접근권한 관리 연동 (admin 항상 통과)
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../config/database.php';

$tabs = [
    'people'   => [
        'label' => '인사·근태', 'icon' => 'users',
        'modules' => ['hr', 'attendance'],
        'moduleLabels' => ['hr' => '인사', 'attendance' => '근태'],
        'desc' => '고용형태, 고용상태, 근무유형, 휴가유형 등 인사·근태 관련 분류 코드를 관리합니다',
    ],
    'finance'  => [
        'label' => '사업·경비', 'icon' => 'briefcase',
        'modules' => ['business', 'card'],
        'moduleLabels' => ['business' => '사업', 'card' => '법인카드'],
        'desc' => '사업원가, 사업상태, 비용항목, 카드유형 등 사업·경비 관련 분류 코드를 관리합니다',
    ],
    'resource' => [
        'label' => '일정·자원', 'icon' => 'calendar-days',
        'modules' => ['schedule', 'reservation'],
        'moduleLabels' => ['schedule' => '일정', 'reservation' => '자원예약'],
        'desc' => '일정유형, 캘린더 색상, 회의실·비품 등 일정·자원 관련 분류 코드를 관리합니다',
    ],
];

$activeTab = $_GET['tab'] ?? 'people';
if (!isset($tabs[$activeTab])) $activeTab = 'people';
$activeModules = $tabs[$activeTab]['modules'];

$detailId = (int)($_GET['detail'] ?? 0);

$useDB = false;
$groupsJson = '[]';
$detailJson = 'null';

$pdo = getDBConnection();
if ($pdo) {
    try {
        $check = $pdo->query("SELECT COUNT(*) FROM common_code_groups")->fetchColumn();
        if ((int)$check === 0) {
            $pdo->exec("
                INSERT INTO common_code_groups (module, name, description, sort_order) VALUES
                ('hr','직급','직원의 직급 분류',1),('hr','직책','직원의 직책 분류',2),
                ('hr','고용형태','직원의 고용 형태 분류',3),('hr','고용상태','직원의 재직/퇴직 상태 분류',4),
                ('attendance','근무유형','출퇴근 근무 유형 분류',1),('attendance','휴가유형','연차/반차 등 휴가 유형',2),
                ('card','비용항목','법인카드 사용 시 비용 분류 항목',1),('card','카드유형','법인카드 종류',2),
                ('business','사업원가항목','사업 비용 책정 시 입력하는 사업원가항목 구분',1),
                ('business','사업상태','사업 진행 상태 분류',2),('business','사업구분','사업 유형 분류',3),
                ('reservation','자원목록','회사가 보유하고 운영하는 자원 정보 (회의실, 비품, 차량 등)',1),
                ('schedule','일정유형','일정 분류 유형',1),('schedule','캘린더 색상','일정 캘린더 색상 구분',2)
            ");
            $pdo->exec("
                INSERT INTO common_code_items (group_id, code, name, sort_order) VALUES
                (1,'CEO','대표이사',1),(1,'DIR','이사',2),(1,'GM','부장',3),(1,'DGM','차장',4),
                (1,'MGR','과장',5),(1,'AM','대리',6),(1,'SR','주임',7),(1,'STF','사원',8),(1,'INT','인턴',9),
                (2,'CEO','CEO',1),(2,'CTO','CTO',2),(2,'CFO','CFO',3),(2,'COO','COO',4),
                (2,'HEAD','본부장',5),(2,'TL','팀장',6),(2,'PL','파트장',7),
                (3,'FT','정규직',1),(3,'CT','계약직',2),(3,'PT','시간제',3),(3,'DP','파견직',4),
                (4,'ACT','재직',1),(4,'LOA','휴직',2),(4,'MAT','육아휴직',3),(4,'RES','퇴사',4),
                (5,'NRM','정상근무',1),(5,'WFH','재택근무',2),(5,'OUT','외근',3),(5,'BIZ','출장',4),(5,'OT','야근',5),(5,'HOL','휴일근무',6),
                (6,'AL','연차',1),(6,'HAM','반차(오전)',2),(6,'HAP','반차(오후)',3),
                (6,'SL','병가',4),(6,'FL','경조사',5),(6,'OL','공가',6),
                (7,'FOOD','식대',1),(7,'TRANS','교통비',2),(7,'ENT','접대비',3),(7,'SUP','소모품',4),(7,'ETC','기타',5),
                (8,'CORP','법인카드',1),(8,'PRIV','개인카드',2),
                (9,'OS_C','외주비(기업)',1),(9,'OS_P','외주비(개인)',2),(9,'RES','자원구입비',3),
                (9,'MKT','마케팅 수수료',4),(9,'PRM','사업 판촉비',5),(9,'EXP','진행 경비',6),(9,'FREE','무상 서비스 원가',7),
                (10,'SALES','영업',1),(10,'CONT','계약',2),(10,'PROG','진행중',3),(10,'DONE','완료',4),(10,'HOLD','보류',5),
                (11,'SI','SI',1),(11,'SM','SM',2),(11,'CONS','컨설팅',3),(11,'EDU','교육',4),
                (12,'MR1','319호 - 회의실',1),(12,'MR2','319호 - 탕비실(회의용)',2),
                (12,'NB1','노트북 1 (내부)',3),(12,'TAB','태블릿',4),
                (13,'MTG','회의',1),(13,'EXT','외부미팅',2),(13,'TRIP','출장',3),(13,'EDU','교육',4),(13,'ETC','기타',5),(13,'OUT','외근',6),(13,'INTV','면담',7),(13,'EVT','행사',8),(13,'DUE','마감',9),
                (14,'BLUE','파랑',1),(14,'RED','빨강',2),(14,'GREEN','초록',3),(14,'YELLOW','노랑',4),(14,'PURPLE','보라',5)
            ");
        }

        try {
            $cols = $pdo->query("SHOW COLUMNS FROM common_code_items LIKE 'code'");
            if ($cols->rowCount() === 0) {
                $pdo->exec("ALTER TABLE common_code_items ADD COLUMN code VARCHAR(50) NULL COMMENT '코드' AFTER group_id");
            }
        } catch (PDOException $e) { /* ignore */ }

        $useDB = true;

        $ph = implode(',', array_fill(0, count($activeModules), '?'));
        $stmt = $pdo->prepare("SELECT g.*,
            (SELECT COUNT(*) FROM common_code_items WHERE group_id = g.id AND is_active = 1) AS item_count,
            (SELECT GROUP_CONCAT(name ORDER BY sort_order SEPARATOR ', ') FROM common_code_items WHERE group_id = g.id AND is_active = 1) AS item_preview
            FROM common_code_groups g WHERE g.module IN ($ph) AND g.is_active = 1 ORDER BY FIELD(g.module, $ph), g.sort_order, g.id");
        $stmt->execute(array_merge($activeModules, $activeModules));
        $groupsJson = json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);

        if ($detailId > 0) {
            $gStmt = $pdo->prepare('SELECT * FROM common_code_groups WHERE id = ? AND is_active = 1');
            $gStmt->execute([$detailId]);
            $group = $gStmt->fetch();
            if ($group) {
                $iStmt = $pdo->prepare('SELECT * FROM common_code_items WHERE group_id = ? ORDER BY sort_order, id');
                $iStmt->execute([$detailId]);
                $group['items'] = $iStmt->fetchAll();
                if (in_array('reservation', $activeModules) && ($group['module'] ?? '') === 'reservation' && !empty($group['items'])) {
                    try {
                        $itemIds = array_column($group['items'], 'id');
                        $ph2 = implode(',', array_fill(0, count($itemIds), '?'));
                        $cfgStmt = $pdo->prepare("SELECT item_id, max_count FROM reservation_resource_config WHERE item_id IN ($ph2)");
                        $cfgStmt->execute($itemIds);
                        $cfgMap = [];
                        foreach ($cfgStmt->fetchAll() as $row) { $cfgMap[$row['item_id']] = (int)$row['max_count']; }
                        foreach ($group['items'] as &$item) { $item['max_count'] = $cfgMap[$item['id']] ?? 1; }
                        unset($item);
                    } catch (PDOException $e) { /* ignore */ }
                }
                $detailJson = json_encode($group, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
            }
        }
    } catch (PDOException $e) {
        error_log('[settings] DB 조회 실패 (스키마 미적용?): ' . $e->getMessage());
    }
}

// Sample data (non-DB)
$sampleGroups = [];
$sampleDetails = [];
if (!$useDB) {
    $sampleGroups = [
        'people' => [
            ['id'=>3,'module'=>'hr','name'=>'고용형태','description'=>'직원의 고용 형태 분류','item_count'=>4],
            ['id'=>4,'module'=>'hr','name'=>'고용상태','description'=>'직원의 재직/퇴직 상태 분류','item_count'=>4],
            ['id'=>5,'module'=>'attendance','name'=>'근무유형','description'=>'출퇴근 근무 유형 분류','item_count'=>6],
            ['id'=>6,'module'=>'attendance','name'=>'휴가유형','description'=>'연차/반차 등 휴가 유형','item_count'=>6],
        ],
        'finance' => [
            ['id'=>9,'module'=>'business','name'=>'사업원가항목','description'=>'사업 비용 책정 시 입력하는 사업원가항목 구분','item_count'=>7],
            ['id'=>10,'module'=>'business','name'=>'사업상태','description'=>'사업 진행 상태 분류','item_count'=>5],
            ['id'=>11,'module'=>'business','name'=>'사업구분','description'=>'사업 유형 분류','item_count'=>4],
            ['id'=>7,'module'=>'card','name'=>'비용항목','description'=>'법인카드 사용 시 비용 분류 항목','item_count'=>5],
            ['id'=>8,'module'=>'card','name'=>'카드유형','description'=>'법인카드 종류','item_count'=>2],
        ],
        'resource' => [
            ['id'=>13,'module'=>'schedule','name'=>'일정유형','description'=>'일정 분류 유형','item_count'=>9],
            ['id'=>14,'module'=>'schedule','name'=>'캘린더 색상','description'=>'일정 캘린더 색상 구분','item_count'=>5],
            ['id'=>12,'module'=>'reservation','name'=>'자원목록','description'=>'회사가 보유하고 운영하는 자원 정보','item_count'=>4],
        ],
    ];
    $groupsJson = json_encode($sampleGroups[$activeTab] ?? [], JSON_UNESCAPED_UNICODE);

    $sampleDetails = [
        3 => ['id'=>3,'name'=>'고용형태','description'=>'직원의 고용 형태 분류','items'=>[
            ['id'=>17,'code'=>'FT','name'=>'정규직','sort_order'=>1,'is_active'=>1],['id'=>18,'code'=>'CT','name'=>'계약직','sort_order'=>2,'is_active'=>1],
            ['id'=>19,'code'=>'PT','name'=>'시간제','sort_order'=>3,'is_active'=>1],['id'=>20,'code'=>'DP','name'=>'파견직','sort_order'=>4,'is_active'=>1],
        ]],
        4 => ['id'=>4,'name'=>'고용상태','description'=>'직원의 재직/퇴직 상태 분류','items'=>[
            ['id'=>21,'code'=>'ACT','name'=>'재직','sort_order'=>1,'is_active'=>1],['id'=>22,'code'=>'LOA','name'=>'휴직','sort_order'=>2,'is_active'=>1],
            ['id'=>23,'code'=>'MAT','name'=>'육아휴직','sort_order'=>3,'is_active'=>1],['id'=>24,'code'=>'RES','name'=>'퇴사','sort_order'=>4,'is_active'=>1],
        ]],
        5 => ['id'=>5,'name'=>'근무유형','description'=>'출퇴근 근무 유형 분류','items'=>[
            ['id'=>25,'code'=>'NRM','name'=>'정상근무','sort_order'=>1,'is_active'=>1],['id'=>26,'code'=>'WFH','name'=>'재택근무','sort_order'=>2,'is_active'=>1],
            ['id'=>27,'code'=>'OUT','name'=>'외근','sort_order'=>3,'is_active'=>1],['id'=>28,'code'=>'BIZ','name'=>'출장','sort_order'=>4,'is_active'=>1],
            ['id'=>97,'code'=>'OT','name'=>'야근','sort_order'=>5,'is_active'=>1],['id'=>98,'code'=>'HOL','name'=>'휴일근무','sort_order'=>6,'is_active'=>1],
        ]],
        6 => ['id'=>6,'name'=>'휴가유형','description'=>'연차/반차 등 휴가 유형','items'=>[
            ['id'=>29,'code'=>'AL','name'=>'연차','sort_order'=>1,'is_active'=>1],['id'=>30,'code'=>'HAM','name'=>'반차(오전)','sort_order'=>2,'is_active'=>1],
            ['id'=>31,'code'=>'HAP','name'=>'반차(오후)','sort_order'=>3,'is_active'=>1],['id'=>32,'code'=>'SL','name'=>'병가','sort_order'=>4,'is_active'=>1],
            ['id'=>33,'code'=>'FL','name'=>'경조사','sort_order'=>5,'is_active'=>1],['id'=>34,'code'=>'OL','name'=>'공가','sort_order'=>6,'is_active'=>1],
        ]],
        7 => ['id'=>7,'name'=>'비용항목','description'=>'법인카드 사용 시 비용 분류 항목','items'=>[
            ['id'=>35,'code'=>'FOOD','name'=>'식대','sort_order'=>1,'is_active'=>1],['id'=>36,'code'=>'TRANS','name'=>'교통비','sort_order'=>2,'is_active'=>1],
            ['id'=>37,'code'=>'ENT','name'=>'접대비','sort_order'=>3,'is_active'=>1],['id'=>38,'code'=>'SUP','name'=>'소모품','sort_order'=>4,'is_active'=>1],
            ['id'=>39,'code'=>'ETC','name'=>'기타','sort_order'=>5,'is_active'=>1],
        ]],
        8 => ['id'=>8,'name'=>'카드유형','description'=>'법인카드 종류','items'=>[
            ['id'=>40,'code'=>'CORP','name'=>'법인카드','sort_order'=>1,'is_active'=>1],['id'=>41,'code'=>'PRIV','name'=>'개인카드','sort_order'=>2,'is_active'=>1],
        ]],
        9 => ['id'=>9,'name'=>'사업원가항목','description'=>'사업 비용 책정 시 입력하는 사업원가항목 구분','items'=>[
            ['id'=>50,'code'=>'OS_C','name'=>'외주비(기업)','sort_order'=>1,'is_active'=>1],['id'=>51,'code'=>'OS_P','name'=>'외주비(개인)','sort_order'=>2,'is_active'=>1],
            ['id'=>52,'code'=>'RES','name'=>'자원구입비','sort_order'=>3,'is_active'=>1],['id'=>53,'code'=>'MKT','name'=>'마케팅 수수료','sort_order'=>4,'is_active'=>1],
            ['id'=>54,'code'=>'PRM','name'=>'사업 판촉비','sort_order'=>5,'is_active'=>1],['id'=>55,'code'=>'EXP','name'=>'진행 경비','sort_order'=>6,'is_active'=>1],
            ['id'=>56,'code'=>'FREE','name'=>'무상 서비스 원가','sort_order'=>7,'is_active'=>1],
        ]],
        10 => ['id'=>10,'name'=>'사업상태','description'=>'사업 진행 상태 분류','items'=>[
            ['id'=>57,'code'=>'SALES','name'=>'영업','sort_order'=>1,'is_active'=>1],['id'=>58,'code'=>'CONT','name'=>'계약','sort_order'=>2,'is_active'=>1],
            ['id'=>59,'code'=>'PROG','name'=>'진행중','sort_order'=>3,'is_active'=>1],['id'=>60,'code'=>'DONE','name'=>'완료','sort_order'=>4,'is_active'=>1],
            ['id'=>61,'code'=>'HOLD','name'=>'보류','sort_order'=>5,'is_active'=>1],
        ]],
        11 => ['id'=>11,'name'=>'사업구분','description'=>'사업 유형 분류','items'=>[
            ['id'=>62,'code'=>'SI','name'=>'SI','sort_order'=>1,'is_active'=>1],['id'=>63,'code'=>'SM','name'=>'SM','sort_order'=>2,'is_active'=>1],
            ['id'=>64,'code'=>'CONS','name'=>'컨설팅','sort_order'=>3,'is_active'=>1],['id'=>65,'code'=>'EDU','name'=>'교육','sort_order'=>4,'is_active'=>1],
        ]],
        12 => ['id'=>12,'name'=>'자원목록','description'=>'회사가 보유하고 운영하는 자원 정보','items'=>[
            ['id'=>70,'code'=>'MR1','name'=>'319호 - 회의실','sort_order'=>1,'is_active'=>1,'max_count'=>1],
            ['id'=>71,'code'=>'MR2','name'=>'319호 - 탕비실(회의용)','sort_order'=>2,'is_active'=>1,'max_count'=>1],
            ['id'=>72,'code'=>'NB1','name'=>'노트북 1 (내부)','sort_order'=>3,'is_active'=>1,'max_count'=>5],
            ['id'=>73,'code'=>'TAB','name'=>'태블릿','sort_order'=>4,'is_active'=>1,'max_count'=>3],
        ]],
        13 => ['id'=>13,'name'=>'일정유형','description'=>'일정 분류 유형','items'=>[
            ['id'=>74,'code'=>'MTG','name'=>'회의','sort_order'=>1,'is_active'=>1],['id'=>75,'code'=>'EXT','name'=>'외부미팅','sort_order'=>2,'is_active'=>1],
            ['id'=>76,'code'=>'TRIP','name'=>'출장','sort_order'=>3,'is_active'=>1],['id'=>77,'code'=>'EDU','name'=>'교육','sort_order'=>4,'is_active'=>1],
            ['id'=>78,'code'=>'ETC','name'=>'기타','sort_order'=>5,'is_active'=>1],['id'=>79,'code'=>'OUT','name'=>'외근','sort_order'=>6,'is_active'=>1],
            ['id'=>80,'code'=>'INTV','name'=>'면담','sort_order'=>7,'is_active'=>1],['id'=>81,'code'=>'EVT','name'=>'행사','sort_order'=>8,'is_active'=>1],
            ['id'=>82,'code'=>'DUE','name'=>'마감','sort_order'=>9,'is_active'=>1],
        ]],
        14 => ['id'=>14,'name'=>'캘린더 색상','description'=>'일정 캘린더 색상 구분','items'=>[
            ['id'=>79,'code'=>'BLUE','name'=>'파랑','sort_order'=>1,'is_active'=>1],['id'=>80,'code'=>'RED','name'=>'빨강','sort_order'=>2,'is_active'=>1],
            ['id'=>81,'code'=>'GREEN','name'=>'초록','sort_order'=>3,'is_active'=>1],['id'=>82,'code'=>'YELLOW','name'=>'노랑','sort_order'=>4,'is_active'=>1],
            ['id'=>83,'code'=>'PURPLE','name'=>'보라','sort_order'=>5,'is_active'=>1],
        ]],
    ];
    if ($detailId > 0 && isset($sampleDetails[$detailId])) {
        $detailJson = json_encode($sampleDetails[$detailId], JSON_UNESCAPED_UNICODE);
    }
}
$sampleDetailsJs = $useDB ? '{}' : json_encode($sampleDetails, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
?>

<style>
.settings-group-item { transition: all 120ms ease; border: 1.5px solid transparent; }
.settings-group-item:hover { background: var(--zm-surface-1, #f8f9fa); }
.settings-group-item.active {
    background: transparent;
    border-color: var(--gi-color, #4F6AFF);
    box-shadow: 1px 2px 6px color-mix(in srgb, var(--gi-color, #4F6AFF) 18%, transparent);
}
.settings-group-item.active .gi-name { color: var(--gi-color, #4F6AFF) !important; font-weight: 600; }
.settings-group-item.active .group-count { background: color-mix(in srgb, var(--gi-color, #4F6AFF) 10%, transparent) !important; color: var(--gi-color, #4F6AFF) !important; }
.settings-item-row .delete-item-btn { color: transparent; }
.settings-item-row:hover .delete-item-btn { color: var(--zm-text-muted); }
.settings-item-row .delete-item-btn:hover { color: #f87171 !important; background: rgba(248,113,113,0.08); }
.settings-item-row:hover { background: var(--zm-surface-1, #f8f9fa); }
.settings-item-row .item-name-input { border-color: transparent !important; background: transparent; padding: 4px 8px; font-size: 13px; }
.settings-item-row .item-name-input:focus { border-color: rgba(79,106,255,0.4) !important; background: var(--zm-surface-1, #f8f9fa); }
.settings-item-row .item-name-input::placeholder { color: var(--zm-text-muted); opacity: 0.5; }
.settings-item-row .max-count-input { width: 42px !important; height: 26px; font-size: 12px; padding: 2px 4px; border-radius: 6px; }
.settings-dragging { opacity: 0.25 !important; }
.settings-split-left::-webkit-scrollbar { width: 4px; }
.settings-split-left::-webkit-scrollbar-thumb { background: var(--zm-surface-2); border-radius: 2px; }
</style>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
<main class="p-6">
    <div class="bg-white rounded-2xl border overflow-hidden" style="border-color:var(--zm-surface-2)">

        <!-- Header + Tabs -->
        <div class="px-6 pt-5 pb-4">
            <div class="mb-4">
                <h2 class="text-xl font-bold" style="color:var(--zm-text-strong)">공통코드 관리</h2>
                <p class="text-sm mt-1" style="color:var(--zm-text-muted)">모듈별 공통 코드 항목을 관리합니다</p>
            </div>
            <div class="flex items-center gap-1 rounded-xl p-1.5" style="background:var(--zm-surface-1)">
                <?php foreach ($tabs as $key => $tab): ?>
                    <a href="<?= $basePath ?>/pages/settings.php?tab=<?= $key ?>"
                       class="flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-lg transition-all
                              <?= $key === $activeTab ? 'bg-primary text-white shadow-sm' : 'hover:bg-gray-200' ?>"
                       <?= $key !== $activeTab ? 'style="color:var(--zm-text-muted)"' : '' ?>>
                        <i data-lucide="<?= $tab['icon'] ?>" class="w-4 h-4"></i>
                        <?= $tab['label'] ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="h-px" style="background:var(--zm-surface-2)"></div>

        <!-- Split View -->
        <div class="flex" style="min-height:560px">

            <!-- Left: Group List -->
            <div class="settings-split-left w-64 flex-shrink-0 border-r overflow-y-auto" style="border-color:var(--zm-surface-2);max-height:calc(100vh - 250px)">
                <div class="p-2" id="groupsList"></div>
            </div>

            <!-- Right: Detail -->
            <div class="flex-1 flex flex-col overflow-hidden" style="max-height:calc(100vh - 250px)">

                <!-- Empty state -->
                <div id="detailEmpty" class="flex-1 flex items-center justify-center">
                    <div class="text-center py-16">
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-3" style="background:var(--zm-surface-1)">
                            <i data-lucide="mouse-pointer-click" class="w-6 h-6" style="color:var(--zm-text-muted)"></i>
                        </div>
                        <p class="text-sm font-medium" style="color:var(--zm-text-default)">왼쪽에서 코드 그룹을 선택하세요</p>
                        <p class="text-xs mt-1" style="color:var(--zm-text-muted)">클릭하면 바로 편집할 수 있습니다</p>
                    </div>
                </div>

                <!-- Detail panel -->
                <div id="detailPanel" class="hidden flex-1 flex flex-col overflow-hidden">

                    <!-- Title bar -->
                    <div class="flex items-center justify-between px-5 py-3 flex-shrink-0 border-b" style="border-color:var(--zm-surface-2)">
                        <div class="flex items-center gap-2">
                            <span id="detailName" class="text-base font-bold" style="color:var(--zm-text-strong)"></span>
                            <span id="itemCount" class="text-xs font-medium px-1.5 py-0.5 rounded" style="color:var(--zm-text-muted);background:var(--zm-surface-1)">0개</span>
                        </div>
                        <button id="btnAddItem" class="w-7 h-7 flex items-center justify-center rounded-lg bg-primary text-white transition-colors" title="항목 추가">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>

                    <!-- Description -->
                    <div class="px-5 py-2 flex-shrink-0">
                        <input type="text" id="detailDesc" class="w-full text-xs px-2.5 py-1.5 rounded-md border bg-transparent transition-colors" style="border-color:var(--zm-surface-2);color:var(--zm-text-default)" placeholder="비고를 입력해주세요">
                    </div>

                    <!-- Items (scrollable) -->
                    <div class="flex-1 overflow-y-auto">
                        <div id="itemsList" class="border-t" style="border-color:var(--zm-surface-2)"></div>
                        <button id="btnAddItemBottom" type="button" class="w-full py-2.5 text-xs font-medium border-t border-dashed transition-colors hover:bg-gray-50" style="color:#4F6AFF;border-color:var(--zm-surface-2)">+ 추가</button>
                        <div id="emptyItems" class="hidden py-12 text-center">
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mx-auto mb-3" style="background:var(--zm-surface-1)">
                                <i data-lucide="inbox" class="w-6 h-6" style="color:var(--zm-text-muted)"></i>
                            </div>
                            <p class="text-sm font-medium" style="color:var(--zm-text-strong)">등록된 항목이 없습니다</p>
                            <p class="text-xs mt-1" style="color:var(--zm-text-muted)">"+ 추가" 버튼으로 추가해주세요</p>
                        </div>
                        <?php if (in_array('reservation', $activeModules)): ?>
                        <p class="px-5 py-1.5 text-xs" style="color:var(--zm-text-muted)">※ 신규 항목의 최대 점유 수는 저장 후 다시 설정해주세요.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Save -->
                    <div class="flex-shrink-0 px-5 py-3 border-t flex items-center justify-end" style="border-color:var(--zm-surface-2);background:var(--zm-surface-1,#f9fafb)">
                        <button id="btnSaveAll" class="btn btn-primary">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i> 반영하기
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const basePath = '<?= $basePath ?>';
    const activeTab = '<?= $activeTab ?>';
    const tabModules = <?= json_encode($activeModules) ?>;
    const moduleLabels = <?= json_encode($tabs[$activeTab]['moduleLabels'], JSON_UNESCAPED_UNICODE) ?>;
    const initialDetailId = <?= $detailId ?>;
    const useDB = <?= $useDB ? 'true' : 'false' ?>;
    const sampleDetails = <?= $sampleDetailsJs ?>;
    let _dirty = false;

    function markDirty() {
        if (_dirty) return;
        _dirty = true;
        const btn = document.getElementById('btnSaveAll');
        if (btn && !btn.classList.contains('ring-2')) btn.classList.add('ring-2', 'ring-primary/40');
    }
    window.addEventListener('beforeunload', e => { if (_dirty) e.preventDefault(); });

    let _pendingAction = null;

    function showUnsavedModal(onSaveAndGo, onJustGo) {
        _pendingAction = { onSaveAndGo, onJustGo };
        document.getElementById('unsavedModal').classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }
    window.hideUnsavedModal = function() {
        document.getElementById('unsavedModal').classList.add('hidden');
        _pendingAction = null;
    };
    window.onUnsavedSaveAndGo = function() {
        if (!_pendingAction) return;
        const action = _pendingAction;
        window.hideUnsavedModal();
        if (action.onSaveAndGo) action.onSaveAndGo();
    };
    window.onUnsavedJustGo = function() {
        if (!_pendingAction) return;
        const action = _pendingAction;
        window.hideUnsavedModal();
        _dirty = false;
        if (action.onJustGo) action.onJustGo();
    };

    document.addEventListener('click', function(e) {
        if (!_dirty) return;
        const link = e.target.closest('a[href]');
        if (!link) return;
        const href = link.getAttribute('href');
        if (!href || href === '#' || href.startsWith('javascript:')) return;
        if (link.closest('#detailPanel')) return;
        e.preventDefault();
        showUnsavedModal(
            function() { saveAll(function() { window.location.href = href; }); },
            function() { window.location.href = href; }
        );
    });

    const allGroups = <?= $groupsJson ?>;

    // Icons & styles
    const groupIcons = {
        '고용형태':'briefcase','고용상태':'user-check','발령유형':'file-text','직급':'award','직책':'shield',
        '근무유형':'clock','휴가유형':'plane','비용항목':'receipt','카드유형':'credit-card',
        '사업원가항목':'calculator','사업상태':'trending-up','사업구분':'layers','자원목록':'monitor',
        '일정유형':'calendar-check','캘린더 색상':'palette',
    };
    const moduleIconStyle = {
        hr:          { color:'#6366f1', bg:'rgba(99,102,241,0.08)' },
        attendance:  { color:'#10b981', bg:'rgba(16,185,129,0.08)' },
        card:        { color:'#f59e0b', bg:'rgba(245,158,11,0.08)' },
        business:    { color:'#a855f7', bg:'rgba(168,85,247,0.08)' },
        reservation: { color:'#06b6d4', bg:'rgba(6,182,212,0.08)' },
        schedule:    { color:'#ec4899', bg:'rgba(236,72,153,0.08)' },
    };
    const HR_DEDICATED_GROUPS = { '직급':'ranks', '직책':'duties' };
    const dedicatedNames = ['직급', '직책'];

    // === Left panel: render groups as compact list ===
    function renderGroups(groups) {
        const container = document.getElementById('groupsList');
        if (!groups.length) {
            container.innerHTML = `<div class="py-16 text-center">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center mx-auto mb-3" style="background:var(--zm-surface-1)">
                    <i data-lucide="database" class="w-5 h-5" style="color:var(--zm-text-muted)"></i>
                </div>
                <p class="text-xs" style="color:var(--zm-text-muted)">등록된 코드 그룹이 없습니다</p>
            </div>`;
            if (window.lucide) lucide.createIcons();
            return;
        }

        let html = '';
        let lastModule = null;
        groups.forEach(g => {
            const mod = g.module || tabModules[0];
            if (mod !== lastModule) {
                lastModule = mod;
                const modLabel = moduleLabels[mod] || mod;
                const mStyle = moduleIconStyle[mod] || moduleIconStyle.hr;
                html += `<div class="px-3 ${mod === tabModules[0] ? 'pt-2' : 'pt-5'} pb-1">
                    <span class="text-[11px] font-bold uppercase tracking-wider inline-block pb-1" style="color:${mStyle.color};border-bottom:2px solid ${mStyle.color}">${esc(modLabel)}</span>
                </div>`;
            }
            const iconName = groupIcons[g.name] || 'tag';
            const mStyle = moduleIconStyle[mod] || moduleIconStyle.hr;
            const isDedicated = dedicatedNames.includes(g.name);
            const countText = isDedicated ? '용어' : (g.item_count ?? 0);
            const extraIcon = isDedicated ? '<i data-lucide="external-link" class="w-3 h-3 flex-shrink-0" style="color:var(--zm-text-muted)"></i>' : '';

            html += `<div class="settings-group-item flex items-center gap-2 px-3 py-2 rounded-lg cursor-pointer" data-group-id="${g.id}" data-module="${mod}" style="--gi-color:${mStyle.color}">
                <i data-lucide="${iconName}" class="w-4 h-4 flex-shrink-0" style="color:${mStyle.color}"></i>
                <span class="gi-name text-[13px] flex-1 truncate" style="color:var(--zm-text-strong)" data-group-name>${esc(g.name)}</span>
                ${extraIcon}
                <span class="group-count text-[11px] font-medium px-1.5 py-0.5 rounded-md flex-shrink-0" style="background:var(--zm-surface-1);color:var(--zm-text-muted)">${countText}</span>
            </div>`;
        });
        container.innerHTML = html;

        container.querySelectorAll('.settings-group-item').forEach(el => {
            el.addEventListener('click', () => {
                const gid = parseInt(el.dataset.groupId);
                const name = el.querySelector('[data-group-name]')?.textContent?.trim();
                if (name && HR_DEDICATED_GROUPS[name]) {
                    location.href = `${basePath}/pages/org_hierarchy_settings.php#${HR_DEDICATED_GROUPS[name]}`;
                    return;
                }
                selectGroup(gid);
            });
        });
        if (window.lucide) lucide.createIcons();
    }

    function highlightGroup(gid) {
        document.querySelectorAll('.settings-group-item').forEach(el => {
            el.classList.toggle('active', parseInt(el.dataset.groupId) === gid);
        });
    }

    function selectGroup(gid) {
        if (gid === currentDetailId) return;
        if (_dirty) {
            showUnsavedModal(
                function() { saveAll(function() { highlightGroup(gid); openDetail(gid); }); },
                function() { highlightGroup(gid); openDetail(gid); }
            );
            return;
        }
        highlightGroup(gid);
        openDetail(gid);
    }

    function showDetailPanel() {
        document.getElementById('detailEmpty').classList.add('hidden');
        document.getElementById('detailPanel').classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }
    function showEmptyState() {
        document.getElementById('detailEmpty').classList.remove('hidden');
        document.getElementById('detailPanel').classList.add('hidden');
    }

    // === Open detail (AJAX or sample) ===
    let currentDetailId = 0;
    let currentModule = '';

    function openDetail(groupId) {
        if (useDB) {
            fetch(`${basePath}/api/common_codes.php?action=getGroup&id=${groupId}`)
                .then(r => r.json())
                .then(data => {
                    if (data.error) { AppUI.toast(data.error, 'error'); return; }
                    currentDetailId = groupId;
                    populateDetail(data.group || data);
                    showDetailPanel();
                })
                .catch(() => AppUI.toast('데이터를 불러올 수 없습니다.', 'error'));
        } else {
            const detail = sampleDetails[groupId];
            if (detail) {
                currentDetailId = groupId;
                populateDetail(detail);
                showDetailPanel();
            } else {
                AppUI.toast('샘플 데이터가 없습니다.', 'warning');
            }
        }
    }

    function populateDetail(detail) {
        _dirty = false;
        const btn = document.getElementById('btnSaveAll');
        if (btn) btn.classList.remove('ring-2', 'ring-primary/40');
        document.getElementById('detailName').textContent = detail.name;
        document.getElementById('detailDesc').value = detail.description || '';
        currentModule = detail.module || '';
        renderItems(detail.items || []);
        updateItemCount();
    }

    // === Detail editing ===
    document.getElementById('detailDesc')?.addEventListener('input', markDirty);

    function addNewItem() {
        const list = document.getElementById('itemsList');
        const rows = list.querySelectorAll('.settings-item-row');
        const nextOrder = rows.length + 1;
        const tempId = 'new_' + Date.now();
        const row = createItemRow({id:tempId,code:'',name:'',sort_order:nextOrder,is_active:1,ref_count:0}, nextOrder);
        list.appendChild(row);
        initDragDrop();
        renumberRows();
        updateItemCount();
        updateEmptyState();
        row.querySelector('.item-name-input').focus();
        if (window.lucide) lucide.createIcons();
        markDirty();
    }
    document.getElementById('btnAddItem')?.addEventListener('click', addNewItem);
    document.getElementById('btnAddItemBottom')?.addEventListener('click', addNewItem);

    document.getElementById('btnDeleteGroup')?.addEventListener('click', async () => {
        const name = document.getElementById('detailName')?.textContent || '';
        if (!(await AppUI.confirm(`"${name}" 공통정보 그룹을 삭제하시겠습니까?\n포함된 모든 항목도 함께 삭제됩니다.`))) return;
        if (!useDB) { AppUI.toast('DB 연결이 필요합니다.', 'warning'); return; }
        fetch(`${basePath}/api/common_codes.php?action=deleteGroup`, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({id: currentDetailId})
        })
        .then(r => r.json())
        .then(data => {
            if (data.error) { AppUI.toast(data.error, 'error'); return; }
            const el = document.querySelector(`.settings-group-item[data-group-id="${currentDetailId}"]`);
            if (el) el.remove();
            currentDetailId = 0;
            showEmptyState();
            AppUI.toast('삭제되었습니다.', 'success');
        });
    });

    document.getElementById('btnSaveAll')?.addEventListener('click', saveAll);

    function renderItems(items) {
        const list = document.getElementById('itemsList');
        list.innerHTML = '';
        items.forEach((item, i) => list.appendChild(createItemRow(item, i + 1)));
        initDragDrop();
        updateEmptyState();
        if (window.lucide) lucide.createIcons();
    }

    function createItemRow(item, order) {
        const row = document.createElement('div');
        row.className = 'settings-item-row flex items-center gap-2 px-4 py-1.5 border-t transition-all';
        row.style.borderColor = 'var(--zm-surface-2)';
        row.dataset.itemId = item.id;
        row.dataset.order = order;
        const isActive = item.is_active == 1;
        if (!isActive) row.style.opacity = '0.45';

        const refCount = item.ref_count ?? null;
        const refUnit = (currentModule === 'hr') ? '명' : '건';
        let refBadgeHtml = '';
        if (refCount !== null) {
            if (refCount >= 10)
                refBadgeHtml = `<span class="ref-count-badge badge-info inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-semibold rounded-full flex-shrink-0"><span style="width:5px;height:5px;border-radius:50%;background:var(--zm-st-info-solid);flex-shrink:0"></span>${refCount}${refUnit}</span>`;
            else if (refCount > 0)
                refBadgeHtml = `<span class="ref-count-badge badge-success inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-medium rounded-full flex-shrink-0"><span style="width:5px;height:5px;border-radius:50%;background:var(--zm-st-success-solid);flex-shrink:0"></span>${refCount}${refUnit}</span>`;
            else
                refBadgeHtml = `<span class="ref-count-badge badge-neutral inline-flex items-center px-1.5 py-0.5 text-[11px] rounded-full flex-shrink-0">${refCount}${refUnit}</span>`;
        }

        const maxCountHtml = tabModules.includes('reservation')
            ? `<input type="number" min="1" max="99" value="${item.max_count ?? 1}" class="max-count-input reg-input text-center flex-shrink-0" placeholder="1">`
            : '';

        row.innerHTML = `
            <span class="item-order text-xs w-4 text-right flex-shrink-0 tabular-nums" style="color:var(--zm-text-muted)">${order}</span>
            <span class="drag-handle cursor-grab active:cursor-grabbing flex-shrink-0 opacity-30 hover:opacity-70" style="color:var(--zm-text-muted)">
                <i data-lucide="grip-vertical" class="w-3.5 h-3.5"></i>
            </span>
            <input type="hidden" class="item-code-input" value="${esc(item.code || '')}">
            <input type="text" value="${esc(item.name)}" class="item-name-input flex-1 min-w-0 rounded-md border transition-colors" style="color:var(--zm-text-strong)" placeholder="항목명">
            ${maxCountHtml}
            ${refBadgeHtml}
            <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                <input type="checkbox" class="sr-only peer item-toggle" ${isActive ? 'checked' : ''}>
                <div class="relative w-8 h-[18px] bg-gray-300 rounded-full peer-checked:bg-primary transition-colors
                            after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-3.5 after:w-3.5 after:transition-all
                            peer-checked:after:translate-x-3.5"></div>
            </label>
            <button type="button" class="delete-item-btn flex-shrink-0 p-1 rounded transition-all" title="삭제">
                <i data-lucide="x" class="w-3 h-3"></i>
            </button>`;

        row.querySelector('.delete-item-btn').addEventListener('click', async () => {
            const itemId = row.dataset.itemId;
            if (String(itemId).startsWith('new_')) {
                row.remove(); renumberRows(); updateItemCount(); updateEmptyState(); return;
            }
            if (!useDB) { row.remove(); renumberRows(); updateItemCount(); updateEmptyState(); return; }
            try {
                const resp = await fetch(`${basePath}/api/common_codes.php?action=checkItemUsage&id=${itemId}`);
                const data = await resp.json();
                if (data.hasLinks) {
                    const details = data.links.map(l => `  - ${l.label}: ${l.count}건`).join('\n');
                    const msg = `"${data.item_name}" 항목이 다음 데이터에서 사용 중입니다:\n\n${details}\n\n삭제할 수 없습니다. 비활성(OFF)으로 전환하시겠습니까?`;
                    if ((await AppUI.confirm(msg))) {
                        const toggle = row.querySelector('.item-toggle');
                        if (toggle && toggle.checked) { toggle.checked = false; toggle.dispatchEvent(new Event('change')); }
                        AppUI.toast(`"${data.item_name}" 항목을 비활성으로 전환했습니다. 반영하기를 눌러 저장하세요.`, 'warning');
                    }
                } else {
                    if ((await AppUI.confirm(`"${data.item_name}" 항목을 삭제하시겠습니까?`))) {
                        row.remove(); renumberRows(); updateItemCount(); updateEmptyState(); markDirty();
                    }
                }
            } catch (e) { AppUI.toast('사용처를 확인할 수 없습니다.', 'error'); }
        });

        row.querySelector('.item-name-input').addEventListener('input', markDirty);
        row.querySelector('.item-toggle').addEventListener('change', function() {
            row.style.opacity = this.checked ? '1' : '0.45';
            markDirty();
        });
        return row;
    }

    function renumberRows() {
        document.querySelectorAll('#itemsList .settings-item-row').forEach((row, i) => {
            const el = row.querySelector('.item-order');
            if (el) el.textContent = i + 1;
        });
    }
    function updateItemCount() {
        const el = document.getElementById('itemCount');
        if (el) el.textContent = document.querySelectorAll('#itemsList .settings-item-row').length + '개';
    }
    function updateEmptyState() {
        const empty = document.getElementById('emptyItems');
        const list = document.getElementById('itemsList');
        if (!empty) return;
        const hasRows = list && list.querySelectorAll('.settings-item-row').length > 0;
        empty.classList.toggle('hidden', hasRows);
        list.classList.toggle('hidden', !hasRows);
    }
    function updateGroupCount(gid, count) {
        const el = document.querySelector(`.settings-group-item[data-group-id="${gid}"] .group-count`);
        if (el) el.textContent = count;
    }

    // === Drag & Drop (items) ===
    let preOrderSnapshot = null;
    function captureOrderSnapshot() {
        return [...document.getElementById('itemsList').querySelectorAll('.settings-item-row')]
            .map(r => r.dataset.order || r.querySelector('.item-order')?.textContent);
    }
    function restoreOrderSnapshot(snapshot) {
        const list = document.getElementById('itemsList');
        const rows = [...list.querySelectorAll('.settings-item-row')];
        const rowMap = new Map();
        rows.forEach(r => rowMap.set(r.dataset.order || r.querySelector('.item-order')?.textContent, r));
        snapshot.forEach(key => { const row = rowMap.get(key); if (row) list.appendChild(row); });
        renumberRows();
    }
    function showSettingsReorderBar() {
        const list = document.getElementById('itemsList');
        const parent = list?.parentElement;
        if (!parent || parent.querySelector('.zm-reorder-bar')) return;
        const bar = document.createElement('div');
        bar.className = 'zm-reorder-bar';
        bar.innerHTML = '<button class="zm-cancel-btn">취소</button><button class="zm-save-btn">저장</button>';
        parent.appendChild(bar);
        bar.querySelector('.zm-cancel-btn').addEventListener('click', () => {
            if (preOrderSnapshot) { restoreOrderSnapshot(preOrderSnapshot); preOrderSnapshot = null; }
            bar.remove(); initDragDrop();
        });
        bar.querySelector('.zm-save-btn').addEventListener('click', () => { preOrderSnapshot = null; bar.remove(); saveAll(); });
    }

    function initDragDrop() {
        const list = document.getElementById('itemsList');
        if (!list) return;
        let ds = null;
        const OLD_KEY = '__settingsDragHandler';
        if (list[OLD_KEY]) list.removeEventListener('pointerdown', list[OLD_KEY]);
        const handler = e => {
            const grip = e.target.closest('.drag-handle');
            if (!grip) return;
            const row = grip.closest('.settings-item-row');
            if (!row) return;
            const rows = [...list.querySelectorAll('.settings-item-row')];
            const idx = rows.indexOf(row);
            if (idx < 0 || rows.length < 2) return;
            e.preventDefault();
            if (!preOrderSnapshot) preOrderSnapshot = captureOrderSnapshot();
            const rect = row.getBoundingClientRect();
            const ox = e.clientX - rect.left, oy = e.clientY - rect.top;
            const origTops = rows.map(r => r.getBoundingClientRect().top);
            const itemH = rows.length > 1 ? origTops[1] - origTops[0] : rect.height;
            const ghost = row.cloneNode(true);
            ghost.classList.remove('settings-dragging');
            ghost.style.cssText = `position:fixed;left:${rect.left}px;top:${rect.top}px;width:${rect.width}px;z-index:9999;pointer-events:none;background:var(--zm-surface-1,#fff);box-shadow:0 12px 28px rgba(0,0,0,0.12),0 4px 8px rgba(0,0,0,0.08);border-radius:8px;`;
            document.body.appendChild(ghost);
            row.classList.add('settings-dragging');
            list.classList.add('zm-drag-active');
            ds = { fromIdx:idx, gapIdx:idx, origTops, itemH, ghost, row, ox, oy, rows };

            function onMove(ev) {
                if (!ds) return;
                ghost.style.left = (ev.clientX - ox) + 'px';
                ghost.style.top = (ev.clientY - oy) + 'px';
                let newGap = rows.length;
                for (let i = 0; i < rows.length; i++) {
                    if (ev.clientY < origTops[i] + itemH / 2) { newGap = i; break; }
                }
                if (newGap === ds.gapIdx) return;
                ds.gapIdx = newGap;
                rows.forEach((r, i) => {
                    if (i === idx) return;
                    let shift = 0;
                    if (idx < newGap && i > idx && i < newGap) shift = -itemH;
                    else if (idx > newGap && i >= newGap && i < idx) shift = itemH;
                    r.style.transform = shift ? `translateY(${shift}px)` : '';
                });
            }
            function onUp() {
                document.removeEventListener('pointermove', onMove);
                document.removeEventListener('pointerup', onUp);
                document.removeEventListener('pointercancel', onUp);
                if (!ds) return;
                if (ghost.isConnected) ghost.remove();
                row.classList.remove('settings-dragging');
                list.classList.remove('zm-drag-active');
                rows.forEach(r => r.style.transform = '');
                const { fromIdx, gapIdx } = ds;
                ds = null;
                if (gapIdx === fromIdx || gapIdx === fromIdx + 1) return;
                const freshRows = [...list.querySelectorAll('.settings-item-row')];
                const movedRow = freshRows[fromIdx];
                const insertAt = gapIdx > fromIdx ? gapIdx - 1 : gapIdx;
                if (insertAt >= freshRows.length) list.appendChild(movedRow);
                else {
                    const targetRow = freshRows.filter((_,i) => i !== fromIdx)[insertAt];
                    if (targetRow) list.insertBefore(movedRow, targetRow);
                    else list.appendChild(movedRow);
                }
                renumberRows();
                showSettingsReorderBar();
            }
            document.addEventListener('pointermove', onMove);
            document.addEventListener('pointerup', onUp);
            document.addEventListener('pointercancel', onUp);
        };
        list[OLD_KEY] = handler;
        list.addEventListener('pointerdown', handler);
    }

    // === Save ===
    function saveAll(onComplete) {
        const rows = document.querySelectorAll('#itemsList .settings-item-row');
        const items = [];
        rows.forEach((row, i) => {
            const name = row.querySelector('.item-name-input')?.value?.trim();
            if (!name) return;
            let id = row.dataset.itemId;
            if (String(id).startsWith('new_')) id = 0;
            items.push({
                id: parseInt(id) || 0,
                code: row.querySelector('.item-code-input')?.value?.trim() || '',
                name, sort_order: i + 1,
                is_active: row.querySelector('.item-toggle')?.checked ? 1 : 0,
            });
        });

        const codeSet = new Set();
        for (const item of items) {
            if (!item.code) continue;
            const upper = item.code.toUpperCase();
            if (codeSet.has(upper)) {
                AppUI.toast(`코드 '${upper}'가 중복됩니다.`, 'error');
                return;
            }
            codeSet.add(upper);
        }

        if (!useDB) { AppUI.toast('DB 연결이 필요합니다.', 'warning'); return; }

        const desc = document.getElementById('detailDesc').value.trim();
        const btn = document.getElementById('btnSaveAll');
        const origHtml = btn.innerHTML;
        btn.disabled = true;
        btn.classList.add('opacity-60', 'pointer-events-none');
        btn.innerHTML = '<svg class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity=".3"/><path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg> 저장 중…';

        function restoreBtn() {
            btn.disabled = false;
            btn.classList.remove('opacity-60', 'pointer-events-none', 'ring-2', 'ring-primary/40');
            btn.innerHTML = origHtml;
            if (window.lucide) lucide.createIcons();
        }

        fetch(`${basePath}/api/common_codes.php?action=saveGroup`, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({id:currentDetailId, name:document.getElementById('detailName').textContent, description:desc})
        });

        fetch(`${basePath}/api/common_codes.php?action=saveItems`, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({group_id:currentDetailId, items})
        })
        .then(r => r.json())
        .then(data => {
            if (data.error) { restoreBtn(); AppUI.toast(data.error, 'error'); return; }
            _dirty = false;

            if (tabModules.includes('reservation')) {
                const promises = [];
                rows.forEach(row => {
                    const itemId = parseInt(row.dataset.itemId);
                    if (!itemId || String(row.dataset.itemId).startsWith('new_')) return;
                    const mc = row.querySelector('.max-count-input');
                    if (!mc) return;
                    promises.push(fetch(`${basePath}/api/reservation.php?action=updateMaxCount`, {
                        method:'POST', headers:{'Content-Type':'application/json'},
                        body: JSON.stringify({item_id:itemId, max_count:parseInt(mc.value)||1})
                    }));
                });
                Promise.all(promises).then(() => afterSave(restoreBtn, onComplete));
            } else {
                afterSave(restoreBtn, onComplete);
            }
        })
        .catch(() => { restoreBtn(); AppUI.toast('저장에 실패했습니다.', 'error'); });
    }

    function afterSave(restoreBtn, onComplete) {
        AppUI.toast('반영되었습니다.', 'success');
        if (typeof onComplete === 'function') {
            restoreBtn();
            onComplete();
            return;
        }
        fetch(`${basePath}/api/common_codes.php?action=getGroup&id=${currentDetailId}`)
            .then(r => r.json())
            .then(data => {
                restoreBtn();
                if (data.group || data.items) {
                    const detail = data.group || data;
                    populateDetail(detail);
                    const activeCount = (detail.items || []).filter(i => i.is_active == 1).length;
                    updateGroupCount(currentDetailId, activeCount);
                }
            })
            .catch(() => restoreBtn());
    }

    // === Helper ===
    function esc(str) {
        if (!str) return '';
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    // === Init ===
    renderGroups(allGroups);

    if (initialDetailId > 0) {
        highlightGroup(initialDetailId);
        const initialDetail = <?= $detailJson ?>;
        if (initialDetail) {
            currentDetailId = initialDetailId;
            populateDetail(initialDetail);
            showDetailPanel();
            if (useDB) {
                fetch(`${basePath}/api/common_codes.php?action=getGroup&id=${initialDetailId}`)
                    .then(r => r.json())
                    .then(data => { if (data.group) populateDetail(data.group); });
            }
        }
    }
});
</script>

<!-- 미저장 변경 확인 모달 -->
<div id="unsavedModal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center">
    <div class="absolute inset-0 bg-black/50" onclick="hideUnsavedModal()"></div>
    <div class="relative bg-white rounded-xl shadow-2xl max-w-sm w-full mx-4 overflow-hidden" style="background: var(--zm-surface-1, #fff);">
        <div class="px-6 pt-6 pb-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-full bg-amber-50 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-500"></i>
                </div>
                <h3 class="text-base font-bold" style="color: var(--zm-text-strong, #1e293b);">저장하지 않은 변경사항</h3>
            </div>
            <p class="text-sm leading-relaxed" style="color: var(--zm-text-default, #64748b);">변경사항이 저장되지 않았습니다. 어떻게 하시겠습니까?</p>
        </div>
        <div class="px-6 pb-5 flex flex-col gap-2">
            <button onclick="onUnsavedSaveAndGo()" class="w-full px-4 py-2.5 bg-primary text-white rounded-lg text-sm font-semibold hover:brightness-110 hover:shadow-md active:scale-[0.98] transition-all cursor-pointer">
                저장하고 이동
            </button>
            <button onclick="onUnsavedJustGo()" class="w-full px-4 py-2.5 rounded-lg text-sm font-semibold border hover:bg-gray-100 active:scale-[0.98] transition-all cursor-pointer" style="color: var(--zm-text-default, #64748b); border-color: var(--zm-border, #e2e8f0);">
                저장하지 않고 이동
            </button>
            <button onclick="hideUnsavedModal()" class="w-full px-4 py-2 text-sm hover:text-gray-700 active:scale-[0.98] transition-all cursor-pointer" style="color: var(--zm-text-muted, #94a3b8);">
                취소
            </button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
