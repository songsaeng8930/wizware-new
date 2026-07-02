<?php
/**
 * Zaemit 그룹웨어 · 연락망 (사내 연락처 디렉토리)
 * 읽기 전용. 재직 중인 전 직원 연락처를 카드/테이블 뷰로 조회.
 */
$pageTitle = '연락망';
$currentPage = 'company';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../config/database.php';

// 서버사이드 데이터 프리로드 (깜빡임 방지)
$employeesJson = '[]';
$departmentsJson = '[]';
$totalCount = 0;

$pdo = getDBConnection();
if ($pdo) {
    try {
        $check = $pdo->query("SHOW TABLES LIKE 'employees'");
        if ($check->rowCount() > 0) {
            $empStmt = $pdo->query("
                SELECT e.id, e.name, e.position, e.title, e.email, e.phone,
                       e.profile_image, e.department_id, d.name AS department_name
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                WHERE e.is_active = 1 AND e.employment_status = '재직'
                ORDER BY d.sort_order, e.name
            ");
            $employees = $empStmt->fetchAll(PDO::FETCH_ASSOC);
            $totalCount = count($employees);
            $employeesJson = json_encode($employees, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);

            $deptStmt = $pdo->query("
                SELECT d.id, d.name
                FROM departments d
                WHERE d.is_active = 1
                ORDER BY d.sort_order, d.name
            ");
            $departmentsJson = json_encode($deptStmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
        }
    } catch (PDOException $e) {
        error_log('Contacts page DB error: ' . $e->getMessage());
    }
}

/** 안전 출력 헬퍼 */
function esc(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
?>

<main id="mainContent" class="ml-60 pt-20 px-8 pb-8 bg-gray-50 min-h-screen transition-all duration-300">

  <!-- 헤더 바 -->
  <div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
      <h1 class="text-xl font-bold" style="color:var(--zm-text-strong)">연락망</h1>
      <span id="countChip" class="text-sm font-medium px-2.5 py-0.5 rounded-full"
            style="background:var(--zm-chip-bg); color:var(--zm-text-muted)">
        <?= $totalCount ?>명
      </span>
    </div>
    <div class="flex items-center gap-2">
      <!-- 조직 필터 -->
      <?php if (isOrgLevelEnabled('department')): ?>
      <select id="deptFilter" class="text-sm rounded-lg px-3 py-2 border focus:ring-2 focus:ring-blue-200 focus:outline-none"
              style="border-color:var(--zm-border); background:var(--zm-surface-1); color:var(--zm-text-default); min-width:160px">
        <option value="">전체 <?= esc(getOrgLabel('department')) ?></option>
      </select>
      <?php else: ?>
      <input type="hidden" id="deptFilter" value="">
      <?php endif; ?>
      <!-- 뷰 토글 -->
      <div class="flex rounded-lg border overflow-hidden" style="border-color:var(--zm-border)">
        <button id="btnGrid" class="view-toggle active px-3 py-2" title="카드 보기">
          <i data-lucide="layout-grid" class="w-4 h-4"></i>
        </button>
        <button id="btnList" class="view-toggle px-3 py-2" title="목록 보기">
          <i data-lucide="list" class="w-4 h-4"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- 검색 바 -->
  <div class="relative mb-6">
    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5" style="color:var(--zm-text-subtle)"></i>
    <input id="searchInput" type="text" placeholder="<?= isOrgLevelEnabled('department') ? '이름, ' . esc(getOrgLabel('department')) . ', 직급, 이메일, 전화번호로 검색' : '이름, 직급, 이메일, 전화번호로 검색' ?>"
           class="w-full pl-10 pr-4 py-3 text-sm rounded-xl border focus:ring-2 focus:ring-blue-200 focus:outline-none"
           style="border-color:var(--zm-border); background:var(--zm-surface-1); color:var(--zm-text-default)">
  </div>

  <!-- 카드 그리드 뷰 -->
  <div id="gridView" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"></div>

  <!-- 테이블 뷰 -->
  <div id="listView" class="hidden rounded-xl border overflow-hidden" style="border-color:var(--zm-border); background:var(--zm-surface-1)">
    <table class="emp-table w-full text-left">
      <thead>
        <tr>
          <th class="w-12"></th>
          <th>이름</th>
          <?php if (isOrgLevelEnabled('department')): ?><th><?= esc(getOrgLabel('department')) ?></th><?php endif; ?>
          <th>직급</th>
          <th>전화번호</th>
          <th>이메일</th>
        </tr>
      </thead>
      <tbody id="listBody"></tbody>
    </table>
  </div>

  <!-- 비어있을 때 -->
  <div id="emptyState" class="hidden text-center py-20" style="color:var(--zm-text-subtle)">
    <i data-lucide="users" class="w-12 h-12 mx-auto mb-3 opacity-40"></i>
    <p class="text-base">검색 결과가 없습니다</p>
  </div>

</main>

<style>
/* 뷰 토글 버튼 */
.view-toggle {
  background: var(--zm-surface-1);
  color: var(--zm-text-muted);
  transition: background 0.15s, color 0.15s;
  display: flex; align-items: center; justify-content: center;
}
.view-toggle:hover { background: var(--zm-surface-2); }
.view-toggle.active {
  background: var(--zm-primary-tint-12);
  color: var(--zm-primary);
}

/* 연락처 카드 */
.contact-card {
  background: var(--zm-surface-1);
  border: 1px solid var(--zm-border);
  border-radius: 12px;
  padding: 20px;
  transition: box-shadow 0.15s, border-color 0.15s;
}
.contact-card:hover {
  box-shadow: var(--zm-card-shadow);
  border-color: #94a3b8;
}

/* 이니셜 아바타 */
.contact-avatar {
  width: 48px; height: 48px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px; font-weight: 700;
  background: var(--zm-primary-tint-12);
  color: var(--zm-primary);
  flex-shrink: 0;
  overflow: hidden;
}
.contact-avatar img {
  width: 100%; height: 100%; object-fit: cover;
}

/* 연락 링크 */
.contact-link {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 14px; color: var(--zm-text-muted);
  text-decoration: none;
  transition: color 0.15s;
}
.contact-link:hover { color: var(--zm-text-default); }

/* 테이블 아바타 (작은) */
.contact-avatar-sm {
  width: 32px; height: 32px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 700;
  background: var(--zm-primary-tint-12);
  color: var(--zm-primary);
  flex-shrink: 0;
  overflow: hidden;
}
.contact-avatar-sm img {
  width: 100%; height: 100%; object-fit: cover;
}
</style>

<script>
(function() {
  var allEmployees = <?= $employeesJson ?>;
  var departments  = <?= $departmentsJson ?>;
  var showDepartment = <?= isOrgLevelEnabled('department') ? 'true' : 'false' ?>;

  var searchInput = document.getElementById('searchInput');
  var deptFilter  = document.getElementById('deptFilter');
  var gridView    = document.getElementById('gridView');
  var listView    = document.getElementById('listView');
  var listBody    = document.getElementById('listBody');
  var emptyState  = document.getElementById('emptyState');
  var countChip   = document.getElementById('countChip');
  var btnGrid     = document.getElementById('btnGrid');
  var btnList     = document.getElementById('btnList');

  var viewMode = 'grid';

  // 조직 필터 옵션 채우기
  if (showDepartment) {
    departments.forEach(function(d) {
      var opt = document.createElement('option');
      opt.value = d.id;
      opt.textContent = d.name;
      deptFilter.appendChild(opt);
    });
  }

  // 뷰 전환
  btnGrid.addEventListener('click', function() { setView('grid'); });
  btnList.addEventListener('click', function() { setView('list'); });

  function setView(mode) {
    viewMode = mode;
    btnGrid.classList.toggle('active', mode === 'grid');
    btnList.classList.toggle('active', mode === 'list');
    render();
  }

  // 검색 + 필터
  searchInput.addEventListener('input', render);
  deptFilter.addEventListener('change', render);

  function getFiltered() {
    var q = searchInput.value.trim().toLowerCase();
    var dept = deptFilter.value;
    return allEmployees.filter(function(e) {
      if (dept && String(e.department_id) !== dept) return false;
      if (!q) return true;
      var hay = [e.name, showDepartment ? e.department_name : '', e.position, e.email, e.phone].join(' ').toLowerCase();
      return hay.indexOf(q) !== -1;
    });
  }

  function esc(s) {
    if (!s) return '';
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  function getInitials(name) {
    if (!name) return '?';
    return name.charAt(0);
  }

  function avatarHtml(emp, cls) {
    if (emp.profile_image) {
      return '<div class="' + cls + '"><img src="' + esc(emp.profile_image) + '" alt=""></div>';
    }
    return '<div class="' + cls + '">' + esc(getInitials(emp.name)) + '</div>';
  }

  function render() {
    var list = getFiltered();
    countChip.textContent = list.length + '명';

    if (list.length === 0) {
      gridView.classList.add('hidden');
      listView.classList.add('hidden');
      emptyState.classList.remove('hidden');
      return;
    }
    emptyState.classList.add('hidden');

    if (viewMode === 'grid') {
      gridView.classList.remove('hidden');
      listView.classList.add('hidden');
      renderGrid(list);
    } else {
      gridView.classList.add('hidden');
      listView.classList.remove('hidden');
      renderList(list);
    }
  }

  function renderGrid(list) {
    var html = '';
    list.forEach(function(e) {
      var phoneLink = e.phone
        ? '<a href="tel:' + esc(e.phone) + '" class="contact-link"><i data-lucide="phone" class="w-3.5 h-3.5"></i>' + esc(e.phone) + '</a>'
        : '<span class="text-sm" style="color:var(--zm-text-subtle)">-</span>';
      var emailLink = e.email
        ? '<a href="mailto:' + esc(e.email) + '" class="contact-link"><i data-lucide="mail" class="w-3.5 h-3.5"></i>' + esc(e.email) + '</a>'
        : '<span class="text-sm" style="color:var(--zm-text-subtle)">-</span>';

      html += '<div class="contact-card">'
        + '<div class="flex items-center gap-3 mb-3">'
        +   avatarHtml(e, 'contact-avatar')
        +   '<div>'
        +     '<div class="font-semibold text-base" style="color:var(--zm-text-strong)">' + esc(e.name) + '</div>'
        +     '<div class="text-sm" style="color:var(--zm-text-muted)">' + esc(e.position || '') + (e.title ? ' · ' + esc(e.title) : '') + '</div>'
        +   '</div>'
        + '</div>'
        + (showDepartment ? '<div class="text-sm mb-3" style="color:var(--zm-text-muted)">'
        +   '<i data-lucide="building-2" class="w-3.5 h-3.5 inline-block align-text-bottom mr-1"></i>'
        +   esc(e.department_name || '-')
        + '</div>' : '')
        + '<div class="flex flex-col gap-1.5">'
        +   phoneLink
        +   emailLink
        + '</div>'
        + '</div>';
    });
    gridView.innerHTML = html;
    lucide.createIcons();
  }

  function renderList(list) {
    var html = '';
    list.forEach(function(e) {
      var phoneCell = e.phone
        ? '<a href="tel:' + esc(e.phone) + '" class="contact-link">' + esc(e.phone) + '</a>'
        : '<span style="color:var(--zm-text-subtle)">-</span>';
      var emailCell = e.email
        ? '<a href="mailto:' + esc(e.email) + '" class="contact-link">' + esc(e.email) + '</a>'
        : '<span style="color:var(--zm-text-subtle)">-</span>';

      html += '<tr>'
        + '<td>' + avatarHtml(e, 'contact-avatar-sm') + '</td>'
        + '<td class="font-medium" style="color:var(--zm-text-strong)">' + esc(e.name) + '</td>'
        + (showDepartment ? '<td>' + esc(e.department_name || '-') + '</td>' : '')
        + '<td>' + esc(e.position || '-') + '</td>'
        + '<td>' + phoneCell + '</td>'
        + '<td>' + emailCell + '</td>'
        + '</tr>';
    });
    listBody.innerHTML = html;
    lucide.createIcons();
  }

  // 초기 렌더
  render();
  lucide.createIcons();
})();
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
