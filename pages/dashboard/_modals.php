<!-- ===== 일정 등록 모달 ===== -->
<div id="createModal" class="hidden fixed inset-0 z-[60] items-center justify-center bg-black/50" onclick="if(event.target===this)closeCreateModal()">
    <div class="bg-slate-900 rounded-2xl shadow-2xl w-full max-w-lg mx-4">
        <div class="flex items-center justify-between px-8 py-5">
            <h3 class="text-xl font-bold text-slate-100">일정 등록</h3>
            <button onclick="closeCreateModal()" class="text-slate-500 hover:text-slate-300 p-2 -mr-2 rounded-lg hover:bg-slate-800" aria-label="닫기"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <div class="px-8 pb-2 space-y-5 max-h-[70vh] overflow-y-auto">
            <div>
                <label class="block text-sm font-semibold text-slate-200 mb-2">일정 제목 <span class="text-slate-400">*</span></label>
                <input type="text" id="cTitle" class="w-full bg-slate-950 rounded-xl px-4 py-3 text-sm focus:bg-slate-900 focus:ring-2 focus:ring-gray-300/30 outline-none" placeholder="일정 제목을 입력해주세요">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-semibold text-slate-200 mb-2">시작일 <span class="text-slate-400">*</span></label><input type="date" id="cStartDate" class="w-full bg-slate-950 rounded-xl px-4 py-3 text-sm focus:bg-slate-900 focus:ring-2 focus:ring-gray-300/30 outline-none"></div>
                <div id="cStartTimeWrap"><label class="block text-sm font-semibold text-slate-200 mb-2">시작시간</label><input type="time" id="cStartTime" class="w-full bg-slate-950 rounded-xl px-4 py-3 text-sm focus:bg-slate-900 focus:ring-2 focus:ring-gray-300/30 outline-none" value="09:00"></div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-semibold text-slate-200 mb-2">종료일 <span class="text-slate-400">*</span></label><input type="date" id="cEndDate" class="w-full bg-slate-950 rounded-xl px-4 py-3 text-sm focus:bg-slate-900 focus:ring-2 focus:ring-gray-300/30 outline-none"></div>
                <div id="cEndTimeWrap"><label class="block text-sm font-semibold text-slate-200 mb-2">종료시간</label><input type="time" id="cEndTime" class="w-full bg-slate-950 rounded-xl px-4 py-3 text-sm focus:bg-slate-900 focus:ring-2 focus:ring-gray-300/30 outline-none" value="18:00"></div>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" id="cAllDay" class="w-4 h-4 accent-primary rounded" onchange="toggleAllDay('c')">
                <label for="cAllDay" class="text-sm text-slate-200">종일 일정</label>
            </div>
            <div><label class="block text-sm font-semibold text-slate-200 mb-2">카테고리</label><select id="cCategory" class="w-full bg-slate-950 rounded-xl px-4 py-3 text-sm focus:bg-slate-900 focus:ring-2 focus:ring-gray-300/30 outline-none"></select></div>
            <div><label class="block text-sm font-semibold text-slate-200 mb-2">작성자 <span class="text-slate-400">*</span></label><select id="cCreator" class="w-full bg-slate-950 rounded-xl px-4 py-3 text-sm focus:bg-slate-900 focus:ring-2 focus:ring-gray-300/30 outline-none"></select></div>
            <div>
                <label class="block text-sm font-semibold text-slate-200 mb-2">참석자</label>
                <div class="relative">
                    <input type="text" id="cAttendeeInput" class="w-full bg-slate-950 rounded-xl px-4 py-3 text-sm focus:bg-slate-900 focus:ring-2 focus:ring-gray-300/30 outline-none" placeholder="이름으로 검색..." autocomplete="off">
                    <div id="cAttendeeDropdown" class="hidden absolute z-10 w-full mt-1 bg-slate-900 rounded-xl shadow-lg max-h-40 overflow-y-auto"></div>
                </div>
                <div id="cAttendeeTags" class="flex flex-wrap gap-1.5 mt-2"></div>
            </div>
            <div><label class="block text-sm font-semibold text-slate-200 mb-2">내용</label><textarea id="cDesc" rows="3" class="w-full bg-slate-950 rounded-xl px-4 py-3 text-sm focus:bg-slate-900 focus:ring-2 focus:ring-gray-300/30 outline-none" placeholder="일정 내용을 입력해주세요"></textarea></div>
        </div>
        <div class="flex justify-end gap-2 px-8 py-5">
            <button onclick="closeCreateModal()" class="px-5 py-2.5 text-sm font-semibold bg-slate-800 rounded-xl hover:bg-slate-700 text-slate-200">취소</button>
            <button onclick="saveEvent()" class="px-5 py-2.5 text-sm font-semibold bg-primary text-white rounded-xl shadow-lg shadow-primary/30 ring-1 ring-inset ring-white/10 hover:bg-primary/90 transition-colors">등록</button>
        </div>
    </div>
