/**
 * labor-rules.js — 취업규칙 리치 에디터 (단일 문서 UPSERT)
 * LABOR_DATA.basePath → API 경로 접두사
 */

const RULES_API = LABOR_DATA.basePath + '/api/labor_rules.php';
let __rulesEditor = null;
let __rulesOriginalBody = '';
let __rulesCurrentBody = '';

/* 초기 로딩 */
async function rulesLoad() {
    const docEl = document.getElementById('rulesDocument');
    if (!docEl) return;
    try {
        const res = await fetch(RULES_API + '?action=loadDoc');
        const data = await res.json();
        if (!data || !data.success) {
            docEl.innerHTML = '<p class="text-rose-400 text-sm">불러오기 실패: ' + ((data && data.error) || '') + '</p>';
            return;
        }
        __rulesCurrentBody = data.body || '';
        rulesRenderRead();
        rulesUpdateMeta(data.updated_at, data.updated_by, data.seeded);
    } catch (e) {
        docEl.innerHTML = '<p class="text-rose-400 text-sm">서버 연결 실패</p>';
    }
}

function rulesRenderRead() {
    const docEl = document.getElementById('rulesDocument');
    if (!docEl) return;
    docEl.innerHTML = __rulesCurrentBody || '<p class="text-slate-500">본문이 비어 있습니다.</p>';
    rulesBuildToc();
    if (window.lucide) lucide.createIcons();
}

function rulesUpdateMeta(updatedAt, updatedBy, seeded) {
    const el = document.getElementById('rulesHeadUpdated');
    if (!el) return;
    if (seeded) { el.textContent = '· 최초 편집 전 (기본 양식)'; return; }
    if (!updatedAt) { el.textContent = ''; return; }
    const t = String(updatedAt).substring(0, 16);
    el.textContent = '· 최종 수정 ' + t + (updatedBy ? ' · 사용자 #' + updatedBy : '');
}

/* 본문의 <h2> 를 이용해 목차를 동적으로 재생성 */
function rulesBuildToc() {
    const toc = document.getElementById('rulesToc');
    const cnt = document.getElementById('rulesTocCount');
    if (!toc) return;
    const docEl = document.getElementById('rulesDocument');
    const headers = [...docEl.querySelectorAll('h2')];
    cnt.textContent = headers.length + '장';
    if (!headers.length) { toc.innerHTML = '<li class="text-[11px] text-gray-400 px-2 py-1">장 제목(H2)이 없습니다.</li>'; return; }
    toc.innerHTML = headers.map((h, i) => {
        const id = 'rules-sec-' + i;
        h.id = id;
        const t = h.textContent || '제' + (i+1) + '장';
        return '<li>' +
            '<a href="#' + id + '" class="rule-toc-link flex items-start gap-2 rounded-md px-2 py-1.5 text-gray-600 hover:bg-gray-100 transition-colors" data-target="' + id + '">' +
                '<span class="inline-flex items-center justify-center w-5 h-5 rounded bg-gray-100 text-gray-500 text-[10px] font-semibold tabular-nums flex-shrink-0 mt-0.5">' + (i+1) + '</span>' +
                '<span class="flex-1 min-w-0"><span class="block text-xs font-medium truncate">' + escHtml(t) + '</span></span>' +
            '</a></li>';
    }).join('');

    // 클릭 → 부드러운 스크롤
    toc.querySelectorAll('.rule-toc-link').forEach(a => {
        a.addEventListener('click', (e) => {
            const el = document.getElementById(a.getAttribute('data-target'));
            if (!el) return;
            e.preventDefault();
            const off = 112;
            window.scrollTo({ top: el.getBoundingClientRect().top + window.pageYOffset - off, behavior: 'smooth' });
            history.replaceState(null, '', '#' + a.getAttribute('data-target'));
        });
    });
}

/* 인쇄 */
function printRules() {
    var dateEl = document.getElementById('rulesPrintDate');
    if (dateEl) {
        var d = new Date();
        var y = d.getFullYear(), m = d.getMonth() + 1, day = d.getDate();
        dateEl.textContent = y + '년 ' + m + '월 ' + day + '일 기준';
    }
    window.print();
}

/* 편집 모드 진입 */
function rulesEnterEdit() {
    const readV = document.getElementById('rulesReadView');
    const editV = document.getElementById('rulesEditView');
    if (!readV || !editV) return;

    __rulesOriginalBody = __rulesCurrentBody;
    readV.classList.add('hidden');
    editV.classList.remove('hidden');
    rulesToggleEditButtons(true);
    document.getElementById('rulesDirtyHint').classList.remove('hidden');

    if (!__rulesEditor) {
        if (typeof TFEditor === 'undefined') {
            alert('에디터를 불러오지 못했습니다.');
            rulesCancelEdit();
            return;
        }
        __rulesEditor = new TFEditor({
            container: '#rulesEditorMount',
            initialContent: __rulesCurrentBody,
            placeholder: '취업규칙 본문을 입력하세요...',
            showHint: false,
            onChange: () => { window.__rulesDirty = true; }
        });
    } else {
        __rulesEditor.setContent(__rulesCurrentBody);
    }
    window.__rulesDirty = false;
}

