<?php
$currentPage = $currentPage ?? 'dashboard';
// v1 숨김 메뉴 · 배열에서 제거하면 다시 표시됨
$hiddenMenus = ['business'];
// 프로젝트 루트를 기준으로 basePath 계산 (pages/ 하위에서도 동일하게 동작)
$docRoot = realpath($_SERVER['DOCUMENT_ROOT']);
$projectRoot = realpath(__DIR__ . '/..');
$basePath = rtrim(str_replace('\\', '/', str_replace($docRoot, '', $projectRoot)), '/');

// 현재 사용자 역할 (admin / manager / user). 비로그인/미설정은 'user'로 처리.
$__sidebarRole = function_exists('getCurrentUserRole') ? (getCurrentUserRole() ?: 'user') : 'user';
// 사이드바 확장 가능성을 위한 화이트리스트 유틸 · 섹션/아이템마다 'roles' 키로 접근 제어
if (!function_exists('bms_role_allowed')) {
    function bms_role_allowed(array $node, string $role): bool
    {
        if (empty($node['roles'])) return true; // roles 미지정 = 모든 사용자에게 노출
        return in_array($role, $node['roles'], true);
    }
}

// 메뉴별 DB 권한 체크 레이어 (menu_permissions 테이블) · 기존 roles 필터 위에 덧붙임
require_once __DIR__ . '/permissions.php';

