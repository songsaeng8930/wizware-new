<?php
/**
 * 노무관리 > 계약서 양식
 * 다중 버전/종류 양식 관리 페이지.
 * - 좌측: 양식 목록 (이름 + 버전 레이블 + 기본 표시)
 * - 우측: 선택된 양식의 본문 (읽기 ↔ 편집 모드)
 * - 편집 모드: 자체 TFEditor (표/목록/제목/정렬/값 필드)
 * - 저장: contract_templates 테이블 CRUD
 */
$pageTitle = '계약서 양식';
$currentPage = 'labor';
require_once __DIR__ . '/../includes/permissions.php';
requireMenuPermission('labor', 'view'); // 접근권한 관리 연동 (admin 항상 통과)
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../config/database.php';

if (!function_exists('esc')) {
    function esc($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

/** 기본 조문 배열을 HTML 로 직렬화 · DB 에 양식이 0건일 때 seed 용 */
function ctplSectionsToHtml(array $sections): string
{
    $html = '';
    foreach ($sections as $idx => $sec) {
        $ch = $idx + 1;
        $html .= '<h2>제' . $ch . '장 ' . esc($sec['title']) . '</h2>' . "\n";
        foreach ($sec['articles'] as $art) {
            $html .= '<h3>제' . (int)$art['n'] . '조 (' . esc($art['t']) . ')</h3>' . "\n";
            foreach (explode("\n", (string)$art['c']) as $line) {
                $line = trim($line);
                if ($line === '') continue;
                $html .= '<p>' . esc($line) . '</p>' . "\n";
            }
        }
    }
    return $html;
}

$templates = [];
$activeTpl = null;

try {
    $pdo = getDBConnection();
    if ($pdo) {
        $templates = $pdo->query("SELECT id, name, version_label, description, body, is_default, updated_by, updated_at
                                  FROM contract_templates
                                  WHERE is_active = 1
                                  ORDER BY is_default DESC, updated_at DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $templates = [];
}

// 양식이 하나도 없으면 기본 양식을 자동 시드
if (empty($templates)) {
    try {
        if ($pdo = getDBConnection()) {
            $defaultSections = require __DIR__ . '/../data/labor_contract_template.php';
            $defaultBody = ctplSectionsToHtml($defaultSections);
            $stmt = $pdo->prepare("INSERT INTO contract_templates (name, version_label, description, body, is_default, is_active)
                                   VALUES (?, ?, ?, ?, 1, 1)");
            $stmt->execute(['정규직 표준 근로계약서', 'v1', '회사 기본 근로계약서 양식', $defaultBody]);
            $templates = $pdo->query("SELECT id, name, version_label, description, body, is_default, updated_by, updated_at
                                      FROM contract_templates WHERE is_active = 1
                                      ORDER BY is_default DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        // 테이블 미생성 · 인메모리 목업으로 폴백
        $defaultSections = require __DIR__ . '/../data/labor_contract_template.php';
        $templates = [[
            'id' => 0,
            'name' => '정규직 표준 근로계약서',
            'version_label' => 'v1',
            'description' => '회사 기본 근로계약서 양식',
            'body' => ctplSectionsToHtml($defaultSections),
            'is_default' => 1,
            'updated_by' => null,
            'updated_at' => null,
        ]];
    }
}

$activeTpl = $templates[0] ?? null;
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6">

    <!-- Page Header · 다른 페이지와 동일한 한 줄 패턴 -->
    <div class="flex items-center gap-3 mb-5">
        <a href="<?= $basePath ?>/pages/labor.php?tab=contract" class="p-1.5 rounded-lg hover:bg-slate-800 transition-colors" title="근로자 계약으로 돌아가기">
            <i data-lucide="arrow-left" class="w-5 h-5 text-gray-400"></i>
        </a>
        <h2 class="text-lg font-bold text-gray-800">계약서 양식</h2>
        <span class="hidden md:inline text-sm text-gray-500">· 여러 종류·버전의 근로계약서 양식을 관리합니다</span>
    </div>

    <!-- 상단 sticky 툴바 -->
    <div class="sticky top-14 z-30 -mx-6 px-6 py-3 bg-white border-b border-gray-200 backdrop-blur">
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 shrink-0">
                <i data-lucide="file-signature" class="w-5 h-5 text-gray-600"></i>
                <h3 id="ctplHeadName" class="text-sm font-bold text-gray-800"></h3>
                <span id="ctplHeadVer" class="text-[11px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-500"></span>
                <span id="ctplHeadDefault" class="hidden text-[11px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 font-semibold">기본</span>
                <span id="ctplHeadUpdated" class="text-[11px] text-gray-400"></span>
            </div>

            <div class="flex-1"></div>

            <div class="flex items-center gap-2 shrink-0" id="ctplToolbarButtons">
                <button id="ctplEditBtn" onclick="ctplEnterEdit()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs text-white bg-primary rounded-lg hover:bg-primary-dark">
                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i> 수정
                </button>
                <button id="ctplSaveBtn" onclick="ctplSaveAll()" class="hidden inline-flex items-center gap-1.5 px-3 py-1.5 text-xs text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">
                    <i data-lucide="save" class="w-3.5 h-3.5"></i> 저장
                </button>
                <button id="ctplCancelBtn" onclick="ctplCancelEdit()" class="hidden inline-flex items-center gap-1.5 px-3 py-1.5 text-xs border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50">취소</button>
                <button id="ctplSetDefaultBtn" onclick="ctplMakeDefault()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50" title="기본 양식으로 지정">
                    <i data-lucide="star" class="w-3.5 h-3.5"></i> 기본으로
                </button>
                <button id="ctplDeleteBtn" onclick="ctplDelete()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs border border-gray-300 text-rose-500 rounded-lg hover:bg-rose-500/10 hover:border-rose-500/40">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> 삭제
                </button>
                <button onclick="window.print()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50">
                    <i data-lucide="printer" class="w-3.5 h-3.5"></i> 인쇄
                </button>
            </div>
        </div>
        <p id="ctplDirtyHint" class="hidden mt-2 text-[11px] text-amber-600">
            <i data-lucide="alert-circle" class="inline w-3 h-3 -mt-0.5"></i>
            수정된 내용이 있습니다. 저장하지 않고 나가면 변경 사항이 사라집니다.
        </p>
    </div>

    <!-- 본문: 좌측 양식 목록 + 우측 문서 -->
    <div class="grid grid-cols-12 gap-6 mt-5">

        <!-- 좌측: 버전/종류 목록 -->
        <aside class="col-span-3 hidden lg:block">
            <div class="sticky top-[7.5rem] bg-white border border-gray-200 rounded-xl p-3 max-h-[calc(100vh-9rem)] overflow-y-auto">
                <div class="flex items-center justify-between px-1 mb-2">
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">양식 종류 · 버전</p>
                    <span id="ctplCount" class="text-[10px] text-gray-400"><?= count($templates) ?>개</span>
                </div>
                <button onclick="ctplNew()" class="w-full mb-2 inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-medium text-gray-600 border border-dashed border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> 새 양식 만들기
                </button>
                <ul id="ctplList" class="space-y-1"></ul>
            </div>
        </aside>

        <!-- 우측: 문서 영역 -->
        <section class="col-span-12 lg:col-span-9 space-y-4">

            <!-- 편집 시 메타 정보 입력 카드 (편집 모드에서만 표시) -->
            <div id="ctplMetaCard" class="hidden bg-white border border-gray-200 rounded-xl p-5">
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="reg-label">양식 이름 <span class="text-amber-500">*</span></label>
                        <input type="text" id="ctplEditName" class="reg-input" placeholder="예: 정규직 표준 근로계약서">
                    </div>
                    <div>
                        <label class="reg-label">버전 레이블</label>
                        <input type="text" id="ctplEditVersion" class="reg-input" placeholder="예: v1, 2026 개정">
                    </div>
                    <div>
                        <label class="reg-label">설명 (선택)</label>
                        <input type="text" id="ctplEditDesc" class="reg-input" placeholder="예: 주 40시간 기준">
                    </div>
                </div>
            </div>

            <!-- 읽기 모드 -->
            <article id="ctplReadView" class="bg-white border border-gray-200 rounded-xl p-6 lg:p-10">
                <div id="ctplDocument"></div>
            </article>

            <!-- 편집 모드 -->
            <div id="ctplEditView" class="hidden">
                <div class="bg-white border border-gray-200 rounded-xl p-4 lg:p-6">
                    <div class="flex items-center gap-2 mb-3 text-sm text-gray-500 flex-wrap">
                        <i data-lucide="edit-3" class="w-4 h-4 text-gray-500"></i>
                        <span>리치 에디터 · 제목·목록·표·정렬·링크를 자유롭게 사용할 수 있습니다.</span>
                        <span class="inline-flex items-center gap-1 ml-auto px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 text-[11px] font-semibold">
                            <span style="font-family:monospace">{&nbsp;}</span> 값 필드 · 계약 작성 시 채울 자리
                        </span>
                    </div>
                    <div id="ctplEditorMount"></div>
                </div>
            </div>
        </section>
    </div>

    </main>
</div>

<!-- 자체 리치 에디터 -->
<link rel="stylesheet" href="<?= $basePath ?>/assets/editor/editor.css">
<script src="<?= $basePath ?>/assets/editor/editor.js"></script>

<script>
const CTPL_API = '<?= $basePath ?>/api/labor_contract_template.php';
let CTPL_LIST = <?= json_encode($templates, JSON_UNESCAPED_UNICODE) ?>;
let CTPL_CURRENT_ID = <?= (int)($activeTpl['id'] ?? 0) ?>;
let __ctplEditor = null;

/* ───────── 좌측 양식 목록 렌더 ───────── */
function ctplRenderList() {
    const ul = document.getElementById('ctplList');
    const cnt = document.getElementById('ctplCount');
    cnt.textContent = CTPL_LIST.length + '개';
    ul.innerHTML = CTPL_LIST.map(t => {
        const isActive = t.id === CTPL_CURRENT_ID;
        const ver = t.version_label || '-';
        const updated = (t.updated_at || '').substring(0, 10).replace(/-/g, '.');
        return `
        <li>
            <button type="button" onclick="ctplSelect(${t.id})"
                    class="ctpl-tpl-item w-full text-left p-3 rounded-lg border transition-colors ${
                        isActive ? 'bg-gray-100 border-gray-300' : 'border-transparent hover:bg-gray-50'
                    }">
                <div class="flex items-center gap-1.5 mb-1">
                    <span class="text-xs font-semibold ${isActive ? 'text-gray-900' : 'text-gray-700'} flex-1 min-w-0 truncate">${esc(t.name)}</span>
                    ${+t.is_default === 1 ? '<span class="text-[9px] px-1.5 py-0.5 rounded bg-gray-200 text-gray-700 font-bold">기본</span>' : ''}
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="text-[10px] text-gray-500 font-mono">${esc(ver)}</span>
                    ${updated ? `<span class="text-[10px] text-gray-400">· ${updated}</span>` : ''}
                </div>
            </button>
        </li>`;
    }).join('');
}

function esc(s) { return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }

/* ───────── 양식 선택 ───────── */
async function ctplSelect(id) {
    if (window.__ctplDirty && !(await AppUI.confirm('수정 중인 내용을 버리고 다른 양식을 여시겠습니까?'))) return;
    const tpl = CTPL_LIST.find(t => t.id === id);
    if (!tpl) return;
    CTPL_CURRENT_ID = id;
    ctplRenderCurrent();
    ctplRenderList();
    // 편집 모드에서 선택하면 읽기 모드로 복귀
    ctplExitEditMode();
}

function ctplRenderCurrent() {
    const tpl = CTPL_LIST.find(t => t.id === CTPL_CURRENT_ID);
    if (!tpl) return;
    document.getElementById('ctplHeadName').textContent = tpl.name;
    document.getElementById('ctplHeadVer').textContent = tpl.version_label || 'v1';
    document.getElementById('ctplHeadDefault').classList.toggle('hidden', +tpl.is_default !== 1);
    const u = (tpl.updated_at || '').substring(0, 16);
    document.getElementById('ctplHeadUpdated').textContent = u ? `· 최종 수정 ${u}${tpl.updated_by ? ' · ' + tpl.updated_by : ''}` : '';
    document.querySelector('#ctplReadView > div').innerHTML = tpl.body || '<p class="text-slate-500">본문이 비어 있습니다.</p>';

    // 기본 양식 / 삭제 버튼 가시성 조정
    document.getElementById('ctplSetDefaultBtn').classList.toggle('hidden', +tpl.is_default === 1);
    document.getElementById('ctplDeleteBtn').classList.toggle('hidden', +tpl.is_default === 1);
}

/* ───────── 새 양식 만들기 ───────── */
function ctplNew() {
    const tpl = {
        id: 0,
        name: '새 근로계약서 양식',
        version_label: 'v1',
        description: '',
        body: '<h2>제1장 (장 제목)</h2>\n<h3>제1조 (조문 제목)</h3>\n<p>본문을 입력하세요.</p>',
        is_default: 0,
        updated_at: null,
    };
    CTPL_LIST.unshift(tpl);
    CTPL_CURRENT_ID = 0;
    ctplRenderList();
    ctplRenderCurrent();
    ctplEnterEdit();
}

/* ───────── 편집 모드 진입 ───────── */
function ctplEnterEdit() {
    const tpl = CTPL_LIST.find(t => t.id === CTPL_CURRENT_ID);
    if (!tpl) return;

    document.getElementById('ctplMetaCard').classList.remove('hidden');
    document.getElementById('ctplReadView').classList.add('hidden');
    document.getElementById('ctplEditView').classList.remove('hidden');
    document.getElementById('ctplEditName').value = tpl.name || '';
    document.getElementById('ctplEditVersion').value = tpl.version_label || '';
    document.getElementById('ctplEditDesc').value = tpl.description || '';

    ctplToggleEditButtons(true);

    if (!__ctplEditor) {
        if (typeof TFEditor === 'undefined') {
            alert('에디터를 불러오지 못했습니다.');
            ctplCancelEdit();
            return;
        }
        __ctplEditor = new TFEditor({
            container: '#ctplEditorMount',
            initialContent: tpl.body || '',
            placeholder: '계약서 본문을 입력하세요...',
            showHint: false,
            showVariableButton: true,
            onChange: () => { window.__ctplDirty = true; }
        });
    } else {
        __ctplEditor.setContent(tpl.body || '');
    }

    document.getElementById('ctplDirtyHint').classList.remove('hidden');
    window.__ctplDirty = false;
}

function ctplToggleEditButtons(editing) {
    document.getElementById('ctplEditBtn').classList.toggle('hidden', editing);
    document.getElementById('ctplSaveBtn').classList.toggle('hidden', !editing);
    document.getElementById('ctplCancelBtn').classList.toggle('hidden', !editing);
    document.getElementById('ctplSetDefaultBtn').classList.toggle('hidden', editing);
    document.getElementById('ctplDeleteBtn').classList.toggle('hidden', editing);
}

function ctplExitEditMode() {
    document.getElementById('ctplMetaCard').classList.add('hidden');
    document.getElementById('ctplReadView').classList.remove('hidden');
    document.getElementById('ctplEditView').classList.add('hidden');
    document.getElementById('ctplDirtyHint').classList.add('hidden');
    ctplToggleEditButtons(false);
    window.__ctplDirty = false;
    // 기본/삭제 버튼은 현재 선택된 양식에 따라 다시 가시성 조정
    ctplRenderCurrent();
}

async function ctplCancelEdit() {
    if (window.__ctplDirty && !(await AppUI.confirm('수정 중인 내용을 버리시겠습니까?'))) return;
    // 신규 양식(id=0)이었으면 목록에서 제거
    if (CTPL_CURRENT_ID === 0) {
        CTPL_LIST = CTPL_LIST.filter(t => t.id !== 0);
        CTPL_CURRENT_ID = CTPL_LIST[0]?.id || 0;
        ctplRenderList();
        ctplRenderCurrent();
    }
    ctplExitEditMode();
}

/* ───────── 저장 ───────── */
async function ctplSaveAll() {
    if (!__ctplEditor) return;
    const name = document.getElementById('ctplEditName').value.trim();
    if (!name) { alert('양식 이름을 입력해주세요.'); return; }
    const body = __ctplEditor.getContent();
    if (!body || !body.replace(/<[^>]+>/g, '').trim()) { alert('본문을 입력해주세요.'); return; }

    const btn = document.getElementById('ctplSaveBtn');
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i> 저장 중...';
    if (window.lucide) lucide.createIcons();

    try {
        const payload = {
            id: CTPL_CURRENT_ID || 0,
            name,
            version_label: document.getElementById('ctplEditVersion').value.trim(),
            description: document.getElementById('ctplEditDesc').value.trim(),
            body,
        };
        const res = await fetch(CTPL_API + '?action=save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (!data.success) { alert('저장 실패: ' + (data.error || '')); return; }

        // 목록 갱신
        const saved = data.template;
        const idx = CTPL_LIST.findIndex(t => t.id === CTPL_CURRENT_ID || (CTPL_CURRENT_ID === 0 && t.id === 0));
        if (idx >= 0) CTPL_LIST.splice(idx, 1, saved);
        else CTPL_LIST.unshift(saved);
        CTPL_CURRENT_ID = saved.id;
        ctplRenderList();
        ctplRenderCurrent();
        ctplExitEditMode();

        // 토스트
        const t = document.createElement('div');
        t.className = 'fixed bottom-6 left-1/2 -translate-x-1/2 px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm shadow-lg z-[60]';
        t.textContent = '양식이 저장되었습니다.';
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 2500);
    } catch (e) {
        alert('저장 중 오류가 발생했습니다.');
        console.error(e);
    } finally {
        btn.disabled = false;
        btn.innerHTML = origHtml;
        if (window.lucide) lucide.createIcons();
    }
}

/* ───────── 기본 양식 지정 ───────── */
async function ctplMakeDefault() {
    const tpl = CTPL_LIST.find(t => t.id === CTPL_CURRENT_ID);
    if (!tpl || !tpl.id) return;
    if (!(await AppUI.confirm(`"${tpl.name}" 을(를) 기본 양식으로 지정할까요?`))) return;

    const res = await fetch(CTPL_API + '?action=setDefault', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: tpl.id }),
    });
    const data = await res.json();
    if (!data.success) { alert('지정 실패: ' + (data.error || '')); return; }
    CTPL_LIST.forEach(t => { t.is_default = (t.id === tpl.id) ? 1 : 0; });
    ctplRenderList();
    ctplRenderCurrent();
}

/* ───────── 삭제 ───────── */
async function ctplDelete() {
    const tpl = CTPL_LIST.find(t => t.id === CTPL_CURRENT_ID);
    if (!tpl || !tpl.id) return;
    if (+tpl.is_default === 1) { alert('기본 양식은 삭제할 수 없습니다.'); return; }
    if (!(await AppUI.confirm(`"${tpl.name}" 양식을 삭제하시겠습니까?\n(이미 계약서에 사용 중이면 목록에서만 숨깁니다)`))) return;

    const res = await fetch(CTPL_API + '?action=delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: tpl.id }),
    });
    const data = await res.json();
    if (!data.success) { alert('삭제 실패: ' + (data.error || '')); return; }
    CTPL_LIST = CTPL_LIST.filter(t => t.id !== tpl.id);
    CTPL_CURRENT_ID = CTPL_LIST[0]?.id || 0;
    ctplRenderList();
    ctplRenderCurrent();
}

/* 새로고침 경고 */
window.addEventListener('beforeunload', (e) => {
    if (window.__ctplDirty) { e.preventDefault(); e.returnValue = ''; }
});

document.addEventListener('DOMContentLoaded', () => {
    ctplRenderList();
    ctplRenderCurrent();
});
</script>

<style>
/* 문서 뷰 타이포그래피 */
#ctplReadView {
    color: var(--zm-text-default);
    font-size: 15px;
    line-height: 1.85;
}
#ctplReadView h2 {
    font-size: 18px;
    font-weight: 700;
    color: var(--zm-text-strong);
    padding-bottom: 10px;
    border-bottom: 1px solid var(--zm-border);
    margin: 2.25rem 0 1rem;
}
#ctplReadView h2:first-child { margin-top: 0; }
#ctplReadView h3 {
    font-size: 15px;
    font-weight: 600;
    color: var(--zm-text-strong);
    margin: 1.25rem 0 0.4rem;
}
#ctplReadView p {
    margin: 0.3rem 0 0.6rem;
    white-space: pre-line;
}
#ctplReadView ul, #ctplReadView ol { padding-left: 1.4em; margin: 0.4rem 0 0.8rem; }
#ctplReadView li { margin: 0.15rem 0; }
#ctplReadView strong { color: var(--zm-text-strong); }
#ctplReadView a { color: var(--zm-primary-fg); text-decoration: underline; }
#ctplReadView table {
    border-collapse: collapse;
    width: 100%;
    margin: 1rem 0;
    font-size: 14px;
}
#ctplReadView th, #ctplReadView td {
    border: 1px solid var(--zm-border);
    padding: 0.5rem 0.75rem;
    vertical-align: top;
}
#ctplReadView th { background: var(--zm-surface-2); color: var(--zm-text-strong); font-weight: 600; text-align: left; }
#ctplReadView td { color: var(--zm-text-default); }

