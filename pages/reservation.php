<?php
$pageTitle = '자원예약';
$currentPage = 'reservation';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../config/database.php';

// 자원 목록 로드 (공통코드 reservation 모듈 + max_count)
$resources = [];
$pdo = getDBConnection();
if ($pdo) {
    try {
        $check = $pdo->query("SHOW TABLES LIKE 'common_code_items'");
        if ($check->rowCount() > 0) {
            $stmt = $pdo->query("
                SELECT i.id, i.name, COALESCE(c.max_count, 1) AS max_count
                FROM common_code_items i
                JOIN common_code_groups g ON i.group_id = g.id
                LEFT JOIN reservation_resource_config c ON c.item_id = i.id
                WHERE g.module = 'reservation' AND i.is_active = 1
                ORDER BY i.sort_order, i.id
            ");
            $resources = $stmt->fetchAll();
        }
    } catch (PDOException $e) { /* ignore */ }
}

$resourcesJson = json_encode($resources, JSON_UNESCAPED_UNICODE);
?>

<style>
/* ===== 자원예약 캘린더 스타일 ===== */
.cal-wrapper {
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border-radius: 0 0 12px 12px;
}
.cal-header-row {
    display: flex;
    border-bottom: 2px solid var(--zm-border);
    background: var(--zm-surface-2);
    position: sticky;
    top: 0;
    z-index: 20;
}
.cal-time-header {
    width: 64px;
    flex-shrink: 0;
    padding: 12px 8px;
    font-size: 12px;
    font-weight: 600;
    color: var(--zm-text-subtle);
    text-align: center;
    border-right: 1px solid var(--zm-border);
}
.cal-res-header {
    flex: 1;
    min-width: 160px;
    padding: 10px 12px;
    border-right: 1px solid var(--zm-border);
    cursor: default;
}
.cal-res-header:last-child { border-right: none; }
.cal-res-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--zm-text-strong);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.cal-res-badge {
    display: inline-flex;
    align-items: center;
    margin-top: 3px;
    padding: 1px 7px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 500;
    background: var(--zm-surface-2);
    color: var(--zm-text-subtle);
}

/* 바디 */
.cal-body-wrap {
    display: flex;
    overflow-y: auto;
    max-height: calc(100vh - 260px);
    min-height: 400px;
}
.cal-time-col {
    width: 64px;
    flex-shrink: 0;
    border-right: 1px solid var(--zm-border);
    background: var(--zm-surface-0);
}
.cal-time-label {
    height: 52px;
    display: flex;
    align-items: flex-start;
    justify-content: flex-end;
    padding: 4px 8px 0 0;
    font-size: 11px;
    color: var(--zm-text-subtle);
    font-weight: 500;
    border-bottom: 1px solid var(--zm-surface-2);
    box-sizing: border-box;
}
.cal-time-label.hour { border-bottom-color: var(--zm-border); }

/* 자원 열 */
.cal-res-cols {
    display: flex;
    flex: 1;
}
.cal-res-col {
    flex: 1;
    min-width: 160px;
    position: relative;
    border-right: 1px solid var(--zm-border);
}
.cal-res-col:last-child { border-right: none; }

/* 슬롯 셀 */
.cal-slot {
    height: 52px;
    border-bottom: 1px solid var(--zm-surface-2);
    box-sizing: border-box;
    cursor: crosshair;
    user-select: none;
}
.cal-slot.hour-start { border-top: 1px solid var(--zm-border); }
.cal-slot:last-child { border-bottom: none; }

/* 드래그 선택 오버레이 */
.sel-overlay {
    position: absolute;
    left: 3px;
    right: 3px;
    background: rgba(0, 0, 0, 0.08);
    border: 1.5px dashed rgba(0, 0, 0, 0.12);
    border-radius: 6px;
    pointer-events: none;
    z-index: 8;
}

/* 예약 블록 */
.res-block {
    position: absolute;
    left: 4px;
    right: 4px;
    border-radius: 6px;
    border-left: 3px solid;
    padding: 4px 7px;
    font-size: 12px;
    overflow: hidden;
    cursor: pointer;
    z-index: 10;
    transition: opacity 0.15s ease, filter 0.15s ease;
    box-sizing: border-box;
}
.res-block:hover { filter: brightness(0.95); }
.res-block-title {
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.3;
}
.res-block-user {
    font-size: 11px;
    opacity: 0.75;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.3;
}