// ─────────────────────────────────────────────────────────────
// 메뉴 구조 · 역할별 그룹핑
//   1) 내 업무     : 전 직원 공통
//   2) 병원 운영   : 병원 대상 특화 업무
//   3) 조직운영     : 부서장 이상 (manager, admin)
//   4) 시스템      : 관리자 전용 (admin)
// 섹션/아이템 단위로 'roles' 배열을 지정해 접근 제어.
// ─────────────────────────────────────────────────────────────
$menuSections = [
    [
        'label' => '내 업무',
        'roles' => ['user', 'manager', 'admin'],
        'items' => [
            [
                'id' => 'dashboard',
                'label' => '대시보드',
                'icon' => 'gauge',
                'url' => $basePath . '/pages/dashboard.php',
                'children' => [],
            ],
            [
                'id' => 'attendance',
                'label' => '근태',
                'icon' => 'clock',
                'url' => $basePath . '/pages/attendance.php',
                'children' => [],
            ],
            [
                'id' => 'schedule',
                'label' => '일정',
                'icon' => 'calendar-days',
                'url' => '#',
                'children' => [
                    ['label' => '캘린더',        'url' => $basePath . '/pages/schedule.php'],
                ],
            ],
            [
                'id' => 'approval',
                'label' => '전자결재',
                'icon' => 'file-signature',
                'url' => '#',
                'children' => [
                    ['label' => '기안하기', 'url' => $basePath . '/pages/approval_register.php'],
                    ['label' => '내 기안함', 'url' => $basePath . '/pages/approval_draft.php', 'active_urls' => [$basePath . '/pages/approval_view.php']],
                    ['label' => '결재함', 'url' => $basePath . '/pages/approval_pending.php'],
                    ['label' => '문서보관함', 'url' => $basePath . '/pages/approval_archive.php'],
                ],
            ],
            [
                'id' => 'board',
                'label' => '게시판',
                'icon' => 'clipboard-list',
                'url' => '#',
                'children' => [
                    ['label' => '공지사항', 'url' => $basePath . '/pages/board.php?type=notice'],
                    ['label' => '자유게시판', 'url' => $basePath . '/pages/board.php?type=free'],
                    ['label' => '자료실', 'url' => $basePath . '/pages/board.php?type=archive'],
                    ['label' => htmlspecialchars(getOrgLabel('department')) . '게시판', 'url' => $basePath . '/pages/board.php?type=department'],
                ],
            ],
            [
                'id' => 'card',
                'label' => '법인카드',
                'icon' => 'credit-card',
                'url' => '#',
                'children' => [
                    ['label' => '지출 등록',     'url' => $basePath . '/pages/card_expense_register.php'],
                    ['label' => '카드지출내역',  'url' => $basePath . '/pages/card_expenses.php'],
                    ['label' => '법인카드 규정', 'url' => $basePath . '/pages/card_regulations.php'],
                ],
            ],
            [
                'id' => 'company',
                'label' => '우리 회사',
                'icon' => 'building-2',
                'url' => '#',
                'children' => [
                    ['label' => '조직도',   'url' => $basePath . '/pages/organization.php?mode=view'],
                    ['label' => '연락망',   'url' => $basePath . '/pages/contacts.php'],
                ],
            ],
            [
                'id' => 'my_payslip',
                'label' => '내 급여',
                'icon' => 'receipt',
                'url' => $basePath . '/pages/payslip.php',
                'children' => [],
            ],
        ],
    ],
    [
        'label' => '병원 운영',
        'roles' => ['manager', 'admin'],
        'items' => [
            [
                'id' => 'hospital',
                'label' => '병원 전용',
                'icon' => 'stethoscope',
                'url' => $basePath . '/pages/hospital.php',
                'children' => [
                    ['label' => '운영 대시보드', 'url' => $basePath . '/pages/hospital.php?tab=dashboard'],
                    ['label' => '근무표',       'url' => $basePath . '/pages/hospital.php?tab=shifts'],
                    ['label' => '일일점검',     'url' => $basePath . '/pages/hospital.php?tab=checks'],
                    ['label' => '수납마감',     'url' => $basePath . '/pages/hospital.php?tab=closing'],
                    ['label' => '재고/장비',    'url' => $basePath . '/pages/hospital.php?tab=assets'],
                    ['label' => '교육/자격',    'url' => $basePath . '/pages/hospital.php?tab=training'],
                ],
            ],
        ],
    ],
    [
        'label' => '조직운영',
        'roles' => ['manager', 'admin'],
        'items' => [
            [
                'id' => 'hr',
                'label' => '인사관리',
                'icon' => 'users',
                'url' => '#',
                'children' => [
                    ['label' => '조직도', 'url' => $basePath . '/pages/organization.php'],
                    ['label' => '직원관리', 'url' => $basePath . '/pages/employees.php', 'active_urls' => [$basePath . '/pages/employee_register.php', $basePath . '/pages/employee_bulk.php']],
                    ['label' => '근태 관리', 'url' => $basePath . '/pages/att_manage.php', 'active_urls' => [$basePath . '/pages/dept_attendance.php']],
                    ['label' => '정보 변경요청', 'url' => $basePath . '/pages/employee_change_requests.php'],
                ],
            ],
            [
                'id' => 'labor',
                'label' => '노무관리',
                'icon' => 'scale',
                'url' => '#',
                'children' => [
                    ['label' => '근로자 계약', 'url' => $basePath . '/pages/labor.php?tab=contract', 'active_urls' => [$basePath . '/pages/labor_contract_form.php', $basePath . '/pages/labor_contract_template.php']],
                    ['label' => '근로자명부', 'url' => $basePath . '/pages/labor.php?tab=roster'],
                    ['label' => '임금대장', 'url' => $basePath . '/pages/labor.php?tab=payroll'],
                    ['label' => '연차관리', 'url' => $basePath . '/pages/labor.php?tab=annual'],
                    ['label' => '취업규칙', 'url' => $basePath . '/pages/labor.php?tab=rules'],
                ],
            ],
            [
                'id' => 'approval_admin',
                'label' => '결재 운영',
                'icon' => 'file-cog',
                'url' => '#',
                'roles' => ['admin', 'manager'],
                'children' => [
                    ['label' => '전체 결재 현황', 'url' => $basePath . '/pages/approval_status.php'],
                    ['label' => '결재라인 설정', 'url' => $basePath . '/pages/approval_line.php'],
                    [
                        'label' => '결재양식 관리',
                        'url' => $basePath . '/pages/approval_forms.php',
                        'active_urls' => [$basePath . '/pages/approval_form_register.php'],
                    ],
                    ['label' => '결재 감사로그', 'url' => $basePath . '/pages/approval_audit.php'],
                    ['label' => '대결 현황', 'url' => $basePath . '/pages/approval_delegate.php?mode=admin'],
                ],
            ],
            [
                'id' => 'accounting',
                'label' => '재무·정산',
                'icon' => 'calculator',
                'roles' => ['admin'],
                'children' => [
                    ['label' => '대시보드',   'url' => $basePath . '/pages/acct_dashboard.php'],
                    ['label' => '계좌관리',   'url' => $basePath . '/pages/acct_bank.php', 'active_urls' => [$basePath . '/pages/tax_account_popbill.php']],
                    ['label' => '법인카드', 'url' => $basePath . '/pages/acct_card.php'],
                    ['label' => '세금계산서', 'url' => $basePath . '/pages/acct_invoice.php'],
                    ['label' => '세무리포트', 'url' => $basePath . '/pages/acct_report.php'],
                    ['label' => '환경설정',   'url' => $basePath . '/pages/acct_settings.php'],
                ],
            ],
            [
                'id' => 'business',
                'label' => '사업',
                'icon' => 'briefcase',
                'url' => '#',
                'children' => [
                    ['label' => '사업 목록', 'url' => $basePath . '/pages/business.php?tab=list', 'active_urls' => [$basePath . '/pages/business_detail.php']],
                    ['label' => '사업 현황', 'url' => $basePath . '/pages/business.php?tab=status'],
                ],
            ],
            [
                'id' => 'business_docs',
                'label' => '업무자료실/의뢰',
                'icon' => 'folder-open',
                'url' => '#',
                'children' => [
                    ['label' => '인사/노무', 'url' => $basePath . '/pages/business_docs.php?tab=hr_labor'],
                    ['label' => '기업연구소/벤처', 'url' => $basePath . '/pages/business_docs.php?tab=research'],
                    ['label' => '특허/상표/디자인', 'url' => $basePath . '/pages/business_docs.php?tab=patent'],
                    ['label' => '감정평가', 'url' => $basePath . '/pages/business_docs.php?tab=appraisal'],
                    ['label' => '법무(등기)', 'url' => $basePath . '/pages/business_docs.php?tab=legal'],
                    ['label' => '주주총회', 'url' => $basePath . '/pages/business_docs.php?tab=shareholder'],
                    ['label' => '절세', 'url' => $basePath . '/pages/business_docs.php?tab=tax'],
                    ['label' => '고용지원금', 'url' => $basePath . '/pages/business_docs.php?tab=subsidy'],
                ],
            ],
        ],
    ],
    [
        'label' => '시스템',
        'roles' => ['admin'],
        'items' => [
            [
                'id' => 'groupware',
                'label' => '그룹웨어 관리',
                'icon' => 'settings',
                'url' => '#',
                'children' => [
                    ['label' => '공통코드 관리',  'url' => $basePath . '/pages/settings.php?tab=people'],
                    ['label' => '접근권한 관리',  'url' => $basePath . '/pages/permissions.php'],
                    ['label' => '조직 용어 설정',  'url' => $basePath . '/pages/org_hierarchy_settings.php'],
                    ['label' => '디자인 설정',    'url' => $basePath . '/pages/display_settings.php'],
                    ['label' => 'API 설정',       'url' => $basePath . '/pages/api_settings.php'],
                    ['label' => '사용자 매뉴얼',  'url' => $basePath . '/pages/manual.php'],
                ],
            ],
        ],
    ],
];

