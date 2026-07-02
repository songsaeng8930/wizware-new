<?php
$pageTitle = 'API 설정';
$currentPage = 'groupware';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<!-- 메인 컨텐츠 영역 -->
<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6">

        <!-- 페이지 헤더 -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-lg font-bold text-slate-100">API 설정</h2>
                <p class="text-sm text-slate-400 mt-0.5">외부 서비스 연동에 필요한 API 키를 관리합니다</p>
            </div>
        </div>

        <!-- 연결 상태 요약 카드 -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <!-- bankapi 상태 카드 -->
            <div id="statusCard_bankapi" onclick="scrollToSection('section_bankapi')" class="bg-slate-900 rounded-xl border border-slate-800 p-5 cursor-pointer hover:border-slate-600 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-primary-light border border-primary-light flex items-center justify-center">
                        <i data-lucide="landmark" class="w-5 h-5 text-primary"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-slate-100">Bank API</p>
                        <p class="text-sm text-slate-500">bankapi.co.kr</p>
                    </div>
                    <span id="badge_bankapi" class="px-2.5 py-1 text-sm font-semibold rounded-full bg-slate-800 text-slate-400">확인 중...</span>
                </div>
                <div class="text-sm text-slate-500" id="statusMsg_bankapi">설정을 불러오는 중입니다.</div>
            </div>

            <!-- 오픈뱅킹 (법인) 상태 카드 -->
            <div id="statusCard_ob_corp" onclick="scrollToSection('section_openbanking')" class="bg-slate-900 rounded-xl border border-slate-800 p-5 cursor-pointer hover:border-slate-600 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-primary-light border border-primary-light flex items-center justify-center">
                        <i data-lucide="building-2" class="w-5 h-5 text-primary"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-slate-100">오픈뱅킹 · 법인</p>
                        <p class="text-sm text-slate-500">금융결제원 (법인용 이용기관)</p>
                    </div>
                    <span id="badge_ob_corp" class="px-2.5 py-1 text-sm font-semibold rounded-full bg-slate-800 text-slate-400">확인 중...</span>
                </div>
                <div class="text-sm text-slate-500" id="statusMsg_ob_corp">설정을 불러오는 중입니다.</div>
            </div>

            <!-- 오픈뱅킹 (개인사업자) 상태 카드 -->
            <div id="statusCard_ob_sole" onclick="scrollToSection('section_openbanking')" class="bg-slate-900 rounded-xl border border-slate-800 p-5 cursor-pointer hover:border-slate-600 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-primary-light border border-primary-light flex items-center justify-center">
                        <i data-lucide="user-circle-2" class="w-5 h-5 text-primary"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-slate-100">오픈뱅킹 · 개인사업자</p>
                        <p class="text-sm text-slate-500">금융결제원 (개인사업자 이용기관)</p>
                    </div>
                    <span id="badge_ob_sole" class="px-2.5 py-1 text-sm font-semibold rounded-full bg-slate-800 text-slate-400">확인 중...</span>
                </div>
                <div class="text-sm text-slate-500" id="statusMsg_ob_sole">설정을 불러오는 중입니다.</div>
            </div>
            <!-- AI 서비스 상태 카드 -->
            <div id="statusCard_ai" onclick="scrollToSection('section_ai')" class="bg-slate-900 rounded-xl border border-slate-800 p-5 cursor-pointer hover:border-slate-600 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-violet-500/15 border border-violet-500/20 flex items-center justify-center">
                        <i data-lucide="sparkles" class="w-5 h-5 text-violet-400"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-slate-100">AI 서비스</p>
                        <p class="text-sm text-slate-500" id="aiProviderLabel">미설정</p>
                    </div>
                    <span id="badge_ai" class="px-2.5 py-1 text-sm font-semibold rounded-full bg-slate-800 text-slate-400">확인 중...</span>
                </div>
                <div class="text-sm text-slate-500" id="statusMsg_ai">설정을 불러오는 중입니다.</div>
            </div>
        </div>

        <!-- 금융결제원 오픈뱅킹 설정 섹션 -->
        <div id="section_openbanking" class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden mb-6">
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-800 bg-slate-950">
                <div class="flex items-center gap-2">
                    <i data-lucide="banknote" class="w-4 h-4 text-primary"></i>
                    <span class="text-sm font-semibold text-slate-200">금융결제원 오픈뱅킹 설정</span>
                    <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-primary/15 text-primary">무료 API</span>
                </div>
                <div class="flex items-center gap-2">
                    <label class="flex items-center gap-1.5 text-sm text-slate-300 mr-2">
                        <span class="text-slate-400">환경:</span>
                        <select id="obEnv" class="h-8 px-2 rounded-md bg-slate-900 border border-slate-800 text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30">
                            <option value="test">테스트베드</option>
                            <option value="prod">실운영</option>
                        </select>
                    </label>
                    <button onclick="saveOpenbanking()" id="btnSaveOb" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-sm font-semibold text-white bg-primary rounded-lg hover:opacity-90 transition-opacity">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        모두 저장
                    </button>
                </div>
            </div>

            <div class="p-5 space-y-5">
                <p class="text-sm text-slate-400">
                    법인 / 개인사업자 이용기관을 <span class="text-slate-200 font-semibold">둘 다 등록</span>할 수 있습니다.
                    필요한 쪽만 입력해도 되고, 두 세트 모두 입력 시 계좌 등록 화면에서 선택해 사용합니다.
                </p>

                <!-- 법인 -->
                <div class="rounded-lg border border-slate-800 overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-2.5 bg-slate-950">
                        <div class="flex items-center gap-2">
                            <i data-lucide="building-2" class="w-4 h-4 text-primary"></i>
                            <span class="text-sm font-semibold text-slate-100">법인 이용기관</span>
                        </div>
                        <button onclick="testOpenbanking('corp')" id="btnTestObCorp" class="btn btn-secondary btn-xs">
                            <i data-lucide="wifi" class="w-3.5 h-3.5"></i>
                            연결 테스트
                        </button>
                    </div>
                    <div class="p-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-200 mb-1.5">Client ID</label>
                            <div class="relative">
                                <input type="password" id="obCorpClientId" placeholder="법인 Client ID"
                                       class="w-full px-3.5 py-2.5 text-sm border border-slate-800 rounded-lg focus:ring-2 focus:ring-gray-300/20 focus:border-gray-300 outline-none transition-all pr-10 font-mono">
                                <button type="button" onclick="toggleKeyVisibility('obCorpClientId')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-200 mb-1.5">Client Secret</label>
                            <div class="relative">
                                <input type="password" id="obCorpClientSecret" placeholder="법인 Client Secret"
                                       class="w-full px-3.5 py-2.5 text-sm border border-slate-800 rounded-lg focus:ring-2 focus:ring-gray-300/20 focus:border-gray-300 outline-none transition-all pr-10 font-mono">
                                <button type="button" onclick="toggleKeyVisibility('obCorpClientSecret')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                        <div class="lg:col-span-2">
                            <label class="block text-sm font-medium text-slate-200 mb-1.5">정산계좌(핀테크이용번호) <span class="text-slate-500 text-xs font-normal">이체 사용 시 필수, 조회만 할 경우 생략 가능</span></label>
                            <input type="text" id="obCorpCntrAccount" placeholder="예: 1100000001"
                                   class="w-full px-3.5 py-2.5 text-sm border border-slate-800 rounded-lg focus:ring-2 focus:ring-gray-300/20 focus:border-gray-300 outline-none transition-all font-mono">
                        </div>
                    </div>
                </div>

                <!-- 개인사업자 -->
                <div class="rounded-lg border border-slate-800 overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-2.5 bg-slate-950">
                        <div class="flex items-center gap-2">
                            <i data-lucide="user-circle-2" class="w-4 h-4 text-primary"></i>
                            <span class="text-sm font-semibold text-slate-100">개인사업자 이용기관</span>
                        </div>
                        <button onclick="testOpenbanking('sole')" id="btnTestObSole" class="btn btn-secondary btn-xs">
                            <i data-lucide="wifi" class="w-3.5 h-3.5"></i>
                            연결 테스트
                        </button>
                    </div>
                    <div class="p-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-200 mb-1.5">Client ID</label>
                            <div class="relative">
                                <input type="password" id="obSoleClientId" placeholder="개인사업자 Client ID"
                                       class="w-full px-3.5 py-2.5 text-sm border border-slate-800 rounded-lg focus:ring-2 focus:ring-gray-300/20 focus:border-gray-300 outline-none transition-all pr-10 font-mono">
                                <button type="button" onclick="toggleKeyVisibility('obSoleClientId')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-200 mb-1.5">Client Secret</label>
                            <div class="relative">
                                <input type="password" id="obSoleClientSecret" placeholder="개인사업자 Client Secret"
                                       class="w-full px-3.5 py-2.5 text-sm border border-slate-800 rounded-lg focus:ring-2 focus:ring-gray-300/20 focus:border-gray-300 outline-none transition-all pr-10 font-mono">
                                <button type="button" onclick="toggleKeyVisibility('obSoleClientSecret')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                        <div class="lg:col-span-2">
                            <label class="block text-sm font-medium text-slate-200 mb-1.5">정산계좌(핀테크이용번호) <span class="text-slate-500 text-xs font-normal">이체 사용 시 필수</span></label>
                            <input type="text" id="obSoleCntrAccount" placeholder="예: 1100000002"
                                   class="w-full px-3.5 py-2.5 text-sm border border-slate-800 rounded-lg focus:ring-2 focus:ring-gray-300/20 focus:border-gray-300 outline-none transition-all font-mono">
                        </div>
                    </div>
                </div>

                <!-- 테스트 결과 -->
                <div id="obTestResult" class="hidden rounded-lg p-4"></div>

                <!-- 정보 -->
                <div class="bg-slate-950 rounded-lg p-4">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <p class="text-slate-500 mb-1">개발자센터</p>
                            <a href="https://developers.kftc.or.kr/dev" target="_blank" rel="noopener" class="text-primary hover:underline font-mono text-xs">developers.kftc.or.kr</a>
                        </div>
                        <div>
                            <p class="text-slate-500 mb-1">테스트베드</p>
                            <a href="https://developers.openbanking.or.kr" target="_blank" rel="noopener" class="text-primary hover:underline font-mono text-xs">developers.openbanking.or.kr</a>
                        </div>
                        <div>
                            <p class="text-slate-500 mb-1">인증 방식</p>
                            <p class="text-slate-300">OAuth 2.0</p>
                        </div>
                        <div>
                            <p class="text-slate-500 mb-1">비용</p>
                            <p class="text-slate-300">테스트 무료 · 상용 건당 과금</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bank API 설정 섹션 -->
        <div id="section_bankapi" class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden mb-6">
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-800 bg-slate-950">
                <div class="flex items-center gap-2">
                    <i data-lucide="landmark" class="w-4 h-4 text-primary"></i>
                    <span class="text-sm font-semibold text-slate-200">Bank API 설정</span>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="testBankapi()" id="btnTestBankapi" class="btn btn-secondary btn-sm">
                        <i data-lucide="wifi" class="w-3.5 h-3.5"></i>
                        연결 테스트
                    </button>
                    <button onclick="saveBankapi()" id="btnSaveBankapi" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-sm font-semibold text-white bg-primary rounded-lg hover:opacity-90 transition-opacity">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        저장
                    </button>
                </div>
            </div>

            <div class="p-5">
                <!-- API Key 입력 -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
                    <div>
                        <label class="block text-sm font-medium text-slate-200 mb-1.5">API Key <span class="text-amber-500">*</span></label>
                        <div class="relative">
                            <input type="password" id="bankapiKey" placeholder="bankapi.co.kr에서 발급받은 API Key"
                                   class="w-full px-3.5 py-2.5 text-sm border border-slate-800 rounded-lg focus:ring-2 focus:ring-gray-300/20 focus:border-gray-300 outline-none transition-all pr-10 font-mono">
                            <button type="button" onclick="toggleKeyVisibility('bankapiKey')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-200 mb-1.5">Secret Key <span class="text-amber-500">*</span></label>
                        <div class="relative">
                            <input type="password" id="bankapiSecret" placeholder="bankapi.co.kr에서 발급받은 Secret Key"
                                   class="w-full px-3.5 py-2.5 text-sm border border-slate-800 rounded-lg focus:ring-2 focus:ring-gray-300/20 focus:border-gray-300 outline-none transition-all pr-10 font-mono">
                            <button type="button" onclick="toggleKeyVisibility('bankapiSecret')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 은행 연동 모드 -->
                <div class="mb-5">
                    <label class="block text-sm font-medium text-slate-200 mb-1.5">연동 모드</label>
                    <select id="bankProviderMode" class="w-full lg:w-72 px-3.5 py-2.5 text-sm border border-slate-800 rounded-lg focus:ring-2 focus:ring-gray-300/20 focus:border-gray-300 outline-none">
                        <option value="auto">자동 (키 있으면 실연동 · 없으면 샌드박스)</option>
                        <option value="real">실연동 강제 (bankapi.co.kr)</option>
                        <option value="mock">샌드박스 강제 (키 없이 데모/개발)</option>
                    </select>
                    <p class="text-sm text-slate-500 mt-1.5">샌드박스는 실제 은행 호출 없이 가짜 거래내역으로 등록→조회→동기화→AI분류 전 과정을 시연합니다.</p>
                </div>

                <!-- 테스트 결과 영역 -->
                <div id="testResult" class="hidden rounded-lg p-4 mb-5">
                </div>

                <!-- 설정 정보 -->
                <div class="bg-slate-950 rounded-lg p-4">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <p class="text-slate-500 mb-1">API Endpoint</p>
                            <p class="font-mono text-slate-300">api.bankapi.co.kr</p>
                        </div>
                        <div>
                            <p class="text-slate-500 mb-1">인증 방식</p>
                            <p class="text-slate-300">Bearer Token</p>
                        </div>
                        <div>
                            <p class="text-slate-500 mb-1">지원 은행</p>
                            <p class="text-slate-300">국민, 농협, 우리</p>
                        </div>
                        <div>
                            <p class="text-slate-500 mb-1">마지막 저장</p>
                            <p class="text-slate-300" id="lastSaved">-</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- AI 서비스 설정 섹션 -->
        <div id="section_ai" class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden mb-6">
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-800 bg-slate-950">
                <div class="flex items-center gap-2">
                    <i data-lucide="sparkles" class="w-4 h-4 text-violet-400"></i>
                    <span class="text-sm font-semibold text-slate-200">AI 서비스 설정</span>
                    <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-violet-500/15 text-violet-300">통장분류 · 매칭 · 리포트</span>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="saveAiSettings()" id="btnSaveAi" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-sm font-semibold text-white bg-violet-600 rounded-lg hover:opacity-90 transition-opacity">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        저장
                    </button>
                </div>
            </div>

            <div class="p-5 space-y-5">
                <!-- 기본 프로바이더 / 모델 선택 -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-slate-200 mb-1.5">기본 AI 프로바이더</label>
                        <select id="aiProvider" onchange="onAiProviderChange()"
                                class="w-full px-3.5 py-2.5 text-sm border border-slate-800 rounded-lg focus:ring-2 focus:ring-gray-300/20 focus:border-gray-300 outline-none transition-all">
                            <option value="">선택 안 함</option>
                            <option value="openai">OpenAI (GPT)</option>
                            <option value="anthropic">Anthropic (Claude)</option>
                            <option value="google">Google (Gemini)</option>
                            <option value="bedrock">AWS Bedrock</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-200 mb-1.5">기본 모델</label>
                        <select id="aiModel"
                                class="w-full px-3.5 py-2.5 text-sm border border-slate-800 rounded-lg focus:ring-2 focus:ring-gray-300/20 focus:border-gray-300 outline-none transition-all">
                            <option value="">프로바이더를 먼저 선택하세요</option>
                        </select>
                    </div>
                </div>

                <!-- OpenAI -->
                <div class="rounded-lg border border-slate-800 overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-2.5 bg-slate-950">
                        <div class="flex items-center gap-2">
                            <span class="w-5 h-5 rounded bg-emerald-500/15 flex items-center justify-center text-xs font-bold text-emerald-400">G</span>
                            <span class="text-sm font-semibold text-slate-100">OpenAI</span>
                        </div>
                        <button onclick="testAiProvider('openai')" class="ai-test-btn inline-flex items-center gap-1.5 px-3 py-1 text-sm font-medium text-violet-400 border border-violet-500/20 rounded-md hover:bg-violet-500/10 transition-colors">
                            <i data-lucide="wifi" class="w-3.5 h-3.5"></i>
                            연결 테스트
                        </button>
                    </div>
                    <div class="p-4">
                        <label class="block text-sm font-medium text-slate-200 mb-1.5">API Key <span class="text-amber-500">*</span></label>
                        <div class="relative">
                            <input type="password" id="openaiApiKey" placeholder="sk-..."
                                   class="w-full px-3.5 py-2.5 text-sm border border-slate-800 rounded-lg focus:ring-2 focus:ring-gray-300/20 focus:border-gray-300 outline-none transition-all pr-10 font-mono">
                            <button type="button" onclick="toggleKeyVisibility('openaiApiKey')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Anthropic -->
                <div class="rounded-lg border border-slate-800 overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-2.5 bg-slate-950">
                        <div class="flex items-center gap-2">
                            <span class="w-5 h-5 rounded bg-orange-500/15 flex items-center justify-center text-xs font-bold text-orange-400">A</span>
                            <span class="text-sm font-semibold text-slate-100">Anthropic</span>
                        </div>
                        <button onclick="testAiProvider('anthropic')" class="ai-test-btn inline-flex items-center gap-1.5 px-3 py-1 text-sm font-medium text-violet-400 border border-violet-500/20 rounded-md hover:bg-violet-500/10 transition-colors">
                            <i data-lucide="wifi" class="w-3.5 h-3.5"></i>
                            연결 테스트
                        </button>
                    </div>
                    <div class="p-4">
                        <label class="block text-sm font-medium text-slate-200 mb-1.5">API Key <span class="text-amber-500">*</span></label>
                        <div class="relative">
                            <input type="password" id="anthropicApiKey" placeholder="sk-ant-..."
                                   class="w-full px-3.5 py-2.5 text-sm border border-slate-800 rounded-lg focus:ring-2 focus:ring-gray-300/20 focus:border-gray-300 outline-none transition-all pr-10 font-mono">
                            <button type="button" onclick="toggleKeyVisibility('anthropicApiKey')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Google Gemini -->
                <div class="rounded-lg border border-slate-800 overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-2.5 bg-slate-950">
                        <div class="flex items-center gap-2">
                            <span class="w-5 h-5 rounded bg-blue-500/15 flex items-center justify-center text-xs font-bold text-blue-400">G</span>
                            <span class="text-sm font-semibold text-slate-100">Google Gemini</span>
                        </div>
                        <button onclick="testAiProvider('google')" class="ai-test-btn inline-flex items-center gap-1.5 px-3 py-1 text-sm font-medium text-violet-400 border border-violet-500/20 rounded-md hover:bg-violet-500/10 transition-colors">
                            <i data-lucide="wifi" class="w-3.5 h-3.5"></i>
                            연결 테스트
                        </button>
                    </div>
                    <div class="p-4">
                        <label class="block text-sm font-medium text-slate-200 mb-1.5">API Key <span class="text-amber-500">*</span></label>
                        <div class="relative">
                            <input type="password" id="googleAiApiKey" placeholder="AIza..."
                                   class="w-full px-3.5 py-2.5 text-sm border border-slate-800 rounded-lg focus:ring-2 focus:ring-gray-300/20 focus:border-gray-300 outline-none transition-all pr-10 font-mono">
                            <button type="button" onclick="toggleKeyVisibility('googleAiApiKey')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- AWS Bedrock -->
                <div class="rounded-lg border border-slate-800 overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-2.5 bg-slate-950">
                        <div class="flex items-center gap-2">
                            <span class="w-5 h-5 rounded bg-amber-500/15 flex items-center justify-center text-xs font-bold text-amber-400">B</span>
                            <span class="text-sm font-semibold text-slate-100">AWS Bedrock</span>
                        </div>
                        <button onclick="testAiProvider('bedrock')" class="ai-test-btn inline-flex items-center gap-1.5 px-3 py-1 text-sm font-medium text-violet-400 border border-violet-500/20 rounded-md hover:bg-violet-500/10 transition-colors">
                            <i data-lucide="wifi" class="w-3.5 h-3.5"></i>
                            연결 테스트
                        </button>
                    </div>
                    <div class="p-4 grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-200 mb-1.5">Access Key ID <span class="text-amber-500">*</span></label>
                            <div class="relative">
                                <input type="password" id="awsAccessKeyId" placeholder="AKIA..."
                                       class="w-full px-3.5 py-2.5 text-sm border border-slate-800 rounded-lg focus:ring-2 focus:ring-gray-300/20 focus:border-gray-300 outline-none transition-all pr-10 font-mono">
                                <button type="button" onclick="toggleKeyVisibility('awsAccessKeyId')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-200 mb-1.5">Secret Access Key <span class="text-amber-500">*</span></label>
                            <div class="relative">
                                <input type="password" id="awsSecretAccessKey" placeholder="Secret Key"
                                       class="w-full px-3.5 py-2.5 text-sm border border-slate-800 rounded-lg focus:ring-2 focus:ring-gray-300/20 focus:border-gray-300 outline-none transition-all pr-10 font-mono">
                                <button type="button" onclick="toggleKeyVisibility('awsSecretAccessKey')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-200 mb-1.5">Region</label>
                            <select id="awsRegion"
                                    class="w-full px-3.5 py-2.5 text-sm border border-slate-800 rounded-lg focus:ring-2 focus:ring-gray-300/20 focus:border-gray-300 outline-none transition-all">
                                <option value="us-east-1">US East (N. Virginia)</option>
                                <option value="us-west-2">US West (Oregon)</option>
                                <option value="ap-northeast-1">Asia Pacific (Tokyo)</option>
                                <option value="ap-northeast-2">Asia Pacific (Seoul)</option>
                                <option value="eu-west-1">Europe (Ireland)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 테스트 결과 -->
                <div id="aiTestResult" class="hidden rounded-lg p-4"></div>
            </div>
        </div>

        <!-- 설정 가이드 -->
        <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden mb-6">
            <div class="flex items-center gap-2 px-5 py-3.5 border-b border-slate-800 bg-slate-950">
                <i data-lucide="book-open" class="w-4 h-4 text-primary"></i>
                <span class="text-sm font-semibold text-slate-200">Bank API 설정 가이드</span>
            </div>

            <div class="p-5">
                <!-- 스텝 가이드 -->
                <div class="space-y-0">
                    <!-- Step 1 -->
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center text-sm font-bold flex-shrink-0">1</div>
                            <div class="w-0.5 flex-1 bg-slate-700 my-1"></div>
                        </div>
                        <div class="pb-6 flex-1">
                            <h4 class="text-sm font-semibold text-slate-100 mb-1">bankapi.co.kr 회원가입</h4>
                            <p class="text-sm text-slate-400 mb-3">bankapi.co.kr에 접속하여 회원가입을 진행합니다.</p>
                            <a href="https://bankapi.co.kr" target="_blank" rel="noopener noreferrer"
                               class="btn btn-secondary btn-sm">
                                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                bankapi.co.kr 바로가기
                            </a>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center text-sm font-bold flex-shrink-0">2</div>
                            <div class="w-0.5 flex-1 bg-slate-700 my-1"></div>
                        </div>
                        <div class="pb-6 flex-1">
                            <h4 class="text-sm font-semibold text-slate-100 mb-1">API Key 발급</h4>
                            <p class="text-sm text-slate-400 mb-2">로그인 후 대시보드에서 API Key와 Secret Key를 발급받습니다.</p>
                            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                                <div class="flex items-start gap-2">
                                    <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5"></i>
                                    <p class="text-sm text-amber-700">Secret Key는 발급 시 한 번만 표시됩니다. 반드시 안전한 곳에 보관하세요.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center text-sm font-bold flex-shrink-0">3</div>
                            <div class="w-0.5 flex-1 bg-slate-700 my-1"></div>
                        </div>
                        <div class="pb-6 flex-1">
                            <h4 class="text-sm font-semibold text-slate-100 mb-1">키 입력 및 저장</h4>
                            <p class="text-sm text-slate-400">위의 입력란에 발급받은 API Key와 Secret Key를 입력하고 "저장" 버튼을 클릭합니다.</p>
                        </div>
                    </div>

                    <!-- Step 4 -->
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center text-sm font-bold flex-shrink-0">4</div>
                            <div class="w-0.5 flex-1 bg-slate-700 my-1"></div>
                        </div>
                        <div class="pb-6 flex-1">
                            <h4 class="text-sm font-semibold text-slate-100 mb-1">연결 테스트</h4>
                            <p class="text-sm text-slate-400">"연결 테스트" 버튼을 클릭하여 API 연결이 정상적인지 확인합니다. 성공 시 계좌 조회 기능을 바로 사용할 수 있습니다.</p>
                        </div>
                    </div>

                    <!-- Step 5 -->
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center text-sm font-bold flex-shrink-0">5</div>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-semibold text-slate-100 mb-1">계좌 등록</h4>
                            <p class="text-sm text-slate-400 mb-2">연결 확인 후, <strong>재무관리 > 계좌관리</strong> 페이지에서 조회할 계좌를 등록합니다.</p>
                            <a href="<?= $basePath ?>/pages/acct_bank.php" class="btn btn-secondary btn-sm">
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                계좌관리 페이지로 이동
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 요금제 안내 -->
        <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
            <div class="flex items-center gap-2 px-5 py-3.5 border-b border-slate-800 bg-slate-950">
                <i data-lucide="credit-card" class="w-4 h-4 text-primary"></i>
                <span class="text-sm font-semibold text-slate-200">bankapi.co.kr 요금제 안내</span>
            </div>

            <div class="p-5">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Free -->
                    <div class="rounded-xl border border-slate-800 p-5 hover:border-gray-400 transition-all">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-bold text-slate-100">Free</h4>
                            <span class="text-lg font-bold text-slate-100">무료</span>
                        </div>
                        <ul class="space-y-2 text-sm text-slate-400">
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-amber-500"></i> 분당 10건 요청</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-amber-500"></i> 월 500건 거래내역</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-amber-500"></i> 계좌 1개 등록</li>
                        </ul>
                    </div>

                    <!-- Starter -->
                    <div class="rounded-xl border-2 border-primary/30 bg-primary/[0.02] p-5 relative">
                        <div class="absolute -top-2.5 right-4">
                            <span class="px-2.5 py-0.5 bg-primary text-white text-sm font-bold rounded-full">추천</span>
                        </div>
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-bold text-slate-100">Starter</h4>
                            <div class="text-right">
                                <span class="text-lg font-bold text-primary">39,000</span>
                                <span class="text-sm text-slate-500">원/월</span>
                            </div>
                        </div>
                        <ul class="space-y-2 text-sm text-slate-400">
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-primary"></i> 분당 30건 요청</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-primary"></i> 월 5,000건 거래내역</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-primary"></i> 계좌 5개 등록</li>
                        </ul>
                    </div>

                    <!-- Business -->
                    <div class="rounded-xl border border-slate-800 p-5 hover:border-gray-400 transition-all">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-bold text-slate-100">Business</h4>
                            <div class="text-right">
                                <span class="text-lg font-bold text-slate-100">99,000</span>
                                <span class="text-sm text-slate-500">원/월</span>
                            </div>
                        </div>
                        <ul class="space-y-2 text-sm text-slate-400">
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-amber-500"></i> 분당 100건 요청</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-amber-500"></i> 월 30,000건 거래내역</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-amber-500"></i> 계좌 20개 등록</li>
                        </ul>
                    </div>
                </div>

                <p class="mt-4 text-sm text-slate-500 text-center">* 요금은 bankapi.co.kr 기준이며, 변동될 수 있습니다. 정확한 요금은 공식 사이트에서 확인하세요.</p>
            </div>
        </div>

    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const basePath = '<?= $basePath ?>';
    loadSettings();
});