/* 현재 시간 라인 */
.now-line {
    position: absolute;
    left: 0;
    right: 0;
    height: 2px;
    background: #ef4444;
    z-index: 15;
    pointer-events: none;
}
.now-line::before {
    content: '';
    position: absolute;
    left: -4px;
    top: -4px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #ef4444;
}

/* 빈 상태 */
.cal-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 80px 20px;
    color: #9ca3af;
}

/* 토스트 */
.toast-item {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 260px;
    max-width: 360px;
    padding: 12px 16px;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
    font-size: 14px;
    font-weight: 500;
    transition: opacity 0.3s ease, transform 0.3s ease;
}
</style>

<!-- 메인 컨텐츠 영역 -->
<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6">

        <!-- 헤더 + 날짜 네비게이션 -->
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-lg font-bold text-slate-100">자원 예약</h2>
            <div class="flex items-center gap-2">
                <button id="btnPrev" class="w-8 h-8 flex items-center justify-center border border-slate-800 rounded-lg hover:bg-slate-950 transition-colors text-slate-300">
                    <i data-lucide="chevron-left" class="w-4 h-4"></i>
                </button>
                <button id="btnToday" class="btn btn-secondary btn-sm">
                    오늘
                </button>
                <div id="dateDisplay" class="px-4 h-8 flex items-center text-sm font-semibold text-slate-100 bg-slate-900 border border-slate-800 rounded-lg min-w-40 justify-center"></div>
                <button id="btnNext" class="w-8 h-8 flex items-center justify-center border border-slate-800 rounded-lg hover:bg-slate-950 transition-colors text-slate-300">
                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        <!-- 캘린더 카드 -->
        <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
            <div id="calContainer">
                <!-- JS로 렌더링 -->
            </div>
        </div>

    </main>
</div>

<!-- ===== 예약 생성 모달 ===== -->
<div id="createModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden" onclick="if(event.target===this)closeCreateModal()">
    <div class="bg-slate-900 rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-100">자원 예약</h3>
            <button id="btnCloseCreate" class="p-2 -mr-2 text-slate-500 hover:text-slate-300 rounded-lg hover:bg-slate-800 transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <!-- 자원/시간 정보 -->
            <div class="bg-primary/5 border border-primary/20 rounded-lg px-4 py-3 flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm text-slate-400 mb-0.5">자원</p>
                    <p id="createResName" class="text-sm font-semibold text-slate-100"></p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-slate-400 mb-0.5">시간</p>
                    <p id="createTimeRange" class="text-sm font-semibold text-primary"></p>
                </div>
            </div>
            <!-- 제목 -->
            <div>
                <label class="reg-label">예약 제목 <span class="text-amber-500">*</span></label>
                <input type="text" id="createTitle" class="reg-input" placeholder="예약 제목을 입력하세요.">
            </div>
            <!-- 예약자명 -->
            <div>
                <label class="reg-label">예약자명</label>
                <input type="text" id="createUser" class="reg-input" placeholder="예약자 이름 (선택)">
            </div>
            <!-- 메모 -->
            <div>
                <label class="reg-label">메모</label>
                <textarea id="createDesc" rows="2" class="reg-input resize-none" placeholder="메모 (선택)"></textarea>
            </div>
        </div>
        <div class="px-6 pb-5 flex justify-end gap-3">
            <button id="btnCancelCreate" class="btn btn-secondary">취소</button>
            <button id="btnSaveCreate" class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:opacity-90 transition-colors flex items-center gap-1.5">
                <i data-lucide="check" class="w-3.5 h-3.5"></i> 예약하기
            </button>
        </div>
    </div>
</div>

