/**
 * 결재 감사로그 페이지 JS
 */
(function () {
    const API = window.__AUDIT_API;
    let currentTab = 'logs';
    let logsPage = 1, actionsPage = 1;
    let logsTotalPages = 1, actionsTotalPages = 1;
    let allLogs = [], allActions = [];
    let expandedIdx = -1;

    const CATEGORY_BADGE = {
        document: { label: '문서', cls: 'badge badge-sky' },
        admin:    { label: '관리자', cls: 'badge badge-rose' },
        form:     { label: '양식', cls: 'badge badge-emerald' },
        config:   { label: '설정', cls: 'badge badge-gray' },
    };

    const EVENT_LABELS = {
        document_created: '문서 생성', document_updated: '문서 수정',
        document_submitted: '문서 상신', document_deleted: '문서 삭제',
        document_viewed: '문서 열람',
        form_created: '양식 생성', form_updated: '양식 수정',
        form_deleted: '양식 삭제', form_toggled: '양식 활성/비활성',
        line_created: '결재선 생성', line_updated: '결재선 수정', line_deleted: '결재선 삭제',
        config_changed: '설정 변경',
        admin_change_approver: '결재자 변경', admin_batch_change_approver: '결재자 일괄 변경',
        admin_force_complete: '강제 완료', admin_force_reject: '강제 반려',
        admin_soft_delete: '문서 삭제 (관리자)',
    };

    const EVENT_ICONS = {
        document_created: 'file-plus', document_updated: 'file-edit', document_submitted: 'send',
        document_deleted: 'file-x', document_viewed: 'eye',
        form_created: 'layout-template', form_updated: 'layout-template', form_deleted: 'layout-template',
        form_toggled: 'toggle-left',
        line_created: 'git-branch', line_updated: 'git-branch', line_deleted: 'git-branch',
        config_changed: 'settings',
        admin_change_approver: 'user-cog', admin_batch_change_approver: 'users',
        admin_force_complete: 'check-circle', admin_force_reject: 'x-circle',
        admin_soft_delete: 'trash-2',
    };

    const FIELD_LABELS = {
        approver_id: '결재자 ID', approver_name: '결재자', approver_dept: '결재자 부서',
        step_order: '결재 단계', status: '상태', title: '제목', doc_type: '문서종류',
        is_active: '활성', default_depth: '기본 결재 단계', description: '설명',
        drafter_name: '기안자', drafter_dept: '기안부서',
        department: '부서', scope: '범위', name: '이름', type: '유형',
        category: '카테고리', content: '내용', form_id: '양식',
        allowed_departments: '허용 부서', allowed_positions: '허용 직급',
        retention_days: '보존기간(일)', created_by: '작성자',
    };

    const STATUS_COLORS = {
        '대기': { bg: 'rgba(107,114,128,0.12)', fg: '#6b7280' },
        '진행': { bg: 'rgba(59,130,246,0.12)', fg: '#3b82f6' },
        '승인': { bg: 'rgba(16,185,129,0.12)', fg: '#10b981' },
        '반려': { bg: 'rgba(244,63,94,0.12)', fg: '#f43f5e' },
        '임시저장': { bg: 'rgba(107,114,128,0.08)', fg: '#9ca3af' },
    };

    const ACTION_BADGE = { '승인': 'badge badge-emerald', '반려': 'badge badge-rose', '협의': 'badge badge-amber' };

    const VALUE_LABELS = {
        personal: '개인', company: '전사', department: '부서별',
        all: '전체', true: '예', false: '아니오',
        '1': '활성', '0': '비활성',
    };

    // --- Init ---
    document.addEventListener('DOMContentLoaded', () => {
        loadStats();
        loadLogs();
        populateEventTypeFilter();
    });

    // --- Tab ---
    window.switchTab = function (tab) {
        currentTab = tab;
        document.querySelectorAll('[data-tab-btn]').forEach(b => {
            b.classList.toggle('audit-tab-active', b.dataset.tabBtn === tab);
        });
        el('tabLogs').classList.toggle('hidden', tab !== 'logs');
        el('tabActions').classList.toggle('hidden', tab !== 'actions');
        if (tab === 'actions' && !allActions.length) loadActions();
    };

    // --- Stats ---
    function loadStats() {
        fetch(`${API}?action=getStats`)
            .then(r => r.json())
            .then(res => {
                if (!res.ok) return;
                const d = res.data;
                el('statToday').textContent = d.today.toLocaleString();
                el('statWeek').textContent = d.week.toLocaleString();
                el('statAdmin').textContent = d.admin_week.toLocaleString();
                renderTopEvents(d.top_events);
            })
            .catch(() => {});
    }

    function renderTopEvents(events) {
        const container = el('topEventsBody');
        if (!events || !events.length) {
            container.innerHTML = `<div class="text-xs" style="color:var(--zm-text-muted)">데이터 없음</div>`;
            return;
        }
        const top3 = events.slice(0, 3);
        const maxCnt = parseInt(top3[0]?.cnt || 1);
        container.innerHTML = top3.map((e, i) => {
            const label = EVENT_LABELS[e.event_type] || e.event_type;
            const icon = EVENT_ICONS[e.event_type] || 'activity';
            const cnt = parseInt(e.cnt);
            const pct = Math.round((cnt / maxCnt) * 100);
            return `<div class="flex items-center gap-2.5 ${i > 0 ? 'mt-2' : ''}">
                <i data-lucide="${icon}" class="w-3.5 h-3.5 shrink-0" style="color:var(--zm-text-muted)"></i>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-0.5">
                        <span class="text-xs truncate" style="color:var(--zm-text-subtle)">${label}</span>
                        <span class="text-xs font-semibold ml-2" style="color:var(--zm-text-strong)">${cnt}</span>
                    </div>
                    <div class="h-1 rounded-full" style="background:var(--zm-surface-2)">
                        <div class="h-full rounded-full" style="width:${pct}%;background:var(--zm-primary);opacity:${1 - i * 0.25}"></div>
                    </div>
                </div>
            </div>`;
        }).join('');
        icons();
    }

    // --- Event type filter ---
    function populateEventTypeFilter() {
        const sel = el('filterEventType');
        if (!sel) return;
        Object.entries(EVENT_LABELS).forEach(([k, v]) => {
            const opt = document.createElement('option');
            opt.value = k; opt.textContent = v;
            sel.appendChild(opt);
        });
    }

    // --- Logs ---
    window.loadLogs = function () {
        const p = new URLSearchParams();
        p.set('action', 'getLogs');
        p.set('page', logsPage);
        p.set('per_page', el('perPageSelect').value);
        addFilter(p, 'filterDateFrom', 'date_from');
        addFilter(p, 'filterDateTo', 'date_to');
        addFilter(p, 'filterActor', 'actor');
        addFilter(p, 'filterCategory', 'event_category');
        addFilter(p, 'filterEventType', 'event_type');
        addFilter(p, 'filterTarget', 'target');

        fetch(`${API}?${p}`)
            .then(r => r.json())
            .then(res => {
                if (!res.ok) { el('logTableBody').innerHTML = errRow(7); return; }
                allLogs = res.data.logs;
                el('totalCount').textContent = res.data.pagination.total.toLocaleString();
                logsTotalPages = res.data.pagination.pages;
                logsPage = res.data.pagination.page;
                renderLogsTable();
                renderPagination('logsPagination', logsPage, logsTotalPages, 'goLogsPage');
            })
            .catch(() => { el('logTableBody').innerHTML = errRow(7); });
    };

    function renderLogsTable() {
        const tbody = el('logTableBody');
        if (!allLogs.length) { tbody.innerHTML = emptyRow(7, hasLogFilters()); icons(); return; }

        tbody.innerHTML = allLogs.map((log, idx) => {
            const cat = CATEGORY_BADGE[log.event_category] || { label: log.event_category, cls: 'badge badge-gray' };
            const eventLabel = EVENT_LABELS[log.event_type] || log.event_type;
            const eventIcon = EVENT_ICONS[log.event_type] || 'activity';
            const changeHtml = buildChangeHtml(log.old_value, log.new_value);
            const targetHtml = buildTargetHtml(log);

            return `<tr data-log-idx="${idx}">
                <td>
                    <div class="whitespace-nowrap" title="${esc(log.created_at)}" style="color:var(--zm-text-muted);font-size:13px">${relTime(log.created_at)}</div>
                    ${log.ip_address ? `<div class="mt-0.5" style="color:var(--zm-text-muted);opacity:0.6;font-size:11px">${esc(log.ip_address)}</div>` : ''}
                </td>
                <td style="color:var(--zm-text-strong)">${esc(log.actor_name)}</td>
                <td class="text-center"><span class="${cat.cls}">${cat.label}</span></td>
                <td>
                    <div class="flex items-center gap-1.5">
                        <i data-lucide="${eventIcon}" class="w-4 h-4" style="color:var(--zm-text-muted)"></i>
                        <span style="color:var(--zm-text-default)">${eventLabel}</span>
                    </div>
                </td>
                <td style="color:var(--zm-text-default)">${targetHtml}</td>
                <td>${changeHtml}</td>
                <td style="color:var(--zm-text-muted)">${log.comment ? esc(log.comment) : ''}</td>
            </tr>`;
        }).join('');

        tbody.querySelectorAll('tr[data-log-idx]').forEach(tr => {
            tr.addEventListener('click', e => {
                if (e.target.closest('a')) return;
                toggleExpand(parseInt(tr.dataset.logIdx));
            });
        });
        icons();
    }

    function buildTargetHtml(log) {
        if (!log.target_label) return '';
        return esc(log.target_label);
    }

    // --- Approval actions ---
    window.loadActions = function () {
        const p = new URLSearchParams();
        p.set('action', 'getApprovalActions');
        p.set('page', actionsPage);
        p.set('per_page', el('actPerPage').value);
        addFilter(p, 'actDateFrom', 'date_from');
        addFilter(p, 'actDateTo', 'date_to');
        addFilter(p, 'actApprover', 'approver');
        addFilter(p, 'actType', 'action_type');
        addFilter(p, 'actTarget', 'target');

        fetch(`${API}?${p}`)
            .then(r => r.json())
            .then(res => {
                if (!res.ok) { el('actTableBody').innerHTML = errRow(5); return; }
                allActions = res.data.actions;
                el('actTotalCount').textContent = res.data.pagination.total.toLocaleString();
                actionsTotalPages = res.data.pagination.pages;
                actionsPage = res.data.pagination.page;
                renderActionsTable();
                renderPagination('actPagination', actionsPage, actionsTotalPages, 'goActionsPage');
            })
            .catch(() => { el('actTableBody').innerHTML = errRow(5); });
    };

    function renderActionsTable() {
        const tbody = el('actTableBody');
        if (!allActions.length) { tbody.innerHTML = emptyRow(5, false, '결재 행위 이력이 없습니다'); icons(); return; }

        tbody.innerHTML = allActions.map(a => {
            const badgeCls = ACTION_BADGE[a.action] || 'badge badge-gray';
            return `<tr>
                <td><div style="color:var(--zm-text-muted);font-size:13px">${fmtDateTime(a.action_date)}</div></td>
                <td>
                    <span style="color:var(--zm-text-strong)">${esc(a.approver_name)}</span>
                    ${a.approver_dept ? `<div class="mt-0.5" style="color:var(--zm-text-muted);font-size:13px">${esc(a.approver_dept)}</div>` : ''}
                </td>
                <td class="text-center"><span class="${badgeCls}">${esc(a.action)}</span></td>
                <td>
                    <span style="color:var(--zm-text-strong)">${esc(a.doc_title || '-')}</span>
                    <div class="mt-0.5" style="color:var(--zm-text-muted);font-size:12px">${esc(a.doc_number || '')}</div>
                    <div class="mt-0.5" style="color:var(--zm-text-muted);font-size:13px">기안: ${esc(a.drafter_name || '-')}${a.drafter_dept ? ` (${esc(a.drafter_dept)})` : ''}</div>
                </td>
                <td style="color:var(--zm-text-muted)">${a.comment ? esc(a.comment) : ''}</td>
            </tr>`;
        }).join('');
        icons();
    }

    // --- Diff display ---
    function translateVal(v) { const s = String(v); return VALUE_LABELS[s] || s; }

    function buildChangeHtml(oldVal, newVal) {
        if (!oldVal && !newVal) return '';
        const parts = [];

        if (oldVal && typeof oldVal === 'object') {
            for (const [k, v] of Object.entries(oldVal)) {
                const nv = newVal && newVal[k] !== undefined ? newVal[k] : null;
                if (nv !== null && nv !== v) {
                    if (k === 'status') {
                        parts.push(buildStatusDiff(v, nv));
                    } else {
                        parts.push(`<div class="audit-diff-row">
                            <span class="audit-diff-label">${esc(fieldLabel(k))}</span>
                            <span class="audit-diff-old">${esc(translateVal(v))}</span>
                            <i data-lucide="arrow-right" class="w-3 h-3 shrink-0" style="color:var(--zm-text-muted)"></i>
                            <span class="audit-diff-new">${esc(translateVal(nv))}</span>
                        </div>`);
                    }
                }
            }
        }

        if (!parts.length && newVal && typeof newVal === 'object') {
            for (const [k, v] of Object.entries(newVal)) {
                const sv = String(v ?? '');
                if (!sv) continue;
                parts.push(`<div class="audit-diff-row">
                    <span class="audit-diff-label">${esc(fieldLabel(k))}</span>
                    <span class="audit-diff-new">${esc(translateVal(v))}</span>
                </div>`);
            }
        }
        return parts.length ? `<div class="audit-diff-wrap">${parts.join('')}</div>` : '';
    }

    function buildStatusDiff(oldStatus, newStatus) {
        const oldC = STATUS_COLORS[oldStatus] || STATUS_COLORS['대기'];
        const newC = STATUS_COLORS[newStatus] || STATUS_COLORS['승인'];
        return `<div class="audit-diff-row">
            <span class="audit-diff-label">상태</span>
            <span class="audit-status-pill" style="background:${oldC.bg};color:${oldC.fg}">${esc(String(oldStatus))}</span>
            <i data-lucide="arrow-right" class="w-3 h-3 shrink-0" style="color:var(--zm-text-muted)"></i>
            <span class="audit-status-pill" style="background:${newC.bg};color:${newC.fg}">${esc(String(newStatus))}</span>
        </div>`;
    }

    function fieldLabel(k) { return FIELD_LABELS[k] || k; }

    // --- Pagination ---
    function renderPagination(containerId, page, totalPages, fnName) {
        const c = el(containerId);
        if (totalPages <= 1) { c.innerHTML = ''; return; }
        let h = `<button class="pg-btn ${page<=1?'pg-disabled':''}" onclick="${fnName}(1)"><i data-lucide="chevrons-left" class="w-3 h-3"></i></button>`;
        h += `<button class="pg-btn ${page<=1?'pg-disabled':''}" onclick="${fnName}(${page-1})"><i data-lucide="chevron-left" class="w-3 h-3"></i></button>`;
        for (let i = 1; i <= totalPages; i++) {
            if (totalPages > 7 && Math.abs(i - page) > 2 && i > 2 && i < totalPages - 1) {
                if (i === 3 || i === totalPages - 2) h += '<span class="px-1" style="color:var(--zm-text-muted)">...</span>';
                continue;
            }
            h += `<button class="pg-btn ${i===page?'pg-active':''}" onclick="${fnName}(${i})">${i}</button>`;
        }
        h += `<button class="pg-btn ${page>=totalPages?'pg-disabled':''}" onclick="${fnName}(${page+1})"><i data-lucide="chevron-right" class="w-3 h-3"></i></button>`;
        h += `<button class="pg-btn ${page>=totalPages?'pg-disabled':''}" onclick="${fnName}(${totalPages})"><i data-lucide="chevrons-right" class="w-3 h-3"></i></button>`;
        c.innerHTML = h;
        icons();
    }

    function goLogsPage(p) { if (p < 1 || p > logsTotalPages) return; logsPage = p; loadLogs(); }
    function goActionsPage(p) { if (p < 1 || p > actionsTotalPages) return; actionsPage = p; loadActions(); }
    window.goLogsPage = goLogsPage;
    window.goActionsPage = goActionsPage;

    // --- CSV ---
    window.downloadCsv = function () {
        if (!allLogs.length) { alert('다운로드할 데이터가 없습니다.'); return; }
        const BOM = '﻿';
        const header = ['시각', '수행자', 'IP', '카테고리', '이벤트', '대상', '변경전', '변경후', '사유'];
        const rows = allLogs.map(l => [
            l.created_at, l.actor_name, l.ip_address || '',
            l.event_category, EVENT_LABELS[l.event_type] || l.event_type,
            l.target_label || '',
            l.old_value ? JSON.stringify(l.old_value) : '',
            l.new_value ? JSON.stringify(l.new_value) : '',
            l.comment || ''
        ].map(v => '"' + csvSafe(v) + '"').join(','));
        const csv = BOM + header.join(',') + '\n' + rows.join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = '결재감사로그_' + new Date().toISOString().slice(0, 10) + '.csv';
        a.click(); URL.revokeObjectURL(url);
    };

    // --- Resets ---
    window.resetLogFilters = function () {
        ['filterDateFrom', 'filterDateTo', 'filterActor', 'filterCategory', 'filterEventType', 'filterTarget'].forEach(id => { el(id).value = ''; });
        logsPage = 1; loadLogs();
    };
    window.resetActFilters = function () {
        ['actDateFrom', 'actDateTo', 'actApprover', 'actType', 'actTarget'].forEach(id => { el(id).value = ''; });
        actionsPage = 1; loadActions();
    };

    // --- Accordion Expand ---
    const FLOW_ICON = { '승인': '✓', '반려': '✕', '대기': '·' };
    const FLOW_COLOR = { '승인': '#10b981', '반려': '#f43f5e', '대기': 'var(--zm-text-muted)' };

    function collapseExisting() {
        return new Promise(resolve => {
            const existing = document.querySelector('.audit-expand-row');
            if (!existing) { resolve(); return; }
            const clip = existing.querySelector('.audit-expand-clip');
            if (clip) {
                clip.classList.add('collapsing');
                const done = () => { existing.remove(); resolve(); };
                clip.addEventListener('transitionend', done, { once: true });
                setTimeout(done, 250);
            } else {
                existing.remove(); resolve();
            }
        });
    }

    function slideOpen(clip) {
        clip.style.maxHeight = '0px';
        clip.style.opacity = '0';
        clip.offsetHeight;
        requestAnimationFrame(() => {
            clip.style.maxHeight = clip.scrollHeight + 'px';
            clip.style.opacity = '1';
            clip.addEventListener('transitionend', () => { clip.style.maxHeight = 'none'; }, { once: true });
        });
    }

    function toggleExpand(idx) {
        const wasOpen = expandedIdx === idx;
        document.querySelectorAll('.audit-row-active').forEach(r => r.classList.remove('audit-row-active'));

        if (wasOpen) {
            collapseExisting();
            expandedIdx = -1;
            return;
        }

        const existing = document.querySelector('.audit-expand-row');
        if (existing) existing.remove();
        expandedIdx = idx;
        const log = allLogs[idx];
        if (!log) return;

        const sourceRow = document.querySelector(`tr[data-log-idx="${idx}"]`);
        if (!sourceRow) return;
        sourceRow.classList.add('audit-row-active');

        const expandRow = document.createElement('tr');
        expandRow.className = 'audit-expand-row';
        expandRow.innerHTML = `<td colspan="7"><div class="audit-expand-clip"><div class="audit-expand-inner" id="expandBody-${idx}">
            <div class="audit-expand-empty">로딩 중...</div>
        </div></div></td>`;
        sourceRow.after(expandRow);

        const clip = expandRow.querySelector('.audit-expand-clip');
        slideOpen(clip);

        if (log.target_type && log.target_id) {
            fetchExpandDetail(log, idx);
        } else {
            renderExpandFallback(log, idx);
        }
    }

    function renderExpandFallback(log, idx) {
        const box = document.getElementById(`expandBody-${idx}`);
        if (!box) return;

        if (log.target_type === 'document' && log.target_id) {
            box.innerHTML = `<div class="audit-expand-flow"><div class="audit-expand-empty">결재 이력 없음</div></div>
                <a href="approval_view.php?id=${log.target_id}" class="audit-expand-btn">
                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i> 문서 보기
                </a>`;
        } else {
            box.innerHTML = `<div class="audit-expand-empty">추가 정보 없음</div>`;
        }
        icons();
    }

    function fetchExpandDetail(log, idx) {
        const p = new URLSearchParams({ action: 'getLogDetail', target_type: log.target_type, target_id: log.target_id });
        fetch(`${API}?${p}`)
            .then(r => r.json())
            .then(res => {
                if (!res.ok) { renderExpandFallback(log, idx); return; }
                renderExpandContent(res.data, log, idx);
            })
            .catch(() => renderExpandFallback(log, idx));
    }

    const DOC_STATUS_CLS = { '임시저장': 'badge-gray', '대기': 'badge-gray', '진행': 'badge-sky', '승인': 'badge-emerald', '반려': 'badge-rose' };

    function renderExpandContent(data, log, idx) {
        const box = document.getElementById(`expandBody-${idx}`);
        if (!box) return;
        let html = '';

        if (log.target_type === 'document' && data.document) {
            const d = data.document;
            const sCls = DOC_STATUS_CLS[d.status] || 'badge-gray';
            const link = log.target_id
                ? `<a href="approval_view.php?id=${log.target_id}" class="ax-doc-btn"><i data-lucide="external-link" style="width:12px;height:12px"></i>문서 보기</a>`
                : '';
            html += `<div class="ax-doc">
                <span class="ax-doc-title">${esc(d.title || '-')}</span>
                <span class="badge ${sCls}">${esc(d.status)}</span>
                <span class="ax-doc-meta">${esc(d.doc_type || '')} · 기안: ${esc(d.drafter_name || '-')}</span>
                ${link}
            </div>`;
        }

        if (log.target_type === 'document' && data.approval_flow && data.approval_flow.length > 0) {
            const steps = data.approval_flow.map((f, i) => {
                const color = FLOW_COLOR[f.action] || FLOW_COLOR['대기'];
                const time = f.action !== '대기' && f.action_date ? ' · ' + relTime(f.action_date) : '';
                const arrow = i < data.approval_flow.length - 1 ? '<span class="ax-arrow">→</span>' : '';
                // 표시 라벨만 변경 (저장값 '건너뜀'은 유지) — 상위 반려/전결로 차례가 오지 않은 단계
                const actLabel = f.action === '건너뜀' ? '미진행' : f.action;
                return `<div class="ax-step">
                    <span class="ax-dot" style="background:${color}"></span>
                    <span class="ax-name">${esc(f.approver_name)}</span>
                    <span class="ax-status" style="color:${color}">${esc(actLabel)}${time}</span>
                </div>${arrow}`;
            }).join('');
            html += `<div class="ax-flow">${steps}</div>`;
        }

        if (log.target_type === 'line' && data.line) {
            html += `<div class="ax-line">
                <i data-lucide="git-branch" style="width:14px;height:14px;color:var(--zm-primary)"></i>
                <span style="font-weight:500">${esc(data.line.name || '-')}</span>
                <span class="ax-doc-meta">범위: ${esc(translateVal(data.line.scope || '-'))}${data.line.department ? ' · ' + esc(data.line.department) : ''}</span>
            </div>`;
        }

        if (!html) { renderExpandFallback(log, idx); return; }
        box.innerHTML = html;
        icons();
        const clip = box.closest('.audit-expand-clip');
        if (clip) clip.style.maxHeight = clip.scrollHeight + 'px';
    }

    document.addEventListener('keydown', e => {
        if (expandedIdx < 0) return;
        if (e.key === 'Escape') { toggleExpand(expandedIdx); e.preventDefault(); }
    });

    // --- Relative time ---
    function relTime(dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr.replace(' ', 'T'));
        const now = new Date();
        const diffMs = now - d;
        const diffMin = Math.floor(diffMs / 60000);
        const diffHr = Math.floor(diffMs / 3600000);
        const diffDay = Math.floor(diffMs / 86400000);

        if (diffMin < 1) return '방금 전';
        if (diffMin < 60) return `${diffMin}분 전`;
        if (diffHr < 24) return `${diffHr}시간 전`;
        if (diffDay < 7) return `${diffDay}일 전`;
        return fmtDateTime(dateStr);
    }

    // --- Helpers ---
    function el(id) { return document.getElementById(id); }
    function addFilter(p, elId, paramName) { const v = el(elId)?.value; if (v) p.set(paramName, v); }
    function esc(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    function fmtDateTime(d) { return d ? d.replace(/-/g, '.').replace('T', ' ').substring(0, 16) : '-'; }
    function csvSafe(v) { const s = String(v ?? ''); return /^[=+\-@\t\r]/.test(s) ? "'" + s.replace(/"/g, '""') : s.replace(/"/g, '""'); }
    function icons() { if (typeof lucide !== 'undefined') lucide.createIcons(); }
    function hasLogFilters() { return ['filterDateFrom', 'filterActor', 'filterCategory', 'filterEventType', 'filterTarget'].some(id => el(id)?.value); }
    function errRow(cols) { return `<tr><td colspan="${cols}" class="py-12 text-center text-sm" style="color:#f43f5e">데이터를 불러오지 못했습니다.</td></tr>`; }
    function emptyRow(cols, hasFilter, defaultMsg) {
        const msg = hasFilter ? '검색 결과가 없습니다.' : (defaultMsg || '감사 로그가 없습니다');
        const icon = hasFilter ? 'search-x' : 'scroll-text';
        return `<tr><td colspan="${cols}" class="py-16 text-center">
            <div class="flex flex-col items-center">
                <div class="w-12 h-12 rounded-full flex items-center justify-center mb-3" style="background:var(--zm-surface-2)">
                    <i data-lucide="${icon}" class="w-5 h-5" style="color:var(--zm-text-muted)"></i>
                </div>
                <p class="text-sm font-medium" style="color:var(--zm-text-subtle)">${msg}</p>
            </div>
        </td></tr>`;
    }
})();