function loadSettings() {
    const basePath = getBasePath();
    fetch(`${basePath}/api/settings.php?action=load`)
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;
            const d = res.data;

            // bankapi.co.kr (기존)
            if (d.has_bankapi_key) {
                document.getElementById('bankapiKey').value = d.bankapi_key;
                document.getElementById('bankapiKey').placeholder = '저장된 키가 있습니다 (변경하려면 새 키 입력)';
            }
            if (d.bank_provider_mode) {
                document.getElementById('bankProviderMode').value = d.bank_provider_mode;
            }
            if (d.has_bankapi_secret) {
                document.getElementById('bankapiSecret').value = d.bankapi_secret;
                document.getElementById('bankapiSecret').placeholder = '저장된 키가 있습니다 (변경하려면 새 키 입력)';
            }

            // 금융결제원 오픈뱅킹 · 법인/개인사업자 각각
            const obFields = [
                ['obCorpClientId',     'openbanking_corp_client_id'],
                ['obCorpClientSecret', 'openbanking_corp_client_secret'],
                ['obCorpCntrAccount',  'openbanking_corp_cntr_account'],
                ['obSoleClientId',     'openbanking_sole_client_id'],
                ['obSoleClientSecret', 'openbanking_sole_client_secret'],
                ['obSoleCntrAccount',  'openbanking_sole_cntr_account'],
            ];
            obFields.forEach(([domId, dataKey]) => {
                const el = document.getElementById(domId);
                if (!el || !d[dataKey]) return;
                el.value = d[dataKey];
                if (el.type === 'password') {
                    el.placeholder = '저장된 값이 있습니다 (변경하려면 새 값 입력)';
                }
            });
            // 환경(test|prod)
            if (d.openbanking_env && document.getElementById('obEnv')) {
                document.getElementById('obEnv').value = d.openbanking_env;
            }

            // 마지막 저장 시간
            if (d.updated_at) {
                document.getElementById('lastSaved').textContent = d.updated_at;
            }

            // 상태 배지 업데이트
            updateStatusBadge(d.has_bankapi_key && d.has_bankapi_secret);
            updateObStatusBadge('corp', !!d.has_openbanking_corp);
            updateObStatusBadge('sole', !!d.has_openbanking_sole);

            // AI 설정 로드
            loadAiFields(d);
        })
        .catch(() => {
            updateStatusBadge(false, '로드 실패');
            updateObStatusBadge('corp', false, '로드 실패');
            updateObStatusBadge('sole', false, '로드 실패');
        });
}