<!-- ===== 예약 상세 모달 ===== -->
<div id="detailModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden" onclick="if(event.target===this)closeDetailModal()">
    <div class="bg-slate-900 rounded-xl shadow-2xl w-full max-w-sm mx-4 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-100">예약 상세</h3>
            <button id="btnCloseDetail" class="p-2 -mr-2 text-slate-500 hover:text-slate-300 rounded-lg hover:bg-slate-800 transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <div class="p-6 space-y-3">
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-slate-950 rounded-lg p-3">
                    <p class="text-sm text-slate-500 mb-1">자원</p>
                    <p id="detailResName" class="text-sm font-semibold text-slate-100"></p>
                </div>
                <div class="bg-primary/5 rounded-lg p-3">
                    <p class="text-sm text-slate-500 mb-1">시간</p>
                    <p id="detailTime" class="text-sm font-semibold text-primary"></p>
                </div>
            </div>
            <div>
                <p class="text-sm text-slate-500 mb-1">예약 제목</p>
                <p id="detailTitle" class="text-sm font-semibold text-slate-100"></p>
            </div>
            <div id="detailUserRow">
                <p class="text-sm text-slate-500 mb-1">예약자</p>
                <p id="detailUser" class="text-sm text-slate-200"></p>
            </div>
            <div id="detailDescRow" class="hidden">
                <p class="text-sm text-slate-500 mb-1">메모</p>
                <p id="detailDesc" class="text-sm text-slate-300"></p>
            </div>
        </div>
        <div class="px-6 pb-5 flex justify-between items-center">
            <button id="btnCloseDetail2" class="btn btn-secondary">닫기</button>
            <button id="btnDeleteReservation" class="px-4 py-2 text-sm font-medium text-white bg-amber-500 rounded-lg hover:bg-amber-600 transition-colors flex items-center gap-1.5">
                <i data-lucide="calendar-x" class="w-3.5 h-3.5"></i> 예약 취소
            </button>
        </div>
    </div>
</div>

