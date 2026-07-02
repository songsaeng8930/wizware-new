<?php
$pageTitle = '직원관리';
$currentPage = 'hr';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/hr_codes.php';
require_once __DIR__ . '/../includes/org_path.php';

$hrPositions = getHrCodeItems('직급');
$hrTitles = getHrCodeItems('직책');
$hrEmpTypes = getHrCodeItems('고용형태');
$hrEmpStatuses = getHrCodeItems('고용상태');

$hrPositionsJson = json_encode(array_map(fn($p) => $p['name'], $hrPositions), JSON_UNESCAPED_UNICODE);
$hrTitlesJson = json_encode(array_map(fn($t) => $t['name'], $hrTitles), JSON_UNESCAPED_UNICODE);
$hrEmpTypesJson = json_encode(array_map(fn($et) => $et['name'], $hrEmpTypes), JSON_UNESCAPED_UNICODE);
$hrEmpStatusesJson = json_encode(array_map(fn($es) => $es['name'], $hrEmpStatuses), JSON_UNESCAPED_UNICODE);

// DB에서 데이터 로드 시도
$useDB = false;
$employeesJson = '';
$departmentsJson = '';

$pdo = getDBConnection();
if ($pdo) {
    try {
        $check = $pdo->query("SHOW TABLES LIKE 'employees'");
        if ($check->rowCount() > 0) {
            $pdo->exec("SET SESSION group_concat_max_len = 65536");
            // 부서 목록
            $deptStmt = $pdo->query('
                SELECT d.id, d.parent_id, d.name, d.code, d.head_employee_id
                FROM departments d WHERE d.is_active = 1
                ORDER BY d.sort_order, d.name
            ');
            $departments = $deptStmt->fetchAll();

            // 직원 목록 (부서 + 본부 + 계약 상태 포함)
            //   contract_state: 'none'(미체결) / 'draft' / 'signed'(체결) / 'expiring'(만료임박, 30일 내)
            $empStmt = $pdo->query("
                SELECT e.*, d.name AS department_name,
                       CASE WHEN d.head_employee_id = e.id THEN 1 ELSE 0 END AS is_head,
                       CASE WHEN pd.parent_id IS NOT NULL THEN pd.name
                            ELSE COALESCE(d.name, '')
                       END AS division_name,
                       COALESCE(lc.contract_status, 'none') AS contract_state,
                       lc.contract_type AS contract_type,
                       lc.contract_start AS contract_start,
                       lc.contract_end AS contract_end,
                       CASE
                           WHEN lc.contract_end IS NOT NULL
                           THEN DATEDIFF(lc.contract_end, CURDATE())
                           ELSE NULL
                       END AS contract_days_left,
                       CASE
                           WHEN lc.contract_end IS NOT NULL
                                AND DATEDIFF(lc.contract_end, CURDATE()) <= 30
                                AND DATEDIFF(lc.contract_end, CURDATE()) >= 0
                           THEN 1 ELSE 0
                       END AS contract_expiring,
                       sk.skills,
                       el.languages,
                       ec.certifications
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN departments pd ON d.parent_id = pd.id
                LEFT JOIN (
                    SELECT lc1.employee_id, lc1.contract_status, lc1.contract_type, lc1.contract_start, lc1.contract_end
                    FROM labor_contracts lc1
                    INNER JOIN (
                        SELECT employee_id, MAX(id) AS max_id
                        FROM labor_contracts GROUP BY employee_id
                    ) latest ON lc1.id = latest.max_id
                ) lc ON lc.employee_id = e.id
                LEFT JOIN (
                    SELECT employee_id, GROUP_CONCAT(skill_name SEPARATOR '||') AS skills
                    FROM employee_skills GROUP BY employee_id
                ) sk ON sk.employee_id = e.id
                LEFT JOIN (
                    SELECT employee_id,
                           GROUP_CONCAT(CONCAT(language, ':', COALESCE(level,''), ':', COALESCE(test_name,''), ':', COALESCE(test_score,'')) SEPARATOR '||') AS languages
                    FROM employee_languages GROUP BY employee_id
                ) el ON el.employee_id = e.id
                LEFT JOIN (
                    SELECT employee_id, GROUP_CONCAT(cert_name SEPARATOR '||') AS certifications
                    FROM employee_certifications GROUP BY employee_id
                ) ec ON ec.employee_id = e.id
                WHERE e.is_active = 1
                ORDER BY e.id DESC
            ");
            $employees = $empStmt->fetchAll();
            attachOrgPathToEmployeeRows($pdo, $employees);

            if (!empty($employees)) {
                $useDB = true;
                $employeesJson = json_encode($employees, JSON_UNESCAPED_UNICODE);
                $departmentsJson = json_encode($departments, JSON_UNESCAPED_UNICODE);
            }
        }
    } catch (PDOException $e) {
        error_log('employees.php DB error: ' . $e->getMessage());
    }
}

// DB 미연결 시 빈 배열 (직원 데이터는 DB에서만 로드)
if (!$useDB) {
    $departmentsJson = json_encode([], JSON_UNESCAPED_UNICODE);
    $employeesJson = json_encode([], JSON_UNESCAPED_UNICODE);
}
?>

<!-- 메인 컨텐츠 영역 -->
<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6">

        <!-- 상단 헤더 -->
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-lg font-bold text-slate-100">직원관리</h2>
            <div class="flex items-center gap-2">
                <button id="btnDownloadEmployees" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-100 hover:border-gray-400 hover:text-gray-900 transition-colors">
                    <i data-lucide="download" class="w-3 h-3"></i>
                    다운로드
                </button>
                <a href="<?= $basePath ?>/pages/employee_register.php" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-100 hover:border-gray-400 hover:text-gray-900 transition-colors">
                    <i data-lucide="user-plus" class="w-3 h-3"></i>
                    등록하기
                </a>
                <a href="<?= $basePath ?>/pages/employee_bulk.php" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-100 hover:border-gray-400 hover:text-gray-900 transition-colors">
                    <i data-lucide="users" class="w-3 h-3"></i>
                    일괄 등록하기
                </a>
            </div>
        </div>

        <!-- 검색 & 필터 -->
        <style>
            .emp-fpill{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:8px;font-size:13px;font-weight:500;color:var(--zm-text-muted);background:var(--zm-surface-2);border:1px solid var(--zm-border);cursor:pointer;user-select:none;transition:all .15s;white-space:nowrap}
            .emp-fpill:hover{border-color:var(--zm-text-subtle);color:var(--zm-text-default)}
            .emp-fpill--active{border-color:var(--zm-text-strong)!important;color:var(--zm-text-strong)!important;background:var(--zm-surface-2)!important;font-weight:600!important}
            .emp-fpill--add{border-style:dashed}.emp-fpill--add:hover{border-color:var(--zm-text-subtle);color:var(--zm-text-default)}
            .emp-fdrop{position:absolute;min-width:220px;max-height:340px;overflow-y:auto;background:var(--zm-surface-1);border:1px solid var(--zm-border);border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:200;padding:4px;animation:empFdropIn .18s cubic-bezier(.2,0,.13,1.02)}
            @keyframes empFdropIn{0%{opacity:0;transform:translateY(-6px) scale(.97)}100%{opacity:1;transform:translateY(0) scale(1)}}
            .emp-fdrop__item{display:flex;align-items:center;gap:8px;padding:8px 12px;border-radius:8px;font-size:13px;color:var(--zm-text-default);cursor:pointer;transition:all .18s cubic-bezier(.4,0,.2,1)}
            .emp-fdrop__item:hover{background:var(--zm-surface-3)}
            .emp-fdrop__item:active{transform:scale(.98);transition-duration:.06s}
            .emp-fdrop__item--active{color:var(--zm-text-strong);background:var(--zm-surface-2);font-weight:600}
            .emp-fdrop__item--active::after{content:'✓';margin-left:auto;font-size:12px}
            .emp-fdrop__sep{height:1px;background:var(--zm-border);margin:4px 8px}
            .emp-fdrop__header{padding:8px 12px 4px;font-size:11px;font-weight:600;color:var(--zm-text-subtle);letter-spacing:.5px}
            .emp-fdrop__back{display:flex;align-items:center;gap:6px;padding:8px 12px;border-radius:8px;font-size:13px;font-weight:600;color:var(--zm-text-strong);cursor:pointer;transition:all .18s cubic-bezier(.4,0,.2,1)}
            .emp-fdrop__back:hover{background:var(--zm-surface-3)}
            .emp-fdrop__search{width:100%;padding:8px 12px;font-size:13px;color:var(--zm-text-default);background:var(--zm-surface-2);border:1px solid var(--zm-border);border-radius:8px;outline:none}
            .emp-fdrop__search:focus{border-color:var(--zm-primary);box-shadow:0 0 0 3px rgba(79,106,255,.12)}
            .emp-fdrop__date{width:100%;padding:6px 10px;font-size:12px;color:var(--zm-text-default);background:var(--zm-surface-2);border:1px solid var(--zm-border);border-radius:6px;outline:none}
            .emp-fdrop__date:focus{border-color:var(--zm-primary);box-shadow:0 0 0 3px rgba(79,106,255,.12)}
            .emp-fchip{display:inline-flex;align-items:center;gap:4px;padding:4px 6px 4px 10px;border-radius:6px;font-size:12px;font-weight:500;background:var(--zm-surface-2);color:var(--zm-text-strong);white-space:nowrap}
            .emp-fchip__x{width:16px;height:16px;display:inline-flex;align-items:center;justify-content:center;border-radius:50%;cursor:pointer;transition:background .15s}
            .emp-fchip__x:hover{background:rgba(0,0,0,0.08)}
            .emp-fchip__x svg{width:10px;height:10px}
        </style>
        <div class="bg-white rounded-xl border border-gray-200 mb-5">
            <!-- 검색바 -->
            <div class="p-4 pb-3">
                <div class="relative">
                    <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none"></i>
                    <input type="text" id="globalSearch"
                           placeholder="이름, 사번, 이메일, 스킬로 검색하세요..."
                           class="w-full pl-11 pr-4 py-3 text-sm bg-gray-50 border border-gray-200 rounded-xl text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300/30 focus:border-gray-300 transition-colors">
                </div>
            </div>
            <!-- 필터 필 -->
            <div class="px-4 pb-4 relative" id="pillRow">
                <div class="flex items-center gap-2 flex-wrap">
                    <?php if (isOrgLevelEnabled('division')): ?><div class="emp-fpill" data-fpill="division"><span class="emp-fpill__text"><?= htmlspecialchars(getOrgLabel('division')) ?></span><i data-lucide="chevron-down" class="w-3 h-3 shrink-0"></i></div><?php endif; ?>
                    <?php if (isOrgLevelEnabled('department')): ?><div class="emp-fpill" data-fpill="department"><span class="emp-fpill__text"><?= htmlspecialchars(getOrgLabel('department')) ?></span><i data-lucide="chevron-down" class="w-3 h-3 shrink-0"></i></div><?php endif; ?>
                    <div class="emp-fpill" data-fpill="position"><span class="emp-fpill__text">직급</span><i data-lucide="chevron-down" class="w-3 h-3 shrink-0"></i></div>
                    <div class="emp-fpill" data-fpill="empType"><span class="emp-fpill__text">고용형태</span><i data-lucide="chevron-down" class="w-3 h-3 shrink-0"></i></div>
                    <div class="emp-fpill" data-fpill="empStatus"><span class="emp-fpill__text">고용상태</span><i data-lucide="chevron-down" class="w-3 h-3 shrink-0"></i></div>
                    <div class="h-5 w-px bg-gray-300 mx-0.5"></div>
                    <div class="emp-fpill emp-fpill--add" data-fpill="more"><i data-lucide="plus" class="w-3.5 h-3.5 shrink-0"></i><span>필터</span></div>
                </div>
                <div id="filterDrop" class="hidden emp-fdrop"></div>
            </div>
            <!-- 적용 칩 -->
            <div id="filterChipBar" class="hidden px-4 pb-3.5 pt-3 border-t border-gray-200">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs text-slate-500 shrink-0">적용 중</span>
                    <div id="chipContainer" class="flex items-center gap-1.5 flex-wrap"></div>
                    <button id="btnClearAll" class="text-xs text-slate-500 hover:text-red-400 transition-colors shrink-0 ml-auto">모두 지우기</button>
                </div>
            </div>
        </div>

        <!-- 결과 헤더 -->
        <div class="flex items-center justify-between mb-3">
            <div class="text-sm text-gray-600">
                구성원 총 <span id="totalCount" class="font-bold text-primary text-base">0</span> 명
            </div>
            <div class="flex items-center gap-2">
                <select id="pageSize" class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-300/20 focus:border-gray-300 bg-white">
                    <option value="20">20</option>
                    <option value="50" selected>50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>

        <!-- 테이블 -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full text-sm emp-table">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="px-4 py-3 text-center text-slate-400 font-medium w-16">No.</th>
                        <th class="px-4 py-3 text-center text-slate-400 font-medium">사번</th>
                        <th class="px-4 py-3 text-center text-slate-400 font-medium">이름</th>
                        <?php if (isOrgLevelEnabled('division')): ?><th class="px-4 py-3 text-center text-slate-400 font-medium" style="min-width:100px"><?= htmlspecialchars(getOrgLabel('division')) ?></th><?php endif; ?>
                        <?php if (isOrgLevelEnabled('department')): ?><th class="px-4 py-3 text-center text-slate-400 font-medium" style="min-width:90px"><?= htmlspecialchars(getOrgLabel('department')) ?></th><?php endif; ?>
                        <?php if (isOrgLevelEnabled('department')): ?><th class="px-4 py-3 text-center text-slate-400 font-medium w-20"><?= htmlspecialchars(getOrgHeadTitle('department')) ?></th><?php endif; ?>
                        <th class="px-4 py-3 text-center text-slate-400 font-medium">직급</th>
                        <th class="px-4 py-3 text-center text-slate-400 font-medium">고용형태</th>
                        <th class="px-4 py-3 text-center text-slate-400 font-medium">고용상태</th>
                        <th class="px-4 py-3 text-center text-slate-400 font-medium" style="min-width:150px">계약기간</th>
                        <th class="px-4 py-3 text-center text-slate-400 font-medium w-24">잔여일</th>
                        <th class="px-4 py-3 text-center text-slate-400 font-medium">이메일</th>
                    </tr>
                </thead>
                <tbody id="empTableBody"></tbody>
            </table>
        </div>

        <!-- 페이지네이션 -->
        <div id="pagination" class="flex items-center justify-center gap-1 mt-5"></div>

    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const basePath = '<?= $basePath ?>';
    const hrPositions = <?= $hrPositionsJson ?>;
    const hrTitles = <?= $hrTitlesJson ?>;
    const hrEmpTypes = <?= $hrEmpTypesJson ?>;
    const hrEmpStatuses = <?= $hrEmpStatusesJson ?>;

    let allEmployees = <?= $employeesJson ?>;
    let allDepartments = <?= $departmentsJson ?>;

    allEmployees.forEach(emp => {
        emp._skills = (emp.skills || '').split('||').filter(Boolean);
        emp._langs = (emp.languages || '').split('||').filter(Boolean).map(s => {
            const p = s.split(':');
            return { lang: p[0]||'', level: p[1]||'', test: p[2]||'', score: p[3]||'' };
        });
        emp._certs = (emp.certifications || '').split('||').filter(Boolean);
    });

    const divisions = [...new Set(allEmployees.map(e => e.division_name||e.affiliation||'').filter(Boolean))].sort();
    const divSet = new Set(divisions);
    const deptNames = [...new Set(allEmployees.map(e => e.department_name||'').filter(d => d && !divSet.has(d)))].sort();
    const deptByDiv = {};
    allEmployees.forEach(e => {
        const div = e.division_name||e.affiliation||'';
        const dept = e.department_name||'';
        if (div && dept && !divSet.has(dept)) {
            if (!deptByDiv[div]) deptByDiv[div] = new Set();
            deptByDiv[div].add(dept);
        }
    });
    const allSkills = [...new Set(allEmployees.flatMap(e => e._skills))].sort();
    const allLangs = [...new Set(allEmployees.flatMap(e => e._langs.map(l => l.lang)))].sort();
    const allCerts = [...new Set(allEmployees.flatMap(e => e._certs))].sort();

    let F = { search:'', division:'', department:'', position:'', title:'',
              empTypes:[], empStatuses:['재직'], contractStates:[],
              hireDateFrom:'', hireDateTo:'',
              skills:[], languages:[], certifications:[] };
    let filteredEmployees = [];
    let currentPage = 1;
    let pageSize = parseInt(document.getElementById('pageSize').value);
    let currentDrop = null;
    const dropEl = document.getElementById('filterDrop');
    const pillRow = document.getElementById('pillRow');
    const _OL = window.ORG_LABELS || {};
    const SHOW_DIVISION = ((_OL.division||{}).enabled !== false);
    const SHOW_DEPARTMENT = ((_OL.department||{}).enabled !== false);
    const LABELS = { division: (_OL.division||{}).label||'본부', department: (_OL.department||{}).label||'부서', position:'직급', title:'직책',
                     empTypes:'고용형태', empStatuses:'고용상태', contractStates:'계약상태',
                     hireDate:'입사일', skills:'스킬', languages:'어학', certifications:'자격증' };

    init();
    function init() { applyFilters(); renderChips(); renderPillStates(); bindEvents(); }

    function openDrop(key) {
        if (currentDrop === key) { closeDrop(); return; }
        closeDrop();
        const pill = document.querySelector('[data-fpill="'+key+'"]');
        if (!pill) return;
        const pr = pillRow.getBoundingClientRect();
        const r = pill.getBoundingClientRect();
        dropEl.style.left = (r.left - pr.left) + 'px';
        dropEl.style.top = (r.bottom - pr.top + 6) + 'px';
        renderDropContent(key);
        dropEl.classList.remove('hidden');
        currentDrop = key;
        pill.classList.add('emp-fpill--active');
    }
    function closeDrop() {
        dropEl.classList.add('hidden'); dropEl.innerHTML = '';
        if (currentDrop) { const p=document.querySelector('[data-fpill="'+currentDrop+'"]'); if(p&&!isActive(currentDrop)) p.classList.remove('emp-fpill--active'); }
        currentDrop = null;
    }
    function isActive(k) {
        if(k==='division') return SHOW_DIVISION && !!F.division; if(k==='department') return SHOW_DEPARTMENT && !!F.department;
        if(k==='position') return !!F.position; if(k==='empType') return F.empTypes.length>0;
        if(k==='empStatus') return F.empStatuses.length>0;
        if(k==='more') return F.title||F.contractStates.length||F.hireDateFrom||F.hireDateTo||F.skills.length||F.languages.length||F.certifications.length;
        return false;
    }
    function renderDropContent(key) {
        if(key==='more'){renderMoreMenu();return;}
        if(key==='division'&&SHOW_DIVISION){renderSelectList('division',divisions,F.division);return;}
        if(key==='department'&&SHOW_DEPARTMENT){const opts=F.division&&deptByDiv[F.division]?[...deptByDiv[F.division]].sort():deptNames;renderSelectList('department',opts,F.department);return;}
        if(key==='position'){renderSelectList('position',hrPositions,F.position);return;}
        if(key==='empType'){renderMultiList('empTypes',hrEmpTypes,F.empTypes);return;}
        if(key==='empStatus'){renderMultiList('empStatuses',hrEmpStatuses,F.empStatuses);return;}
        if(key==='title'){renderSelectList('title',hrTitles,F.title,true);return;}
        if(key==='contractState'){renderMultiList('contractStates',['미체결','체결','만료임박','만료'],F.contractStates,true);return;}
        if(key==='hireDate'){renderDateRange();return;}
        if(key==='skill'){renderTagList('skills',allSkills,F.skills,'스킬 검색...',true);return;}
        if(key==='language'){renderTagList('languages',allLangs,F.languages,'언어 검색...',true);return;}
        if(key==='certification'){renderTagList('certifications',allCerts,F.certifications,'자격증 검색...',true);return;}
    }
    function renderSelectList(key,opts,cur,hasBack){
        let h='';
        if(hasBack) h+=backHtml(LABELS[key]);
        h+='<div class="emp-fdrop__item'+(!cur?' emp-fdrop__item--active':'')+'" data-sv="" data-sk="'+key+'">전체</div>';
        opts.forEach(o=>{h+='<div class="emp-fdrop__item'+(cur===o?' emp-fdrop__item--active':'')+'" data-sv="'+esc(o)+'" data-sk="'+key+'">'+esc(o)+'</div>';});
        dropEl.innerHTML=h; lucide.createIcons({nodes:[dropEl]});
        dropEl.querySelectorAll('[data-sv]').forEach(el=>el.addEventListener('click',()=>{
            F[el.dataset.sk]=el.dataset.sv;
            if(el.dataset.sk==='division'){const validDepts=el.dataset.sv&&deptByDiv[el.dataset.sv]?deptByDiv[el.dataset.sv]:null;if(F.department&&validDepts&&!validDepts.has(F.department))F.department='';}
            applyFilters();renderChips();renderPillStates();closeDrop();
        }));
        if(hasBack) bindBack();
    }
    function renderMultiList(key,opts,sel,hasBack){
        let h='';
        if(hasBack) h+=backHtml(LABELS[key]);
        opts.forEach(o=>{const ck=sel.includes(o)?' checked':'';h+='<label class="emp-fdrop__item" style="cursor:pointer"><input type="checkbox" value="'+esc(o)+'"'+ck+' class="accent-primary mr-1" data-mk="'+key+'"> '+esc(o)+'</label>';});
        dropEl.innerHTML=h; lucide.createIcons({nodes:[dropEl]});
        dropEl.querySelectorAll('input[type="checkbox"]').forEach(cb=>cb.addEventListener('change',()=>{
            const k=cb.dataset.mk;
            if(cb.checked){if(!F[k].includes(cb.value))F[k].push(cb.value);}else{F[k]=F[k].filter(v=>v!==cb.value);}
            applyFilters();renderChips();renderPillStates();
        }));
        if(hasBack) bindBack();
    }
    function renderTagList(key,opts,sel,ph,hasBack){
        let h='';
        if(hasBack) h+=backHtml(LABELS[key]);
        h+='<div class="p-2"><input type="text" class="emp-fdrop__search" placeholder="'+ph+'" id="tagSrch"></div><div id="tagLst">';
        if(!opts.length) h+='<div class="emp-fdrop__item" style="color:#64748b;cursor:default">등록된 항목이 없어요</div>';
        else opts.forEach(o=>{h+='<div class="emp-fdrop__item'+(sel.includes(o)?' emp-fdrop__item--active':'')+'" data-tg="'+esc(o)+'" data-tk="'+key+'">'+esc(o)+'</div>';});
        h+='</div>';
        dropEl.innerHTML=h; lucide.createIcons({nodes:[dropEl]});
        const si=dropEl.querySelector('#tagSrch');
        si?.addEventListener('input',()=>{const q=si.value.toLowerCase();dropEl.querySelectorAll('[data-tg]').forEach(el=>el.style.display=el.dataset.tg.toLowerCase().includes(q)?'':'none');});
        dropEl.querySelectorAll('[data-tg]').forEach(el=>el.addEventListener('click',()=>{
            const k=el.dataset.tk,v=el.dataset.tg;
            if(F[k].includes(v)){F[k]=F[k].filter(x=>x!==v);el.classList.remove('emp-fdrop__item--active');}
            else{F[k].push(v);el.classList.add('emp-fdrop__item--active');}
            applyFilters();renderChips();renderPillStates();
        }));
        setTimeout(()=>si?.focus(),50);
        if(hasBack) bindBack();
    }
    function renderDateRange(){
        let h=backHtml('입사일');
        h+='<div class="p-3 space-y-3"><div><label class="text-xs text-slate-500 mb-1 block">시작일</label><input type="date" class="emp-fdrop__date" id="fdDF" value="'+(F.hireDateFrom||'')+'"></div>';
        h+='<div><label class="text-xs text-slate-500 mb-1 block">종료일</label><input type="date" class="emp-fdrop__date" id="fdDT" value="'+(F.hireDateTo||'')+'"></div>';
        h+='<button class="w-full py-2 text-xs font-medium text-white bg-primary rounded-lg hover:opacity-90" id="fdDA">적용</button></div>';
        dropEl.innerHTML=h; lucide.createIcons({nodes:[dropEl]});
        document.getElementById('fdDA')?.addEventListener('click',()=>{
            F.hireDateFrom=document.getElementById('fdDF').value; F.hireDateTo=document.getElementById('fdDT').value;
            applyFilters();renderChips();renderPillStates();closeDrop();
        });
        bindBack();
    }
    function renderMoreMenu(){
        dropEl.innerHTML=
            '<div class="emp-fdrop__header">인사</div>'+
            '<div class="emp-fdrop__item" data-sub="title"><i data-lucide="briefcase" class="w-3.5 h-3.5"></i>직책</div>'+
            '<div class="emp-fdrop__sep"></div>'+
            '<div class="emp-fdrop__header">기간</div>'+
            '<div class="emp-fdrop__item" data-sub="hireDate"><i data-lucide="calendar" class="w-3.5 h-3.5"></i>입사일</div>'+
            '<div class="emp-fdrop__item" data-sub="contractState"><i data-lucide="file-text" class="w-3.5 h-3.5"></i>계약상태</div>'+
            '<div class="emp-fdrop__sep"></div>'+
            '<div class="emp-fdrop__header">역량</div>'+
            '<div class="emp-fdrop__item" data-sub="skill"><i data-lucide="wrench" class="w-3.5 h-3.5"></i>보유 스킬'+(allSkills.length?' <span class="ml-auto text-xs text-gray-400">'+allSkills.length+'</span>':'')+'</div>'+
            '<div class="emp-fdrop__item" data-sub="language"><i data-lucide="globe" class="w-3.5 h-3.5"></i>어학 능력'+(allLangs.length?' <span class="ml-auto text-xs text-gray-400">'+allLangs.length+'</span>':'')+'</div>'+
            '<div class="emp-fdrop__item" data-sub="certification"><i data-lucide="award" class="w-3.5 h-3.5"></i>자격증'+(allCerts.length?' <span class="ml-auto text-xs text-gray-400">'+allCerts.length+'</span>':'')+'</div>';
        lucide.createIcons({nodes:[dropEl]});
        dropEl.querySelectorAll('[data-sub]').forEach(el=>el.addEventListener('click',()=>renderDropContent(el.dataset.sub)));
    }
    function backHtml(l){return '<div class="emp-fdrop__back" data-back><i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>'+l+'</div><div class="emp-fdrop__sep"></div>';}
    function bindBack(){dropEl.querySelector('[data-back]')?.addEventListener('click',()=>renderMoreMenu());}

    function renderChips(){
        const ct=document.getElementById('chipContainer'),bar=document.getElementById('filterChipBar'),chips=[];
        if(SHOW_DIVISION&&F.division)chips.push({k:'division',l:LABELS.division,v:F.division});
        if(SHOW_DEPARTMENT&&F.department)chips.push({k:'department',l:LABELS.department,v:F.department});
        if(F.position)chips.push({k:'position',l:'직급',v:F.position});
        if(F.title)chips.push({k:'title',l:'직책',v:F.title});
        F.empTypes.forEach(v=>chips.push({k:'empTypes',l:'고용',v}));
        F.empStatuses.forEach(v=>chips.push({k:'empStatuses',l:'상태',v}));
        F.contractStates.forEach(v=>chips.push({k:'contractStates',l:'계약',v}));
        if(F.hireDateFrom||F.hireDateTo)chips.push({k:'hireDate',l:'입사일',v:(F.hireDateFrom||'?')+' ~ '+(F.hireDateTo||'?')});
        F.skills.forEach(v=>chips.push({k:'skills',l:'스킬',v}));
        F.languages.forEach(v=>chips.push({k:'languages',l:'어학',v}));
        F.certifications.forEach(v=>chips.push({k:'certifications',l:'자격증',v}));
        if(!chips.length){bar.classList.add('hidden');return;}
        bar.classList.remove('hidden');
        const xSvg='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
        ct.innerHTML=chips.map((c,i)=>'<span class="emp-fchip"><span class="text-gray-400">'+c.l+':</span> '+esc(c.v)+'<span class="emp-fchip__x" data-ci="'+i+'">'+xSvg+'</span></span>').join('');
        ct.querySelectorAll('[data-ci]').forEach(el=>el.addEventListener('click',()=>{
            const c=chips[+el.dataset.ci];
            if(['division','department','position','title'].includes(c.k))F[c.k]='';
            else if(c.k==='hireDate'){F.hireDateFrom='';F.hireDateTo='';}
            else F[c.k]=F[c.k].filter(x=>x!==c.v);
            applyFilters();renderChips();renderPillStates();
        }));
    }
    function renderPillStates(){
        const map={division:SHOW_DIVISION&&!!F.division,department:SHOW_DEPARTMENT&&!!F.department,position:!!F.position,empType:F.empTypes.length>0,empStatus:F.empStatuses.length>0};
        const def={division:LABELS.division,department:LABELS.department,position:'직급',empType:'고용형태',empStatus:'고용상태'};
        Object.entries(map).forEach(([k,a])=>{
            const pill=document.querySelector('[data-fpill="'+k+'"]');if(!pill)return;
            const txt=pill.querySelector('.emp-fpill__text'); pill.classList.toggle('emp-fpill--active',a);
            if(k==='empType')txt.textContent=a?'고용형태 · '+F.empTypes.length:'고용형태';
            else if(k==='empStatus')txt.textContent=a?'고용상태 · '+F.empStatuses.length:'고용상태';
            else txt.textContent=a?F[k]:def[k];
        });
        const mp=document.querySelector('[data-fpill="more"]');
        if(mp){const c=(F.title?1:0)+F.contractStates.length+(F.hireDateFrom||F.hireDateTo?1:0)+F.skills.length+F.languages.length+F.certifications.length;mp.classList.toggle('emp-fpill--active',c>0);}
    }

    function applyFilters(){
        const q=F.search.toLowerCase();
        filteredEmployees=allEmployees.filter(emp=>{
            if(q){const hay=[emp.name,emp.employee_no,emp.email,emp.position,emp.title,...(emp._skills||[]),...(emp._langs||[]).map(l=>l.lang+' '+l.test+' '+l.score),...(emp._certs||[])].filter(Boolean).join(' ').toLowerCase();if(!hay.includes(q))return false;}
            if(SHOW_DIVISION&&F.division&&(emp.division_name||emp.affiliation||'')!==F.division)return false;
            if(SHOW_DEPARTMENT&&F.department&&(emp.department_name||'')!==F.department)return false;
            if(F.position&&(emp.position||'')!==F.position)return false;
            if(F.title&&(emp.title||'')!==F.title)return false;
            if(F.empTypes.length&&!F.empTypes.includes(emp.employment_type||'정규직'))return false;
            if(F.empStatuses.length&&!F.empStatuses.includes(emp.employment_status||'재직'))return false;
            if(F.contractStates.length){const dl=emp.contract_days_left!=null?parseInt(emp.contract_days_left):null;const cs=emp.contract_state||'none';if(!F.contractStates.some(v=>{if(v==='만료')return cs==='signed'&&dl!==null&&dl<0;if(v==='만료임박')return cs==='signed'&&dl!==null&&dl>=0&&dl<=30;if(v==='체결')return cs==='signed'&&(dl===null||dl>30);if(v==='미체결')return cs==='none';return false;}))return false;}
            if(F.hireDateFrom&&(emp.hire_date||'')<F.hireDateFrom)return false;
            if(F.hireDateTo&&(emp.hire_date||'')>F.hireDateTo)return false;
            if(F.skills.length&&!F.skills.some(s=>(emp._skills||[]).includes(s)))return false;
            if(F.languages.length&&!F.languages.some(l=>(emp._langs||[]).some(el=>el.lang===l)))return false;
            if(F.certifications.length&&!F.certifications.some(c=>(emp._certs||[]).includes(c)))return false;
            return true;
        });
        currentPage=1;renderTable();
    }
    function clearAll(){
        F={search:'',division:'',department:'',position:'',title:'',empTypes:[],empStatuses:['재직'],contractStates:[],hireDateFrom:'',hireDateTo:'',skills:[],languages:[],certifications:[]};
        document.getElementById('globalSearch').value='';
        applyFilters();renderChips();renderPillStates();
    }

    function bindEvents(){
        let st=null; const si=document.getElementById('globalSearch');
        si.addEventListener('input',()=>{clearTimeout(st);st=setTimeout(()=>{F.search=si.value.trim();applyFilters();},200);});
        si.addEventListener('keydown',e=>{if(e.key==='Enter'){clearTimeout(st);F.search=si.value.trim();applyFilters();}});
        document.querySelectorAll('[data-fpill]').forEach(p=>p.addEventListener('click',e=>{e.stopPropagation();openDrop(p.dataset.fpill);}));
        dropEl.addEventListener('click',e=>e.stopPropagation());
        document.addEventListener('click',()=>{if(currentDrop)closeDrop();});
        document.getElementById('btnClearAll').addEventListener('click',clearAll);
        document.getElementById('pageSize').addEventListener('change',function(){pageSize=parseInt(this.value);currentPage=1;renderTable();});
        document.getElementById('empTableBody').addEventListener('click',e=>{const r=e.target.closest('.emp-row');if(r)location.href=basePath+'/pages/employee_register.php?id='+r.dataset.id;});
        document.getElementById('btnDownloadEmployees').addEventListener('click',downloadCSV);
    }


    function renderTable(){
        const tbody=document.getElementById('empTableBody');
        const start=(currentPage-1)*pageSize, end=start+pageSize;
        const pageData=filteredEmployees.slice(start,end);
        document.getElementById('totalCount').textContent=filteredEmployees.length;
        if(!pageData.length){
            const colCount = 9 + (SHOW_DIVISION ? 1 : 0) + (SHOW_DEPARTMENT ? 2 : 0);
            tbody.innerHTML='<tr><td colspan="'+colCount+'" class="px-4 py-16 text-center text-gray-400"><div class="flex flex-col items-center gap-2"><i data-lucide="search" class="w-8 h-8 text-gray-300"></i><p>검색 결과가 없습니다.</p></div></td></tr>';
            document.getElementById('pagination').innerHTML='';
            lucide.createIcons({nodes:[tbody]});
            return;
        }
        tbody.innerHTML=pageData.map(emp=>{
            const div=emp.division_name||emp.affiliation||'';
            const dept=emp.department_name||'';
            const deptDisplay=(dept&&dept!==div)?esc(dept):'<span class="text-gray-300">—</span>';
            const headIcon=emp.is_head==1?'<i data-lucide="check" class="w-4 h-4 text-primary mx-auto"></i>':'';
            return '<tr class="border-b border-gray-100 emp-row cursor-pointer hover:bg-blue-50 transition-colors" data-id="'+emp.id+'">'+
                '<td class="px-4 py-3.5 text-center text-gray-400">'+emp.id+'</td>'+
                '<td class="px-4 py-3.5 text-center text-gray-400 text-xs">'+esc(emp.employee_no||'')+'</td>'+
                '<td class="px-4 py-3.5 text-center text-gray-800 font-medium">'+esc(emp.name)+'</td>'+
                (SHOW_DIVISION?'<td class="px-4 py-3.5 text-center text-gray-600">'+esc(div)+'</td>':'')+
                (SHOW_DEPARTMENT?'<td class="px-4 py-3.5 text-center text-gray-600">'+deptDisplay+'</td>':'')+
                (SHOW_DEPARTMENT?'<td class="px-4 py-3.5 text-center">'+headIcon+'</td>':'')+
                '<td class="px-4 py-3.5 text-center text-gray-600">'+esc(emp.position||'')+'</td>'+
                '<td class="px-4 py-3.5 text-center text-gray-600">'+esc(emp.employment_type||'정규직')+'</td>'+
                '<td class="px-4 py-3.5 text-center text-gray-600">'+esc(emp.employment_status||'재직')+'</td>'+
                '<td class="px-4 py-3.5 text-center">'+renderContractPeriod(emp)+'</td>'+
                '<td class="px-4 py-3.5 text-center">'+renderContractDday(emp)+'</td>'+
                '<td class="px-4 py-3.5 text-center text-gray-400 text-xs">'+esc(emp.email||'')+'</td>'+
            '</tr>';
        }).join('');
        renderPagination();
    }
    function renderPagination(){
        const tp=Math.ceil(filteredEmployees.length/pageSize);
        const ct=document.getElementById('pagination');
        if(tp<=1){ct.innerHTML='';return;}
        let h='<button class="pg-btn '+(currentPage===1?'pg-disabled':'')+'" data-page="'+(currentPage-1)+'" '+(currentPage===1?'disabled':'')+'><i data-lucide="chevron-left" class="w-3 h-3"></i></button>';
        const mv=5;let sp=Math.max(1,currentPage-Math.floor(mv/2));let ep=Math.min(tp,sp+mv-1);
        if(ep-sp<mv-1)sp=Math.max(1,ep-mv+1);
        if(sp>1){h+='<button class="pg-btn" data-page="1">1</button>';if(sp>2)h+='<span class="px-1 text-gray-400">...</span>';}
        for(let i=sp;i<=ep;i++)h+='<button class="pg-btn '+(i===currentPage?'pg-active':'')+'" data-page="'+i+'">'+i+'</button>';
        if(ep<tp){if(ep<tp-1)h+='<span class="px-1 text-gray-400">...</span>';h+='<button class="pg-btn" data-page="'+tp+'">'+tp+'</button>';}
        h+='<button class="pg-btn '+(currentPage===tp?'pg-disabled':'')+'" data-page="'+(currentPage+1)+'" '+(currentPage===tp?'disabled':'')+'><i data-lucide="chevron-right" class="w-3 h-3"></i></button>';
        ct.innerHTML=h;
        ct.querySelectorAll('[data-page]').forEach(b=>b.addEventListener('click',()=>{const p=+b.dataset.page;if(p>=1&&p<=tp){currentPage=p;renderTable();window.scrollTo({top:0,behavior:'smooth'});}}));
    }
    function downloadCSV(){
        const hdr=['No.','사번','이름'];
        if(SHOW_DIVISION) hdr.push(LABELS.division);
        if(SHOW_DEPARTMENT) hdr.push(LABELS.department);
        if(SHOW_DEPARTMENT) hdr.push(((_OL.department||{}).head||'부서장'));
        hdr.push('직급','고용형태','고용상태','계약시작','계약종료','잔여일');
        hdr.push('이메일','스킬');
        const rows=filteredEmployees.map(e=>{
            const row=[e.id,e.employee_no||'',e.name||''];
            if(SHOW_DIVISION) row.push(e.division_name||e.affiliation||'');
            if(SHOW_DEPARTMENT) row.push(e.department_name||'');
            if(SHOW_DEPARTMENT) row.push(e.is_head==1?'O':'X');
            row.push(e.position||'',e.employment_type||'정규직',e.employment_status||'재직',e.contract_start||'',e.contract_end||'',e.contract_days_left!=null?e.contract_days_left:'');
            row.push(e.email||'',(e._skills||[]).join(', '));
            return row;
        });
        const bom='﻿';
        const csv=bom+[hdr,...rows].map(r=>r.map(c=>'"'+String(c).replace(/"/g,'""')+'"').join(',')).join('\n');
        const b=new Blob([csv],{type:'text/csv;charset=utf-8;'});const u=URL.createObjectURL(b);
        const a=document.createElement('a');a.href=u;a.download='직원목록_'+new Date().toISOString().slice(0,10)+'.csv';a.click();URL.revokeObjectURL(u);
    }
    function esc(s){if(!s)return'';const d=document.createElement('div');d.textContent=s;return d.innerHTML;}
    function fmtDate(d){ if(!d) return ''; const p=d.split('-'); return p[0]+'.'+p[1]+'.'+p[2]; }
    function fmtDateShort(d){ if(!d) return ''; const p=d.split('-'); return p[0].slice(2)+'.'+p[1]+'.'+p[2]; }

    function renderContractPeriod(emp){
        const s=emp.contract_state||'none';
        if(s==='none') return '<span class="text-gray-300">—</span>';
        if(s==='draft') return '<span class="text-xs text-gray-400">작성중</span>';
        const start=emp.contract_start, end=emp.contract_end;
        if(!start && !end) return '<span class="text-xs text-gray-400">기간 미설정</span>';
        const type=emp.contract_type||'';
        if(type==='permanent' && !end) return '<span class="text-xs text-gray-600">'+fmtDateShort(start)+' ~</span><span class="block text-[11px] text-gray-400 mt-0.5">정규(무기한)</span>';
        return '<span class="text-xs text-gray-600">'+fmtDateShort(start||'')+' ~ '+fmtDateShort(end||'')+'</span>';
    }

    function renderContractDday(emp){
        const s=emp.contract_state||'none';
        if(s==='none' || s==='draft') return '<span class="text-gray-300">—</span>';
        const type=emp.contract_type||'';
        if(type==='permanent' && !emp.contract_end) return '<span class="text-xs text-gray-400">—</span>';
        if(emp.contract_end==null) return '<span class="text-xs text-gray-400">—</span>';
        const d=emp.contract_days_left;
        if(d==null) return '<span class="text-gray-300">—</span>';
        const days=parseInt(d);
        if(days<0) return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-50 text-red-600 border border-red-200">만료</span>';
        if(days===0) return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-50 text-red-600 border border-red-200 animate-pulse">D-Day</span>';
        if(days<=7) return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-50 text-red-600 border border-red-200">D-'+days+'</span>';
        if(days<=30) return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-600 border border-amber-200">D-'+days+'</span>';
        if(days<=90) return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-500 border border-blue-200">D-'+days+'</span>';
        return '<span class="text-xs text-gray-400">D-'+days+'</span>';
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