function updateObStatusBadge(entityType, configured, errorMsg) {
    const badge = document.getElementById(`badge_ob_${entityType}`);
    const msg = document.getElementById(`statusMsg_ob_${entityType}`);
    if (!badge || !msg) return;
    if (errorMsg) {
        badge.className = 'px-2.5 py-1 text-sm font-semibold rounded-full bg-amber-50 text-amber-700';
        badge.textContent = '오류';
        msg.textContent = errorMsg;
    } else if (configured) {
        badge.className = 'px-2.5 py-1 text-sm font-semibold rounded-full bg-emerald-500/15 text-emerald-300';
        badge.textContent = '설정 완료';
        msg.textContent = '이용기관 키가 등록되어 있습니다. 연결 테스트로 확인하세요.';
    } else {
        badge.className = 'px-2.5 py-1 text-sm font-semibold rounded-full bg-slate-800 text-slate-400';
        badge.textContent = '미설정';
        msg.textContent = '이용기관 키를 아직 등록하지 않았습니다.';
    }
}

function saveOpenbanking() {
    const basePath = getBasePath();
    const btn = document.getElementById('btnSaveOb');
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i> 저장 중...';

    const payload = {
        openbanking_env:                 document.getElementById('obEnv').value,
        openbanking_corp_client_id:      document.getElementById('obCorpClientId').value.trim(),
        openbanking_corp_client_secret:  document.getElementById('obCorpClientSecret').value.trim(),
        openbanking_corp_cntr_account:   document.getElementById('obCorpCntrAccount').value.trim(),
        openbanking_sole_client_id:      document.getElementById('obSoleClientId').value.trim(),
        openbanking_sole_client_secret:  document.getElementById('obSoleClientSecret').value.trim(),
        openbanking_sole_cntr_account:   document.getElementById('obSoleCntrAccount').value.trim(),
    };

    fetch(`${basePath}/api/settings.php?action=save`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
        showObTestResult(res.success ? 'success' : 'error', res.message);
        if (res.success) loadSettings();
    })
    .catch(() => showObTestResult('error', '저장 요청 중 오류가 발생했습니다.'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="check" class="w-3.5 h-3.5"></i> 모두 저장';
        if (window.lucide) lucide.createIcons();
    });
}

