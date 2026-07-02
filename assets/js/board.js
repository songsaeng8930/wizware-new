/* ================================================================
   사내게시판 (board.js)
   글로벌 변수 BOARD_TYPE, API_URL, HAS_DB, CURRENT_USER,
   EMPTY_MSG, CAT_COLORS, NEW_THRESHOLD, IS_DEPT_BOARD,
   IS_MANAGER, COL_SPAN 은 pages/board.php 에서 인라인 선언
   ================================================================ */

let currentCat  = '전체';
let currentPage = 1;
let perPage     = 10;
let totalCount  = 0;
let currentKeyword = '';
let currentField   = 'title';
let currentDetailPost = null;
let deptFilterId = window.__BOARD_DEPT_FILTER_ID || 0;

// 글쓰기 모달에서 선택한 첨부파일 (File 객체 배열)
let pendingFiles = [];
const MAX_FILES = 5;
const MAX_FILE_SIZE = 10 * 1024 * 1024;
const ALLOWED_EXTS = ['pdf','doc','docx','hwp','hwpx','xls','xlsx','ppt','pptx','txt','zip','jpg','jpeg','png','gif'];

// ========== 초기화 ==========
document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('deptFilter');
    if (sel) deptFilterId = parseInt(sel.value, 10) || 0;
    loadPosts();
});

// ========== XSS 방지 ==========
function esc(str) {
    if (str == null) return '';
    const d = document.createElement('div');
    d.textContent = String(str);
    return d.innerHTML;
}

// ========== 파일 크기 포맷 ==========
function formatFileSize(bytes) {
    if (!bytes || bytes <= 0) return '0B';
    if (bytes < 1024) return bytes + 'B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + 'KB';
    return (bytes / (1024 * 1024)).toFixed(1) + 'MB';
}

// ========== 부서 필터 변경 (관리자용) ==========
function changeDeptFilter() {
    const sel = document.getElementById('deptFilter');
    deptFilterId = parseInt(sel.value, 10) || 0;
    currentPage = 1;
    loadPosts();
}

// ========== 목록 로드 ==========
function loadPosts() {
    const params = new URLSearchParams({
        action: 'getPosts',
        type: BOARD_TYPE,
        category: currentCat,
        keyword: currentKeyword,
        field: currentField,
        page: currentPage,
        perPage: perPage,
    });

    if (IS_DEPT_BOARD && deptFilterId > 0) {
        params.set('department_id', deptFilterId);
    }

    fetch(`${API_URL}?${params}`)
        .then(r => r.json())
        .then(data => {
            const posts = data.posts || [];
            totalCount = data.total ?? posts.length;

            document.getElementById('totalBadge').textContent = totalCount;
            document.getElementById('infoTotal').textContent  = totalCount;

            renderPosts(posts);
            renderPagination();

            if (data.error) showToast(data.error, true);
        })
        .catch(err => {
            console.error('게시글 로드 실패:', err);
            document.getElementById('postBody').innerHTML =
                `<tr><td colspan="${COL_SPAN}" class="py-20 text-center text-slate-500">데이터를 불러올 수 없습니다.</td></tr>`;
        });
}