</div>

<!-- ===== 일정 상세/수정 모달 ===== -->
<div id="detailModal" class="hidden fixed inset-0 z-[60] items-center justify-center bg-black/50" onclick="if(event.target===this)closeDetailModal()">
    <div class="bg-slate-900 rounded-2xl shadow-2xl w-full max-w-lg mx-4">
        <div class="flex items-center justify-between px-8 py-5">
            <h3 id="dModalTitle" class="text-xl font-bold text-slate-100">일정 상세</h3>
            <button onclick="closeDetailModal()" class="text-slate-500 hover:text-slate-300 p-2 -mr-2 rounded-lg hover:bg-slate-800" aria-label="닫기"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <div class="px-8 pb-2 max-h-[70vh] overflow-y-auto">
            <div id="detailView" class="space-y-5">
                <div><p class="text-sm text-slate-500 mb-1.5">제목</p><p id="dTitle" class="text-base font-bold text-slate-100"></p></div>
                <div><p class="text-sm text-slate-500 mb-1.5">일시</p><p id="dDate" class="text-sm text-slate-200"></p></div>
                <div><p class="text-sm text-slate-500 mb-1.5">카테고리</p><div id="dCategory"></div></div>
                <div><p class="text-sm text-slate-500 mb-1.5">작성자</p><p id="dCreator" class="text-sm text-slate-200"></p></div>
                <div id="dAttendeesWrap"><p class="text-sm text-slate-500 mb-1.5">참석자</p><div id="dAttendees"></div></div>
                <div id="dDescWrap"><p class="text-sm text-slate-500 mb-1.5">내용</p><p id="dDesc" class="text-sm text-slate-200 whitespace-pre-wrap"></p></div>
            </div>
            <div id="editView" class="hidden space-y-5">
                <input type="hidden" id="eId">
                <div><label class="block text-sm font-semibold text-slate-200 mb-2">일정 제목 <span class="text-slate-400">*</span></label><input type="text" id="eTitle" class="w-full bg-slate-950 rounded-xl px-4 py-3 text-sm focus:bg-slate-900 focus:ring-2 focus:ring-gray-300/30 outline-none"></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-semibold text-slate-200 mb-2">시작일</label><input type="date" id="eStartDate" class="w-full bg-slate-950 rounded-xl px-4 py-3 text-sm focus:bg-slate-900 focus:ring-2 focus:ring-gray-300/30 outline-none"></div>
                    <div id="eStartTimeWrap"><label class="block text-sm font-semibold text-slate-200 mb-2">시작시간</label><input type="time" id="eStartTime" class="w-full bg-slate-950 rounded-xl px-4 py-3 text-sm focus:bg-slate-900 focus:ring-2 focus:ring-gray-300/30 outline-none"></div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-semibold text-slate-200 mb-2">종료일</label><input type="date" id="eEndDate" class="w-full bg-slate-950 rounded-xl px-4 py-3 text-sm focus:bg-slate-900 focus:ring-2 focus:ring-gray-300/30 outline-none"></div>
                    <div id="eEndTimeWrap"><label class="block text-sm font-semibold text-slate-200 mb-2">종료시간</label><input type="time" id="eEndTime" class="w-full bg-slate-950 rounded-xl px-4 py-3 text-sm focus:bg-slate-900 focus:ring-2 focus:ring-gray-300/30 outline-none"></div>
                </div>
                <div class="flex items-center gap-2"><input type="checkbox" id="eAllDay" class="w-4 h-4 accent-primary rounded" onchange="toggleAllDay('e')"><label for="eAllDay" class="text-sm text-slate-200">종일 일정</label></div>
                <div><label class="block text-sm font-semibold text-slate-200 mb-2">카테고리</label><select id="eCategory" class="w-full bg-slate-950 rounded-xl px-4 py-3 text-sm focus:bg-slate-900 focus:ring-2 focus:ring-gray-300/30 outline-none"></select></div>
                <div>
                    <label class="block text-sm font-semibold text-slate-200 mb-2">참석자</label>
                    <div class="relative"><input type="text" id="eAttendeeInput" class="w-full bg-slate-950 rounded-xl px-4 py-3 text-sm focus:bg-slate-900 focus:ring-2 focus:ring-gray-300/30 outline-none" placeholder="이름으로 검색..." autocomplete="off"><div id="eAttendeeDropdown" class="hidden absolute z-10 w-full mt-1 bg-slate-900 rounded-xl shadow-lg max-h-40 overflow-y-auto"></div></div>
                    <div id="eAttendeeTags" class="flex flex-wrap gap-1.5 mt-2"></div>
                </div>
                <div><label class="block text-sm font-semibold text-slate-200 mb-2">내용</label><textarea id="eDesc" rows="3" class="w-full bg-slate-950 rounded-xl px-4 py-3 text-sm focus:bg-slate-900 focus:ring-2 focus:ring-gray-300/30 outline-none"></textarea></div>
            </div>
        </div>
        <div class="flex justify-between px-8 py-5">
            <button onclick="confirmDelete()" class="px-5 py-2.5 text-sm font-semibold text-slate-200 hover:bg-slate-800 rounded-xl">삭제</button>
            <div class="flex gap-2">
                <button onclick="closeDetailModal()" class="px-5 py-2.5 text-sm font-semibold bg-slate-800 rounded-xl hover:bg-slate-700 text-slate-200">닫기</button>
                <button id="btnEdit" onclick="switchToEdit()" class="px-5 py-2.5 text-sm font-semibold bg-primary text-white rounded-xl shadow-lg shadow-primary/30 ring-1 ring-inset ring-white/10 hover:bg-primary/90 transition-colors">수정</button>
                <button id="btnSave" onclick="saveEdit()" class="hidden px-5 py-2.5 text-sm font-semibold bg-primary text-white rounded-xl shadow-lg shadow-primary/30 ring-1 ring-inset ring-white/10 hover:bg-primary/90 transition-colors">저장</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== 외근 등록 모달 ===== -->