/* 값 필드 */
#ctplReadView .tf-var {
    display: inline-block;
    padding: 1px 8px;
    margin: 0 1px;
    background: rgba(0, 0, 0, 0.04);
    border: 1px dashed rgba(0, 0, 0, 0.12);
    border-radius: 4px;
    color: #6366f1;
}
html[data-theme="dark"] #ctplReadView .tf-var {
    color: #a5b4fc;
}
#ctplReadView .tf-var::before {
    content: attr(data-var-name);
    display: inline-block;
    font-size: 10px;
    color: var(--zm-text-strong);
    background: rgba(0, 0, 0, 0.04);
    padding: 0 5px;
    border-radius: 2px;
    margin-right: 6px;
    font-weight: 600;
}

@media print {
    #sidebar, #header, .sticky, aside, #ctplEditView, #ctplMetaCard,
    #ctplEditBtn, #ctplSaveBtn, #ctplCancelBtn, #ctplSetDefaultBtn, #ctplDeleteBtn,
    [onclick*="print"], nav { display: none !important; }
    #mainContent { margin-left: 0 !important; margin-top: 0 !important; }
    body { background: white !important; }
    #ctplReadView { background: white !important; color: black !important; border: none !important; padding: 0 !important; }
    #ctplReadView * { color: black !important; background: white !important; }
    #ctplReadView h2 { border-bottom: 1px solid #333 !important; }
    #ctplReadView th, #ctplReadView td { border: 1px solid #666 !important; }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