// 현재 역할이 접근 가능한 섹션/아이템만 남긴다.
// 1차: 코드에 하드코딩된 roles 화이트리스트 (bms_role_allowed)
// 2차: DB menu_permissions 테이블 (sidebarMenuVisible) · 있으면 추가 필터, 없으면 통과
$menuSections = array_values(array_filter($menuSections, function ($section) use ($__sidebarRole) {
    return bms_role_allowed($section, $__sidebarRole);
}));
foreach ($menuSections as $si => $section) {
    $menuSections[$si]['items'] = array_values(array_filter($section['items'], function ($item) use ($__sidebarRole) {
        if (!bms_role_allowed($item, $__sidebarRole)) return false;
        // 메뉴 ID 가 있으면 DB 권한도 확인 (admin 은 항상 통과)
        if (!empty($item['id']) && !sidebarMenuVisible($item['id'])) return false;
        return true;
    }));
}
// 섹션 내 아이템이 모두 필터링되어 비면 섹션 자체 제거
$menuSections = array_values(array_filter($menuSections, fn($s) => !empty($s['items'])));

// 역할별 섹션 순서 · admin은 병원/운영 메뉴를 먼저 보고, 설정은 최하단.
// manager/user는 기본 순서 유지 (내 업무 → 회사 관리 → 시스템).
if ($__sidebarRole === 'admin') {
    $__sectionPriority = ['병원 운영' => 0, '조직운영' => 1, '내 업무' => 2, '시스템' => 3];
    usort($menuSections, function ($a, $b) use ($__sectionPriority) {
        $pa = $__sectionPriority[$a['label']] ?? 99;
        $pb = $__sectionPriority[$b['label']] ?? 99;
        return $pa <=> $pb;
    });
}