// ========== 렌더링 ==========
function renderPosts(posts) {
    const tbody = document.getElementById('postBody');

    if (!posts.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="${COL_SPAN}" class="py-20 text-center">
                    <div class="flex flex-col items-center gap-3">
                        <div class="w-14 h-14 rounded-full bg-slate-800 flex items-center justify-center">
                            <i data-lucide="inbox" class="w-7 h-7 text-slate-500"></i>
                        </div>
                        <div>
                            <p class="text-slate-400 font-medium">${esc(EMPTY_MSG)}</p>
                            <p class="text-slate-500 text-sm mt-1">첫 번째 글을 작성해보세요</p>
                        </div>
                    </div>
                </td>
            </tr>`;
        if (typeof lucide !== 'undefined') lucide.createIcons();
        return;
    }

    let html = '';
    posts.forEach(p => {
        const isPinned = Number(p.is_pinned) === 1;
        const rowClass = isPinned
            ? 'bg-primary-light/40 hover:bg-gray-100 cursor-pointer border-b border-primary-light transition-colors'
            : 'hover:bg-slate-950 cursor-pointer border-b border-slate-800 transition-colors';

        const catClass = CAT_COLORS[p.category] || 'bg-slate-800 text-slate-300';
        const dateStr  = p.created_at ? p.created_at.substring(5, 10).replace('-', '.') : '';
        const dateOnly = p.created_at ? p.created_at.substring(0, 10) : '';
        const isNew    = dateOnly >= NEW_THRESHOLD;
        const cmtCount = Number(p.comment_count || 0);
        const attCount = Number(p.attachment_count || 0);

        html += `<tr class="${rowClass}" onclick="openDetailModal(${p.id})">`;
        html += `<td class="py-3.5 px-4 text-center">`;
        if (isPinned) {
            html += `<i data-lucide="pin" class="w-4 h-4 text-primary inline-block"></i>`;
        } else {
            html += `<span class="text-slate-400">${esc(p.id)}</span>`;
        }
        html += `</td>`;

        if (!IS_DEPT_BOARD) {
            html += `<td class="py-3.5 px-4 text-center"><span class="inline-block px-2.5 py-0.5 text-sm font-semibold rounded-full ${catClass}">${esc(p.category)}</span></td>`;
        }

        html += `<td class="py-3.5 px-4"><div class="flex items-center gap-2">`;
        html += `<span class="${isPinned ? 'text-slate-100 font-medium' : 'text-slate-100'} ">${esc(p.title)}</span>`;
        if (attCount > 0) {
            html += `<i data-lucide="paperclip" class="w-3.5 h-3.5 text-slate-500 flex-shrink-0"></i>`;
        }
        if (cmtCount > 0) {
            html += `<span class="text-primary text-sm font-semibold flex-shrink-0">[${cmtCount}]</span>`;
        }
        if (isNew) {
            html += `<span class="inline-block px-1.5 py-0.5 text-sm font-bold bg-amber-500 text-white rounded leading-none">N</span>`;
        }
        html += `</div></td>`;
        html += `<td class="py-3.5 px-4 text-center text-slate-300">${esc(p.author_name)}</td>`;
        html += `<td class="py-3.5 px-4 text-center text-slate-400">${esc(dateStr)}</td>`;
        html += `<td class="py-3.5 px-4 text-center text-slate-400">${Number(p.views || 0).toLocaleString()}</td>`;
        html += `</tr>`;
    });
    tbody.innerHTML = html;
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

// ========== 페이지네이션 ==========
function renderPagination() {
    const container = document.getElementById('pagination');
    const totalPages = Math.max(1, Math.ceil(totalCount / perPage));

    if (totalCount === 0) {
        container.innerHTML = '';
        return;
    }

    let html = '';
    html += `<button class="pg-btn ${currentPage <= 1 ? 'pg-disabled' : ''}" ${currentPage > 1 ? `onclick="goPage(1)"` : ''}><i data-lucide="chevrons-left" class="w-4 h-4"></i></button>`;
    html += `<button class="pg-btn ${currentPage <= 1 ? 'pg-disabled' : ''}" ${currentPage > 1 ? `onclick="goPage(${currentPage - 1})"` : ''}><i data-lucide="chevron-left" class="w-4 h-4"></i></button>`;

    let start = Math.max(1, currentPage - 2);
    let end   = Math.min(totalPages, start + 4);
    if (end - start < 4) start = Math.max(1, end - 4);

    for (let i = start; i <= end; i++) {
        html += `<button class="pg-btn ${i === currentPage ? 'pg-active' : ''}" onclick="goPage(${i})">${i}</button>`;
    }

    html += `<button class="pg-btn ${currentPage >= totalPages ? 'pg-disabled' : ''}" ${currentPage < totalPages ? `onclick="goPage(${currentPage + 1})"` : ''}><i data-lucide="chevron-right" class="w-4 h-4"></i></button>`;
    html += `<button class="pg-btn ${currentPage >= totalPages ? 'pg-disabled' : ''}" ${currentPage < totalPages ? `onclick="goPage(${totalPages})"` : ''}><i data-lucide="chevrons-right" class="w-4 h-4"></i></button>`;

    container.innerHTML = html;
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function goPage(p) {
    currentPage = p;
    loadPosts();
}

function changePerPage() {
    perPage = parseInt(document.getElementById('perPageSelect').value, 10);
    currentPage = 1;
    loadPosts();
}

// ========== 카테고리 필터 ==========
function filterCategory(btn, cat) {
    document.querySelectorAll('.cat-chip').forEach(c => {
        c.classList.toggle('zm-tab-active', c === btn);
    });

    currentCat  = cat;
    currentPage = 1;
    loadPosts();
}

// ========== 검색 ==========
function searchPosts() {
    currentKeyword = document.getElementById('searchInput').value.trim();
    currentField   = document.getElementById('searchField').value;
    currentPage = 1;
    loadPosts();
}

// ========== 글쓰기 모달 ==========
function openCreateModal() {
    document.getElementById('createTitle').value   = '';
    document.getElementById('createContent').value = '';
    const catSel = document.getElementById('createCat');
    if (catSel) catSel.selectedIndex = 0;
    const pinnedChk = document.getElementById('createPinned');
    if (pinnedChk) pinnedChk.checked = false;
    pendingFiles = [];
    renderPendingFiles();
    document.getElementById('createModal').classList.remove('hidden');
}

function addCreateFile() {
    if (pendingFiles.length >= MAX_FILES) {
        showToast(`첨부파일은 최대 ${MAX_FILES}개까지 가능합니다.`, true);
        return;
    }
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = ALLOWED_EXTS.map(e => '.' + e).join(',');
    input.onchange = () => {
        if (!input.files[0]) return;
        const file = input.files[0];
        if (file.size > MAX_FILE_SIZE) {
            showToast('파일 크기는 10MB를 초과할 수 없습니다.', true);
            return;
        }
        const ext = file.name.split('.').pop().toLowerCase();
        if (!ALLOWED_EXTS.includes(ext)) {
            showToast('허용되지 않는 파일 형식입니다.', true);
            return;
        }
        if (pendingFiles.length >= MAX_FILES) {
            showToast(`첨부파일은 최대 ${MAX_FILES}개까지 가능합니다.`, true);
            return;
        }
        pendingFiles.push(file);
        renderPendingFiles();
    };
    input.click();
}

function removePendingFile(idx) {
    pendingFiles.splice(idx, 1);
    renderPendingFiles();
}

function renderPendingFiles() {
    const container = document.getElementById('createFileList');
    if (!container) return;
    if (pendingFiles.length === 0) {
        container.innerHTML = '<p class="text-sm text-slate-500">선택된 파일이 없습니다</p>';
        return;
    }
    let html = '';
    pendingFiles.forEach((f, i) => {
        html += `<div class="flex items-center gap-2 text-sm text-slate-300">
            <i data-lucide="file" class="w-4 h-4 text-slate-500 flex-shrink-0"></i>
            <span class="truncate flex-1">${esc(f.name)}</span>
            <span class="text-slate-500 flex-shrink-0">${formatFileSize(f.size)}</span>
            <button onclick="removePendingFile(${i})" class="text-slate-500 hover:text-red-400 flex-shrink-0"><i data-lucide="x" class="w-4 h-4 pointer-events-none"></i></button>
        </div>`;
    });
    container.innerHTML = html;
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

async function uploadFilesToPost(postId) {
    let uploaded = 0;
    for (const file of pendingFiles) {
        const formData = new FormData();
        formData.append('post_id', postId);
        formData.append('file', file);
        try {
            const res = await fetch(API_URL + '?action=uploadAttachment', {
                method: 'POST',
                body: formData,
            });
            const json = await res.json();
            if (json.ok !== false && !json.error) uploaded++;
        } catch (e) {
            console.error('첨부파일 업로드 실패:', file.name, e);
        }
    }
    return uploaded;
}

async function submitCreate() {
    const title   = document.getElementById('createTitle').value.trim();
    const content = document.getElementById('createContent').value.trim();
    const catSel  = document.getElementById('createCat');
    const cat     = catSel ? catSel.value : '';
    const pinnedChk = document.getElementById('createPinned');
    const pinned  = pinnedChk && pinnedChk.checked ? 1 : 0;

    if (!title) { showToast('제목을 입력해주세요.', true); return; }
    if (!content) { showToast('내용을 입력해주세요.', true); return; }

    try {
        const res = await fetch(API_URL + '?action=createPost', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                type: BOARD_TYPE,
                category: cat,
                title: title,
                content: content,
                isPinned: pinned,
            }),
        });
        const data = await res.json();
        if (data.error) { showToast(data.error, true); return; }

        if (pendingFiles.length > 0 && data.id) {
            await uploadFilesToPost(data.id);
        }

        closeModals();
        showToast('게시글이 등록되었습니다.');
        currentPage = 1;
        loadPosts();
    } catch {
        showToast('등록 중 오류가 발생했습니다.', true);
    }
}

// ========== 상세 모달 ==========
function openDetailModal(id) {
    fetch(`${API_URL}?action=getPost&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.error) { showToast(data.error, true); return; }
            const p = data.post;
            currentDetailPost = p;

            const catBadge = document.getElementById('detailCatBadge');
            if (p.category && !IS_DEPT_BOARD) {
                const catClass = CAT_COLORS[p.category] || 'bg-slate-800 text-slate-300';
                catBadge.className = `inline-block px-2.5 py-0.5 text-sm font-semibold rounded-full ${catClass}`;
                catBadge.textContent = p.category;
                catBadge.classList.remove('hidden');
            } else {
                catBadge.classList.add('hidden');
            }

            const pinBadge = document.getElementById('detailPinBadge');
            if (Number(p.is_pinned) === 1) {
                pinBadge.classList.remove('hidden');
            } else {
                pinBadge.classList.add('hidden');
            }

            document.getElementById('detailTitle').textContent   = p.title;
            document.getElementById('detailAuthor').textContent  = p.author_name;
            document.getElementById('detailDept').textContent    = p.author_dept || '';
            document.getElementById('detailDate').textContent    = (p.created_at || '').substring(0, 10);
            document.getElementById('detailViews').textContent   = '조회 ' + Number(p.views || 0).toLocaleString();
            document.getElementById('detailContent').textContent = p.content || '';

            // 수정/삭제 버튼은 can_edit일 때만
            const canEdit = !!p.can_edit;
            document.getElementById('btnEdit').classList.toggle('hidden', !canEdit);
            document.getElementById('btnDelete').classList.toggle('hidden', !canEdit);

            renderDetailAttachments(p.attachments || []);
            loadComments(p.id);

            switchToRead();
            document.getElementById('detailModal').classList.remove('hidden');
            if (typeof lucide !== 'undefined') lucide.createIcons();
        })
        .catch(() => showToast('게시글을 불러올 수 없습니다.', true));
}

