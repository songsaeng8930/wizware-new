<?php
$pageTitle = '세무 설정';
$currentPage = 'tax';
require_once __DIR__ . '/../config/database.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

// 샘플 설정 데이터
$config = [
    'hometax_connected' => false,
    'biz_no' => '',
    'company_name' => '주식회사 재밋',
    'ceo_name' => '송승환',
    'cert_type' => '',        // 공동인증서 | 간편인증
    'cert_name' => '',
    'cert_expires' => '',
    'api_key' => '',
    'api_secret' => '',
    'auto_sync' => false,
    'sync_schedule' => 'daily_9',
    'sync_range_months' => 3,
    'last_sync_at' => null,
    'last_sync_status' => null,
    'last_sync_count' => 0,
];

// 샘플 동기화 이력
$syncLogs = [
    ['id'=>1, 'sync_type'=>'sales_invoice',    'sync_count'=>7,  'status'=>'성공', 'message'=>'2026년 2월 매출 세금계산서 7건 동기화 완료', 'started_at'=>'2026-02-28 09:00:00', 'finished_at'=>'2026-02-28 09:00:12'],
    ['id'=>2, 'sync_type'=>'purchase_invoice',  'sync_count'=>6,  'status'=>'성공', 'message'=>'2026년 2월 매입 세금계산서 6건 동기화 완료', 'started_at'=>'2026-02-28 09:00:12', 'finished_at'=>'2026-02-28 09:00:25'],
    ['id'=>3, 'sync_type'=>'sales_invoice',    'sync_count'=>11, 'status'=>'성공', 'message'=>'2026년 1월 매출 세금계산서 11건 동기화 완료', 'started_at'=>'2026-01-31 09:00:00', 'finished_at'=>'2026-01-31 09:00:18'],
    ['id'=>4, 'sync_type'=>'purchase_invoice',  'sync_count'=>9,  'status'=>'성공', 'message'=>'2026년 1월 매입 세금계산서 9건 동기화 완료', 'started_at'=>'2026-01-31 09:00:18', 'finished_at'=>'2026-01-31 09:00:30'],
    ['id'=>5, 'sync_type'=>'sales_invoice',    'sync_count'=>0,  'status'=>'실패', 'message'=>'인증서 만료로 동기화 실패', 'started_at'=>'2025-12-31 09:00:00', 'finished_at'=>'2025-12-31 09:00:03'],
];
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6">

        <!-- 헤더 -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-lg font-bold text-slate-100">세무 설정</h2>
                <p class="text-sm text-slate-400 mt-0.5">홈택스 연동 및 세금계산서 동기화 설정을 관리합니다</p>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- 1. 연동 현황 카드 -->
        <!-- ============================================================ -->
        <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden mb-5">
            <div class="flex items-center gap-2 px-5 py-3.5 bg-slate-950 border-b border-slate-800">
                <i data-lucide="activity" class="w-4 h-4 text-primary"></i>
                <span class="text-sm font-semibold text-slate-200">연동 현황</span>
            </div>
            <div class="p-5">
                <!-- 연동 상태 배너 -->
                <div id="statusBanner" class="flex items-center gap-4 p-4 rounded-xl border mb-5 bg-amber-50 border-amber-200">
                    <div class="w-11 h-11 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-500" id="statusIcon"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-amber-700" id="statusTitle">홈택스 연동이 설정되지 않았습니다</p>
                        <p class="text-sm text-amber-600 mt-0.5" id="statusDesc">아래 설정을 완료하면 세금계산서를 자동으로 수집할 수 있습니다</p>
                    </div>
                    <button onclick="document.getElementById('settingsSection').scrollIntoView({behavior:'smooth'})"
                            class="px-4 py-2 text-sm font-medium text-amber-700 bg-slate-900 border border-amber-300 rounded-lg hover:bg-amber-50 transition-colors shrink-0">
                        설정하기
                    </button>
                </div>

                <!-- 현황 그리드 -->
                <div class="grid grid-cols-4 gap-4">
                    <div class="p-4 rounded-xl border border-slate-800 bg-slate-950/50">
                        <div class="flex items-center gap-2 mb-2">
                            <i data-lucide="link" class="w-3.5 h-3.5 text-slate-500"></i>
                            <span class="text-sm font-medium text-slate-400">연동 상태</span>
                        </div>
                        <p class="text-sm font-semibold" id="dispConnStatus">
                            <span class="inline-flex items-center gap-1.5 text-slate-500">
                                <span class="w-2 h-2 rounded-full bg-slate-700"></span> 미연동
                            </span>
                        </p>
                    </div>
                    <div class="p-4 rounded-xl border border-slate-800 bg-slate-950/50">
                        <div class="flex items-center gap-2 mb-2">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-slate-500"></i>
                            <span class="text-sm font-medium text-slate-400">인증 방식</span>
                        </div>
                        <p class="text-sm font-semibold text-slate-500" id="dispCertType">미설정</p>
                    </div>
                    <div class="p-4 rounded-xl border border-slate-800 bg-slate-950/50">
                        <div class="flex items-center gap-2 mb-2">
                            <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-slate-500"></i>
                            <span class="text-sm font-medium text-slate-400">마지막 동기화</span>
                        </div>
                        <p class="text-sm font-semibold text-slate-500" id="dispLastSync">-</p>
                    </div>
                    <div class="p-4 rounded-xl border border-slate-800 bg-slate-950/50">
                        <div class="flex items-center gap-2 mb-2">
                            <i data-lucide="calendar-clock" class="w-3.5 h-3.5 text-slate-500"></i>
                            <span class="text-sm font-medium text-slate-400">자동 동기화</span>
                        </div>
                        <p class="text-sm font-semibold text-slate-500" id="dispAutoSync">비활성</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- 2. 홈택스 연동 설정 -->
        <!-- ============================================================ -->
        <div id="settingsSection" class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden mb-5">
            <div class="flex items-center gap-2 px-5 py-3.5 bg-slate-950 border-b border-slate-800">
                <i data-lucide="settings" class="w-4 h-4 text-primary"></i>
                <span class="text-sm font-semibold text-slate-200">홈택스 연동 설정</span>
            </div>

            <form id="settingsForm" class="p-0">
                <!-- 사업자 정보 -->
                <div class="px-5 pt-5 pb-4">
                    <div class="form-section-title">사업자 정보</div>
                </div>
                <div class="filter-grid mx-5 mb-5 rounded-lg overflow-hidden border border-slate-800">
                    <div class="filter-row">
                        <div class="filter-label">사업자등록번호 <span class="text-amber-500 ml-0.5">*</span></div>
                        <div class="filter-value">
                            <div class="filter-input-wrap">
                                <i data-lucide="hash" class="filter-icon"></i>
                                <input type="text" id="cfgBizNo" class="filter-input" placeholder="000-00-00000" maxlength="12">
                            </div>
                        </div>
                        <div class="filter-label">상호명</div>
                        <div class="filter-value">
                            <div class="filter-input-wrap">
                                <i data-lucide="building-2" class="filter-icon"></i>
                                <input type="text" id="cfgCompanyName" class="filter-input" value="주식회사 재밋" placeholder="상호명">
                            </div>
                        </div>
                    </div>
                    <div class="filter-row" style="border-bottom:none;">
                        <div class="filter-label">대표자명</div>
                        <div class="filter-value">
                            <div class="filter-input-wrap">
                                <i data-lucide="user" class="filter-icon"></i>
                                <input type="text" id="cfgCeoName" class="filter-input" value="송승환" placeholder="대표자명">
                            </div>
                        </div>
                        <div class="filter-label">업종</div>
                        <div class="filter-value">
                            <div class="filter-input-wrap">
                                <i data-lucide="briefcase" class="filter-icon"></i>
                                <input type="text" id="cfgBizType" class="filter-input" placeholder="예: 정보통신업">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 인증 설정 -->
                <div class="px-5 pb-4">
                    <div class="form-section-title">인증 설정</div>
                </div>
                <div class="filter-grid mx-5 mb-5 rounded-lg overflow-hidden border border-slate-800">
                    <div class="filter-row">
                        <div class="filter-label">인증 방식 <span class="text-amber-500 ml-0.5">*</span></div>
                        <div class="filter-value">
                            <div class="zm-radio-group">
                                <label class="cursor-pointer">
                                    <input type="radio" name="certType" value="공동인증서" class="sr-only peer"><span class="zm-radio">공동인증서 (구 공인인증서)</span>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="certType" value="간편인증" class="sr-only peer"><span class="zm-radio">간편인증</span>
                                </label>
                            </div>
                        </div>
                        <div class="filter-label">인증서 상태</div>
                        <div class="filter-value">
                            <span id="cfgCertStatus" class="inline-flex items-center gap-1.5 text-sm text-slate-500">
                                <span class="w-2 h-2 rounded-full bg-slate-700"></span> 미등록
                            </span>
                        </div>
                    </div>
                    <div class="filter-row" style="border-bottom:none;">
                        <div class="filter-label">인증서 파일</div>
                        <div class="filter-value" style="border-right:none;" colspan="3">
                            <div class="flex items-center gap-3 w-full">
                                <label class="btn btn-secondary cursor-pointer">
                                    <i data-lucide="upload" class="w-4 h-4"></i> 인증서 등록
                                    <input type="file" id="cfgCertFile" class="hidden" accept=".pfx,.p12,.der">
                                </label>
                                <span id="cfgCertFileName" class="text-sm text-slate-500">선택된 파일 없음</span>
                                <span class="text-sm text-slate-500 ml-auto">지원 형식: .pfx, .p12, .der</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- API 키 설정 -->
                <div class="px-5 pb-4">
                    <div class="form-section-title">홈택스 API 키</div>
                    <p class="text-sm text-slate-500 -mt-4 mb-4 ml-3">홈택스 OpenAPI 또는 스크래핑 서비스 연동에 필요한 인증 정보입니다</p>
                </div>
                <div class="filter-grid mx-5 mb-5 rounded-lg overflow-hidden border border-slate-800">
                    <div class="filter-row">
                        <div class="filter-label">API Key <span class="text-amber-500 ml-0.5">*</span></div>
                        <div class="filter-value" style="border-right:none;">
                            <div class="filter-input-wrap w-full">
                                <i data-lucide="key" class="filter-icon"></i>
                                <input type="password" id="cfgApiKey" class="filter-input" placeholder="API Key를 입력하세요" autocomplete="off">
                            </div>
                            <button type="button" onclick="togglePassword('cfgApiKey', this)" class="ml-2 text-slate-500 hover:text-slate-300 shrink-0" title="표시/숨기기">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                    <div class="filter-row">
                        <div class="filter-label">API Secret <span class="text-amber-500 ml-0.5">*</span></div>
                        <div class="filter-value" style="border-right:none;">
                            <div class="filter-input-wrap w-full">
                                <i data-lucide="lock" class="filter-icon"></i>
                                <input type="password" id="cfgApiSecret" class="filter-input" placeholder="API Secret을 입력하세요" autocomplete="off">
                            </div>
                            <button type="button" onclick="togglePassword('cfgApiSecret', this)" class="ml-2 text-slate-500 hover:text-slate-300 shrink-0" title="표시/숨기기">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                    <div class="filter-row" style="border-bottom:none;">
                        <div class="filter-label">서비스 제공자</div>
                        <div class="filter-value">
                            <select id="cfgProvider" class="reg-select">
                                <option value="">선택</option>
                                <option value="hometax_direct">홈택스 직접 연동 (OpenAPI)</option>
                                <option value="tilko">틸코 (Tilko)</option>
                                <option value="barobill">바로빌</option>
                                <option value="popbill">팝빌</option>
                                <option value="custom">커스텀 API</option>
                            </select>
                        </div>
                        <div class="filter-label">연동 테스트</div>
                        <div class="filter-value">
                            <button type="button" id="btnTestConnection" onclick="testConnection()"
                                    class="btn btn-secondary">
                                <i data-lucide="plug-zap" class="w-4 h-4"></i> 연동 테스트
                            </button>
                            <span id="testResult" class="ml-3 text-sm hidden"></span>
                        </div>
                    </div>
                </div>

                <!-- 동기화 설정 -->
                <div class="px-5 pb-4">
                    <div class="form-section-title">동기화 설정</div>
                </div>
                <div class="filter-grid mx-5 mb-5 rounded-lg overflow-hidden border border-slate-800">
                    <div class="filter-row">
                        <div class="filter-label">자동 동기화</div>
                        <div class="filter-value">
                            <div class="flex items-center gap-3">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="cfgAutoSync" class="sr-only peer">
                                    <div class="relative w-10 h-5 bg-slate-700 rounded-full peer-checked:bg-primary transition-colors
                                                after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-slate-900 after:rounded-full after:h-4 after:w-4 after:transition-all
                                                peer-checked:after:translate-x-5"></div>
                                </label>
                                <span id="autoSyncLabel" class="text-sm text-slate-400">비활성</span>
                            </div>
                        </div>
                        <div class="filter-label">동기화 주기</div>
                        <div class="filter-value">
                            <select id="cfgSyncSchedule" class="reg-select">
                                <option value="daily_9">매일 오전 9시</option>
                                <option value="daily_18">매일 오후 6시</option>
                                <option value="weekly_mon">매주 월요일 오전 9시</option>
                                <option value="monthly_1">매월 1일 오전 9시</option>
                            </select>
                        </div>
                    </div>
                    <div class="filter-row" style="border-bottom:none;">
                        <div class="filter-label">동기화 범위</div>
                        <div class="filter-value">
                            <select id="cfgSyncRange" class="reg-select">
                                <option value="1">최근 1개월</option>
                                <option value="3" selected>최근 3개월</option>
                                <option value="6">최근 6개월</option>
                                <option value="12">최근 12개월</option>
                            </select>
                        </div>
                        <div class="filter-label">알림 설정</div>
                        <div class="filter-value">
                            <div class="flex items-center gap-4">
                                <label class="emp-checkbox-label">
                                    <input type="checkbox" id="cfgNotiSync" class="emp-checkbox" checked> 동기화 완료 알림
                                </label>
                                <label class="emp-checkbox-label">
                                    <input type="checkbox" id="cfgNotiError" class="emp-checkbox" checked> 오류 발생 알림
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 저장 버튼 -->
                <div class="flex items-center justify-center gap-3 px-5 pb-6">
                    <button type="button" onclick="resetForm()" class="btn btn-secondary">
                        <i data-lucide="rotate-cw" class="w-3.5 h-3.5"></i> 초기화
                    </button>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-6 py-2.5 text-sm font-semibold text-white bg-primary rounded-lg hover:opacity-90 transition-opacity">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i> 설정 저장
                    </button>
                </div>
            </form>
        </div>

        <!-- ============================================================ -->
        <!-- 3. 동기화 이력 -->
        <!-- ============================================================ -->
        <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden mb-5">
            <div class="flex items-center justify-between px-5 py-3.5 bg-slate-950 border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <i data-lucide="history" class="w-4 h-4 text-primary"></i>
                    <span class="text-sm font-semibold text-slate-200">동기화 이력</span>
                </div>
                <button onclick="manualSync()" class="btn btn-secondary btn-sm">
                    <i data-lucide="refresh-cw" class="w-3 h-3"></i> 수동 동기화
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm emp-table">
                    <thead>
                        <tr class="border-b border-slate-800 bg-slate-950/50">
                            <th class="px-4 py-3 text-center font-medium text-slate-300 w-32">일시</th>
                            <th class="px-4 py-3 text-center font-medium text-slate-300 w-28">유형</th>
                            <th class="px-4 py-3 text-center font-medium text-slate-300 w-20">건수</th>
                            <th class="px-4 py-3 text-center font-medium text-slate-300 w-20">결과</th>
                            <th class="px-4 py-3 text-center font-medium text-slate-300">상세</th>
                            <th class="px-4 py-3 text-center font-medium text-slate-300 w-24">소요시간</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($syncLogs as $log):
                            $typeBadge = $log['sync_type'] === 'sales_invoice'
                                ? '<span class="px-2 py-0.5 text-sm font-medium rounded-full bg-primary-light text-primary whitespace-nowrap">매출</span>'
                                : '<span class="px-2 py-0.5 text-sm font-medium rounded-full bg-amber-50 text-amber-700 whitespace-nowrap">매입</span>';
                            $statusBadge = $log['status'] === '성공'
                                ? '<span class="px-2 py-0.5 text-sm font-medium rounded-full bg-amber-50 text-amber-700 whitespace-nowrap">성공</span>'
                                : '<span class="px-2 py-0.5 text-sm font-medium rounded-full bg-amber-50 text-amber-500 whitespace-nowrap">실패</span>';
                            $duration = '-';
                            if ($log['finished_at'] && $log['started_at']) {
                                $diff = strtotime($log['finished_at']) - strtotime($log['started_at']);
                                $duration = $diff . '초';
                            }
                        ?>
                        <tr class="border-b border-slate-800 hover:bg-slate-950 transition-colors">
                            <td class="px-4 py-3 text-center text-slate-300 tabular-nums"><?= date('Y.m.d H:i', strtotime($log['started_at'])) ?></td>
                            <td class="px-4 py-3 text-center"><?= $typeBadge ?></td>
                            <td class="px-4 py-3 text-center text-slate-200 font-medium tabular-nums"><?= $log['sync_count'] ?></td>
                            <td class="px-4 py-3 text-center"><?= $statusBadge ?></td>
                            <td class="px-4 py-3 text-slate-300"><?= htmlspecialchars($log['message']) ?></td>
                            <td class="px-4 py-3 text-center text-slate-500 tabular-nums"><?= $duration ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- 4. 설정 가이드 -->
        <!-- ============================================================ -->
        <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
            <div class="flex items-center gap-2 px-5 py-3.5 bg-slate-950 border-b border-slate-800">
                <i data-lucide="book-open" class="w-4 h-4 text-primary"></i>
                <span class="text-sm font-semibold text-slate-200">홈택스 연동 설정 가이드</span>
            </div>
            <div class="p-5">
                <!-- 스텝 가이드 -->
                <div class="space-y-0">

                    <!-- Step 1 -->
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full bg-primary text-white text-sm font-bold flex items-center justify-center shrink-0">1</div>
                            <div class="w-0.5 flex-1 bg-slate-700 my-1"></div>
                        </div>
                        <div class="pb-6 flex-1">
                            <h4 class="text-sm font-semibold text-slate-100 mb-1">홈택스 API 서비스 신청</h4>
                            <p class="text-sm text-slate-400 leading-relaxed mb-3">
                                홈택스 연동을 위해 API 서비스 제공업체에 가입하고 API Key를 발급받습니다.<br>
                                아래 제공업체 중 하나를 선택하여 가입 후 API Key와 Secret을 발급받으세요.
                            </p>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="p-3 rounded-lg border border-slate-800 bg-slate-950/50">
                                    <div class="flex items-center gap-2 mb-1.5">
                                        <span class="w-6 h-6 rounded bg-primary-light flex items-center justify-center text-sm font-bold text-primary">T</span>
                                        <span class="text-sm font-medium text-slate-200">틸코 (Tilko)</span>
                                        <span class="text-sm text-slate-500 ml-auto">추천</span>
                                    </div>
                                    <p class="text-sm text-slate-400">홈택스 스크래핑 기반, 별도 인증서 불필요 옵션</p>
                                </div>
                                <div class="p-3 rounded-lg border border-slate-800 bg-slate-950/50">
                                    <div class="flex items-center gap-2 mb-1.5">
                                        <span class="w-6 h-6 rounded bg-amber-100 flex items-center justify-center text-sm font-bold text-amber-700">B</span>
                                        <span class="text-sm font-medium text-slate-200">바로빌</span>
                                    </div>
                                    <p class="text-sm text-slate-400">세금계산서 발행/수신 통합 서비스</p>
                                </div>
                                <div class="p-3 rounded-lg border border-slate-800 bg-slate-950/50">
                                    <div class="flex items-center gap-2 mb-1.5">
                                        <span class="w-6 h-6 rounded bg-primary-light flex items-center justify-center text-sm font-bold text-primary">P</span>
                                        <span class="text-sm font-medium text-slate-200">팝빌</span>
                                    </div>
                                    <p class="text-sm text-slate-400">전자세금계산서 + 카카오 알림톡 연동</p>
                                </div>
                                <div class="p-3 rounded-lg border border-slate-800 bg-slate-950/50">
                                    <div class="flex items-center gap-2 mb-1.5">
                                        <span class="w-6 h-6 rounded bg-slate-800 flex items-center justify-center text-sm font-bold text-slate-300">H</span>
                                        <span class="text-sm font-medium text-slate-200">홈택스 직접 연동</span>
                                    </div>
                                    <p class="text-sm text-slate-400">국세청 OpenAPI 직접 연동 (공동인증서 필수)</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full bg-primary text-white text-sm font-bold flex items-center justify-center shrink-0">2</div>
                            <div class="w-0.5 flex-1 bg-slate-700 my-1"></div>
                        </div>
                        <div class="pb-6 flex-1">
                            <h4 class="text-sm font-semibold text-slate-100 mb-1">사업자 정보 입력</h4>
                            <p class="text-sm text-slate-400 leading-relaxed">
                                위 설정 폼에서 <strong>사업자등록번호</strong>, <strong>상호명</strong>, <strong>대표자명</strong>을 정확히 입력합니다.<br>
                                홈택스에 등록된 정보와 일치해야 정상적으로 연동됩니다.
                            </p>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full bg-primary text-white text-sm font-bold flex items-center justify-center shrink-0">3</div>
                            <div class="w-0.5 flex-1 bg-slate-700 my-1"></div>
                        </div>
                        <div class="pb-6 flex-1">
                            <h4 class="text-sm font-semibold text-slate-100 mb-1">인증서 등록 및 API Key 입력</h4>
                            <p class="text-sm text-slate-400 leading-relaxed mb-3">
                                인증 방식을 선택하고 필요한 인증서를 등록합니다. 발급받은 API Key와 Secret을 입력합니다.
                            </p>
                            <div class="p-3 rounded-lg bg-amber-50 border border-amber-200">
                                <div class="flex items-start gap-2">
                                    <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-500 mt-0.5 shrink-0"></i>
                                    <div class="text-sm text-amber-700 leading-relaxed">
                                        <strong>보안 주의사항:</strong> API Key와 인증서는 암호화하여 저장됩니다.
                                        인증서 비밀번호는 서버에 저장되지 않으며, 동기화 시마다 입력이 필요할 수 있습니다.
                                        공동인증서의 경우 유효기간(1년)을 확인하고 만료 전 갱신하세요.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4 -->
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full bg-primary text-white text-sm font-bold flex items-center justify-center shrink-0">4</div>
                            <div class="w-0.5 flex-1 bg-slate-700 my-1"></div>
                        </div>
                        <div class="pb-6 flex-1">
                            <h4 class="text-sm font-semibold text-slate-100 mb-1">연동 테스트</h4>
                            <p class="text-sm text-slate-400 leading-relaxed">
                                설정 완료 후 <strong>"연동 테스트"</strong> 버튼을 클릭하여 정상 연결을 확인합니다.<br>
                                테스트 성공 시 "설정 저장" 버튼으로 설정을 저장하세요.
                            </p>
                        </div>
                    </div>

                    <!-- Step 5 -->
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full bg-amber-500 text-white text-sm font-bold flex items-center justify-center shrink-0">
                                <i data-lucide="check" class="w-4 h-4"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-semibold text-slate-100 mb-1">동기화 시작</h4>
                            <p class="text-sm text-slate-400 leading-relaxed">
                                자동 동기화를 활성화하거나, <strong>"수동 동기화"</strong> 버튼으로 즉시 세금계산서를 수집합니다.<br>
                                수집된 데이터는 <strong>세무 > 세금계산서</strong> 페이지에서 확인할 수 있습니다.
                            </p>
                        </div>
                    </div>

                </div>

                <!-- FAQ -->
                <div class="mt-6 pt-5 border-t border-slate-800">
                    <h4 class="text-sm font-semibold text-slate-200 mb-3 flex items-center gap-1.5">
                        <i data-lucide="help-circle" class="w-4 h-4 text-slate-500"></i> 자주 묻는 질문
                    </h4>
                    <div class="space-y-2" id="faqList">
                        <div class="faq-item border border-slate-800 rounded-lg overflow-hidden">
                            <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-slate-950 transition-colors">
                                <span class="text-sm text-slate-200 font-medium">공동인증서 없이도 연동할 수 있나요?</span>
                                <i data-lucide="chevron-down" class="w-4 h-4 text-slate-500 transition-transform"></i>
                            </button>
                            <div class="faq-answer hidden px-4 pb-3">
                                <p class="text-sm text-slate-400 leading-relaxed">네, 틸코(Tilko)나 바로빌 같은 스크래핑 서비스를 이용하면 공동인증서 없이도 간편인증(카카오/PASS 등)으로 홈택스 데이터를 수집할 수 있습니다. 다만 서비스 이용 요금이 별도 발생합니다.</p>
                            </div>
                        </div>
                        <div class="faq-item border border-slate-800 rounded-lg overflow-hidden">
                            <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-slate-950 transition-colors">
                                <span class="text-sm text-slate-200 font-medium">동기화는 어떤 데이터를 가져오나요?</span>
                                <i data-lucide="chevron-down" class="w-4 h-4 text-slate-500 transition-transform"></i>
                            </button>
                            <div class="faq-answer hidden px-4 pb-3">
                                <p class="text-sm text-slate-400 leading-relaxed">매출/매입 전자세금계산서의 승인번호, 작성일자, 거래처 정보, 공급가액, 세액, 합계금액, 과세유형, 상태(정상/수정/취소)를 수집합니다. 종이 세금계산서는 수집 대상이 아닙니다.</p>
                            </div>
                        </div>
                        <div class="faq-item border border-slate-800 rounded-lg overflow-hidden">
                            <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-slate-950 transition-colors">
                                <span class="text-sm text-slate-200 font-medium">동기화 비용이 발생하나요?</span>
                                <i data-lucide="chevron-down" class="w-4 h-4 text-slate-500 transition-transform"></i>
                            </button>
                            <div class="faq-answer hidden px-4 pb-3">
                                <p class="text-sm text-slate-400 leading-relaxed">홈택스 직접 연동(OpenAPI)은 무료입니다. 틸코, 바로빌, 팝빌 등 외부 서비스를 이용하는 경우 월정액 또는 건당 요금이 발생할 수 있으며, 각 서비스의 요금 정책을 확인하세요.</p>
                            </div>
                        </div>
                        <div class="faq-item border border-slate-800 rounded-lg overflow-hidden">
                            <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-slate-950 transition-colors">
                                <span class="text-sm text-slate-200 font-medium">인증서가 만료되면 어떻게 되나요?</span>
                                <i data-lucide="chevron-down" class="w-4 h-4 text-slate-500 transition-transform"></i>
                            </button>
                            <div class="faq-answer hidden px-4 pb-3">
                                <p class="text-sm text-slate-400 leading-relaxed">인증서가 만료되면 자동 동기화가 실패하며, 오류 알림이 발송됩니다. 새 인증서를 발급받아 다시 등록하면 정상적으로 동기화가 재개됩니다. 인증서 만료 30일 전에 갱신 알림을 보내드립니다.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