function testOpenbanking(entityType) {
    const basePath = getBasePath();
    const btnId = entityType === 'corp' ? 'btnTestObCorp' : 'btnTestObSole';
    const btn = document.getElementById(btnId);
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i> 테스트 중...';

    fetch(`${basePath}/api/settings.php?action=test_openbanking`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ entity_type: entityType })
    })
    .then(r => r.json())
    .then(res => {
        showObTestResult(res.success ? 'success' : 'error',
            (entityType === 'corp' ? '[법인] ' : '[개인사업자] ') + res.message);
        if (res.success) {
            const badge = document.getElementById(`badge_ob_${entityType}`);
            badge.className = 'px-2.5 py-1 text-sm font-semibold rounded-full bg-emerald-500/15 text-emerald-300';
            badge.textContent = '연결됨';
            document.getElementById(`statusMsg_ob_${entityType}`).textContent = '연결이 정상적으로 확인되었습니다.';
        }
    })
    .catch(() => showObTestResult('error', '연결 테스트 중 오류가 발생했습니다.'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="wifi" class="w-3.5 h-3.5"></i> 연결 테스트';
        if (window.lucide) lucide.createIcons();
    });
}

function showObTestResult(type, message) {
    const el = document.getElementById('obTestResult');
    if (!el) return;
    el.classList.remove('hidden');
    const icon = type === 'success' ? 'check-circle' : 'alert-circle';
    const cls  = type === 'success'
        ? 'bg-emerald-500/10 border border-emerald-500/30 text-emerald-300'
        : 'bg-amber-500/10 border border-amber-500/30 text-amber-300';
    el.className = 'rounded-lg p-4 ' + cls;
    el.innerHTML = `<div class="flex items-center gap-2"><i data-lucide="${icon}" class="w-4 h-4"></i><p class="text-sm font-medium">${esc(message)}</p></div>`;
    if (window.lucide) lucide.createIcons();
}