<!-- ===== 토스트 컨테이너 ===== -->
<div id="toastContainer" class="fixed top-20 right-4 z-[100] flex flex-col gap-2 pointer-events-none"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const basePath = '<?= $basePath ?>';

    // ===== 상수 =====
    const SLOT_HEIGHT = 52;   // px per 30-min slot
    const START_HOUR  = 8;
    const END_HOUR    = 20;
    const TOTAL_SLOTS = (END_HOUR - START_HOUR) * 2;  // 24 slots

    const COLORS = [
        { bg: '#dbeafe', border: '#3b82f6', text: '#1e40af' },
        { bg: '#dcfce7', border: '#22c55e', text: '#166534' },
        { bg: '#fce7f3', border: '#ec4899', text: '#9d174d' },
        { bg: '#ede9fe', border: '#8b5cf6', text: '#5b21b6' },
        { bg: '#ffedd5', border: '#f97316', text: '#9a3412' },
        { bg: '#e0f2fe', border: '#0ea5e9', text: '#075985' },
    ];

    // ===== 상태 =====
    const resources = <?= $resourcesJson ?>;
    let reservations = [];
    let currentDate  = new Date();
    let dragState    = null;
    let pendingModal = null;  // { resourceId, startTime, endTime }
    let detailTarget = null;  // 현재 상세 모달의 예약 ID

    // ===== 초기화 =====
    updateDateDisplay();
    renderCalendar();
    loadReservations();

    // 날짜 네비게이션
    document.getElementById('btnPrev').addEventListener('click', () => {
        currentDate = addDays(currentDate, -1);
        updateDateDisplay();
        loadReservations();
    });
    document.getElementById('btnNext').addEventListener('click', () => {
        currentDate = addDays(currentDate, 1);
        updateDateDisplay();
        loadReservations();
    });
    document.getElementById('btnToday').addEventListener('click', () => {
        currentDate = new Date();
        updateDateDisplay();
        loadReservations();
    });

    // ===== 캘린더 렌더링 =====
    function renderCalendar() {
        const container = document.getElementById('calContainer');

        if (resources.length === 0) {
            container.innerHTML = `
                <div class="cal-empty">
                    <i data-lucide="package-open" class="w-12 h-12 mb-3 text-slate-600"></i>
                    <p class="text-sm font-medium text-slate-500">등록된 자원이 없습니다.</p>
                    <p class="text-sm text-slate-500 mt-1">그룹웨어 관리 &gt; 공통코드 &gt; 자원예약에서 자원을 등록해주세요.</p>
                </div>`;
            lucide.createIcons();
            return;
        }

        // 헤더 행
        let headerHtml = `<div class="cal-header-row"><div class="cal-time-header">시간</div>`;
        resources.forEach((res, idx) => {
            const c = COLORS[idx % COLORS.length];
            headerHtml += `
                <div class="cal-res-header">
                    <div class="cal-res-name">${esc(res.name)}</div>
                    <div class="cal-res-badge" style="background:${c.bg};color:${c.text}">
                        최대 ${res.max_count}회
                    </div>
                </div>`;
        });
        headerHtml += `</div>`;

        // 바디: 시간 열 + 자원 열
        let timeColHtml = `<div class="cal-time-col">`;
        for (let s = 0; s < TOTAL_SLOTS; s++) {
            const isHour = s % 2 === 0;
            const label  = isHour ? slotToTime(s) : '';
            timeColHtml += `<div class="cal-time-label${isHour ? ' hour' : ''}">${label}</div>`;
        }
        timeColHtml += `</div>`;

        let resCols = `<div class="cal-res-cols">`;
        resources.forEach((res, idx) => {
            resCols += `<div class="cal-res-col" data-res-id="${res.id}" data-res-idx="${idx}" id="rescol-${res.id}">`;
            for (let s = 0; s < TOTAL_SLOTS; s++) {
                const isHour = s % 2 === 0;
                resCols += `<div class="cal-slot${isHour ? ' hour-start' : ''}" data-slot="${s}" data-resource="${res.id}"></div>`;
            }
            resCols += `</div>`;
        });
        resCols += `</div>`;

        container.innerHTML = headerHtml + `
            <div class="cal-wrapper">
                <div class="cal-body-wrap" id="calBodyWrap">
                    ${timeColHtml}
                    ${resCols}
                </div>
            </div>`;

        lucide.createIcons();
        attachDragHandlers();
        drawNowLine();
    }

    // ===== 현재 시간 라인 =====
    function drawNowLine() {
        document.querySelectorAll('.now-line').forEach(el => el.remove());
        const now = new Date();
        if (!isSameDay(now, currentDate)) return;

        const h = now.getHours(), m = now.getMinutes();
        if (h < START_HOUR || h >= END_HOUR) return;

        const top = ((h - START_HOUR) * 60 + m) / 30 * SLOT_HEIGHT;
        document.querySelectorAll('.cal-res-col').forEach(col => {
            const line = document.createElement('div');
            line.className = 'now-line';
            line.style.top = top + 'px';
            col.appendChild(line);
        });
    }

    // ===== 예약 데이터 로드 =====
    function loadReservations() {
        const dateStr = getDateStr(currentDate);
        fetch(`${basePath}/api/reservation.php?action=getReservations&date=${dateStr}`)
            .then(r => r.json())
            .then(data => {
                reservations = data.reservations || [];
                renderReservations();
                drawNowLine();
            })
            .catch(() => {
                reservations = [];
                renderReservations();
            });
    }

    // ===== 예약 블록 렌더링 =====
    function renderReservations() {
        // 기존 블록 제거
        document.querySelectorAll('.res-block').forEach(el => el.remove());

        reservations.forEach(r => {
            const col = document.getElementById(`rescol-${r.resource_item_id}`);
            if (!col) return;

            const resIdx = resources.findIndex(res => String(res.id) === String(r.resource_item_id));
            const c = COLORS[resIdx % COLORS.length];

            const top    = timeToTop(r.start_time);
            const height = durationToHeight(r.start_time, r.end_time);
            if (height <= 0) return;

            const block = document.createElement('div');
            block.className = 'res-block';
            block.dataset.reservationId = r.id;
            block.style.cssText = `
                top: ${top}px;
                height: ${Math.max(height - 2, 20)}px;
                background: ${c.bg};
                border-left-color: ${c.border};
                color: ${c.text};
            `;
            block.innerHTML = `
                <div class="res-block-title">${esc(r.title)}</div>
                ${r.user_name ? `<div class="res-block-user">${esc(r.user_name)}</div>` : ''}
            `;
            block.addEventListener('mousedown', (e) => e.stopPropagation());
            block.addEventListener('click', (e) => {
                e.stopPropagation();
                openDetailModal(r);
            });
            col.appendChild(block);
        });
    }

    // ===== 드래그 핸들러 =====
    function attachDragHandlers() {
        const bodyWrap = document.getElementById('calBodyWrap');
        if (!bodyWrap) return;

        let selOverlay = null;

        bodyWrap.addEventListener('mousedown', (e) => {
            const slot = e.target.closest('[data-slot][data-resource]');
            if (!slot) return;
            if (e.button !== 0) return;
            e.preventDefault();

            const resId = slot.dataset.resource;
            const slotIdx = +slot.dataset.slot;
            const col = document.getElementById(`rescol-${resId}`);

            dragState = { resId, startSlot: slotIdx, endSlot: slotIdx, col };

            selOverlay = document.createElement('div');
            selOverlay.className = 'sel-overlay';
            col.appendChild(selOverlay);
            updateSelOverlay(selOverlay, slotIdx, slotIdx);
        });

        document.addEventListener('mousemove', (e) => {
            if (!dragState || !selOverlay) return;
            const slot = e.target.closest(`[data-resource="${dragState.resId}"][data-slot]`);
            if (slot) {
                dragState.endSlot = +slot.dataset.slot;
                updateSelOverlay(selOverlay, dragState.startSlot, dragState.endSlot);
            }
        });

        document.addEventListener('mouseup', () => {
            if (!dragState) return;
            const { resId, startSlot, endSlot } = dragState;
            dragState = null;

            if (selOverlay) { selOverlay.remove(); selOverlay = null; }

            const from = Math.min(startSlot, endSlot);
            const to   = Math.max(startSlot, endSlot) + 1;
            openCreateModal(resId, slotToTime(from), slotToTime(to));
        });

        function updateSelOverlay(overlay, s1, s2) {
            const from = Math.min(s1, s2);
            const to   = Math.max(s1, s2) + 1;
            overlay.style.top    = (from * SLOT_HEIGHT) + 'px';
            overlay.style.height = ((to - from) * SLOT_HEIGHT) + 'px';
        }
    }

    // ===== 예약 생성 모달 =====
    function openCreateModal(resId, startTime, endTime) {
        const res = resources.find(r => String(r.id) === String(resId));
        if (!res) return;

        pendingModal = { resId, startTime, endTime };

        document.getElementById('createResName').textContent    = res.name;
        document.getElementById('createTimeRange').textContent  = `${startTime} ~ ${endTime}`;
        document.getElementById('createTitle').value  = '';
        document.getElementById('createUser').value   = '';
        document.getElementById('createDesc').value   = '';

        document.getElementById('createModal').classList.remove('hidden');
        setTimeout(() => document.getElementById('createTitle').focus(), 50);
    }

    function closeCreateModal() {
        document.getElementById('createModal').classList.add('hidden');
        pendingModal = null;
    }

    document.getElementById('btnCloseCreate').addEventListener('click', closeCreateModal);
    document.getElementById('btnCancelCreate').addEventListener('click', closeCreateModal);
    document.getElementById('createModal').addEventListener('click', (e) => {
        if (e.target === document.getElementById('createModal')) closeCreateModal();
    });

    document.getElementById('btnSaveCreate').addEventListener('click', () => {
        if (!pendingModal) return;
        const title    = document.getElementById('createTitle').value.trim();
        const userName = document.getElementById('createUser').value.trim();
        const desc     = document.getElementById('createDesc').value.trim();

        if (!title) {
            showToast('예약 제목을 입력해주세요.', 'warning');
            document.getElementById('createTitle').focus();
            return;
        }

        const payload = {
            resource_item_id: pendingModal.resId,
            title,
            user_name: userName,
            date: getDateStr(currentDate),
            start_time: pendingModal.startTime,
            end_time:   pendingModal.endTime,
            description: desc,
        };

        const btn = document.getElementById('btnSaveCreate');
        btn.disabled = true;
        btn.innerHTML = '<i data-lucide="loader" class="w-3.5 h-3.5 animate-spin"></i> 처리 중...';
        lucide.createIcons();

        fetch(`${basePath}/api/reservation.php?action=createReservation`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload),
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="check" class="w-3.5 h-3.5"></i> 예약하기';
            lucide.createIcons();

            if (data.error) {
                showToast(data.error, data.type === 'capacity_exceeded' ? 'error' : 'warning');
                return;
            }
            closeCreateModal();
            showToast(data.message || '예약이 완료되었습니다.', 'success');
            loadReservations();
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="check" class="w-3.5 h-3.5"></i> 예약하기';
            lucide.createIcons();
            showToast('예약 저장 중 서버 연결 오류가 발생했습니다.', 'error');
        });
    });

    // 엔터키로 제출
    document.getElementById('createTitle').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') document.getElementById('btnSaveCreate').click();
    });

    // ===== 예약 상세 모달 =====
    function openDetailModal(r) {
        const res = resources.find(res => String(res.id) === String(r.resource_item_id));
        detailTarget = r.id;

        document.getElementById('detailResName').textContent = res ? res.name : '-';
        document.getElementById('detailTime').textContent    = `${r.start_time} ~ ${r.end_time}`;
        document.getElementById('detailTitle').textContent   = r.title;
        document.getElementById('detailUser').textContent    = r.user_name || '(없음)';

        const descRow = document.getElementById('detailDescRow');
        if (r.description) {
            descRow.classList.remove('hidden');
            document.getElementById('detailDesc').textContent = r.description;
        } else {
            descRow.classList.add('hidden');
        }

        document.getElementById('detailModal').classList.remove('hidden');
    }

    function closeDetailModal() {
        document.getElementById('detailModal').classList.add('hidden');
        detailTarget = null;
    }

    document.getElementById('btnCloseDetail').addEventListener('click', closeDetailModal);
    document.getElementById('btnCloseDetail2').addEventListener('click', closeDetailModal);
    document.getElementById('detailModal').addEventListener('click', (e) => {
        if (e.target === document.getElementById('detailModal')) closeDetailModal();
    });

    document.getElementById('btnDeleteReservation').addEventListener('click', async () => {
        if (!detailTarget) return;
        if (!(await AppUI.confirm('이 예약을 취소하시겠습니까?'))) return;

        fetch(`${basePath}/api/reservation.php?action=deleteReservation`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ id: detailTarget }),
        })
        .then(r => r.json())
        .then(data => {
            closeDetailModal();
            if (data.error) { showToast(data.error, 'error'); return; }
            showToast(data.message || '예약이 취소되었습니다.', 'success');
            loadReservations();
        })
        .catch(() => {
            closeDetailModal();
            showToast('예약 취소 중 서버 연결 오류가 발생했습니다.', 'error');
        });
    });

    // ===== 토스트 =====
    function showToast(message, type = 'success') {
        const colors = {
            success: { bg: '#22c55e', icon: 'check-circle' },
            error:   { bg: '#ef4444', icon: 'x-circle' },
            warning: { bg: '#f59e0b', icon: 'alert-triangle' },
        };
        const c = colors[type] || colors.success;

        const toast = document.createElement('div');
        toast.className = 'toast-item pointer-events-auto';
        toast.style.cssText = `background:${c.bg}; color:#fff; transform: translateX(0); opacity: 1;`;
        toast.innerHTML = `
            <i data-lucide="${c.icon}" class="w-5 h-5 flex-shrink-0"></i>
            <span>${esc(message)}</span>
        `;
        document.getElementById('toastContainer').appendChild(toast);
        lucide.createIcons({ rootElement: toast });

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(110%)';
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    }

    // ===== 날짜 유틸 =====
    function updateDateDisplay() {
        const d = currentDate;
        const days = ['일', '월', '화', '수', '목', '금', '토'];
        const str = `${d.getFullYear()}년 ${d.getMonth() + 1}월 ${d.getDate()}일 (${days[d.getDay()]})`;
        document.getElementById('dateDisplay').textContent = str;
    }

    function getDateStr(d) {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${dd}`;
    }

    function addDays(date, n) {
        const d = new Date(date);
        d.setDate(d.getDate() + n);
        return d;
    }

    function isSameDay(a, b) {
        return a.getFullYear() === b.getFullYear()
            && a.getMonth()    === b.getMonth()
            && a.getDate()     === b.getDate();
    }

    // ===== 시간 유틸 =====
    function slotToTime(slot) {
        const totalMin = slot * 30 + START_HOUR * 60;
        const h = Math.floor(totalMin / 60);
        const m = totalMin % 60;
        return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
    }

    function timeToTop(timeStr) {
        const [h, m] = timeStr.split(':').map(Number);
        return ((h - START_HOUR) * 60 + m) / 30 * SLOT_HEIGHT;
    }

    function durationToHeight(startStr, endStr) {
        const toMin = t => { const [h, m] = t.split(':').map(Number); return h * 60 + m; };
        return (toMin(endStr) - toMin(startStr)) / 30 * SLOT_HEIGHT;
    }

    function esc(str) {
        if (!str) return '';
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    // 매 분마다 현재 시간 라인 갱신
    setInterval(drawNowLine, 60000);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