// ========== 상세 첨부파일 렌더 ==========
function renderDetailAttachments(attachments) {
    const container = document.getElementById('detailAttachments');
    if (!container) return;

    if (!attachments || attachments.length === 0) {
        container.classList.add('hidden');
        return;
    }

    container.classList.remove('hidden');
    const listEl = container.querySelector('.att-list');
    if (!listEl) return;

    let html = '';
    attachments.forEach(a => {
        html += `<div class="flex items-center gap-2 py-1.5 text-sm">
            <i data-lucide="file" class="w-4 h-4 text-slate-500 flex-shrink-0"></i>
            <span class="text-slate-300 truncate flex-1">${esc(a.original_name)}</span>
            <span class="text-slate-500 flex-shrink-0 text-xs">${formatFileSize(a.file_size)}</span>
            <button onclick="event.stopPropagation(); downloadAttachment(${a.id})" class="text-primary hover:opacity-70 flex-shrink-0" title="다운로드">
                <i data-lucide="download" class="w-4 h-4 pointer-events-none"></i>
            </button>`;
        if (a.can_delete) {
            html += `<button onclick="event.stopPropagation(); deleteAttachment(${a.id})" class="text-slate-500 hover:text-red-400 flex-shrink-0" title="삭제">
                <i data-lucide="x" class="w-4 h-4 pointer-events-none"></i>
            </button>`;
        }
        html += `</div>`;
    });
    listEl.innerHTML = html;
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function downloadAttachment(id) {
    window.open(API_URL + '?action=downloadAttachment&id=' + id, '_blank');
}

async function deleteAttachment(id) {
    if (!(await AppUI.confirm('이 첨부파일을 삭제하시겠습니까?'))) return;

    try {
        const res = await fetch(API_URL + '?action=deleteAttachment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id }),
        });
        const data = await res.json();
        if (data.error || (data.ok === false)) {
            showToast((data.error && data.error.message) || data.error || '삭제에 실패했습니다.', true);
            return;
        }
        showToast('첨부파일이 삭제되었습니다.');
        if (currentDetailPost) {
            openDetailModal(currentDetailPost.id);
        }
    } catch {
        showToast('삭제 중 오류가 발생했습니다.', true);
    }
}

