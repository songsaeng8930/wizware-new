        <!-- ===== 취업규칙 (리치 에디터 기반 단일 문서 뷰) ===== -->

        <!-- 상단 sticky 툴바 -->
        <div class="sticky top-14 z-30 -mx-6 -mt-6 px-6 py-3 bg-white rounded-t-xl border-b border-gray-200 backdrop-blur">
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2 shrink-0">
                    <i data-lucide="book-open" class="w-5 h-5 text-primary"></i>
                    <h3 class="text-sm font-bold text-gray-800">표준 취업규칙</h3>
                    <span id="rulesHeadUpdated" class="text-[11px] text-gray-400"></span>
                </div>

                <div class="flex-1"></div>

                <!-- 검색 (읽기 모드) -->
                <div id="rulesSearchWrap" class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg px-3 py-1.5 w-56">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-gray-400 flex-shrink-0"></i>
                    <input id="ruleSearch" type="text" placeholder="문서 검색" class="flex-1 bg-transparent text-xs text-gray-700 placeholder:text-gray-400 outline-none">
                    <button onclick="clearRuleSearch()" id="ruleSearchClear" class="hidden text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-3 h-3"></i></button>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <button id="rulesEditBtn" onclick="rulesEnterEdit()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs text-white bg-primary rounded-lg hover:bg-primary-dark">
                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i> 수정
                    </button>
                    <button id="rulesSaveBtn" onclick="rulesSaveAll()" class="hidden inline-flex items-center gap-1.5 px-3 py-1.5 text-xs text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">
                        <i data-lucide="save" class="w-3.5 h-3.5"></i> 저장
                    </button>
                    <button id="rulesCancelBtn" onclick="rulesCancelEdit()" class="hidden btn btn-secondary btn-sm inline-flex items-center gap-1.5">취소</button>
                    <button onclick="printRules()" class="btn btn-secondary btn-sm inline-flex items-center gap-1.5">
                        <i data-lucide="printer" class="w-3.5 h-3.5"></i> 인쇄
                    </button>
                </div>
            </div>
            <p id="rulesDirtyHint" class="hidden mt-2 text-[11px] text-amber-600">
                <i data-lucide="alert-circle" class="inline w-3 h-3 -mt-0.5"></i>
                수정된 내용이 있습니다. 저장하지 않고 나가면 변경 사항이 사라집니다.
            </p>
        </div>

        <!-- 본문: 좌측 동적 TOC + 우측 문서/에디터 -->
        <div class="grid grid-cols-12 gap-6 mt-5">

            <!-- 좌측: 자동 생성 목차 -->
            <aside id="rulesTocAside" class="col-span-3 hidden lg:block">
                <nav class="sticky top-[7.5rem] bg-white border border-gray-200 rounded-xl p-3 max-h-[calc(100vh-9rem)] overflow-y-auto">
                    <div class="flex items-center justify-between px-1 mb-2">
                        <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">목차</p>
                        <span id="rulesTocCount" class="text-[10px] text-gray-400">0장</span>
                    </div>
                    <ol id="rulesToc" class="space-y-0.5 text-sm"></ol>
                </nav>
            </aside>

            <!-- 우측: 문서 영역 -->
            <section id="rulesContentSection" class="col-span-12 lg:col-span-9 space-y-4">

                <!-- 읽기 모드 -->
                <article id="rulesReadView" class="bg-white border border-gray-200 rounded-xl p-6 lg:p-10">
                    <!-- 인쇄 전용 표지 (화면에서는 숨김) -->
                    <div id="rulesPrintCover" class="hidden">
                        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:80vh;text-align:center;">
                            <p style="font-size:14px;letter-spacing:6px;color:#666;margin-bottom:48px;">주식회사 Zaemit</p>
                            <h1 style="font-size:36px;font-weight:800;letter-spacing:3px;margin:0 0 16px;color:#111;">표준 취업규칙</h1>
                            <div style="width:80px;height:3px;background:#333;margin:24px auto;"></div>
                            <p id="rulesPrintDate" style="font-size:13px;color:#888;margin-top:32px;"></p>
                            <p style="font-size:12px;color:#aaa;margin-top:8px;">본 문서는 근로기준법 제93조에 의거하여 작성된 취업규칙입니다.</p>
                        </div>
                    </div>
                    <div id="rulesDocument" class="text-sm text-gray-700">로딩 중...</div>
                </article>

                <!-- 편집 모드 -->
                <div id="rulesEditView" class="hidden">
                    <div class="bg-white border border-gray-200 rounded-xl p-4 lg:p-6">
                        <div class="flex items-center gap-2 mb-3 text-sm text-gray-500 flex-wrap">
                            <i data-lucide="edit-3" class="w-4 h-4 text-primary"></i>
                            <span>리치 에디터 · 제목(제N장은 <code class="text-primary">H2</code>, 제N조는 <code class="text-primary">H3</code>)·목록·표·정렬을 자유롭게 사용할 수 있습니다.</span>
                        </div>
                        <div id="rulesEditorMount"></div>
                    </div>
                </div>
            </section>
        </div>