function updateStatusBadge(configured, errorMsg) {
    const badge = document.getElementById('badge_bankapi');
    const msg = document.getElementById('statusMsg_bankapi');

    if (errorMsg) {
        badge.className = 'px-2.5 py-1 text-sm font-semibold rounded-full bg-amber-50 text-amber-700';
        badge.textContent = '오류';
        msg.textContent = errorMsg;
    } else if (configured) {
        badge.className = 'px-2.5 py-1 text-sm font-semibold rounded-full bg-amber-50 text-amber-700';
        badge.textContent = '설정 완료';
        msg.textContent = 'API 키가 등록되어 있습니다. 연결 테스트로 확인하세요.';
    } else {
        badge.className = 'px-2.5 py-1 text-sm font-semibold rounded-full bg-amber-50 text-amber-600';
        badge.textContent = '미설정';
        msg.textContent = 'API 키를 아직 등록하지 않았습니다.';
    }
}

function saveBankapi() {
    const key = document.getElementById('bankapiKey').value.trim();
    const secret = document.getElementById('bankapiSecret').value.trim();
    const basePath = getBasePath();

    const btn = document.getElementById('btnSaveBankapi');
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i> 저장 중...';

    fetch(`${basePath}/api/settings.php?action=save`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ bankapi_key: key, bankapi_secret: secret, bank_provider_mode: document.getElementById('bankProviderMode').value })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showTestResult('success', res.message);
            loadSettings();
        } else {
            showTestResult('error', res.message);
        }
    })
    .catch(err => {
        showTestResult('error', '저장 요청 중 오류가 발생했습니다.');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="check" class="w-3.5 h-3.5"></i> 저장';
        if (window.lucide) lucide.createIcons();
    });
}