<div id="outsideWorkModal" class="hidden fixed inset-0 z-[60] items-center justify-center bg-black/50" onclick="if(event.target===this)closeOutsideWorkModal()">
    <div class="bg-slate-900 rounded-2xl shadow-2xl w-full max-w-md mx-4">
        <div class="flex items-center justify-between px-8 py-5">
            <h3 class="text-xl font-bold text-slate-100">외근 등록</h3>
            <button onclick="closeOutsideWorkModal()" class="text-slate-500 hover:text-slate-300 p-2 -mr-2 rounded-lg hover:bg-slate-800" aria-label="닫기"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <div class="px-8 pb-2 space-y-5">
            <div><label class="block text-sm font-semibold text-slate-200 mb-2">외근일 <span class="text-slate-400">*</span></label><input type="date" id="owDate" class="w-full bg-slate-950 rounded-xl px-4 py-3 text-sm focus:bg-slate-900 focus:ring-2 focus:ring-gray-300/30 outline-none"></div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="block text-sm font-semibold text-slate-200 mb-2">출발시간</label><input type="time" id="owDepartureTime" class="w-full bg-slate-950 rounded-xl px-4 py-3 text-sm focus:bg-slate-900 focus:ring-2 focus:ring-gray-300/30 outline-none"></div>
                <div><label class="block text-sm font-semibold text-slate-200 mb-2">복귀시간</label><input type="time" id="owReturnTime" class="w-full bg-slate-950 rounded-xl px-4 py-3 text-sm focus:bg-slate-900 focus:ring-2 focus:ring-gray-300/30 outline-none"></div>
            </div>
            <div><label class="block text-sm font-semibold text-slate-200 mb-2">방문처 <span class="text-slate-400">*</span></label><input type="text" id="owDestination" class="w-full bg-slate-950 rounded-xl px-4 py-3 text-sm focus:bg-slate-900 focus:ring-2 focus:ring-gray-300/30 outline-none" placeholder="방문 회사/장소"></div>
            <div><label class="block text-sm font-semibold text-slate-200 mb-2">목적</label><textarea id="owPurpose" rows="2" class="w-full bg-slate-950 rounded-xl px-4 py-3 text-sm focus:bg-slate-900 focus:ring-2 focus:ring-gray-300/30 outline-none" placeholder="외근 목적"></textarea></div>
        </div>
        <div class="flex justify-end gap-2 px-8 py-5">
            <button onclick="closeOutsideWorkModal()" class="px-5 py-2.5 text-sm font-semibold bg-slate-800 rounded-xl hover:bg-slate-700 text-slate-200">취소</button>
            <button onclick="saveOutsideWork()" class="px-5 py-2.5 text-sm font-semibold bg-primary text-white rounded-xl shadow-lg shadow-primary/30 ring-1 ring-inset ring-white/10 hover:bg-primary/90 transition-colors">등록</button>
        </div>
    </div>