// ========== 수정 모드 첨부파일 ==========
let editPendingFiles = [];

function switchToRead() {
    document.getElementById('detailView').classList.remove('hidden');
    document.getElementById('editView').classList.add('hidden');
    document.getElementById('detailBtnRead').classList.remove('hidden');
    document.getElementById('detailBtnEdit').classList.add('hidden');
    document.getElementById('detailModalTitle').textContent = '게시글 상세';
}

function switchToEdit() {
    if (!currentDetailPost) return;
    const p = currentDetailPost;
    document.getElementById('editId').value      = p.id;
    document.getElementById('editTitle').value    = p.title;
    document.getElementById('editContent').value  = p.content || '';
    const pinnedChk = document.getElementById('editPinned');
    if (pinnedChk) pinnedChk.checked = Number(p.is_pinned) === 1;

    editPendingFiles = [];
    renderEditAttachments(p.attachments || []);
    renderEditPendingFiles();

    document.getElementById('detailView').classList.add('hidden');
    document.getElementById('editView').classList.remove('hidden');
    document.getElementById('detailBtnRead').classList.add('hidden');
    document.getElementById('detailBtnEdit').classList.remove('hidden');
    document.getElementById('detailModalTitle').textContent = '게시글 수정';
}

function renderEditAttachments(attachments) {
    const container = document.getElementById('editExistingFiles');
    if (!container) return;
    if (!attachments || attachments.length === 0) {
        container.innerHTML = '';
        return;
    }
    let html = '';
    attachments.forEach(a => {
        html += `<div class="flex items-center gap-2 text-sm text-slate-300" data-att-id="${a.id}">
            <i data-lucide="file" class="w-4 h-4 text-slate-500 flex-shrink-0"></i>
            <span class="truncate flex-1">${esc(a.original_name)}</span>
            <span class="text-slate-500 flex-shrink-0 text-xs">${formatFileSize(a.file_size)}</span>
            <button onclick="deleteAttachmentInEdit(${a.id})" class="text-slate-500 hover:text-red-400 flex-shrink-0"><i data-lucide="x" class="w-4 h-4 pointer-events-none"></i></button>
        </div>`;
    });
    container.innerHTML = html;
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

async function deleteAttachmentInEdit(id) {
    try {
        const res = await fetch(API_URL + '?action=deleteAttachment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id }),
        });
        const data = await res.json();
        if (data.error || (data.ok === false)) {
            showToast('삭제에 실패했습니다.', true);
            return;
        }
        const row = document.querySelector(`[data-att-id="${id}"]`);
        if (row) row.remove();
        if (currentDetailPost && currentDetailPost.attachments) {
            currentDetailPost.attachments = currentDetailPost.attachments.filter(a => a.id !== id);
        }
        showToast('첨부파일이 삭제되었습니다.');
    } catch {
        showToast('삭제 중 오류가 발생했습니다.', true);
    }
}

