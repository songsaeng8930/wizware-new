/**
 * 결재 UI 공유 렌더링 모듈
 * 3가지 모드: Compact(간략) / Flow(가로 카드) / Timeline(세로 편집)
 */
window.ApvUI = (function () {
  'use strict';

  /* ── helpers ── */
  const _escEl = document.createElement('span');
  function esc(s) {
    _escEl.textContent = s == null ? '' : String(s);
    return _escEl.innerHTML;
  }

  function roleBadge(role) {
    const map = { '결재': 'apv-role--approve', '전결': 'apv-role--final', '협조': 'apv-role--coop', '참조': 'apv-role--cc', '기안': 'apv-role--draft' };
    const cls = map[role] || 'apv-role--default';
    return `<span class="apv-role ${cls}">${esc(role || '결재')}</span>`;
  }

  function statusIcon(status, idx) {
    const map = {
      draft:    { cls: 'apv-icon--draft',    text: '✓' },
      done:     { cls: 'apv-icon--done',     text: '✓' },
      current:  { cls: 'apv-icon--current',  text: '→' },
      rejected: { cls: 'apv-icon--rejected', text: '✕' },
      skipped:  { cls: 'apv-icon--skipped',  text: '-' },
      waiting:  { cls: 'apv-icon--waiting',  text: String(idx ?? '○') },
    };
    const m = map[status] || map.waiting;
    return `<span class="apv-icon ${m.cls}">${m.text}</span>`;
  }

  function resolveStatus(step, currentStep) {
    const act = step.action || step.status;
    if (act === '승인' || act === '합의' || act === '의견') return 'done';
    if (act === '반려') return 'rejected';
    if (act === '건너뜀') return 'skipped';
    if (act === '기안' || step.role === '기안') return 'draft';
    if (currentStep) {
      if (step.step_order !== undefined && step.step_order === currentStep.step_order) return 'current';
      const sName = step.approver_name || step.name;
      const cName = currentStep.approver_name || currentStep.name;
      if (sName && sName === cName && step.role === currentStep.role) return 'current';
    }
    return 'waiting';
  }

  function avatar(name, size) {
    const initial = esc((name || '?').charAt(0));
    const sz = size === 'sm' ? 'apv-avatar--sm' : '';
    return `<span class="apv-avatar ${sz}">${initial}</span>`;
  }

  function arrow(dir) {
    const icon = dir === 'down' ? 'chevron-down' : 'chevron-right';
    return `<span class="apv-arrow"><i data-lucide="${icon}" class="w-4 h-4"></i></span>`;
  }

  /* ── Mode 1: Compact Inline ── */
  function renderCompact(steps, opts) {
    opts = opts || {};
    if (!steps || !steps.length) return '<span class="text-sm text-slate-500">-</span>';

    const currentStep = steps.find(s => (s.action || s.status) === '대기');
    return '<span class="apv-compact">' + steps.map((s, i) => {
      const role = s.role || '결재';
      const status = resolveStatus(s, currentStep);
      const showRole = opts.showRole !== false;
      return (i > 0 ? '<span class="apv-compact__sep">→</span>' : '') +
        `<span class="apv-compact__step apv-compact__step--${status}">` +
          statusIcon(status, i + 1) +
          `<span class="apv-compact__name">${esc(s.approver_name || s.name || '-')}</span>` +
          (showRole ? `<span class="apv-compact__role">(${esc(role)})</span>` : '') +
        '</span>';
    }).join('') + '</span>';
  }

  /* ── Mode 2: Horizontal Card Flow ── */
  function renderFlow(steps, opts) {
    opts = opts || {};
    if (!steps || !steps.length) {
      return '<div class="apv-flow"><div class="text-sm text-slate-500 py-6">결재선 정보가 없습니다.</div></div>';
    }

    const currentStep = steps.find(s => (s.action || s.status) === '대기');
    let html = '';

    steps.forEach((step, i) => {
      const role = step.role || '결재';
      const name = step.approver_name || step.name || '-';
      const dept = step.approver_dept || step.dept || '';
      const status = resolveStatus(step, currentStep);
      const rawDate = step.action_date || step.date || '';
      const dateText = rawDate ? rawDate.substring(0, 10).replace(/-/g, '.') : '';
      const actionText = status === 'draft' ? '기안'
        : status === 'done' ? dateText
        : status === 'rejected' ? '반려 ' + dateText
        : status === 'skipped' ? '전결 처리'
        : status === 'current' ? '결재 대기'
        : '대기';

      if (i > 0) html += arrow('right');

      html += `<div class="apv-flow__card apv-flow__card--${status}">`;
      html += avatar(name, 'sm');
      html += '<div class="apv-flow__info">';
      html += `<span class="apv-flow__name">${esc(name)}${dept ? ' <span class="apv-flow__dept">' + esc(dept) + '</span>' : ''}</span>`;
      const delegateLabel = step.delegate_name ? `<span class="apv-flow__delegate">대결: ${esc(step.delegate_name)}</span>` : '';
      html += `<div class="apv-flow__meta">${roleBadge(role)}<span class="apv-flow__action">${actionText}</span>${delegateLabel}</div>`;
      html += '</div>';

      if (opts.editable) {
        const rmFn = opts.onRemove || 'removeApprover';
        const roleFn = opts.onRoleChange || 'changeRole';
        html += `<button type="button" class="apv-flow__remove" onclick="${esc(rmFn)}(${i})" title="제거">` +
          '<i data-lucide="x-circle" class="w-4 h-4"></i></button>';
        html += `<div class="apv-flow__role-click" onclick="${esc(roleFn)}(${i})" title="클릭하여 역할 변경"></div>`;
      }

      html += '</div>';
    });

    if (opts.references && opts.references.length) {
      html += '<span class="apv-flow__divider"></span>';
      opts.references.forEach(ref => {
        const refName = ref.ref_name || ref.name || '';
        const refDept = ref.ref_dept || ref.dept || '';
        html += `<div class="apv-flow__card apv-flow__card--cc">`;
        html += avatar(refName, 'sm');
        html += '<div class="apv-flow__info">';
        html += `<span class="apv-flow__name">${esc(refName)}${refDept ? ' <span class="apv-flow__dept">' + esc(refDept) + '</span>' : ''}</span>`;
        html += `<div class="apv-flow__meta">${roleBadge('참조')}<span class="apv-flow__action">열람</span></div>`;
        html += '</div>';
        html += '</div>';
      });
    }

    return `<div class="apv-flow">${html}</div>`;
  }

  /* ── Mode 3: Vertical Timeline ── */
  function renderTimeline(steps, opts) {
    opts = opts || {};
    let html = '<div class="apv-timeline">';

    if (opts.showStart !== false) {
      html += `
        <div class="apv-timeline__node apv-timeline__start">
          <span class="apv-timeline__dot apv-timeline__dot--start">
            <i data-lucide="pen-line" class="w-3.5 h-3.5"></i>
          </span>
          <div class="apv-timeline__content">
            <div class="text-sm font-semibold text-slate-300">기안 · 문서 작성자</div>
            <div class="text-[11px] text-slate-500">문서를 작성·상신하면 아래 결재자에게 순서대로 넘어가요</div>
          </div>
        </div>`;
    }

    if (!steps || !steps.length) {
      html += `
        <div class="apv-timeline__node apv-timeline__empty">
          <span class="apv-timeline__dot apv-timeline__dot--empty"></span>
          <div class="apv-timeline__content apv-timeline__drop-zone" id="routeDropZone">
            <i data-lucide="user-plus" class="w-7 h-7 text-slate-700 mb-1.5"></i>
            <p class="text-sm font-medium text-slate-400">결재자를 여기로 드래그</p>
            <p class="text-[11px] text-slate-600">왼쪽 조직에서 직원 카드를 끌어다 놓으세요</p>
          </div>
        </div>`;
    } else {
      steps.forEach((s, idx) => {
        const isLast = idx === steps.length - 1;
        const role = s.role || (isLast ? '전결' : '결재');
        html += `
        <div class="apv-timeline__node apv-timeline__step" data-step-idx="${idx}">
          <span class="apv-timeline__dot"></span>
          <div class="apv-timeline__content apv-timeline__card">
            <span class="apv-timeline__num">${idx + 1}</span>
            <span class="tl-grip cursor-grab active:cursor-grabbing text-slate-600 flex-shrink-0" title="드래그하여 순서 변경">
              <i data-lucide="grip-vertical" class="w-4 h-4"></i>
            </span>
            ${avatar(s.name)}
            <div class="apv-timeline__info">
              <div class="text-sm font-semibold text-slate-100 truncate">${esc(s.name || '-')}</div>
              <div class="text-[11px] text-slate-500 truncate">${esc(s.position || '')} · ${esc(s.dept || '')}</div>
            </div>
            <select class="apv-timeline__role-select reg-select" onchange="updateStepRole(${idx}, this.value)"
                    title="결재: 순서대로 승인 / 전결: 이 사람이 최종 승인(이후 단계 생략)">
              <option value="결재" ${role === '결재' ? 'selected' : ''}>결재</option>
              <option value="전결" ${role === '전결' ? 'selected' : ''}>전결</option>
            </select>
            <button type="button" onclick="removeRouteStep(${idx})" class="apv-timeline__remove" title="제거">
              <i data-lucide="x" class="w-4 h-4"></i>
            </button>
          </div>
        </div>`;
      });
    }

    if (opts.showEnd !== false && steps && steps.length) {
      html += `
        <div class="apv-timeline__node apv-timeline__end">
          <span class="apv-timeline__dot apv-timeline__dot--end">
            <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
          </span>
          <div class="apv-timeline__content">
            <div class="text-sm font-semibold" style="color:var(--zm-st-success-fg)">결재 완료</div>
            <div class="text-[11px] text-slate-500">모든 결재자가 승인하면 문서가 확정돼요</div>
          </div>
        </div>`;
    }

    html += '</div>';
    return html;
  }

  /* ── Tooltip (for draft/pending hover) ── */
  function renderTooltip(steps, refs) {
    if (!steps || !steps.length) return '<div class="text-sm text-slate-500">결재선 없음</div>';

    const currentStep = steps.find(s => (s.action || s.status) === '대기');
    let html = '<div class="apv-tooltip__list">';

    steps.forEach((s, i) => {
      const status = resolveStatus(s, currentStep);
      const role = s.role || '결재';
      const isCurrent = status === 'current';
      html += `
        <div class="apv-tooltip__step">
          ${statusIcon(status, i + 1)}
          <div class="apv-tooltip__info">
            <span class="apv-tooltip__name">${esc(s.approver_name || s.name || '-')}</span>
            ${roleBadge(role)}
            ${isCurrent ? '<span class="apv-tooltip__current-tag">현재</span>' : ''}
            <div class="apv-tooltip__meta">${esc(s.approver_dept || s.dept || '')} ${esc(s.approver_position || s.position || '')}</div>
          </div>
        </div>`;
    });

    if (refs && refs.length) {
      html += '<div class="apv-tooltip__divider"></div>';
      refs.forEach(r => {
        html += `
          <div class="apv-tooltip__step">
            <span class="apv-icon" style="background:#f3e8ff;color:#7c3aed;font-size:9px;">CC</span>
            <div class="apv-tooltip__info">
              <span class="apv-tooltip__name">${esc(r.ref_name || r.name || '')}</span>
              ${roleBadge('참조')}
            </div>
          </div>`;
      });
    }

    html += '</div>';
    return html;
  }

  /* ── Timeline Pointer Drag — 이벤트 위임, 매 드래그마다 fresh DOM ── */
  function initTimelineDrag(containerId, opts) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const onReorder = opts?.onReorder;
    let ds = null;

    container.addEventListener('pointerdown', e => {
      const grip = e.target.closest('.tl-grip');
      if (!grip) return;
      const node = grip.closest('.apv-timeline__step');
      if (!node) return;

      const nodes = [...container.querySelectorAll('.apv-timeline__step')];
      const idx = nodes.indexOf(node);
      if (idx < 0 || nodes.length < 2) return;

      e.preventDefault();
      const card = node.querySelector('.apv-timeline__card');
      if (!card) return;
      const rect = card.getBoundingClientRect();
      const ox = e.clientX - rect.left, oy = e.clientY - rect.top;
      const origTops = nodes.map(n => n.querySelector('.apv-timeline__card').getBoundingClientRect().top);
      const itemH = nodes.length > 1 ? origTops[1] - origTops[0] : rect.height;

      const ghost = card.cloneNode(true);
      ghost.className = 'apv-timeline-drag-ghost';
      ghost.style.cssText = `position:fixed;left:${rect.left}px;top:${rect.top}px;width:${rect.width}px;height:${rect.height}px;z-index:9999;pointer-events:none;`;
      document.body.appendChild(ghost);
      node.classList.add('tl-dragging');
      container.classList.add('zm-drag-active');

      ds = { fromIdx: idx, gapIdx: idx, origTops, itemH, ghost, node, ox, oy, nodes };

      function onMove(ev) {
        if (!ds) return;
        ghost.style.left = (ev.clientX - ox) + 'px';
        ghost.style.top = (ev.clientY - oy) + 'px';
        let newGap = nodes.length;
        for (let i = 0; i < nodes.length; i++) {
          if (ev.clientY < origTops[i] + itemH / 2) { newGap = i; break; }
        }
        if (newGap === ds.gapIdx) return;
        ds.gapIdx = newGap;
        nodes.forEach((n, i) => {
          if (i === idx) return;
          let shift = 0;
          if (idx < newGap && i > idx && i < newGap) shift = -itemH;
          else if (idx > newGap && i >= newGap && i < idx) shift = itemH;
          n.style.transform = shift ? `translateY(${shift}px)` : '';
        });
      }
      function onUp() {
        document.removeEventListener('pointermove', onMove);
        document.removeEventListener('pointerup', onUp);
        document.removeEventListener('pointercancel', onUp);
        if (!ds) return;
        if (ghost.isConnected) ghost.remove();
        node.classList.remove('tl-dragging');
        container.classList.remove('zm-drag-active');
        nodes.forEach(n => n.style.transform = '');
        const { fromIdx, gapIdx } = ds;
        ds = null;
        if (gapIdx === fromIdx || gapIdx === fromIdx + 1) return;
        const toIdx = gapIdx > fromIdx ? gapIdx - 1 : gapIdx;
        if (onReorder) onReorder(fromIdx, toIdx);
      }
      document.addEventListener('pointermove', onMove);
      document.addEventListener('pointerup', onUp);
      document.addEventListener('pointercancel', onUp);
    });
  }

  /* ── public API ── */
  return {
    esc,
    roleBadge,
    statusIcon,
    resolveStatus,
    avatar,
    renderCompact,
    renderFlow,
    renderTimeline,
    renderTooltip,
    initTimelineDrag,
  };
})();