</div>

<!-- ===== 위젯 설정 모달 ===== -->
<div id="widgetSettingsModal" class="hidden fixed inset-0 z-[60] items-center justify-center bg-black/50" onclick="if(event.target===this)closeWidgetSettings()">
    <div class="bg-slate-900 rounded-2xl shadow-2xl w-full max-w-sm mx-4">
        <div class="flex items-center justify-between px-8 py-5">
            <h3 class="text-xl font-bold text-slate-100">위젯 설정</h3>
            <button onclick="closeWidgetSettings()" class="text-slate-500 hover:text-slate-300 p-2 -mr-2 rounded-lg hover:bg-slate-800" aria-label="닫기"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <div class="px-8 pb-2">
            <p class="text-sm text-slate-400 mb-4">대시보드에 표시할 위젯을 선택하세요.</p>
            <div id="widgetSettingsList" class="space-y-3"></div>
        </div>
        <div class="flex justify-between px-8 py-5">
            <button onclick="resetWidgetSettings()" class="px-4 py-2.5 text-sm font-semibold text-slate-400 hover:text-slate-200 hover:bg-slate-800 rounded-xl transition-colors">기본값 복원</button>
            <div class="flex gap-2">
                <button onclick="closeWidgetSettings()" class="px-5 py-2.5 text-sm font-semibold bg-slate-800 rounded-xl hover:bg-slate-700 text-slate-200">취소</button>
                <button onclick="saveWidgetSettings()" class="px-5 py-2.5 text-sm font-semibold bg-primary text-white rounded-xl shadow-lg shadow-primary/30 ring-1 ring-inset ring-white/10 hover:bg-primary/90 transition-colors">저장</button>
            </div>
        </div>
    </div>
</div>

<!-- 토스트 -->
<div id="toastContainer" class="fixed bottom-6 right-6 z-[70] space-y-2"></div>