function addEditFile() {
    const existCount = currentDetailPost?.attachments?.length || 0;
    if (existCount + editPendingFiles.length >= MAX_FILES) {
        showToast(`첨부파일은 최대 ${MAX_FILES}개까지 가능합니다.`, true);
        return;
    }
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = ALLOWED_EXTS.map(e => '.' + e).join(',');
    input.onchange = () => {
        if (!input.files[0]) return;
        const file = input.files[0];
        if (file.size > MAX_FILE_SIZE) {
            showToast('파일 크기는 10MB를 초과할 수 없습니다.', true);
            return;
        }
        const ext = file.name.split('.').pop().toLowerCase();
        if (!ALLOWED_EXTS.includes(ext)) {
            showToast('허용되지 않는 파일 형식입니다.', true);
            return;
        }
        editPendingFiles.push(file);
        renderEditPendingFiles();
    };
    input.click();
}

function removeEditPendingFile(idx) {
    editPendingFiles.splice(idx, 1);
    renderEditPendingFiles();
}

function renderEditPendingFiles() {
    const container = document.getElementById('editNewFiles');
    if (!container) return;
    if (editPendingFiles.length === 0) {
        container.innerHTML = '';
        return;
    }
    let html = '<p class="text-xs text-slate-500 mt-2 mb-1">새로 추가할 파일</p>';
    editPendingFiles.forEach((f, i) => {
        html += `<div class="flex items-center gap-2 text-sm text-slate-300">
            <i data-lucide="file-plus" class="w-4 h-4 text-primary flex-shrink-0"></i>
            <span class="truncate flex-1">${esc(f.name)}</span>
            <span class="text-slate-500 flex-shrink-0">${formatFileSize(f.size)}</span>
            <button onclick="removeEditPendingFile(${i})" class="text-slate-500 hover:text-red-400 flex-shrink-0"><i data-lucide="x" class="w-4 h-4 pointer-events-none"></i></button>
        </div>`;
    });
    container.innerHTML = html;
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

async function submitUpdate() {
    const id      = parseInt(document.getElementById('editId').value, 10);
    const title   = document.getElementById('editTitle').value.trim();
    const content = document.getElementById('editContent').value.trim();
    const pinnedChk = document.getElementById('editPinned');
    const pinned  = pinnedChk && pinnedChk.checked ? 1 : 0;

    if (!title) { showToast('제목을 입력해주세요.', true); return; }
    if (!content) { showToast('내용을 입력해주세요.', true); return; }

    try {
        const res = await fetch(API_URL + '?action=updatePost', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, title, content, isPinned: pinned }),
        });
        const data = await res.json();
        if (data.error) { showToast(data.error, true); return; }

        if (editPendingFiles.length > 0) {
            await uploadFilesToPost(id);
        }

        closeModals();
        showToast('게시글이 수정되었습니다.');
        loadPosts();
    } catch {
        showToast('수정 중 오류가 발생했습니다.', true);
    }
}