function testBankapi() {
    const basePath = getBasePath();
    const btn = document.getElementById('btnTestBankapi');
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i> 테스트 중...';

    fetch(`${basePath}/api/settings.php?action=test_bankapi`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showTestResult('success', res.message + (res.data?.accounts !== undefined ? ` (등록된 계좌: ${res.data.accounts}개)` : ''));
            // 상태 배지를 연결됨으로 업데이트
            const badge = document.getElementById('badge_bankapi');
            badge.className = 'px-2.5 py-1 text-sm font-semibold rounded-full bg-amber-50 text-amber-700';
            badge.textContent = '연결됨';
            document.getElementById('statusMsg_bankapi').textContent = '연결이 정상적으로 확인되었습니다.';
        } else {
            showTestResult('error', res.message);
            const badge = document.getElementById('badge_bankapi');
            badge.className = 'px-2.5 py-1 text-sm font-semibold rounded-full bg-amber-50 text-amber-700';
            badge.textContent = '연결 실패';
            document.getElementById('statusMsg_bankapi').textContent = res.message;
        }
    })
    .catch(err => {
        showTestResult('error', '연결 테스트 중 오류가 발생했습니다.');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="wifi" class="w-3.5 h-3.5"></i> 연결 테스트';
        if (window.lucide) lucide.createIcons();
    });
}