function rulesToggleEditButtons(editing) {
    document.getElementById('rulesEditBtn').classList.toggle('hidden', editing);
    document.getElementById('rulesSaveBtn').classList.toggle('hidden', !editing);
    document.getElementById('rulesCancelBtn').classList.toggle('hidden', !editing);
    const sw = document.getElementById('rulesSearchWrap');
    sw.classList.toggle('invisible', editing);
    sw.classList.toggle('pointer-events-none', editing);
    const tocAside = document.getElementById('rulesTocAside');
    if (tocAside) tocAside.classList.toggle('lg:hidden', editing);
    const contentSec = document.getElementById('rulesContentSection');
    if (contentSec) contentSec.classList.toggle('lg:col-span-9', !editing);
}

/* 편집 취소 */
async function rulesCancelEdit() {
    if (window.__rulesDirty && !(await AppUI.confirm('수정 중인 내용을 버리시겠습니까?'))) return;
    document.getElementById('rulesReadView').classList.remove('hidden');
    document.getElementById('rulesEditView').classList.add('hidden');
    rulesToggleEditButtons(false);
    document.getElementById('rulesDirtyHint').classList.add('hidden');
    __rulesCurrentBody = __rulesOriginalBody;
    rulesRenderRead();
    window.__rulesDirty = false;
}

/* 전체 저장 */
async function rulesSaveAll() {
    if (!__rulesEditor) return;
    const body = __rulesEditor.getContent();
    if (!body || !body.replace(/<[^>]+>/g, '').trim()) { alert('본문을 입력해주세요.'); return; }

    const btn = document.getElementById('rulesSaveBtn');
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i> 저장 중...';
    if (window.lucide) lucide.createIcons();

    try {
        const res = await fetch(RULES_API + '?action=saveDoc', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ body }),
        });
        const data = await res.json();
        if (!data || !data.success) {
            alert('저장 실패: ' + ((data && data.error) || ''));
            return;
        }
        __rulesCurrentBody = body;
        rulesUpdateMeta(data.updated_at, data.updated_by, false);

        document.getElementById('rulesReadView').classList.remove('hidden');
        document.getElementById('rulesEditView').classList.add('hidden');
        rulesToggleEditButtons(false);
        document.getElementById('rulesDirtyHint').classList.add('hidden');
        rulesRenderRead();
        window.__rulesDirty = false;

        const t = document.createElement('div');
        t.className = 'fixed bottom-6 left-1/2 -translate-x-1/2 px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm shadow-lg z-[60]';
        t.textContent = '취업규칙이 저장되었습니다.';
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 2500);
    } catch (e) {
        alert('저장 중 오류가 발생했습니다.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = origHtml;
        if (window.lucide) lucide.createIcons();
    }
}

/* 새로고침 전 경고 */
window.addEventListener('beforeunload', (e) => {
    if (window.__rulesDirty) { e.preventDefault(); e.returnValue = ''; }
});

/* 본문 내 텍스트 검색 */
function clearRuleSearch() {
    const input = document.getElementById('ruleSearch');
    if (!input) return;
    input.value = '';
    document.getElementById('ruleSearchClear').classList.add('hidden');
    doRuleSearch('');
}
function doRuleSearch(q) {
    const docEl = document.getElementById('rulesDocument');
    if (!docEl) return;
    const lower = q.trim().toLowerCase();

    const kids = Array.from(docEl.children);
    kids.forEach(el => { el.style.display = ''; });
    if (!lower) return;

    let groups = [];
    let current = null;
    kids.forEach(el => {
        if (el.tagName === 'H2') { current = { h2: el, rest: [] }; groups.push(current); }
        else if (current) current.rest.push(el);
    });

    groups.forEach(g => {
        const textAll = (g.h2.textContent + ' ' + g.rest.map(x => x.textContent).join(' ')).toLowerCase();
        const hit = textAll.includes(lower);
        g.h2.style.display = hit ? '' : 'none';
        g.rest.forEach(el => { el.style.display = hit ? '' : 'none'; });
    });
}

// 검색 input DOMContentLoaded 바인딩
document.addEventListener('DOMContentLoaded', () => {
    const ruleSearchInput = document.getElementById('ruleSearch');
    if (ruleSearchInput) {
        ruleSearchInput.addEventListener('input', () => {
            const v = ruleSearchInput.value;
            document.getElementById('ruleSearchClear').classList.toggle('hidden', !v);
            doRuleSearch(v);
        });
    }
    if (document.getElementById('rulesDocument')) rulesLoad();
});