// 현재 요청 URI를 기반으로 어느 하위 메뉴가 선택되었는지 계산.
// child['url']의 path가 동일하고, child에 쿼리 파라미터가 있다면 그 키/값이 현재 URI에 모두 포함되어야 매칭.
$currentUri = $_SERVER['REQUEST_URI'] ?? '';

if (!function_exists('bms_match_child_url')) {
    function bms_match_child_url(string $childUrl, string $currentUri): bool
    {
        $a = parse_url($childUrl);
        $b = parse_url($currentUri);
        if (($a['path'] ?? '') !== ($b['path'] ?? '')) return false;
        if (empty($a['query'])) return true;
        parse_str($a['query'], $aq);
        parse_str($b['query'] ?? '', $bq);
        foreach ($aq as $k => $v) {
            if (($bq[$k] ?? null) !== $v) return false;
        }
        return true;
    }
}

// 활성 메뉴는 전체 사이드바를 통틀어 "단 하나"만 허용한다.
// 우선순위: (1) 현재 URL이 child URL과 정확히 매칭되는 메뉴가 있으면 그 메뉴만 active.
//          (2) 그런 매칭이 없을 때만 $currentPage(id) 기반 fallback.
// 이 prescan 없이는, URL은 admin 메뉴의 자식을 가리키지만 $currentPage 문자열은
// 다른 메뉴의 id와 우연히 같은 경우 양쪽이 동시에 active 로 표시됨(LNB 이중 하이라이트 버그).
$__urlActive = null; // ['section' => int, 'item' => string, 'child' => int]
$__urlFallback = null; // 쿼리 없는 child URL의 path-only 매치 (덜 구체적)
foreach ($menuSections as $__si => $__sec) {
    foreach ($__sec['items'] as $__it) {
        if (empty($__it['children'])) continue;
        foreach ($__it['children'] as $__ci => $__ch) {
            if (!bms_role_allowed($__ch, $__sidebarRole)) continue;
            $__matchUrls = array_merge([$__ch['url']], $__ch['active_urls'] ?? []);
            foreach ($__matchUrls as $__matchUrl) {
                if (bms_match_child_url($__matchUrl, $currentUri)) {
                    $__parsed = parse_url($__matchUrl);
                    $__match = ['section' => $__si, 'item' => $__it['id'], 'child' => $__ci];
                    if (!empty($__parsed['query'])) {
                        $__urlActive = $__match;
                        break 4;
                    } elseif ($__urlFallback === null) {
                        $__urlFallback = $__match;
                    }
                }
            }
        }
    }
}
if ($__urlActive === null) $__urlActive = $__urlFallback;
$__urlActiveExists = $__urlActive !== null;
?>