function showTestResult(type, message) {
    const el = document.getElementById('testResult');
    el.classList.remove('hidden');

    if (type === 'success') {
        el.className = 'rounded-lg p-4 mb-5 bg-amber-50 border border-amber-200';
        el.innerHTML = `
            <div class="flex items-center gap-2">
                <i data-lucide="check-circle" class="w-4 h-4 text-amber-700"></i>
                <p class="text-sm font-medium text-amber-700">${esc(message)}</p>
            </div>`;
    } else {
        el.className = 'rounded-lg p-4 mb-5 bg-amber-50 border border-amber-200';
        el.innerHTML = `
            <div class="flex items-center gap-2">
                <i data-lucide="alert-circle" class="w-4 h-4 text-amber-700"></i>
                <p class="text-sm font-medium text-amber-700">${esc(message)}</p>
            </div>`;
    }

    if (window.lucide) lucide.createIcons();
}

function toggleKeyVisibility(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.nextElementSibling.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.setAttribute('data-lucide', 'eye-off');
    } else {
        input.type = 'password';
        icon.setAttribute('data-lucide', 'eye');
    }
    if (window.lucide) lucide.createIcons();
}

function getBasePath() {
    return '<?= $basePath ?>';
}

function esc(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

// ─── AI 서비스 설정 ───

const AI_MODELS_MAP = {
    openai: [
        {id: 'gpt-4.1',      name: 'GPT-4.1'},
        {id: 'gpt-4.1-mini', name: 'GPT-4.1 Mini'},
        {id: 'gpt-4.1-nano', name: 'GPT-4.1 Nano'},
        {id: 'gpt-4o',       name: 'GPT-4o'},
        {id: 'gpt-4o-mini',  name: 'GPT-4o Mini'},
        {id: 'o3-mini',      name: 'o3-mini'},
    ],
    anthropic: [
        {id: 'claude-opus-4-20250514',    name: 'Claude Opus 4'},
        {id: 'claude-sonnet-4-20250514',  name: 'Claude Sonnet 4'},
        {id: 'claude-haiku-3-5-20241022', name: 'Claude 3.5 Haiku'},
    ],
    google: [
        {id: 'gemini-2.5-pro',    name: 'Gemini 2.5 Pro'},
        {id: 'gemini-2.5-flash',  name: 'Gemini 2.5 Flash'},
        {id: 'gemini-2.0-flash',  name: 'Gemini 2.0 Flash'},
    ],
    bedrock: [
        {id: 'us.anthropic.claude-opus-4-20250514-v1:0',    name: 'Claude Opus 4 (Bedrock)'},
        {id: 'us.anthropic.claude-sonnet-4-20250514-v1:0',  name: 'Claude Sonnet 4 (Bedrock)'},
        {id: 'us.anthropic.claude-haiku-3-5-20241022-v1:0', name: 'Claude 3.5 Haiku (Bedrock)'},
    ],
};

const AI_PROVIDER_NAMES = {
    openai: 'OpenAI', anthropic: 'Anthropic', google: 'Google Gemini', bedrock: 'AWS Bedrock'
};

function loadAiFields(d) {
    const aiKeyFields = [
        ['openaiApiKey',       'openai_api_key'],
        ['anthropicApiKey',    'anthropic_api_key'],
        ['googleAiApiKey',     'google_ai_api_key'],
        ['awsAccessKeyId',     'aws_access_key_id'],
        ['awsSecretAccessKey', 'aws_secret_access_key'],
    ];
    aiKeyFields.forEach(([domId, dataKey]) => {
        const el = document.getElementById(domId);
        if (!el || !d[dataKey]) return;
        el.value = d[dataKey];
        el.placeholder = '저장된 키가 있습니다 (변경하려면 새 키 입력)';
    });

    if (d.aws_region) {
        document.getElementById('awsRegion').value = d.aws_region;
    }

    if (d.ai_provider) {
        document.getElementById('aiProvider').value = d.ai_provider;
        onAiProviderChange();
        if (d.ai_model) {
            document.getElementById('aiModel').value = d.ai_model;
        }
    }

    const hasAny = d.has_openai || d.has_anthropic || d.has_google_ai || d.has_bedrock;
    updateAiStatusBadge(hasAny, d.ai_provider);
}

function onAiProviderChange() {
    const provider = document.getElementById('aiProvider').value;
    const modelSelect = document.getElementById('aiModel');
    modelSelect.innerHTML = '';

    const models = AI_MODELS_MAP[provider] || [];
    if (!models.length) {
        modelSelect.innerHTML = '<option value="">프로바이더를 먼저 선택하세요</option>';
        return;
    }
    models.forEach(m => {
        const opt = document.createElement('option');
        opt.value = m.id;
        opt.textContent = m.name;
        modelSelect.appendChild(opt);
    });
}

function updateAiStatusBadge(configured, provider) {
    const badge = document.getElementById('badge_ai');
    const msg = document.getElementById('statusMsg_ai');
    const label = document.getElementById('aiProviderLabel');

    if (configured && provider) {
        badge.className = 'px-2.5 py-1 text-sm font-semibold rounded-full bg-violet-500/15 text-violet-300';
        badge.textContent = '설정 완료';
        msg.textContent = '연결 테스트로 확인하세요.';
        label.textContent = AI_PROVIDER_NAMES[provider] || provider;
    } else if (configured) {
        badge.className = 'px-2.5 py-1 text-sm font-semibold rounded-full bg-violet-500/15 text-violet-300';
        badge.textContent = '키 등록됨';
        msg.textContent = '기본 프로바이더를 선택하세요.';
        label.textContent = '프로바이더 미선택';
    } else {
        badge.className = 'px-2.5 py-1 text-sm font-semibold rounded-full bg-slate-800 text-slate-400';
        badge.textContent = '미설정';
        msg.textContent = 'AI API 키를 등록하지 않았습니다.';
        label.textContent = '미설정';
    }
}

function saveAiSettings() {
    const basePath = getBasePath();
    const btn = document.getElementById('btnSaveAi');
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i> 저장 중...';

    const payload = {
        ai_provider:          document.getElementById('aiProvider').value,
        ai_model:             document.getElementById('aiModel').value,
        openai_api_key:       document.getElementById('openaiApiKey').value.trim(),
        anthropic_api_key:    document.getElementById('anthropicApiKey').value.trim(),
        google_ai_api_key:    document.getElementById('googleAiApiKey').value.trim(),
        aws_access_key_id:    document.getElementById('awsAccessKeyId').value.trim(),
        aws_secret_access_key:document.getElementById('awsSecretAccessKey').value.trim(),
        aws_region:           document.getElementById('awsRegion').value,
    };

    fetch(`${basePath}/api/settings.php?action=save`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
        showAiTestResult(res.success ? 'success' : 'error', res.message);
        if (res.success) loadSettings();
    })
    .catch(() => showAiTestResult('error', '저장 요청 중 오류가 발생했습니다.'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="check" class="w-3.5 h-3.5"></i> 저장';
        if (window.lucide) lucide.createIcons();
    });
}