<script>
// 폼 제출
document.getElementById('settingsForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const bizNo = document.getElementById('cfgBizNo').value.trim();
    const apiKey = document.getElementById('cfgApiKey').value.trim();
    const apiSecret = document.getElementById('cfgApiSecret').value.trim();
    const certType = document.querySelector('input[name="certType"]:checked');

    if (!bizNo) { alert('사업자등록번호를 입력해주세요.'); document.getElementById('cfgBizNo').focus(); return; }
    if (!certType) { alert('인증 방식을 선택해주세요.'); return; }
    if (!apiKey) { alert('API Key를 입력해주세요.'); document.getElementById('cfgApiKey').focus(); return; }
    if (!apiSecret) { alert('API Secret을 입력해주세요.'); document.getElementById('cfgApiSecret').focus(); return; }

    // 저장 시뮬레이션
    alert('설정이 저장되었습니다.\n\n홈택스 연동이 활성화되면 세금계산서 데이터를 자동으로 수집합니다.');

    // 상태 업데이트
    updateStatus(true);
});

// 연동 테스트
function testConnection() {
    const btn = document.getElementById('btnTestConnection');
    const result = document.getElementById('testResult');
    const apiKey = document.getElementById('cfgApiKey').value.trim();

    if (!apiKey) { alert('API Key를 먼저 입력해주세요.'); return; }

    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> 테스트 중...';
    result.className = 'ml-3 text-sm hidden';
    lucide.createIcons();

    setTimeout(() => {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="plug-zap" class="w-4 h-4"></i> 연동 테스트';
        lucide.createIcons();

        result.classList.remove('hidden');
        result.className = 'ml-3 text-sm text-amber-700 font-medium';
        result.innerHTML = '<span class="inline-flex items-center gap-1"><i data-lucide="check-circle-2" class="w-4 h-4"></i> 연동 성공</span>';
        lucide.createIcons();
    }, 2000);
}