async function submitDelete() {
    if (!currentDetailPost) return;
    if (!(await AppUI.confirm('이 게시글을 삭제하시겠습니까?'))) return;

    fetch(API_URL + '?action=deletePost', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: currentDetailPost.id }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) { showToast(data.error, true); return; }
        closeModals();
        showToast('게시글이 삭제되었습니다.');
        loadPosts();
    })
    .catch(() => showToast('삭제 중 오류가 발생했습니다.', true));
}

// ========== 댓글 ==========
function loadComments(postId) {
    const container = document.getElementById('detailComments');
    if (!container) return;

    fetch(`${API_URL}?action=getComments&post_id=${postId}`)
        .then(r => r.json())
        .then(data => {
            const comments = (data.ok !== false && data.data) ? data.data.comments : (data.comments || []);
            const count = (data.ok !== false && data.data) ? data.data.count : (data.count || 0);
            renderComments(comments, count);
        })
        .catch(err => {
            console.error('댓글 로드 실패:', err);
            container.classList.add('hidden');
        });
}

function renderComments(comments, count) {
    const container = document.getElementById('detailComments');
    if (!container) return;

    container.classList.remove('hidden');
    const countEl = container.querySelector('.comment-count');
    if (countEl) countEl.textContent = count;

    const listEl = container.querySelector('.comment-list');
    if (!listEl) return;

    if (!comments || comments.length === 0) {
        listEl.innerHTML = '<p class="text-sm text-slate-500 py-2">댓글이 없습니다.</p>';
        return;
    }

    let html = '';
    comments.forEach(c => {
        const dateStr = (c.created_at || '').substring(0, 16).replace('T', ' ');
        html += `<div class="py-3 border-b border-slate-800 last:border-b-0">
            <div class="flex items-center gap-2 mb-1">
                <span class="text-sm font-semibold text-slate-200">${esc(c.author_name)}</span>`;
        if (c.author_dept) {
            html += `<span class="text-xs text-slate-500">${esc(c.author_dept)}</span>`;
        }
        html += `   <span class="text-xs text-slate-500 ml-auto">${esc(dateStr)}</span>`;
        if (c.can_delete) {
            html += `<button onclick="deleteComment(${c.id})" class="text-slate-500 hover:text-red-400 ml-1" title="삭제"><i data-lucide="trash-2" class="w-3.5 h-3.5 pointer-events-none"></i></button>`;
        }
        html += `</div>
            <p class="text-sm text-slate-300 whitespace-pre-wrap">${esc(c.content)}</p>
        </div>`;
    });
    listEl.innerHTML = html;
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

async function submitComment() {
    if (!currentDetailPost) return;
    const input = document.getElementById('commentInput');
    if (!input) return;
    const content = input.value.trim();
    if (!content) { showToast('댓글 내용을 입력해주세요.', true); return; }
    if (content.length > 2000) { showToast('댓글은 2000자까지 입력할 수 있습니다.', true); return; }

    try {
        const res = await fetch(API_URL + '?action=addComment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ post_id: currentDetailPost.id, content }),
        });
        const data = await res.json();
        if (data.error || (data.ok === false)) {
            const msg = (data.error && data.error.message) || data.error || '댓글 등록에 실패했습니다.';
            showToast(msg, true);
            return;
        }
        input.value = '';
        showToast('댓글이 등록되었습니다.');
        loadComments(currentDetailPost.id);
    } catch {
        showToast('댓글 등록 중 오류가 발생했습니다.', true);
    }
}

