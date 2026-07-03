document.addEventListener('DOMContentLoaded', function() {
    const { rawData, basePath, useDB, canEdit } = window.ORG_PAGE_CONFIG;
    let allDepartments = rawData.departments;
    let allEmployees = rawData.employees;
    let currentView = 'org';
    let selectedDeptId = null;
    let chartExpandedState = {};

    function showToast(msg, type) {
        let container = document.getElementById('orgToastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'orgToastContainer';
            container.className = 'fixed top-20 left-1/2 -translate-x-1/2 z-[9999] flex flex-col items-center gap-2';
            document.body.appendChild(container);
        }
        const toast = document.createElement('div');
        toast.className = 'px-5 py-3 rounded-xl text-sm font-semibold text-white shadow-lg transition-all duration-300 ' + (type === 'error' ? 'bg-red-500' : 'bg-primary');
        toast.textContent = msg;
        container.appendChild(toast);
        setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 2500);
    }

    // === 조직도 줌 (차트가 넓으면 한 화면에 맞춤) =========================
    // zoom 속성 사용: 레이아웃이 실제로 줄어 스크롤/중앙정렬이 자연스럽고,
    // 드롭은 네이티브 DnD(e.target 기반)라 좌표 보정 불필요.
    let chartZoom = 1;
    const CHART_ZOOM_MIN = 0.4, CHART_ZOOM_MAX = 1.3;
    function setChartZoom(z) {
        chartZoom = Math.min(CHART_ZOOM_MAX, Math.max(CHART_ZOOM_MIN, Math.round(z * 100) / 100));
        const chart = document.getElementById('orgChart');
        if (chart) chart.style.zoom = chartZoom;
        const lab = document.getElementById('chartZoomReset');
        if (lab) lab.textContent = Math.round(chartZoom * 100) + '%';
    }
    function fitChartZoom() {
        const area = document.getElementById('chartScrollArea');
        const chart = document.getElementById('orgChart');
        if (!area || !chart) return;
        chart.style.zoom = 1;                       // 원배율에서 자연 폭 측정
        const natural = chart.scrollWidth;
        const avail = area.clientWidth;
        const z = (natural > avail && natural > 0) ? (avail / natural) : 1;
        setChartZoom(z);
    }

    // === 초기화 ===
    init();

    function init() {
        document.getElementById('totalCount').textContent = allEmployees.length;
        const root = allDepartments.find(d => !d.parent_id);
        selectedDeptId = root ? root.id : null;
        renderOrgView();
        bindEvents();
    }

    // === 트리 구축 ===
    function buildTree(departments, parentId = null) {
        return departments
            .filter(d => d.parent_id == parentId)
            .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0))
            .map(dept => ({
                ...dept,
                employees: allEmployees.filter(e => e.department_id == dept.id),
                children: buildTree(departments, dept.id)
            }));
    }

    // 트리에서 특정 부서 노드 찾기
    function findNodeInTree(nodes, deptId) {
        for (const node of nodes) {
            if (node.id == deptId) return node;
            if (node.children?.length) {
                const found = findNodeInTree(node.children, deptId);
                if (found) return found;
            }
        }
        return null;
    }

    function hasActiveFilter(filter) {
        return filter && filter.employee;
    }

    function filterEmployees(employees, filter) {
        if (!filter || !filter.employee) return [...(employees || [])];
        const s = filter.employee.toLowerCase();
        return (employees || []).filter(e => e.name.toLowerCase().includes(s));
    }

    // === 조직도 통합 뷰 렌더링 ===
    function renderOrgView(filter = {}) {
        renderChartView(filter);
        renderDetailPanel(filter);
    }

    // === 리스트뷰 렌더링 ===
    function renderListView(filter = {}) {
        const tbody = document.getElementById('listViewBody');
        let employees = [...allEmployees];

        if (filter.employee) {
            const s = filter.employee.toLowerCase();
            employees = employees.filter(e => e.name.toLowerCase().includes(s));
        }

        if (employees.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-slate-400">
                        <i data-lucide="search" class="w-6 h-6 mb-2"></i>
                        <p>검색 결과가 없습니다.</p>
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = employees.map(emp => `
            <tr class="border-b border-slate-800 hover:bg-slate-950 cursor-pointer transition-colors" data-emp-row="${emp.id}">
                <td class="px-4 py-3 text-center">
                    <div class="flex items-center justify-center gap-2">
                        <div class="w-7 h-7 rounded-full bg-slate-800 flex items-center justify-center">
                            <i data-lucide="user" class="text-slate-400 w-3 h-3"></i>
                        </div>
                        <span class="font-medium text-slate-100">${escapeHtml(emp.name)}</span>
                    </div>
                </td>
                <td class="px-4 py-3 text-slate-300 text-center">${escapeHtml(getDeptName(emp.department_id))}</td>
                <td class="px-4 py-3 text-center">
                    <span class="inline-flex items-center px-2 py-0.5 text-sm font-medium rounded-full whitespace-nowrap ${getPositionColor(emp.position)}">${escapeHtml(emp.position || '-')}</span>
                </td>
                <td class="px-4 py-3 text-slate-300 text-center">${escapeHtml(emp.title || '-')}</td>
                <td class="px-4 py-3 text-slate-400 text-center">${escapeHtml(emp.email || '-')}</td>
                <td class="px-4 py-3 text-slate-400 text-center">${escapeHtml(emp.phone || '-')}</td>
            </tr>
        `).join('');

        // 행 클릭 이벤트
        tbody.querySelectorAll('[data-emp-row]').forEach(row => {
            row.addEventListener('click', () => {
                const empId = parseInt(row.dataset.empRow);
                const emp = allEmployees.find(e => e.id === empId);
                if (emp) {
                    const dept = allDepartments.find(d => d.id == emp.department_id);
                    showEmployeeDetail(emp, dept);
                }
            });
        });

        document.getElementById('totalCount').textContent = employees.length;
    }

    // === 직원 상세 모달 ===
    function showEmployeeDetail(emp, dept) {
        const modal = document.getElementById('employeeModal');
        const content = document.getElementById('modalContent');

        const isHead = emp.is_dept_head == 1 || emp.id == dept?.head_employee_id;
        const headChip = isHead
            ? `<span class="org-head-chip ml-1.5" title="${(ORG_LABELS.department||{}).head||'부서장'}"><i data-lucide="user-round-check" class="pointer-events-none"></i></span>`
            : '';
        content.innerHTML = `
            <div class="flex items-center gap-4 mb-6">
                <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center">
                    <i data-lucide="user" class="text-primary w-6 h-6"></i>
                </div>
                <div>
                    <h4 class="text-lg font-bold text-slate-100 flex items-center">${escapeHtml(emp.name)}</h4>
                    <p class="text-sm text-slate-400 flex items-center gap-1">${escapeHtml(emp.position || '')}${emp.title ? ' · ' + escapeHtml(emp.title) : ''}${headChip}</p>
                </div>
            </div>
            <div class="space-y-3">
                <div class="flex items-center gap-3 text-sm">
                    <div class="w-8 h-8 rounded-lg bg-primary-light flex items-center justify-center">
                        <i data-lucide="building-2" class="text-primary w-3 h-3"></i>
                    </div>
                    <div>
                        <span class="text-slate-400 text-sm">${escapeHtml((ORG_LABELS.department||{}).label||'부서')}</span>
                        <p class="text-slate-200">${escapeHtml(dept?.name || '-')}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center">
                        <i data-lucide="mail" class="text-amber-500 w-3 h-3"></i>
                    </div>
                    <div>
                        <span class="text-slate-400 text-sm">이메일</span>
                        <p class="text-slate-200">${escapeHtml(emp.email || '-')}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <div class="w-8 h-8 rounded-lg bg-primary-light flex items-center justify-center">
                        <i data-lucide="phone" class="text-primary w-3 h-3"></i>
                    </div>
                    <div>
                        <span class="text-slate-400 text-sm">연락처</span>
                        <p class="text-slate-200">${escapeHtml(emp.phone || '-')}</p>
                    </div>
                </div>
            </div>
        `;

        modal.classList.remove('hidden');
    }

    // === 구성원 배치 피커 (신규 부서 생성 시) ===
    function setupMemberPicker() {
        const wrap = document.getElementById('deptMemberAssign');
        const listEl = document.getElementById('memberCheckboxList');
        const searchEl = document.getElementById('memberSearchInput');
        const countEl = document.getElementById('memberSelectedCount');
        if (!wrap || !listEl) return;
        wrap.classList.remove('hidden');
        if (searchEl) searchEl.value = '';

        const deptNameOf = (id) => {
            const d = allDepartments.find(x => x.id == id);
            return d ? d.name : '미배정';
        };
        const members = allEmployees.slice()
            .sort((a, b) => String(a.name || '').localeCompare(String(b.name || ''), 'ko'));

        listEl.innerHTML = members.length ? members.map(emp =>
            '<label class="member-row flex items-center gap-2 py-1.5 px-2 rounded-lg hover:bg-gray-50 cursor-pointer" data-name="' + escapeHtml(emp.name || '') + '">' +
                '<input type="checkbox" name="member_ids" value="' + emp.id + '" class="rounded border-gray-300 text-primary focus:ring-primary">' +
                '<span class="text-sm text-gray-700">' + escapeHtml(emp.name || '') + '</span>' +
                '<span class="text-xs text-gray-400 ml-auto">' + escapeHtml(deptNameOf(emp.department_id)) + '</span>' +
            '</label>'
        ).join('') : '<p class="text-xs text-gray-400 px-2 py-2">직원이 없습니다</p>';

        const updateCount = () => {
            const n = listEl.querySelectorAll('input[name="member_ids"]:checked').length;
            if (countEl) countEl.textContent = n + '명 선택됨';
        };
        listEl.onchange = updateCount;
        updateCount();

        if (searchEl) {
            searchEl.oninput = () => {
                const q = searchEl.value.trim().toLowerCase();
                listEl.querySelectorAll('.member-row').forEach(row => {
                    const name = (row.getAttribute('data-name') || '').toLowerCase();
                    row.classList.toggle('hidden', q !== '' && !name.includes(q));
                });
            };
        }
    }

    // === 부서 모달 ===
    function showDeptModal(dept = null) {
        const modal = document.getElementById('deptModal');
        const _deptLabel = (ORG_LABELS.department||{}).label||'부서';
        document.getElementById('deptModalTitle').textContent = dept ? _deptLabel+' 수정' : _deptLabel+' 추가';

        const editId = dept ? dept.id : null;
        let currentParentId = dept ? dept.parent_id : null;

        // 폼 필드 세팅
        if (dept) {
            document.getElementById('deptId').value        = dept.id;
            document.getElementById('deptName').value      = dept.name;
            document.getElementById('deptCode').value      = dept.code || '';
            document.getElementById('deptSortOrder').value = dept.sort_order || 0;
        } else {
            document.getElementById('deptId').value        = '';
            document.getElementById('deptName').value      = '';
            document.getElementById('deptCode').value      = '';
            document.getElementById('deptSortOrder').value = 0;
        }

        // 삭제 버튼 (수정 모드에서만)
        const deleteBtn = document.getElementById('deleteDeptBtn');
        if (dept) {
            deleteBtn.classList.remove('hidden');
            deleteBtn.onclick = async () => {
                if ((await AppUI.confirm(`"${dept.name}" ${_deptLabel}를 삭제하시겠습니까?\n\n소속 직원이나 하위 조직이 있으면 삭제할 수 없습니다.`))) {
                    const deleted = await deleteDepartment(dept.id);
                    if (deleted) document.getElementById('deptModal').classList.add('hidden');
                }
            };
        } else {
            deleteBtn.classList.add('hidden');
        }
        // 부서 상세 섹션 (수정 모드에서만)
        const detailSection = document.getElementById('deptDetailInfo');
        const pathRow = document.getElementById('deptPathRow');
        if (dept) {
            detailSection.classList.remove('hidden');
            pathRow.classList.remove('hidden');
            const directEmps = allEmployees.filter(e => e.department_id == dept.id);
            const headWrap = document.getElementById('deptHeadCheckboxes');
            if (directEmps.length === 0) {
                headWrap.innerHTML = '<p class="text-xs text-gray-400 px-2 py-2">이 부서에 소속된 직원이 없습니다</p>';
            } else {
                headWrap.innerHTML = directEmps.map(emp => {
                    const isH = emp.is_dept_head == 1 || emp.id == dept.head_employee_id;
                    return '<label class="flex items-center gap-2 py-1.5 px-2 rounded-lg hover:bg-gray-50 cursor-pointer">' +
                        '<input type="checkbox" name="dept_head_ids" value="' + emp.id + '" ' + (isH ? 'checked' : '') + ' class="rounded border-gray-300 text-primary focus:ring-primary">' +
                        '<span class="text-sm text-gray-700">' + escapeHtml(emp.name) + '</span>' +
                        '<span class="text-xs text-gray-400 ml-auto">' + escapeHtml(emp.position || '') + '</span>' +
                    '</label>';
                }).join('');
            }
            document.getElementById('deptMemberAssign').classList.add('hidden');
        } else {
            detailSection.classList.add('hidden');
            pathRow.classList.add('hidden');
            setupMemberPicker();
        }

        // 상위 조직 hidden input 동기화
        function setParent(parentId) {
            currentParentId = parentId != null ? parseInt(parentId) : null;
            document.getElementById('deptParent').value = currentParentId || '';

            // 상위 부서 표시 (최상위 → 선택 브레드크럼)
            const parentBox = document.getElementById('deptParentDisplay');
            if (parentBox) {
                if (currentParentId) {
                    const chain = getAncestorChain(currentParentId).reverse();
                    parentBox.innerHTML = chain.map((id, i) =>
                        '<span class="text-sm ' + (i === chain.length - 1 ? 'font-semibold text-gray-800' : 'text-gray-400') + '">' +
                        escapeHtml(deptNameById(id)) + '</span>'
                    ).join('<span class="mx-1 text-gray-300">/</span>');
                } else {
                    parentBox.innerHTML = '<span class="text-sm text-gray-500">최상위 조직 (상위 없음)</span>';
                }
            }

            // 조직 유형 표시 (깊이 기반 자동 결정)
            const levelBox = document.getElementById('deptLevelDisplay');
            if (levelBox) {
                const depthIdx = currentParentId ? getAncestorChain(currentParentId).length : 0;
                const lvl = enabledLevels[depthIdx];
                if (lvl) {
                    levelBox.className = 'text-sm font-semibold text-gray-800';
                    levelBox.textContent = lvl.label;
                } else if (!enabledLevels.length) {
                    levelBox.className = 'text-sm text-gray-400';
                    levelBox.textContent = '상위 부서를 선택하면 자동 결정됩니다';
                } else {
                    levelBox.className = 'text-sm text-rose-500';
                    levelBox.textContent = '최대 조직 단계를 초과합니다';
                }
            }

            renderTree(document.getElementById('deptTreeSearch').value);
            if (dept) {
                const pathEl = document.getElementById('deptDetailPath');
                if (pathEl) {
                    const path = [];
                    let cur = currentParentId ? allDepartments.find(d => d.id == currentParentId) : null;
                    while (cur) { path.unshift(cur); cur = allDepartments.find(d => d.id == cur.parent_id) || null; }
                    path.push(dept);
                    pathEl.innerHTML = path.map(p =>
                        '<span class="' + (p.id == dept.id ? 'font-semibold text-gray-800' : 'text-gray-500') + '">' + escapeHtml(p.name) + '</span>'
                    ).join('<span class="mx-1 text-gray-300">/</span>');
                }
            }
        }

        // ── 트리 렌더 ──────────────────────────────────────────
        let isDragging = false;
        let draggedDeptId = null;
        let draggedDeptName = '';

        // ── 깊이 제한 ──
        const enabledLevels = (window.ORG_LABELS && window.ORG_LABELS._levels)
            ? window.ORG_LABELS._levels.filter(l => l.enabled)
            : [];
        const maxAllowedDepth = enabledLevels.length > 0 ? enabledLevels.length - 1 : 99;

        function getSubtreeDepth(deptId) {
            const children = allDepartments.filter(d => d.parent_id == deptId);
            if (!children.length) return 0;
            return 1 + Math.max(...children.map(c => getSubtreeDepth(c.id)));
        }

        function isDescendantOf(checkId, ancestorId) {
            let cur = checkId;
            while (cur != null) {
                if (cur == ancestorId) return true;
                const d = allDepartments.find(x => x.id == cur);
                if (!d) return false;
                cur = d.parent_id;
            }
            return false;
        }

        function getAncestorChain(parentId) {
            const chain = [];
            let cur = parentId;
            while (cur != null) {
                chain.push(parseInt(cur));
                const d = allDepartments.find(x => x.id == cur);
                if (!d) break;
                cur = d.parent_id;
            }
            return chain;
        }

        function deptNameById(id) {
            const d = allDepartments.find(x => x.id == id);
            return d ? d.name : '';
        }

        function getDropAfterSiblingId(refDeptId, resolvedParentId) {
            if (refDeptId == resolvedParentId) return null;
            let cur = refDeptId;
            while (cur != null) {
                const d = allDepartments.find(x => x.id == cur);
                if (!d) return null;
                if (d.parent_id == resolvedParentId) return cur;
                cur = d.parent_id;
            }
            return null;
        }

        function calcSortOrder(newParentId, deptId, afterSiblingId) {
            const siblings = allDepartments
                .filter(d => d.parent_id == newParentId && d.id !== deptId)
                .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
            if (!siblings.length) return 1;
            if (afterSiblingId == null) return (siblings[0].sort_order || 1) - 1;
            const idx = siblings.findIndex(d => d.id == afterSiblingId);
            if (idx < 0) return Math.max(...siblings.map(d => d.sort_order || 0)) + 1;
            const after = siblings[idx].sort_order || 0;
            const before = idx + 1 < siblings.length ? siblings[idx + 1].sort_order : after + 2;
            return (after + before) / 2;
        }

        async function moveDeptParent(deptId, newParentId, afterSiblingId) {
            const dept = allDepartments.find(d => d.id === deptId);
            if (!dept) return;
            const oldParentId = dept.parent_id;
            const oldSortOrder = dept.sort_order;
            const newSortOrder = calcSortOrder(newParentId, deptId, afterSiblingId);
            dept.parent_id = newParentId;
            dept.sort_order = newSortOrder;
            clearDrop();
            renderTree(document.getElementById('deptTreeSearch')?.value || '');
            const panel = document.getElementById('orgTreePanel');
            const movedCard = panel && panel.querySelector('[data-drop-id="' + deptId + '"]');
            if (movedCard) {
                movedCard.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                movedCard.classList.add('drop-success');
                setTimeout(() => movedCard.classList.remove('drop-success'), 800);
            }
            if (useDB) {
                try {
                    const resp = await fetch(`${basePath}/api/organization.php?action=updateDepartment`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: deptId, parent_id: newParentId, name: dept.name, code: dept.code || '', sort_order: newSortOrder })
                    });
                    const result = await resp.json();
                    if (result.error) {
                        dept.parent_id = oldParentId;
                        dept.sort_order = oldSortOrder;
                        renderTree(document.getElementById('deptTreeSearch')?.value || '');
                        showToast(result.error, 'error');
                    } else {
                        applyFilters();
                        showToast('이동되었습니다');
                    }
                } catch (e) {
                    dept.parent_id = oldParentId;
                    dept.sort_order = oldSortOrder;
                    renderTree(document.getElementById('deptTreeSearch')?.value || '');
                    console.error('moveDeptParent:', e);
                    showToast('이동 중 오류가 발생했습니다', 'error');
                }
            }
        }

        function hasMatch(d, q) {
            if (!q) return true;
            if (d.name.toLowerCase().includes(q)) return true;
            return allDepartments.filter(c => c.parent_id == d.id).some(c => hasMatch(c, q));
        }

        function buildLevel(wrap, parentId, depth) {
            const kids = allDepartments
                .filter(d => d.parent_id == parentId)
                .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));

            const q = (document.getElementById('deptTreeSearch').value || '').trim().toLowerCase();
            const visible = kids.filter(d => hasMatch(d, q));
            if (!visible.length) return;

            // 자식 그룹 (border-l 이 세로 연결선)
            const group = document.createElement('div');
            group.className = 'ml-5 pl-3 border-l-2 border-slate-200 mt-1.5 space-y-1.5';
            wrap.appendChild(group);

            visible.forEach(d => {
                const isEditing  = d.id === editId;
                const isParent   = d.id === currentParentId;

                const row = document.createElement('div');
                row.dataset.deptId = d.id;
                row.dataset.dropId = d.id;
                row.dataset.dlDepth = depth;

                if (isEditing) {
                    row.className = 'tree-row flex items-center gap-2 px-3 py-2.5 rounded-xl bg-indigo-500 text-white font-semibold text-xs cursor-grab select-none shadow-md ring-2 ring-indigo-300/50 drop-target w-52 flex-shrink-0';
                    row.innerHTML = `<span class="truncate flex-1">${escapeHtml(d.name)}</span><span class="text-[10px] text-white/60 font-normal flex-shrink-0">편집 중</span>`;
                } else {
                    const DEPTH_BG = ['bg-sky-500 hover:bg-sky-600', 'bg-sky-400 hover:bg-sky-500', 'bg-sky-300 hover:bg-sky-400'];
                    const cardBg = isParent
                        ? (DEPTH_BG[Math.min(depth - 1, 2)] || 'bg-sky-300 hover:bg-sky-400') + ' ring-2 ring-white/50 shadow-md'
                        : (DEPTH_BG[Math.min(depth - 1, 2)] || 'bg-sky-300 hover:bg-sky-400') + ' shadow-sm';
                    row.className = [
                        'tree-row flex items-center gap-2 px-3 py-2.5 rounded-xl text-xs cursor-grab select-none transition-all drop-target text-white font-medium w-52 flex-shrink-0',
                        cardBg,
                    ].join(' ');
                    const badge = isParent
                        ? `<span class="ml-auto text-[10px] text-white/70 font-normal flex-shrink-0">상위</span>`
                        : '';
                    row.innerHTML = `<span class="truncate flex-1">${escapeHtml(d.name)}</span>${badge}`;
                    row.addEventListener('click', () => {
                        if (isDragging || dragOccurred) return;
                        if (editId == null) setParent(d.id);   // 추가 모드: 클릭 = 상위 부서 지정
                        else showDeptModal(d);                  // 수정 모드: 클릭 = 해당 부서로 전환
                    });
                }
                row.addEventListener('pointerdown', e => {
                    if (e.button !== 0 || isDragging || pendingDrag) return;
                    pendingDrag = { deptId: d.id, deptName: d.name, row, startX: e.clientX, startY: e.clientY };
                    document.addEventListener('pointermove', onDragPointerMove);
                    document.addEventListener('pointerup', onDragPointerUp);
                });

                group.appendChild(row);

                // 하위 재귀
                buildLevel(group, d.id, depth + 1);
            });

        }

        // === Pointer DnD (X축 3분할: 좌=상위, 중=같은, 우=하위) ===
        const INDENT_PX = 34;
        const DRAG_THRESHOLD = 5;
        const dropIndicator = document.createElement('div');
        dropIndicator.className = 'dept-drop-indicator';
        let lastDropTarget = null;
        let dropResolvedParent = null;
        let lastZone = 'center';
        let ghostEl = null;
        let pendingDrag = null;
        let dragOccurred = false;
        let ghostOffsetX = 0, ghostOffsetY = 0;
        let lastPointerY = 0;
        let autoScrollId = null;

        function clearDrop() {
            if (lastDropTarget) lastDropTarget.classList.remove('drop-ref', 'drop-container');
            dropIndicator.classList.remove('show');
            dropIndicator.style.marginLeft = '';
            lastDropTarget = null;
            dropResolvedParent = null;
            lastZone = 'center';
        }

        function resolveDropParent(refCard, clientX) {
            const refId = parseInt(refCard.dataset.dropId);
            const refDept = allDepartments.find(d => d.id === refId);
            if (!refDept) return false;
            const refDepth = parseInt(refCard.dataset.dlDepth || '0');
            const dragSubtreeDepth = draggedDeptId ? getSubtreeDepth(draggedDeptId) : 0;
            const maxDepth = Math.min(refDepth + 1, maxAllowedDepth - dragSubtreeDepth);

            const cardRect = refCard.getBoundingClientRect();
            let targetDepth;

            if (refCard !== lastDropTarget) lastZone = 'center';

            if (clientX < cardRect.left) {
                const pxLeft = cardRect.left - clientX;
                targetDepth = refDepth - Math.max(1, Math.ceil(pxLeft / INDENT_PX));
                lastZone = 'left';
            } else if (clientX > cardRect.right) {
                targetDepth = refDepth + 1;
                lastZone = 'right';
            } else {
                const xRatio = (clientX - cardRect.left) / cardRect.width;
                if (lastZone === 'left') {
                    if (xRatio > 0.40) lastZone = xRatio > 0.72 ? 'right' : 'center';
                } else if (lastZone === 'right') {
                    if (xRatio < 0.55) lastZone = xRatio < 0.22 ? 'left' : 'center';
                } else {
                    if (xRatio < 0.25) lastZone = 'left';
                    else if (xRatio > 0.72) lastZone = 'right';
                }
                if (lastZone === 'left') targetDepth = refDepth - 1;
                else if (lastZone === 'right') targetDepth = refDepth + 1;
                else targetDepth = refDepth;
            }
            targetDepth = Math.max(1, Math.min(maxDepth, targetDepth));

            let resolved;
            if (targetDepth > refDepth) {
                resolved = refId;
            } else if (refDept.parent_id != null) {
                const ancestors = getAncestorChain(refDept.parent_id);
                if (!ancestors.length) return false;
                const levelsUp = refDepth - targetDepth;
                resolved = levelsUp < ancestors.length
                    ? ancestors[levelsUp] : ancestors[ancestors.length - 1];
            } else {
                return false;
            }
            if (resolved === draggedDeptId || isDescendantOf(resolved, draggedDeptId)) return false;

            dropIndicator.style.marginLeft = ((targetDepth - refDepth) * INDENT_PX) + 'px';

            refCard.classList.remove('drop-ref', 'drop-container');
            refCard.classList.add(targetDepth > refDepth ? 'drop-container' : 'drop-ref');

            dropResolvedParent = resolved;
            return true;
        }

        function startDrag(pd, e) {
            isDragging = true;
            dragOccurred = true;
            draggedDeptId = parseInt(pd.deptId);
            draggedDeptName = pd.deptName;

            const rect = pd.row.getBoundingClientRect();
            ghostOffsetX = e.clientX - rect.left;
            ghostOffsetY = e.clientY - rect.top;

            ghostEl = document.createElement('div');
            ghostEl.className = 'dept-ghost';
            ghostEl.textContent = pd.deptName;
            ghostEl.style.width = rect.width + 'px';
            ghostEl.style.height = rect.height + 'px';
            document.body.appendChild(ghostEl);
            updateGhost(e.clientX, e.clientY);

            pd.row.classList.add('dept-dragging');
            document.getElementById('deptModal').classList.add('is-dept-dragging');
            document.body.style.userSelect = 'none';
            document.body.style.cursor = 'grabbing';
            document.addEventListener('keydown', onDragKeyDown);
        }

        function updateGhost(x, y) {
            if (!ghostEl) return;
            let left = x - ghostOffsetX;
            let top = y - ghostOffsetY;
            // 고스트를 좌측 조직트리 패널 경계 안으로 제한 (오른쪽 영역 침범 방지)
            const panel = document.getElementById('orgTreePanel');
            if (panel) {
                const p = panel.getBoundingClientRect();
                const gw = ghostEl.offsetWidth;
                const gh = ghostEl.offsetHeight;
                left = Math.max(p.left, Math.min(left, p.right - gw));
                top = Math.max(p.top, Math.min(top, p.bottom - gh));
            }
            ghostEl.style.left = left + 'px';
            ghostEl.style.top = top + 'px';
        }

        function findDropTarget(x, y) {
            const panel = document.getElementById('orgTreePanel');
            if (!panel) return null;
            const allCards = panel.querySelectorAll('.drop-target');
            let best = null, bestDist = Infinity;
            for (const c of allCards) {
                const cId = parseInt(c.dataset.dropId);
                if (cId === draggedDeptId || isDescendantOf(cId, draggedDeptId)) continue;
                const r = c.getBoundingClientRect();
                const d = Math.abs(y - (r.top + r.height / 2));
                if (d < bestDist) { bestDist = d; best = c; }
            }
            return best;
        }

        function updateDropTarget(x, y) {
            const target = findDropTarget(x, y);
            if (!target) { clearDrop(); return; }
            const dropId = parseInt(target.dataset.dropId);
            if (isNaN(dropId) || isDescendantOf(dropId, draggedDeptId)) { clearDrop(); return; }

            if (target === lastDropTarget) {
                resolveDropParent(target, x);
                return;
            }

            if (lastDropTarget) {
                const lastRect = lastDropTarget.getBoundingClientRect();
                const lastDist = Math.abs(y - (lastRect.top + lastRect.height / 2));
                const newRect = target.getBoundingClientRect();
                const newDist = Math.abs(y - (newRect.top + newRect.height / 2));
                if (newDist > lastDist - 10) return;
            }

            clearDrop();
            if (!resolveDropParent(target, x)) return;
            lastDropTarget = target;
            target.after(dropIndicator);
            requestAnimationFrame(() => dropIndicator.classList.add('show'));
        }

        function executeDrop() {
            if (!draggedDeptId || dropResolvedParent == null) return;
            const pid = parseInt(dropResolvedParent);
            const refId = lastDropTarget ? parseInt(lastDropTarget.dataset.dropId) : null;
            const afterSibId = refId != null ? getDropAfterSiblingId(refId, pid) : null;
            // ── 깊이 제한 체크 ──
            const subtreeDepth = getSubtreeDepth(draggedDeptId);
            if (getAncestorChain(pid).length + subtreeDepth > maxAllowedDepth) {
                const maxLabel = enabledLevels.length > 0 ? enabledLevels.length + '단계' : '';
                showToast('최대 ' + maxLabel + '까지만 설정할 수 있습니다', 'error');
                return;
            }
            if (!isNaN(pid) && pid !== draggedDeptId && !isDescendantOf(pid, draggedDeptId)) {
                if (draggedDeptId === editId) {
                    const ed = allDepartments.find(d => d.id === editId);
                    if (ed) {
                        ed.parent_id = pid;
                        ed.sort_order = calcSortOrder(pid, draggedDeptId, afterSibId);
                    }
                    setParent(pid);
                } else {
                    moveDeptParent(draggedDeptId, pid, afterSibId);
                }
            }
        }

        function endDrag() {
            clearDrop();
            if (ghostEl) { ghostEl.remove(); ghostEl = null; }
            const panel = document.getElementById('orgTreePanel');
            const src = panel && panel.querySelector('.dept-dragging');
            if (src) src.classList.remove('dept-dragging');
            document.getElementById('deptModal').classList.remove('is-dept-dragging');
            isDragging = false;
            draggedDeptId = null;
            draggedDeptName = '';
            document.body.style.userSelect = '';
            document.body.style.cursor = '';
            document.removeEventListener('keydown', onDragKeyDown);
            stopAutoScroll();
            setTimeout(() => { dragOccurred = false; }, 0);
        }

        function startAutoScroll() {
            const EDGE = 40, SPEED = 6;
            function tick() {
                if (!isDragging) { autoScrollId = null; return; }
                const panel = document.getElementById('orgTreePanel');
                if (!panel) { autoScrollId = null; return; }
                const rect = panel.getBoundingClientRect();
                if (lastPointerY < rect.top + EDGE && panel.scrollTop > 0) {
                    panel.scrollTop -= SPEED * Math.max(0.2, 1 - (lastPointerY - rect.top) / EDGE);
                } else if (lastPointerY > rect.bottom - EDGE) {
                    panel.scrollTop += SPEED * Math.max(0.2, 1 - (rect.bottom - lastPointerY) / EDGE);
                }
                autoScrollId = requestAnimationFrame(tick);
            }
            if (!autoScrollId) autoScrollId = requestAnimationFrame(tick);
        }
        function stopAutoScroll() {
            if (autoScrollId) { cancelAnimationFrame(autoScrollId); autoScrollId = null; }
        }

        function onDragPointerMove(e) {
            if (!isDragging && pendingDrag) {
                const dx = e.clientX - pendingDrag.startX;
                const dy = e.clientY - pendingDrag.startY;
                if (dx * dx + dy * dy < DRAG_THRESHOLD * DRAG_THRESHOLD) return;
                startDrag(pendingDrag, e);
                startAutoScroll();
            }
            if (!isDragging) return;
            lastPointerY = e.clientY;
            updateGhost(e.clientX, e.clientY);
            updateDropTarget(e.clientX, e.clientY);
        }

        function onDragPointerUp(e) {
            document.removeEventListener('pointermove', onDragPointerMove);
            document.removeEventListener('pointerup', onDragPointerUp);
            if (isDragging) {
                executeDrop();
                endDrag();
            }
            pendingDrag = null;
        }

        function onDragKeyDown(e) {
            if (e.key === 'Escape') {
                document.removeEventListener('pointermove', onDragPointerMove);
                document.removeEventListener('pointerup', onDragPointerUp);
                endDrag();
                pendingDrag = null;
            }
        }

        function renderTree(q) {
            const panel = document.getElementById('orgTreePanel');
            panel.innerHTML = '';
            const root = allDepartments.find(d => !d.parent_id);
            if (!root) return;

            const isRootParent = root.id === currentParentId;
            const rootRow = document.createElement('div');
            rootRow.className = [
                'tree-row flex items-center gap-2 px-3 py-2.5 rounded-xl text-xs cursor-grab select-none transition-all drop-target font-semibold text-white shadow-md w-52 flex-shrink-0',
                isRootParent ? 'bg-primary hover:opacity-90 ring-2 ring-white/50' : 'bg-primary hover:opacity-90',
            ].join(' ');
            rootRow.dataset.dropId = root.id;
            rootRow.dataset.dlDepth = '0';
            const rootBadge = isRootParent ? '<span class="ml-auto text-[10px] text-white/70 font-normal flex-shrink-0">상위</span>' : '';
            rootRow.innerHTML = '<span class="truncate flex-1">' + escapeHtml(root.name) + '</span>' + rootBadge;
            rootRow.addEventListener('click', () => {
                if (isDragging || dragOccurred) return;
                if (editId == null) setParent(root.id);   // 추가 모드: 클릭 = 상위 부서 지정
                else showDeptModal(root);                  // 수정 모드: 클릭 = 해당 부서로 전환
            });
            rootRow.addEventListener('pointerdown', e => {
                if (e.button !== 0 || isDragging || pendingDrag) return;
                pendingDrag = { deptId: root.id, deptName: root.name, row: rootRow, startX: e.clientX, startY: e.clientY };
                document.addEventListener('pointermove', onDragPointerMove);
                document.addEventListener('pointerup', onDragPointerUp);
            });
            panel.appendChild(rootRow);

            buildLevel(panel, root.id, 1);
        }

        // 검색
        document.getElementById('deptTreeSearch').value = '';
        document.getElementById('deptTreeSearch').oninput = e => renderTree(e.target.value);

        setParent(currentParentId);

        // 수정 모드: 조직 상세 정보 채우기
        const detailBox = document.getElementById('deptDetailInfo');
        if (dept) {
            detailBox.classList.remove('hidden');
            const directEmps = allEmployees.filter(e => e.department_id == dept.id);
            const deptHeads = directEmps.filter(e => e.is_dept_head == 1 || e.id == dept.head_employee_id);
            const subDepts = allDepartments.filter(d => d.parent_id == dept.id).sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
            const childDeptIds = getAllChildDeptIds(dept.id);
            const totalEmps = allEmployees.filter(e => childDeptIds.includes(e.department_id));

            // 조직 경로
            const path = [];
            let cur = dept;
            while (cur) { path.unshift(cur); cur = allDepartments.find(d => d.id == cur.parent_id) || null; }
            document.getElementById('deptDetailPath').innerHTML =
                path.map(p => '<span class="' + (p.id == dept.id ? 'font-semibold text-gray-800' : 'text-gray-500') + '">' + escapeHtml(p.name) + '</span>')
                .join('<span class="mx-1 text-gray-300">/</span>');

            // 부서장 체크박스 채우기
            const headWrap = document.getElementById('deptHeadCheckboxes');
            if (directEmps.length === 0) {
                headWrap.innerHTML = '<p class="text-xs text-gray-400">소속 직원이 없습니다</p>';
            } else {
                headWrap.innerHTML = directEmps.map(emp => {
                    var isH = emp.is_dept_head == 1 || emp.id == dept.head_employee_id;
                    return '<label class="flex items-center gap-2 py-1 px-2 rounded hover:bg-gray-50 cursor-pointer">' +
                        '<input type="checkbox" name="dept_head_ids" value="' + emp.id + '" ' + (isH ? 'checked' : '') + ' class="rounded border-gray-300 text-primary focus:ring-primary">' +
                        '<span class="text-sm text-gray-700">' + escapeHtml(emp.name) + '</span>' +
                        '<span class="text-xs text-gray-400">' + escapeHtml(emp.position || '') + '</span>' +
                    '</label>';
                }).join('');
            }

            // 직속 직원
            const empEl = document.getElementById('deptDetailEmployees');
            if (directEmps.length === 0) {
                empEl.innerHTML = '<p class="text-xs text-gray-400">소속 직원 없음</p>';
            } else {
                const hues = [220, 260, 300, 20, 160, 40, 180, 340, 80, 200];
                empEl.innerHTML = directEmps.map(emp => {
                    const isHead = emp.is_dept_head == 1 || emp.id == dept.head_employee_id;
                    const hue = emp.name ? hues[emp.name.charCodeAt(0) % hues.length] : 220;
                    return '<div class="flex items-center gap-2 py-1.5 px-2 rounded-lg hover:bg-gray-50">' +
                        '<div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0" style="background:' + (isHead ? 'linear-gradient(135deg,#4F6AFF,#7090ff);color:#fff' : 'hsl(' + hue + ',50%,92%);color:hsl(' + hue + ',60%,40%)') + '">' + escapeHtml(emp.name.charAt(0)) + '</div>' +
                        (isHead ? '<span class="org-head-chip" title="' + ((ORG_LABELS.department||{}).head||'부서장') + '"><i data-lucide="user-round-check" class="pointer-events-none"></i></span>' : '') +
                        '<span class="text-sm font-medium text-gray-700">' + escapeHtml(emp.name) + '</span>' +
                        '<span class="text-xs text-gray-400 ml-auto">' + escapeHtml(emp.position || '') + '</span>' +
                    '</div>';
                }).join('');
            }

            // 하위 조직
            const subWrap = document.getElementById('deptDetailSubWrap');
            const subsEl = document.getElementById('deptDetailSubs');
            if (subDepts.length === 0) {
                subWrap.classList.add('hidden');
            } else {
                subWrap.classList.remove('hidden');
                subsEl.innerHTML = subDepts.map(sd => {
                    const empN = allEmployees.filter(e => e.department_id == sd.id).length;
                    const sdHeads = allEmployees.filter(e => e.department_id == sd.id && (e.is_dept_head == 1 || e.id == sd.head_employee_id));
                    var sdHeadNames = sdHeads.map(h => escapeHtml(h.name)).join(', ');
                    return '<div class="flex items-center gap-2 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs">' +
                        '<i data-lucide="building-2" class="w-3.5 h-3.5 text-gray-400"></i>' +
                        '<span class="font-medium text-gray-700">' + escapeHtml(sd.name) + '</span>' +
                        '<span class="text-gray-400">' + empN + '명</span>' +
                        (sdHeadNames ? '<span class="text-gray-400 ml-auto">' + sdHeadNames + '</span>' : '') +
                    '</div>';
                }).join('');
            }

            if (window.lucide) lucide.createIcons();
        } else {
            detailBox.classList.add('hidden');
        }

        modal.classList.remove('hidden');
        setTimeout(() => document.getElementById('deptName').focus(), 80);
    }

    function openDeptEdit(deptId) {
        if (!canEdit) return;
        const dept = allDepartments.find(d => d.id === deptId);
        if (dept) showDeptModal(dept);
    }

    async function deleteDepartment(deptId) {
        if (useDB) {
            try {
                const response = await fetch(`${basePath}/api/organization.php?action=deleteDepartment`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: deptId })
                });
                const data = await response.json();
                if (!response.ok || data.error) {
                    showToast(data.error || '삭제에 실패했습니다', 'error');
                    return false;
                }
                allDepartments = allDepartments.filter(d => d.id != deptId);
                if (selectedDeptId == deptId) {
                    const root = allDepartments.find(d => !d.parent_id);
                    selectedDeptId = root ? root.id : null;
                }
                applyFilters();
                showToast('삭제되었습니다');
                return true;
            } catch (err) {
                console.error('부서 삭제 실패:', err);
                showToast('삭제 중 오류가 발생했습니다', 'error');
                return false;
            }
        }

        if (allDepartments.some(d => d.parent_id == deptId)) {
            alert('하위 부서가 존재합니다. 먼저 하위 부서를 삭제해주세요.');
            return false;
        }
        if (allEmployees.some(e => e.department_id == deptId)) {
            alert('소속 직원이 존재합니다. 먼저 직원을 이동하거나 삭제해주세요.');
            return false;
        }
        allDepartments = allDepartments.filter(d => d.id != deptId);
        if (selectedDeptId == deptId) {
            const root = allDepartments.find(d => !d.parent_id);
            selectedDeptId = root ? root.id : null;
        }
        applyFilters();
        return true;
    }

    // === 이벤트 바인딩 ===
    function bindEvents() {
        // 뷰 전환 (조직도 / 리스트뷰)
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                currentView = btn.dataset.view;
                document.querySelectorAll('.view-btn').forEach(b => {
                    b.classList.remove('active', 'bg-slate-900', 'shadow-sm', 'text-primary');
                    b.classList.add('text-slate-400');
                });
                btn.classList.add('active', 'bg-slate-900', 'shadow-sm', 'text-primary');
                btn.classList.remove('text-slate-400');

                document.getElementById('orgViewContainer').classList.add('hidden');
                document.getElementById('listViewContainer').classList.add('hidden');
                if (currentView === 'list') {
                    document.getElementById('listViewContainer').classList.remove('hidden');
                } else {
                    document.getElementById('orgViewContainer').classList.remove('hidden');
                }
                applyFilters();
            });
        });

        // 초기 활성 버튼 스타일
        document.getElementById('btnOrgView').classList.add('bg-slate-900', 'shadow-sm', 'text-primary');

        // 상세 패널 이벤트 · 부서 칩/브레드크럼 클릭 → 선택 변경, 직원 클릭 → 모달, 수정/삭제
        document.getElementById('orgDetailPanel').addEventListener('click', async (e) => {
            const selectBtn = e.target.closest('[data-select-dept]');
            if (selectBtn) {
                selectedDeptId = parseInt(selectBtn.dataset.selectDept);
                expandAncestors(selectedDeptId);
                applyFilters();
                var targetCard = document.querySelector('#orgChart .chart-node[data-dept-id="' + selectedDeptId + '"]');
                if (targetCard) {
                    document.querySelectorAll('#orgChart .chart-node--selected').forEach(el => el.classList.remove('chart-node--selected'));
                    targetCard.classList.add('chart-node--selected');
                    targetCard.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
                }
                return;
            }

            const editBtn = e.target.closest('[data-edit-dept]');
            if (editBtn) {
                const dept = allDepartments.find(d => d.id === parseInt(editBtn.dataset.editDept));
                if (dept) showDeptModal(dept);
                return;
            }

            const deleteBtn = e.target.closest('[data-delete-dept]');
            if (deleteBtn) {
                const deptId = parseInt(deleteBtn.dataset.deleteDept);
                const dept = allDepartments.find(d => d.id === deptId);
                if (dept && (await AppUI.confirm(`"${dept.name}" 부서를 삭제하시겠습니까?`))) {
                    deleteDepartment(deptId);
                }
                return;
            }

            const setHeadBtn = e.target.closest('[data-set-head]');
            if (setHeadBtn) {
                const deptId = parseInt(setHeadBtn.dataset.setHead);
                const empId = parseInt(setHeadBtn.dataset.empId);
                setDeptHead(deptId, empId);
                return;
            }

            const unsetHeadBtn = e.target.closest('[data-unset-head]');
            if (unsetHeadBtn) {
                const deptId = parseInt(unsetHeadBtn.dataset.unsetHead);
                setDeptHead(deptId, null);
                return;
            }

            const empItem = e.target.closest('[data-emp-id]');
            if (empItem) {
                const emp = allEmployees.find(x => x.id === parseInt(empItem.dataset.empId));
                if (emp) {
                    const dept = allDepartments.find(d => d.id == emp.department_id);
                    showEmployeeDetail(emp, dept);
                }
            }
        });

        function setDeptHead(deptId, empId) {
            const dept = allDepartments.find(d => d.id === deptId);
            if (!dept) return;
            if (useDB) {
                fetch(`${basePath}/api/organization.php?action=updateDepartment`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: dept.id,
                        name: dept.name,
                        code: dept.code,
                        parent_id: dept.parent_id,
                        head_employee_id: empId,
                        sort_order: dept.sort_order || 0,
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.error) { alert(data.error); return; }
                    dept.head_employee_id = empId;
                    applyFilters();
                })
                .catch(() => alert(((ORG_LABELS.department||{}).head||'부서장') + ' 설정 중 오류가 발생했습니다.'));
            } else {
                dept.head_employee_id = empId;
                applyFilters();
            }
        }

        // 검색 & 필터
        const searchInput = document.getElementById('searchEmployee');
        const clearBtn = document.getElementById('btnSearchClear');
        let searchTimeout;
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            clearBtn.classList.toggle('hidden', !searchInput.value);
            searchTimeout = setTimeout(applyFilters, 200);
        });
        clearBtn?.addEventListener('click', () => {
            searchInput.value = '';
            clearBtn.classList.add('hidden');
            applyFilters();
            searchInput.focus();
        });

        // 모달 닫기
        document.getElementById('closeModal').addEventListener('click', () => {
            document.getElementById('employeeModal').classList.add('hidden');
        });
        document.getElementById('employeeModal').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) e.currentTarget.classList.add('hidden');
        });

        // 부서 추가 버튼 (canEdit=false일 때 DOM 미존재)
        if (canEdit) {
        document.getElementById('btnAddDept').addEventListener('click', () => showDeptModal());
        document.getElementById('closeDeptModal').addEventListener('click', () => {
            document.getElementById('deptModal').classList.add('hidden');
        });
        document.getElementById('cancelDeptForm').addEventListener('click', () => {
            document.getElementById('deptModal').classList.add('hidden');
        });
        document.getElementById('deptModal').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) e.currentTarget.classList.add('hidden');
        });

        // 부서 폼 제출
        document.getElementById('deptForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const id = document.getElementById('deptId').value;
            const parentId = document.getElementById('deptParent').value || null;
            // 신규 추가 시 같은 상위 조직 내 마지막 순서로 자동 배치
            let sortOrder = parseInt(document.getElementById('deptSortOrder').value) || 0;
            if (!id) {
                const siblings = allDepartments.filter(d => String(d.parent_id) === String(parentId));
                sortOrder = siblings.length > 0 ? Math.max(...siblings.map(d => d.sort_order || 0)) + 1 : 1;
            }
            const headChecks = document.querySelectorAll('#deptHeadCheckboxes input[name="dept_head_ids"]:checked');
            const headIds = Array.from(headChecks).map(cb => parseInt(cb.value));
            const data = {
                id: id ? parseInt(id) : null,
                parent_id: parentId,
                name: document.getElementById('deptName').value.trim(),
                code: document.getElementById('deptCode').value.trim(),
                head_employee_id: headIds.length > 0 ? headIds[0] : null,
                head_employee_ids: headIds,
                sort_order: sortOrder,
            };

            // 신규 부서: 선택한 구성원 ID 수집 (수정 모드에는 없음)
            if (!id) {
                const memberChecks = document.querySelectorAll('#memberCheckboxList input[name="member_ids"]:checked');
                data.member_ids = Array.from(memberChecks).map(cb => parseInt(cb.value));
            }

            if (!data.name) { alert(((ORG_LABELS.department||{}).label||'부서') + '명을 입력해주세요.'); return; }

            if (useDB) {
                const action = id ? 'updateDepartment' : 'createDepartment';
                const btn = e.submitter || document.querySelector('button[form="deptForm"]');
                if (btn) { btn.disabled = true; btn.textContent = '저장 중…'; }
                fetch(`${basePath}/api/organization.php?action=${action}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                })
                .then(r => {
                    if (!r.ok) return r.text().then(t => {
                        let msg = t;
                        try { const j = JSON.parse(t); if (j && j.error) msg = j.error; } catch (e) {}
                        throw new Error(msg || `HTTP ${r.status}`);
                    });
                    return r.json();
                })
                .then(result => {
                    if (result.error) { alert(result.error); return; }
                    if (id) {
                        const idx = allDepartments.findIndex(d => d.id === parseInt(id));
                        if (idx >= 0) Object.assign(allDepartments[idx], data);
                    } else {
                        const newId = result.id || (Math.max(...allDepartments.map(d => d.id), 0) + 1);
                        data.id = newId;
                        allDepartments.push(data);
                        // 선택한 구성원을 새 부서로 이동 (로컬 상태 반영)
                        if (Array.isArray(data.member_ids) && data.member_ids.length) {
                            allEmployees.forEach(emp => {
                                if (data.member_ids.includes(parseInt(emp.id))) emp.department_id = newId;
                            });
                        }
                    }
                    allEmployees.forEach(emp => {
                        if (emp.department_id == (id || data.id)) {
                            emp.is_dept_head = headIds.includes(parseInt(emp.id)) ? 1 : 0;
                        }
                    });
                    document.getElementById('deptModal').classList.add('hidden');
                    applyFilters();
                    showToast(id ? '저장되었습니다' : '부서가 추가되었습니다');
                })
                .catch(err => {
                    console.error('부서 저장 실패:', err);
                    showToast(err.message || '저장 중 오류가 발생했습니다', 'error');
                })
                .finally(() => {
                    if (btn) { btn.disabled = false; btn.textContent = '저장'; }
                });
            } else {
                // 클라이언트 사이드 처리
                if (id) {
                    const idx = allDepartments.findIndex(d => d.id === parseInt(id));
                    if (idx >= 0) Object.assign(allDepartments[idx], data);
                } else {
                    data.id = Math.max(...allDepartments.map(d => d.id), 0) + 1;
                    data.head_employee_id = null;
                    allDepartments.push(data);
                }
                allEmployees.forEach(emp => {
                    if (emp.department_id == (id || data.id)) {
                        emp.is_dept_head = headIds.includes(parseInt(emp.id)) ? 1 : 0;
                    }
                });
                document.getElementById('deptModal').classList.add('hidden');
                applyFilters();
            }
        });
        } // end canEdit

        // === 차트뷰 이벤트 ===
        document.getElementById('orgChart').addEventListener('click', (e) => {
            const toggleBtn = e.target.closest('[data-chart-toggle]');
            if (toggleBtn) {
                e.stopPropagation();
                const deptId = toggleBtn.dataset.chartToggle;
                const li = toggleBtn.closest('li');
                chartExpandedState[deptId] = li.classList.contains('chart-branch--collapsed');
                li.classList.toggle('chart-branch--collapsed');
                toggleBtn.textContent = li.classList.contains('chart-branch--collapsed') ? '+' : '−';
                toggleBtn.title = li.classList.contains('chart-branch--collapsed') ? '펼치기' : '접기';
            }
        });

        document.getElementById('chartExpandAll').addEventListener('click', () => {
            chartExpandedState = {};
            document.querySelectorAll('.chart-branch--collapsed').forEach(li => {
                li.classList.remove('chart-branch--collapsed');
            });
            document.querySelectorAll('.chart-node__toggle').forEach(btn => {
                btn.textContent = '−';
                btn.title = '접기';
            });
            requestAnimationFrame(fitChartZoom); // 펼치면 넓어지므로 다시 맞춤
        });

        // 줌 버튼 연결 (확대/축소/화면맞춤)
        document.getElementById('chartZoomOut').addEventListener('click', () => setChartZoom(chartZoom - 0.1));
        document.getElementById('chartZoomIn').addEventListener('click', () => setChartZoom(chartZoom + 0.1));
        document.getElementById('chartZoomReset').addEventListener('click', fitChartZoom);
        // 창 크기 변경 시 자동 재맞춤 (디바운스)
        let __zoomResizeT = null;
        window.addEventListener('resize', () => {
            clearTimeout(__zoomResizeT);
            __zoomResizeT = setTimeout(fitChartZoom, 150);
        });

        document.getElementById('chartCollapseAll').addEventListener('click', () => {
            document.querySelectorAll('#orgChart li').forEach(li => {
                const childUl = li.querySelector(':scope > ul');
                if (childUl) {
                    const deptId = li.querySelector('.chart-node')?.dataset.deptId;
                    if (deptId) chartExpandedState[deptId] = false;
                    li.classList.add('chart-branch--collapsed');
                }
            });
            document.querySelectorAll('.chart-node__toggle').forEach(btn => {
                btn.textContent = '+';
                btn.title = '펼치기';
            });
            requestAnimationFrame(fitChartZoom); // 접으면 좁아지므로 다시 맞춤(보통 100%)
        });

        // === 드래그 앤 드롭: 직원을 다른 부서로 이동 ===
        const detailPanel = document.getElementById('orgDetailPanel');
        const chartEl = document.getElementById('orgChart');

        // 직원 행 dragstart/end · 이벤트 위임 (패널이 매번 리렌더됨)
        detailPanel.addEventListener('dragstart', (e) => {
            const row = e.target.closest('.org-detail-emp[draggable="true"]');
            if (!row) return;
            const empId    = parseInt(row.dataset.empId);
            const srcDept  = parseInt(row.dataset.srcDept);
            const empName  = row.dataset.empName;
            if (!empId) return;

            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', String(empId));
            e.dataTransfer.setData('application/x-zaemit-emp', JSON.stringify({ empId, srcDept, empName }));

            document.body.classList.add('is-dragging-emp');
            row.classList.add('is-dragging');
            // 출발 부서 노드 하이라이트
            const srcNode = chartEl.querySelector('.chart-node[data-dept-id="' + srcDept + '"]');
            if (srcNode) srcNode.classList.add('chart-node--drag-source');
        });

        detailPanel.addEventListener('dragend', (e) => {
            document.body.classList.remove('is-dragging-emp');
            chartEl.querySelectorAll('.chart-node--drop-hover, .chart-node--drag-source')
                .forEach(el => el.classList.remove('chart-node--drop-hover', 'chart-node--drag-source'));
            detailPanel.querySelectorAll('.is-dragging').forEach(el => el.classList.remove('is-dragging'));
        });

        // 차트 노드 dragover/dragleave/drop
        chartEl.addEventListener('dragover', (e) => {
            const target = e.target.closest('.chart-node');
            if (!target) return;
            if (!document.body.classList.contains('is-dragging-emp')) return;
            // 출발 부서엔 드롭 불가
            if (target.classList.contains('chart-node--drag-source')) return;
            e.preventDefault(); // 드롭 허용
            e.dataTransfer.dropEffect = 'move';
            if (!target.classList.contains('chart-node--drop-hover')) {
                chartEl.querySelectorAll('.chart-node--drop-hover').forEach(el => el.classList.remove('chart-node--drop-hover'));
                target.classList.add('chart-node--drop-hover');
            }
        });

        chartEl.addEventListener('dragleave', (e) => {
            const target = e.target.closest('.chart-node');
            if (!target) return;
            // 자식 요소로 이동한 경우는 유지
            if (target.contains(e.relatedTarget)) return;
            target.classList.remove('chart-node--drop-hover');
        });

        chartEl.addEventListener('drop', async (e) => {
            const target = e.target.closest('.chart-node');
            if (!target) return;
            e.preventDefault();
            target.classList.remove('chart-node--drop-hover');

            let payload = null;
            try { payload = JSON.parse(e.dataTransfer.getData('application/x-zaemit-emp') || '{}'); } catch(_) {}
            if (!payload || !payload.empId) return;

            const destDeptId = parseInt(target.dataset.deptId);
            if (!destDeptId || destDeptId === payload.srcDept) return;

            const destDept = allDepartments.find(d => d.id === destDeptId);
            if (!destDept) return;

            if (!(await AppUI.confirm(`${payload.empName} 님을\n"${destDept.name}"(으)로 이동할까요?`))) return;

            try {
                if (useDB) {
                    const res = await fetch(`${basePath}/api/organization.php?action=moveEmployee`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ employee_id: payload.empId, department_id: destDeptId })
                    });
                    const data = await res.json();
                    if (data.error) { alert(data.error); return; }
                }
                // 로컬 상태 갱신 + 소프트 리렌더
                const emp = allEmployees.find(x => x.id === payload.empId);
                if (emp) emp.department_id = destDeptId;
                // 부서장이었던 경우 해제 (서버와 동일)
                const srcDept = allDepartments.find(d => d.id === payload.srcDept);
                if (srcDept && srcDept.head_employee_id == payload.empId) {
                    srcDept.head_employee_id = null;
                }
                applyFilters();
            } catch (err) {
                alert('이동 중 오류가 발생했습니다.');
            }
        });
    }

    // ===== 같은 부서 내 직원 상하 순서 변경 (포인터 기반 — 고스트+슬라이드) =====
    let empPreOrder = null;

    function initEmpDragDrop() {
        const list = document.getElementById('empList');
        if (!list) return;

        let ds = null;
        list.addEventListener('pointerdown', e => {
            const grip = e.target.closest('.emp-grip');
            if (!grip) return;
            const row = grip.closest('.org-detail-emp');
            if (!row) return;

            const rows = [...list.querySelectorAll('.org-detail-emp')];
            const idx = rows.indexOf(row);
            if (idx < 0 || rows.length < 2) return;
            e.preventDefault();

            if (!empPreOrder) {
                empPreOrder = rows.map(r => parseInt(r.dataset.empId, 10));
            }
            const rect = row.getBoundingClientRect();
            const ox = e.clientX - rect.left, oy = e.clientY - rect.top;
            const origTops = rows.map(r => r.getBoundingClientRect().top);
            const itemH = rows.length > 1 ? origTops[1] - origTops[0] : rect.height;

            const ghost = row.cloneNode(true);
            ghost.className = 'org-emp-drag-ghost';
            ghost.style.cssText = `position:fixed;left:${rect.left}px;top:${rect.top}px;width:${rect.width}px;height:${rect.height}px;z-index:9999;pointer-events:none;`;
            document.body.appendChild(ghost);
            row.classList.add('emp-dragging');
            list.classList.add('zm-drag-active');

            ds = { fromIdx: idx, gapIdx: idx, origTops, itemH, ghost, row, ox, oy, rows };

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
                row.classList.remove('emp-dragging');
                list.classList.remove('zm-drag-active');
                rows.forEach(r => r.style.transform = '');

                const { fromIdx, gapIdx } = ds;
                ds = null;
                if (gapIdx === fromIdx || gapIdx === fromIdx + 1) return;

                const freshRows = [...list.querySelectorAll('.org-detail-emp')];
                const movedRow = freshRows[fromIdx];
                const insertAt = gapIdx > fromIdx ? gapIdx - 1 : gapIdx;
                const remaining = freshRows.filter((_, i) => i !== fromIdx);
                if (insertAt >= remaining.length) {
                    list.appendChild(movedRow);
                } else {
                    list.insertBefore(movedRow, remaining[insertAt]);
                }
                showEmpReorderBar(list);
            }
            document.addEventListener('pointermove', onMove);
            document.addEventListener('pointerup', onUp);
            document.addEventListener('pointercancel', onUp);
        });
    }

    function showEmpReorderBar(list) {
        const parent = list.parentElement;
        if (!parent || parent.querySelector('.zm-reorder-bar')) return;
        const bar = document.createElement('div');
        bar.className = 'zm-reorder-bar';
        bar.innerHTML = '<button class="zm-cancel-btn">취소</button><button class="zm-save-btn">저장</button>';
        parent.appendChild(bar);

        bar.querySelector('.zm-cancel-btn').addEventListener('click', () => {
            empPreOrder = null;
            bar.remove();
            const urlDeptId = new URLSearchParams(location.search).get('dept');
            if (urlDeptId) {
                applyFilters();
                setTimeout(() => openDeptEdit(parseInt(urlDeptId)), 200);
            }
        });
        bar.querySelector('.zm-save-btn').addEventListener('click', async () => {
            const deptId = parseInt(list.dataset.deptId, 10);
            const newIds = [...list.querySelectorAll('.org-detail-emp')]
                .map(el => parseInt(el.dataset.empId, 10)).filter(Boolean);

            const deptEmps = allEmployees.filter(emp => emp.department_id == deptId);
            const deptEmpById = new Map(deptEmps.map(emp => [emp.id, emp]));
            let cursor = 0;
            for (let i = 0; i < allEmployees.length; i++) {
                if (allEmployees[i].department_id == deptId) {
                    const nextId = newIds[cursor++];
                    allEmployees[i] = deptEmpById.get(nextId) || allEmployees[i];
                }
            }

            if (useDB) {
                try {
                    const res = await fetch(`${basePath}/api/organization.php?action=reorderEmployees`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ department_id: deptId, employee_ids: newIds })
                    });
                    const data = await res.json();
                    if (data.error) { alert(data.error); return; }
                } catch (err) {
                    alert('순서 저장 중 오류가 발생했습니다.');
                    return;
                }
            }
            empPreOrder = null;
            bar.remove();
            applyFilters();
            const urlDeptId = new URLSearchParams(location.search).get('dept');
            if (urlDeptId) {
                setTimeout(() => openDeptEdit(parseInt(urlDeptId)), 200);
            }
        });
    }

    function applyFilters() {
        const filter = {
            employee: document.getElementById('searchEmployee').value.trim(),
        };

        if (filter.employee) {
            // 검색 시 매칭 직원이 속한 부서를 자동 펼침
            const s = filter.employee.toLowerCase();
            allEmployees.forEach(e => {
                if (e.name.toLowerCase().includes(s)) {
                    expandAncestors(e.department_id);
                    chartExpandedState[e.department_id] = true;
                }
            });
        }

        if (currentView === 'list') {
            renderListView(filter);
        } else {
            renderOrgView(filter);
        }

        const filtered = filter.employee
            ? allEmployees.filter(e => e.name.toLowerCase().includes(filter.employee.toLowerCase()))
            : allEmployees;
        document.getElementById('totalCount').textContent = filtered.length;
    }

    // 선택된 부서의 모든 조상을 차트에서 펼침
    function expandAncestors(deptId) {
        let cur = allDepartments.find(d => d.id == deptId);
        while (cur && cur.parent_id) {
            chartExpandedState[cur.parent_id] = true;
            cur = allDepartments.find(d => d.id == cur.parent_id);
        }
    }

    // 선택한 부서 + 하위 부서 ID 모두 반환
    function getAllChildDeptIds(deptId) {
        const ids = [deptId];
        function collect(parentId) {
            allDepartments.forEach(d => {
                if (d.parent_id == parentId) {
                    ids.push(d.id);
                    collect(d.id);
                }
            });
        }
        collect(deptId);
        return ids;
    }

    // === 헬퍼 ===
    function getDeptName(deptId) {
        const dept = allDepartments.find(d => d.id == deptId);
        return dept ? dept.name : '-';
    }

    function getPositionColor(position) {
        const colors = {
            '대표이사': 'bg-red-600 text-white',
            '이사':     'bg-orange-600 text-white',
            '부장':     'bg-blue-600 text-white',
            '차장':     'bg-cyan-600 text-white',
            '과장':     'bg-emerald-600 text-white',
            '대리':     'bg-violet-600 text-white',
            '주임':     'bg-pink-600 text-white',
            '사원':     'bg-slate-500 text-white',
        };
        return colors[position] || 'bg-slate-500 text-white';
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // === 차트 렌더링 (연결선 있는 top-down) ===
    function renderChartView(filter = {}) {
        let tree = buildTree(allDepartments);
        const container = document.getElementById('orgChart');
        container.innerHTML = '';

        if (tree.length === 0) {
            container.innerHTML = '<div class="text-center py-16 text-slate-400"><i data-lucide="git-branch" class="w-9 h-9 mb-3 mx-auto"></i><p>검색 결과가 없습니다.</p></div>';
            if (window.lucide) lucide.createIcons();
            return;
        }

        const matchedDeptIds = new Set();
        if (hasActiveFilter(filter)) {
            collectMatchedDepts(tree, filter, matchedDeptIds);
        }

        const rootUl = document.createElement('ul');
        tree.forEach(node => {
            rootUl.appendChild(createChartNode(node, 0, filter, matchedDeptIds));
        });
        container.appendChild(rootUl);
        if (window.lucide) lucide.createIcons();
        requestAnimationFrame(fitChartZoom); // 렌더 직후 화면 폭에 맞춰 자동 축소
    }

    function collectMatchedDepts(nodes, filter, matched) {
        nodes.forEach(node => {
            const emps = filterEmployees(node.employees || [], filter);
            if (emps.length > 0) matched.add(node.id);
            if (node.children?.length) {
                collectMatchedDepts(node.children, filter, matched);
            }
        });
    }

    function createChartNode(node, depth, filter, matchedDeptIds) {
        const li = document.createElement('li');
        const hasChildren = node.children?.length > 0;
        const isExpanded = chartExpandedState[node.id] !== false;
        const empCount = node.employees?.length || 0;
        const heads = (node.employees || []).filter(e => e.is_dept_head == 1 || e.id == node.head_employee_id);

        if (hasChildren && !isExpanded) {
            li.classList.add('chart-branch--collapsed');
        }

        const isFiltering = hasActiveFilter(filter);
        let highlightClass = '';
        if (isFiltering) {
            highlightClass = matchedDeptIds.has(node.id) ? ' chart-node--highlighted' : ' chart-node--dimmed';
        }
        if (selectedDeptId && node.id == selectedDeptId) {
            highlightClass += ' chart-node--selected';
        }

        let depthClass = 'chart-node--team';
        if (depth === 0) { depthClass = 'chart-node--root'; }
        else if (depth === 1) { depthClass = 'chart-node--division'; }

        const card = document.createElement('div');
        card.className = 'chart-node ' + depthClass + highlightClass;
        card.dataset.deptId = node.id;

        var headAvatarsHtml = '';
        if (heads.length > 0) {
            var avatarItems = heads.slice(0, 3).map(function(h) {
                if (h.profile_image) {
                    return '<img src="' + basePath + '/' + escapeHtml(h.profile_image) + '" alt="' + escapeHtml(h.name) + '" class="chart-head-avatar" title="' + escapeHtml(h.name) + '">';
                }
                var initial = (h.name || '?').charAt(0);
                return '<span class="chart-head-avatar chart-head-avatar--init" title="' + escapeHtml(h.name) + '">' + escapeHtml(initial) + '</span>';
            }).join('');
            headAvatarsHtml = '<div class="chart-head-avatars">' + avatarItems + '</div>';
        } else {
            headAvatarsHtml = '<div class="chart-head-avatars"><span class="chart-head-avatar chart-head-avatar--empty"><i data-lucide="user" class="w-3.5 h-3.5"></i></span></div>';
        }

        var metaText = heads.length > 0
            ? heads.map(function(h) { return escapeHtml(h.name); }).join(', ')
            : ((ORG_LABELS.department||{}).head||'부서장') + ' 미지정';

        card.innerHTML =
            headAvatarsHtml +
            '<div class="chart-node__content">' +
                '<div class="chart-node__name" title="' + escapeHtml(node.name) + '">' + escapeHtml(node.name) + '</div>' +
                '<div class="chart-node__meta">' + metaText + '</div>' +
            '</div>' +
            '<span class="chart-node__count">' + empCount + '명</span>' +
            (hasChildren
                ? '<span class="chart-node__hover-bridge" aria-hidden="true"></span><div class="chart-node__toggle" data-chart-toggle="' + node.id + '" title="' + (isExpanded ? '접기' : '펼치기') + '">' + (isExpanded ? '−' : '+') + '</div>'
                : '');

        card.addEventListener('click', (e) => {
            if (e.target.closest('[data-chart-toggle]')) return;
            selectedDeptId = node.id;
            document.querySelectorAll('#orgChart .chart-node--selected').forEach(el => el.classList.remove('chart-node--selected'));
            card.classList.add('chart-node--selected');
            renderDetailPanel(filter);
        });

        card.addEventListener('dblclick', (e) => {
            if (e.target.closest('[data-chart-toggle]')) return;
            e.preventDefault();
            selectedDeptId = node.id;
            document.querySelectorAll('#orgChart .chart-node--selected').forEach(el => el.classList.remove('chart-node--selected'));
            card.classList.add('chart-node--selected');
            renderDetailPanel(filter);
            openDeptEdit(node.id);
        });

        li.appendChild(card);

        if (hasChildren) {
            const childUl = document.createElement('ul');
            node.children.forEach(child => {
                childUl.appendChild(createChartNode(child, depth + 1, filter, matchedDeptIds));
            });
            li.appendChild(childUl);
        }

        return li;
    }

    // === 선택된 부서 상세 패널 ===
    function renderDetailPanel(filter = {}) {
        const header = document.getElementById('detailPanelHeader');
        const body   = document.getElementById('detailPanelBody');
        if (!selectedDeptId) {
            header.innerHTML = '<div class="flex flex-col items-center justify-center py-6 text-center"><i data-lucide="mouse-pointer-click" class="w-5 h-5 text-gray-300 mb-2"></i><div class="text-sm text-gray-400">조직도에서 ' + escapeHtml((ORG_LABELS.department||{}).label||'부서') + ' 카드를 클릭하면<br>상세 정보가 여기 표시돼요</div></div>';
            body.innerHTML = '';
            if (window.lucide) lucide.createIcons();
            return;
        }

        const dept = allDepartments.find(d => d.id == selectedDeptId);
        if (!dept) {
            header.innerHTML = '<div class="text-sm text-slate-500">선택된 ' + escapeHtml((ORG_LABELS.department||{}).label||'부서') + '가 없습니다.</div>';
            body.innerHTML = '';
            return;
        }

        const childDeptIds = getAllChildDeptIds(dept.id);
        const directEmps = allEmployees.filter(e => e.department_id == dept.id);
        const totalEmps  = allEmployees.filter(e => childDeptIds.includes(e.department_id));
        const detailHeads = directEmps.filter(e => e.is_dept_head == 1 || e.id == dept.head_employee_id);
        const subDepts = allDepartments
            .filter(d => d.parent_id == dept.id)
            .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));

        // 필터링된 직원 (이름/직급 필터)
        const displayEmps = hasActiveFilter(filter) ? filterEmployees(directEmps, filter) : directEmps;

        // 경로 breadcrumb
        const path = [];
        let cur = dept;
        while (cur) {
            path.unshift(cur);
            cur = allDepartments.find(d => d.id == cur.parent_id) || null;
        }
        const crumb = path.map(p =>
            p.id == dept.id
                ? '<span class="text-slate-100 font-semibold">' + escapeHtml(p.name) + '</span>'
                : '<a data-select-dept="' + p.id + '" class="hover:text-primary cursor-pointer">' + escapeHtml(p.name) + '</a>'
        ).join('<span class="mx-1.5 text-slate-600">/</span>');

        header.innerHTML =
            '<div class="text-xs text-slate-500 mb-1.5">' + crumb + '</div>' +
            '<div class="flex items-start justify-between gap-3">' +
                '<div>' +
                    '<h3 class="text-base font-bold text-slate-100">' + escapeHtml(dept.name) + '</h3>' +
                    '<p class="text-xs text-slate-400 mt-0.5">' +
                        (detailHeads.length > 0 ? ((ORG_LABELS.department||{}).head||'부서장') + ' ' + detailHeads.map(h => escapeHtml(h.name)).join(', ') : ((ORG_LABELS.department||{}).head||'부서장') + ' 미지정') +
                    '</p>' +
                '</div>' +
                (canEdit ? '<div class="flex items-center gap-2">' +
                    '<button data-edit-dept="' + dept.id + '" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-slate-300 border border-slate-700 rounded-lg hover:bg-slate-800 hover:text-slate-100 transition-colors"><i data-lucide="pencil" class="w-3.5 h-3.5 pointer-events-none"></i>수정</button>' +
                    '<button data-delete-dept="' + dept.id + '" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-rose-400 border border-rose-400/30 rounded-lg hover:bg-rose-400/10 hover:text-rose-300 transition-colors"><i data-lucide="trash-2" class="w-3.5 h-3.5 pointer-events-none"></i>삭제</button>' +
                '</div>' : '') +
            '</div>' +
            '<div class="mt-3 flex items-center gap-3 text-xs text-slate-400">' +
                '<span class="inline-flex items-center gap-1.5"><i data-lucide="user" class="w-3.5 h-3.5"></i>직속 <span class="text-slate-200 font-semibold tabular-nums">' + directEmps.length + '</span>명</span>' +
                '<span class="text-slate-700">·</span>' +
                '<span class="inline-flex items-center gap-1.5"><i data-lucide="users" class="w-3.5 h-3.5"></i>전체 <span class="text-slate-200 font-semibold tabular-nums">' + totalEmps.length + '</span>명</span>' +
            '</div>';

        // 본문
        let bodyHtml = '';

        // 하위 조직
        if (subDepts.length > 0) {
            bodyHtml += '<div><div class="text-xs font-semibold text-slate-400 mb-2 uppercase tracking-wider">하위 조직 ' + subDepts.length + '개</div>';
            bodyHtml += '<div class="flex flex-wrap gap-2">';
            subDepts.forEach(sd => {
                const empN = allEmployees.filter(e => e.department_id == sd.id).length;
                bodyHtml += '<span class="org-subdept-chip" data-select-dept="' + sd.id + '">' +
                                '<i data-lucide="building-2" class="w-3.5 h-3.5"></i>' +
                                escapeHtml(sd.name) +
                                '<span class="text-slate-500 ml-1">' + empN + '</span>' +
                            '</span>';
            });
            bodyHtml += '</div></div>';
        }

        // 직원
        bodyHtml += '<div><div class="text-xs font-semibold text-slate-400 mb-2 uppercase tracking-wider">직속 직원 ' + displayEmps.length + '명' +
                    (hasActiveFilter(filter) && directEmps.length !== displayEmps.length ? ' <span class="text-slate-500 normal-case font-normal">(전체 ' + directEmps.length + '명 중 필터)</span>' : '') +
                    '</div>';
        if (displayEmps.length === 0) {
            bodyHtml += '<div class="text-sm text-slate-500 text-center py-6 rounded-lg bg-slate-950/50">소속 직원이 없습니다.</div>';
        } else {
            const hues = [220, 260, 300, 20, 160, 40, 180, 340, 80, 200];
            // empList = 드롭 인디케이터 기준 컨테이너 (결재선 routeList 패턴)
            bodyHtml += '<div id="empList" class="space-y-1.5 relative" data-dept-id="' + dept.id + '">';
            displayEmps.forEach((emp, idx) => {
                const isHead = emp.is_dept_head == 1 || emp.id == dept.head_employee_id;
                const initial = emp.name ? emp.name.charAt(0) : '?';
                const hue = emp.name ? hues[emp.name.charCodeAt(0) % hues.length] : 220;
                const avatarStyle = isHead
                    ? 'background:linear-gradient(135deg,#4F6AFF,#7090ff);color:#fff;'
                    : 'background:hsl(' + hue + ',55%,25%);color:hsl(' + hue + ',70%,80%);';
                const posStyle = getPositionColorInline(emp.position);
                bodyHtml += '<div class="org-detail-emp"' + (canEdit ? ' draggable="true"' : '') + ' data-emp-id="' + emp.id + '" data-emp-name="' + escapeHtml(emp.name) + '" data-src-dept="' + dept.id + '" data-emp-idx="' + idx + '">' +
                                (canEdit ? '<div class="org-detail-emp__drag emp-grip" title="드래그하여 순서 변경"><i data-lucide="grip-vertical" class="w-3.5 h-3.5"></i></div>' : '') +
                                '<div class="org-detail-emp__avatar" style="' + avatarStyle + '">' + escapeHtml(initial) + '</div>' +
                                '<div class="org-detail-emp__info">' +
                                    '<div class="org-detail-emp__name">' +
                                        escapeHtml(emp.name) +
                                        '<span class="org-pos-badge" style="' + posStyle + '">' + escapeHtml(emp.position || '-') + '</span>' +
                                        (isHead ? '<span class="org-head-chip" title="' + ((ORG_LABELS.department||{}).head||'부서장') + '"><i data-lucide="user-round-check" class="pointer-events-none"></i></span>' : '') +
                                    '</div>' +
                                    (emp.title || emp.email
                                        ? '<div class="org-detail-emp__meta">' + escapeHtml(emp.title || emp.email || '') + '</div>'
                                        : '') +
                                '</div>' +
                                (canEdit
                                    ? (isHead
                                        ? '<button data-unset-head="' + dept.id + '" data-emp-id="' + emp.id + '" class="org-head-btn org-head-btn--unset" title="' + ((ORG_LABELS.department||{}).head||'부서장') + ' 해제"><i data-lucide="crown" class="w-3.5 h-3.5 pointer-events-none"></i>해제</button>'
                                        : '<button data-set-head="' + dept.id + '" data-emp-id="' + emp.id + '" class="org-head-btn org-head-btn--set" title="' + ((ORG_LABELS.department||{}).head||'부서장') + '으로 지정"><i data-lucide="shield-check" class="w-3.5 h-3.5 pointer-events-none"></i>' + ((ORG_LABELS.department||{}).head||'부서장') + ' 지정</button>')
                                    : '') +
                            '</div>';
            });
            bodyHtml += '</div>';
        }
        bodyHtml += '</div>';

        body.innerHTML = bodyHtml;
        if (window.lucide) lucide.createIcons();
        initEmpDragDrop();
    }

    function getPositionColorInline(position) {
        const map = {
            '대표이사': 'background:#dc2626;color:#fff;',
            '이사':     'background:#ea580c;color:#fff;',
            '부장':     'background:#2563eb;color:#fff;',
            '차장':     'background:#0891b2;color:#fff;',
            '과장':     'background:#059669;color:#fff;',
            '대리':     'background:#7c3aed;color:#fff;',
            '주임':     'background:#db2777;color:#fff;',
            '사원':     'background:#64748b;color:#fff;',
        };
        return map[position] || 'background:#64748b;color:#fff;';
    }
});