// 패스워드 토글
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type = 'text';
        btn.innerHTML = '<i data-lucide="eye-off" class="w-4 h-4"></i>';
    } else {
        input.type = 'password';
        btn.innerHTML = '<i data-lucide="eye" class="w-4 h-4"></i>';
    }
    lucide.createIcons();
}

// 자동 동기화 토글
document.getElementById('cfgAutoSync').addEventListener('change', function() {
    document.getElementById('autoSyncLabel').textContent = this.checked ? '활성' : '비활성';
    document.getElementById('autoSyncLabel').className = 'text-sm ' + (this.checked ? 'text-primary font-medium' : 'text-slate-400');
});

// 인증서 파일 선택
document.getElementById('cfgCertFile').addEventListener('change', function() {
    const name = this.files[0]?.name || '선택된 파일 없음';
    document.getElementById('cfgCertFileName').textContent = name;
    if (this.files[0]) {
        document.getElementById('cfgCertFileName').className = 'text-sm text-slate-200 font-medium';
        document.getElementById('cfgCertStatus').innerHTML = '<span class="w-2 h-2 rounded-full bg-amber-400"></span> 등록 대기';
        document.getElementById('cfgCertStatus').className = 'inline-flex items-center gap-1.5 text-sm text-amber-600 font-medium';
    }
});