async function deleteComment(id) {
    if (!(await AppUI.confirm('이 댓글을 삭제하시겠습니까?'))) return;

    try {
        const res = await fetch(API_URL + '?action=deleteComment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id }),
        });
        const data = await res.json();
        if (data.error || (data.ok === false)) {
            showToast('삭제에 실패했습니다.', true);
            return;
        }
        showToast('댓글이 삭제되었습니다.');
        if (currentDetailPost) loadComments(currentDetailPost.id);
    } catch {
        showToast('삭제 중 오류가 발생했습니다.', true);
    }
}

// ========== 모달 닫기 ==========
function closeModals() {
    document.getElementById('createModal').classList.add('hidden');
    document.getElementById('detailModal').classList.add('hidden');
    currentDetailPost = null;
    pendingFiles = [];
    editPendingFiles = [];
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeModals();
});

// ========== 토스트 ==========
function showToast(msg, isError) {
    const toast    = document.getElementById('toast');
    const toastMsg = document.getElementById('toastMsg');
    const inner    = toast.firstElementChild;

    toastMsg.textContent = msg;
    inner.classList.toggle('bg-amber-600', !!isError);
    inner.classList.toggle('bg-slate-700', !isError);

    toast.classList.remove('hidden');
    clearTimeout(window._toastTimer);
    window._toastTimer = setTimeout(() => toast.classList.add('hidden'), 3000);
}