<!-- 좌측 사이드바 -->
<div id="sidebarBackdrop" class="fixed inset-x-0 top-14 bottom-0 z-30 hidden bg-slate-950/60 backdrop-blur-[1px] lg:hidden" aria-hidden="true"></div>
<aside id="sidebar" class="fixed top-14 left-0 h-[calc(100vh-3.5rem)] w-60 -translate-x-full lg:translate-x-0 bg-slate-900 border-r border-slate-800 z-40 flex flex-col transition-transform duration-300 ease-in-out">
    <!-- 메뉴 -->
    <nav class="flex-1 overflow-y-auto py-2 zm-nav" aria-label="주 메뉴">

        <?php foreach ($menuSections as $sectionIdx => $section): ?>
            <!-- ───── 섹션: <?= $section['label'] ?> ───── -->
            <section class="<?= $sectionIdx === 0 ? 'pt-3 pb-2' : 'mt-3 pt-4 pb-2 border-t border-slate-800' ?>">
                <!-- 섹션 라벨 (대분류) -->
                <div class="px-6 mb-2">
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-[0.14em]"><?= $section['label'] ?></p>
                </div>

                <?php foreach ($section['items'] as $item): ?>
                    <?php
                    if (in_array($item['id'], $hiddenMenus)) continue;

                    // 이 항목이 전역 URL-active 타겟인지
                    $__isUrlActiveItem = $__urlActiveExists
                        && $__urlActive['item'] === $item['id'];

                    // 현재 URL과 매칭되는 child 인덱스 · URL-active 타겟에서만 유효
                    $activeChildIdx = $__isUrlActiveItem ? $__urlActive['child'] : null;

                    // $currentPage(id) 기반 fallback · URL-active 가 존재하면 전면 억제
                    $currentPageMatch = (!$__urlActiveExists) && ($currentPage === $item['id']);

                    // 부모 헤더 하이라이트: 자식 매칭 없이 자신이 $currentPage 타겟일 때만
                    $parentHighlight = $currentPageMatch && $activeChildIdx === null;
                    // 서브메뉴 펼침: URL로 이 메뉴의 자식이 매칭됐거나, fallback 매칭일 때
                    $isExpanded = $currentPageMatch || $activeChildIdx !== null;
                    ?>
                    <?php if (empty($item['children'])): ?>
                        <?php $isActive = $currentPageMatch; ?>
                        <a href="<?= $item['url'] ?>"
                           class="flex items-center justify-between px-6 py-2 mx-3 text-sm font-semibold rounded-lg transition-colors <?= $isActive ? 'text-primary bg-primary-light' : 'text-slate-200 hover:bg-slate-950' ?>">
                            <div class="flex items-center gap-2.5">
                                <i data-lucide="<?= $item['icon'] ?>" class="w-[18px] h-[18px] <?= $isActive ? 'text-primary' : 'text-slate-400' ?>"></i>
                                <span><?= $item['label'] ?></span>
                            </div>
                        </a>
                    <?php else: ?>
                        <?php $hasLink = !empty($item['url']) && $item['url'] !== '#'; $submenuId = 'submenu-' . $item['id']; ?>
                        <div class="submenu-item mb-0.5">
                            <div class="flex items-stretch mx-3 rounded-lg <?= $parentHighlight ? 'bg-primary-light' : 'hover:bg-slate-950' ?>">
                                <?php if ($hasLink): ?>
                                    <a href="<?= $item['url'] ?>"
                                       class="flex items-center gap-2.5 flex-1 min-w-0 px-6 py-2 text-sm font-semibold rounded-l-lg <?= $parentHighlight ? 'text-primary' : 'text-slate-200' ?>">
                                        <i data-lucide="<?= $item['icon'] ?>" class="w-[18px] h-[18px] <?= $parentHighlight ? 'text-primary' : 'text-slate-400' ?>"></i>
                                        <span class="truncate"><?= $item['label'] ?></span>
                                    </a>
                                <?php else: ?>
                                    <button type="button" class="submenu-toggle flex items-center gap-2.5 flex-1 min-w-0 px-6 py-2 text-sm font-semibold text-left rounded-l-lg <?= $parentHighlight ? 'text-primary' : 'text-slate-200' ?>"
                                            data-target="<?= $submenuId ?>" aria-controls="<?= $submenuId ?>" aria-expanded="<?= $isExpanded ? 'true' : 'false' ?>">
                                        <i data-lucide="<?= $item['icon'] ?>" class="w-[18px] h-[18px] <?= $parentHighlight ? 'text-primary' : 'text-slate-400' ?>"></i>
                                        <span class="truncate"><?= $item['label'] ?></span>
                                    </button>
                                <?php endif; ?>
                                <button type="button" class="submenu-toggle flex-shrink-0 px-3 flex items-center rounded-r-lg hover:bg-slate-800/70" aria-label="<?= $item['label'] ?> 하위메뉴 토글"
                                        data-target="<?= $submenuId ?>" aria-controls="<?= $submenuId ?>" aria-expanded="<?= $isExpanded ? 'true' : 'false' ?>">
                                    <svg class="w-3 h-3 text-slate-500 submenu-arrow <?= $isExpanded ? 'rotate-180' : '' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                            </div>
                            <!-- 소분류(하위 메뉴) · 들여쓰기 + 폰트 한 단계 다운 -->
                            <div id="<?= $submenuId ?>" class="submenu-children <?= $isExpanded ? '' : 'hidden' ?> mt-1 mx-3 pl-2">
                                <div class="pl-3 space-y-0.5">
                                    <?php foreach ($item['children'] as $idx => $child): ?>
                                        <?php if (!bms_role_allowed($child, $__sidebarRole)) continue; ?>
                                        <?php $childActive = ($idx === $activeChildIdx); ?>
                                        <a href="<?= $child['url'] ?>"
                                           class="relative block px-3 py-1.5 text-[13px] rounded transition-colors overflow-hidden text-ellipsis <?= $childActive ? 'text-primary bg-primary-light font-medium' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-950' ?>"
                                           style="white-space:nowrap; word-break:keep-all;">
                                            <span class="inline-block max-w-full overflow-hidden text-ellipsis align-bottom" style="white-space:nowrap; word-break:keep-all;"><?= $child['label'] ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>
    </nav>

</aside>