// FAQ 토글
function toggleFaq(btn) {
    const answer = btn.nextElementSibling;
    const icon = btn.querySelector('i');
    const isOpen = !answer.classList.contains('hidden');

    answer.classList.toggle('hidden');
    icon.style.transform = isOpen ? '' : 'rotate(180deg)';
}

// 수동 동기화
async function manualSync() {
    if (!(await AppUI.confirm('수동 동기화를 실행하시겠습니까?\n설정된 동기화 범위의 세금계산서를 수집합니다.'))) return;
    alert('동기화가 완료되었습니다.\n매출 7건, 매입 7건 동기화됨');
}

// 상태 업데이트
function updateStatus(connected) {
    const banner = document.getElementById('statusBanner');
    if (connected) {
        banner.className = 'flex items-center gap-4 p-4 rounded-xl border mb-5 bg-amber-50 border-amber-200';
        document.getElementById('statusIcon').className = 'w-5 h-5 text-amber-500';
        document.getElementById('statusIcon').setAttribute('data-lucide', 'check-circle-2');
        document.getElementById('statusTitle').className = 'text-sm font-semibold text-amber-700';
        document.getElementById('statusTitle').textContent = '홈택스 연동이 정상적으로 설정되었습니다';
        document.getElementById('statusDesc').className = 'text-sm text-amber-700 mt-0.5';
        document.getElementById('statusDesc').textContent = '세금계산서 자동 수집이 활성화되었습니다';

        document.getElementById('dispConnStatus').innerHTML = '<span class="inline-flex items-center gap-1.5 text-amber-700 font-medium"><span class="w-2 h-2 rounded-full bg-amber-500"></span> 연동됨</span>';
        const certType = document.querySelector('input[name="certType"]:checked')?.value || '-';
        document.getElementById('dispCertType').textContent = certType;
        document.getElementById('dispCertType').className = 'text-sm font-semibold text-slate-200';

        document.getElementById('cfgCertStatus').innerHTML = '<span class="w-2 h-2 rounded-full bg-amber-500"></span> 등록 완료';
        document.getElementById('cfgCertStatus').className = 'inline-flex items-center gap-1.5 text-sm text-amber-700 font-medium';
    }
    lucide.createIcons();
}

// 폼 초기화
function resetForm() {
    document.getElementById('settingsForm').reset();
    document.getElementById('cfgCertFileName').textContent = '선택된 파일 없음';
    document.getElementById('cfgCertFileName').className = 'text-sm text-slate-500';
    document.getElementById('autoSyncLabel').textContent = '비활성';
    document.getElementById('autoSyncLabel').className = 'text-sm text-slate-400';
    document.getElementById('testResult').className = 'ml-3 text-sm hidden';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