function testAiProvider(provider) {
    const basePath = getBasePath();
    const btn = event.currentTarget;
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i> 테스트 중...';

    fetch(`${basePath}/api/settings.php?action=test_ai`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ provider })
    })
    .then(r => r.json())
    .then(res => {
        const label = AI_PROVIDER_NAMES[provider] || provider;
        showAiTestResult(res.success ? 'success' : 'error', `[${label}] ${res.message}`);
        if (res.success) {
            updateAiStatusBadge(true, document.getElementById('aiProvider').value);
        }
    })
    .catch(() => showAiTestResult('error', '연결 테스트 중 오류가 발생했습니다.'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="wifi" class="w-3.5 h-3.5"></i> 연결 테스트';
        if (window.lucide) lucide.createIcons();
    });
}

function scrollToSection(sectionId) {
    const el = document.getElementById(sectionId);
    if (!el) return;
    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    el.style.transition = 'box-shadow 0.3s';
    el.style.boxShadow = '0 0 0 2px rgba(0,0,0,0.04)';
    setTimeout(() => { el.style.boxShadow = ''; }, 1500);
}

function showAiTestResult(type, message) {
    const el = document.getElementById('aiTestResult');
    if (!el) return;
    el.classList.remove('hidden');
    const icon = type === 'success' ? 'check-circle' : 'alert-circle';
    const cls = type === 'success'
        ? 'bg-emerald-500/10 border border-emerald-500/30 text-emerald-300'
        : 'bg-amber-500/10 border border-amber-500/30 text-amber-300';
    el.className = 'rounded-lg p-4 ' + cls;
    el.innerHTML = `<div class="flex items-center gap-2"><i data-lucide="${icon}" class="w-4 h-4"></i><p class="text-sm font-medium">${esc(message)}</p></div>`;
    if (window.lucide) lucide.createIcons();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
