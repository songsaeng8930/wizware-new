<?php
// 환경설정 > 공인인증서 탭 · acct_settings.php에서 include
// 샘플 인증서 목록 (실제 운영 시 DB 또는 암호화 저장소에서 조회)
$certs = [
    [
        'id' => 'cert_1',
        'alias' => '(주)재밋_사업자용',
        'type' => '공동인증서',
        'usage' => '사업자',
        'subject' => 'CN=(주)재밋, O=(주)재밋, OU=재무팀',
        'issuer' => '한국정보인증(주)',
        'serial' => '0x1A2B3C4D',
        'issued_at' => '2025-11-20',
        'expires_at' => '2026-11-20',
        'status' => 'active',
    ],
    [
        'id' => 'cert_2',
        'alias' => '카카오 간편인증 (송승환)',
        'type' => '간편인증',
        'usage' => '개인',
        'subject' => '송승환 (대표이사)',
        'issuer' => '카카오페이',
        'serial' => '-',
        'issued_at' => '2026-01-15',
        'expires_at' => '2027-01-15',
        'status' => 'active',
    ],
    [
        'id' => 'cert_3',
        'alias' => '구 공인인증서 (2024)',
        'type' => '공동인증서',
        'usage' => '사업자',
        'subject' => 'CN=(주)재밋, O=(주)재밋',
        'issuer' => '한국정보인증(주)',
        'serial' => '0x98765432',
        'issued_at' => '2024-02-10',
        'expires_at' => '2025-02-10',
        'status' => 'expired',
    ],
];

// 통계 계산
$today = strtotime('2026-04-10');
$statActive = 0;
$statExpiring = 0;
$statExpired = 0;
foreach ($certs as $c) {
    $daysLeft = floor((strtotime($c['expires_at']) - $today) / 86400);
    if ($daysLeft < 0) $statExpired++;
    elseif ($daysLeft <= 30) $statExpiring++;
    else $statActive++;
}
?>

<!-- 상단 요약 카드 -->
<div class="grid grid-cols-4 gap-4 mb-5">
    <div class="bg-slate-900 rounded-xl border border-slate-800 p-4">
        <div class="flex items-center gap-2 mb-2">
            <div class="w-8 h-8 rounded-lg bg-primary-light flex items-center justify-center">
                <i data-lucide="shield-check" class="w-4 h-4 text-primary"></i>
            </div>
            <span class="text-sm font-medium text-slate-400">등록된 인증서</span>
        </div>
        <p class="text-2xl font-bold text-slate-100 tabular-nums"><?= count($certs) ?><span class="text-sm text-slate-500 font-normal ml-1">건</span></p>
    </div>
    <div class="bg-slate-900 rounded-xl border border-slate-800 p-4">
        <div class="flex items-center gap-2 mb-2">
            <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center">
                <i data-lucide="check-circle-2" class="w-4 h-4 text-amber-500"></i>
            </div>
            <span class="text-sm font-medium text-slate-400">정상</span>
        </div>
        <p class="text-2xl font-bold text-amber-700 tabular-nums"><?= $statActive ?><span class="text-sm text-slate-500 font-normal ml-1">건</span></p>
    </div>
    <div class="bg-slate-900 rounded-xl border border-slate-800 p-4">
        <div class="flex items-center gap-2 mb-2">
            <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center">
                <i data-lucide="clock-alert" class="w-4 h-4 text-amber-500"></i>
            </div>
            <span class="text-sm font-medium text-slate-400">만료 임박 (30일)</span>
        </div>
        <p class="text-2xl font-bold text-amber-600 tabular-nums"><?= $statExpiring ?><span class="text-sm text-slate-500 font-normal ml-1">건</span></p>
    </div>
    <div class="bg-slate-900 rounded-xl border border-slate-800 p-4">
        <div class="flex items-center gap-2 mb-2">
            <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center">
                <i data-lucide="x-circle" class="w-4 h-4 text-amber-500"></i>
            </div>
            <span class="text-sm font-medium text-slate-400">만료됨</span>
        </div>
        <p class="text-2xl font-bold text-amber-500 tabular-nums"><?= $statExpired ?><span class="text-sm text-slate-500 font-normal ml-1">건</span></p>
    </div>
</div>

<!-- 만료 임박 경고 배너 -->
<?php if ($statExpiring > 0 || $statExpired > 0): ?>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-5">
    <div class="flex items-start gap-3">
        <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-500 mt-0.5 shrink-0"></i>
        <div class="flex-1">
            <p class="text-sm font-semibold text-amber-700">인증서 점검이 필요합니다</p>
            <p class="text-sm text-amber-600 mt-1">
                <?php if ($statExpired > 0): ?>
                    만료된 인증서가 <strong><?= $statExpired ?>건</strong> 있습니다. 새 인증서로 교체하세요.
                <?php endif; ?>
                <?php if ($statExpiring > 0): ?>
                    만료 30일 이내 인증서가 <strong><?= $statExpiring ?>건</strong> 있습니다. 갱신을 준비하세요.
                <?php endif; ?>
            </p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- 상단 액션 -->
<div class="flex items-center justify-between mb-5">
    <div>
        <p class="text-sm font-medium text-slate-200">등록된 인증서 목록</p>
        <p class="text-sm text-slate-500 mt-0.5">홈택스 연동 · 전자세금계산서 발행 · 4대보험 업무에 사용됩니다</p>
    </div>
    <button onclick="openCertModal()" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:opacity-90 transition-opacity">
        <i data-lucide="plus" class="w-4 h-4"></i> 인증서 등록
    </button>
</div>

<!-- 인증서 목록 -->
<div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden mb-5">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-800 bg-slate-950/50">
                    <th class="px-4 py-3 text-left font-medium text-slate-300">인증서 별칭</th>
                    <th class="px-4 py-3 text-center font-medium text-slate-300 w-28">유형</th>
                    <th class="px-4 py-3 text-center font-medium text-slate-300 w-24">용도</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-300">발급기관</th>
                    <th class="px-4 py-3 text-center font-medium text-slate-300 w-32">유효기간</th>
                    <th class="px-4 py-3 text-center font-medium text-slate-300 w-24">상태</th>
                    <th class="px-4 py-3 text-center font-medium text-slate-300 w-28">관리</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($certs as $cert):
                    $daysLeft = floor((strtotime($cert['expires_at']) - $today) / 86400);
                    if ($daysLeft < 0) {
                        $statusBadge = '<span class="px-2 py-0.5 text-sm font-medium rounded-full bg-amber-50 text-amber-500">만료</span>';
                        $dateColor = 'text-amber-500';
                        $dateSuffix = ' <span class="text-sm text-amber-500">(' . abs($daysLeft) . '일 경과)</span>';
                    } elseif ($daysLeft <= 30) {
                        $statusBadge = '<span class="px-2 py-0.5 text-sm font-medium rounded-full bg-amber-50 text-amber-600">만료 임박</span>';
                        $dateColor = 'text-amber-600';
                        $dateSuffix = ' <span class="text-sm text-amber-500">(D-' . $daysLeft . ')</span>';
                    } else {
                        $statusBadge = '<span class="px-2 py-0.5 text-sm font-medium rounded-full bg-amber-50 text-amber-700">정상</span>';
                        $dateColor = 'text-slate-300';
                        $dateSuffix = '';
                    }
                    $typeBadge = $cert['type'] === '공동인증서'
                        ? '<span class="px-2 py-0.5 text-sm font-medium rounded-full bg-primary-light text-primary">공동인증서</span>'
                        : '<span class="px-2 py-0.5 text-sm font-medium rounded-full bg-primary-light text-primary">간편인증</span>';
                ?>
                <tr class="border-b border-slate-800 hover:bg-slate-950 transition-colors">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-slate-800 flex items-center justify-center shrink-0">
                                <i data-lucide="<?= $cert['type'] === '공동인증서' ? 'file-key' : 'smartphone' ?>" class="w-4 h-4 text-slate-400"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-slate-100 truncate"><?= htmlspecialchars($cert['alias']) ?></p>
                                <p class="text-sm text-slate-500 truncate"><?= htmlspecialchars($cert['subject']) ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center"><?= $typeBadge ?></td>
                    <td class="px-4 py-3 text-center text-slate-300"><?= htmlspecialchars($cert['usage']) ?></td>
                    <td class="px-4 py-3 text-slate-300"><?= htmlspecialchars($cert['issuer']) ?></td>
                    <td class="px-4 py-3 text-center tabular-nums <?= $dateColor ?>">
                        <?= str_replace('-', '.', $cert['expires_at']) ?><?= $dateSuffix ?>
                    </td>
                    <td class="px-4 py-3 text-center"><?= $statusBadge ?></td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <button onclick="viewCert('<?= $cert['id'] ?>')" class="p-1.5 text-slate-500 hover:text-gray-900 hover:bg-gray-100 rounded transition-colors" title="상세보기">
                                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                            </button>
                            <button onclick="renewCert('<?= $cert['id'] ?>')" class="p-1.5 text-slate-500 hover:text-amber-500 hover:bg-amber-50 rounded transition-colors" title="갱신">
                                <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                            </button>
                            <button onclick="deleteCert('<?= $cert['id'] ?>', '<?= htmlspecialchars($cert['alias']) ?>')" class="p-1.5 text-slate-500 hover:text-amber-500 hover:bg-amber-50 rounded transition-colors" title="삭제">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($certs)): ?>
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center">
                        <i data-lucide="shield-off" class="w-10 h-10 mx-auto mb-3 text-slate-600"></i>
                        <p class="text-sm text-slate-500">등록된 인증서가 없습니다.</p>
                        <p class="text-sm text-slate-500 mt-1">위의 "인증서 등록" 버튼으로 인증서를 추가하세요.</p>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 인증서 가이드 -->
<div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
    <div class="flex items-center gap-2 px-5 py-3.5 bg-slate-950 border-b border-slate-800">
        <i data-lucide="book-open" class="w-4 h-4 text-primary"></i>
        <span class="text-sm font-semibold text-slate-200">공인인증서 등록 가이드</span>
    </div>
    <div class="p-5">
        <div class="grid grid-cols-2 gap-5">
            <!-- 공동인증서 -->
            <div class="p-4 rounded-xl border border-slate-800 bg-slate-950/50">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-8 h-8 rounded-lg bg-primary-light flex items-center justify-center">
                        <i data-lucide="file-key" class="w-4 h-4 text-primary"></i>
                    </div>
                    <h4 class="text-sm font-semibold text-slate-100">공동인증서 (구 공인인증서)</h4>
                </div>
                <p class="text-sm text-slate-400 leading-relaxed mb-3">
                    은행 · 세무서 · 금융결제원 등에서 발급하는 전자서명 인증서입니다. 홈택스 직접 연동에 필수입니다.
                </p>
                <div class="space-y-1.5 text-sm text-slate-400">
                    <div class="flex items-start gap-2">
                        <i data-lucide="check" class="w-3 h-3 text-amber-500 mt-0.5 shrink-0"></i>
                        <span>지원 형식: .pfx, .p12, .der</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <i data-lucide="check" class="w-3 h-3 text-amber-500 mt-0.5 shrink-0"></i>
                        <span>유효기간: 보통 1년 (만료 전 갱신 필요)</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <i data-lucide="check" class="w-3 h-3 text-amber-500 mt-0.5 shrink-0"></i>
                        <span>용도: 사업자용 (법인), 개인용 (대표자)</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <i data-lucide="check" class="w-3 h-3 text-amber-500 mt-0.5 shrink-0"></i>
                        <span>발급처: 한국정보인증, 코스콤, 금융결제원 등</span>
                    </div>
                </div>
            </div>

            <!-- 간편인증 -->
            <div class="p-4 rounded-xl border border-slate-800 bg-slate-950/50">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-8 h-8 rounded-lg bg-primary-light flex items-center justify-center">
                        <i data-lucide="smartphone" class="w-4 h-4 text-primary"></i>
                    </div>
                    <h4 class="text-sm font-semibold text-slate-100">간편인증</h4>
                </div>
                <p class="text-sm text-slate-400 leading-relaxed mb-3">
                    카카오 · PASS · 네이버 · 페이코 등 모바일 앱 기반 인증 방식입니다. 스크래핑 서비스와 함께 사용합니다.
                </p>
                <div class="space-y-1.5 text-sm text-slate-400">
                    <div class="flex items-start gap-2">
                        <i data-lucide="check" class="w-3 h-3 text-amber-500 mt-0.5 shrink-0"></i>
                        <span>지원 서비스: 카카오, PASS, 네이버, 페이코 등</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <i data-lucide="check" class="w-3 h-3 text-amber-500 mt-0.5 shrink-0"></i>
                        <span>파일 업로드 불필요 (모바일 인증만 진행)</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <i data-lucide="check" class="w-3 h-3 text-amber-500 mt-0.5 shrink-0"></i>
                        <span>용도: 개인 (대표자) 인증 전용</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <i data-lucide="check" class="w-3 h-3 text-amber-500 mt-0.5 shrink-0"></i>
                        <span>틸코(Tilko) 등 스크래핑 서비스 필수</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 보안 안내 -->
        <div class="mt-5 p-4 rounded-lg bg-amber-50 border border-amber-200">
            <div class="flex items-start gap-2">
                <i data-lucide="shield-alert" class="w-5 h-5 text-amber-500 mt-0.5 shrink-0"></i>
                <div class="text-sm text-amber-700 leading-relaxed">
                    <strong class="block mb-1">보안 주의사항</strong>
                    인증서는 AES-256으로 암호화되어 저장됩니다. 인증서 비밀번호는 서버에 저장되지 않으며, 사용 시마다 입력하거나 세션 동안만 메모리에 보관됩니다.
                    인증서 파일 유출 시 즉시 발급기관에 폐기 신청하고, 새 인증서를 재발급받아 등록하세요.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 인증서 등록 모달 -->
<div id="certModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeCertModal()"></div>
    <div class="relative bg-slate-900 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800 sticky top-0 bg-slate-900">
            <div>
                <h3 class="text-base font-bold text-slate-100">인증서 등록</h3>
                <p class="text-sm text-slate-500 mt-0.5">공동인증서 또는 간편인증을 등록합니다</p>
            </div>
            <button onclick="closeCertModal()" class="text-slate-500 hover:text-slate-300">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="p-6 space-y-4">
            <!-- 인증서 유형 선택 -->
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-2">인증서 유형 <span class="text-amber-500">*</span></label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="cert-type-option flex items-center gap-2 p-3 border border-slate-800 rounded-lg cursor-pointer hover:border-gray-400 transition-colors has-[:checked]:border-primary has-[:checked]:bg-primary-light">
                        <input type="radio" name="certTypeModal" value="공동인증서" class="accent-primary" checked>
                        <i data-lucide="file-key" class="w-4 h-4 text-primary"></i>
                        <span class="text-sm font-medium text-slate-200">공동인증서</span>
                    </label>
                    <label class="cert-type-option flex items-center gap-2 p-3 border border-slate-800 rounded-lg cursor-pointer hover:border-gray-400 transition-colors has-[:checked]:border-primary has-[:checked]:bg-primary-light">
                        <input type="radio" name="certTypeModal" value="간편인증" class="accent-primary">
                        <i data-lucide="smartphone" class="w-4 h-4 text-primary"></i>
                        <span class="text-sm font-medium text-slate-200">간편인증</span>
                    </label>
                </div>
            </div>

            <!-- 별칭 -->
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1.5">인증서 별칭 <span class="text-amber-500">*</span></label>
                <input type="text" id="certAlias" placeholder="예: (주)재밋 사업자용" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30">
                <p class="text-sm text-slate-500 mt-1">관리용 이름입니다. 자유롭게 지정하세요.</p>
            </div>

            <!-- 용도 -->
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1.5">용도</label>
                <select id="certUsage" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30">
                    <option value="사업자">사업자 (법인)</option>
                    <option value="개인">개인 (대표자)</option>
                </select>
            </div>

            <!-- 공동인증서 전용: 파일 + 비밀번호 -->
            <div id="certFileFields">
                <label class="block text-sm font-medium text-slate-200 mb-1.5">인증서 파일 <span class="text-amber-500">*</span></label>
                <label class="flex items-center gap-2 px-4 py-3 text-sm border-2 border-dashed border-slate-800 rounded-lg cursor-pointer hover:border-gray-400 hover:bg-gray-100 transition-colors">
                    <i data-lucide="upload-cloud" class="w-5 h-5 text-slate-500"></i>
                    <span id="certFileLabel" class="text-slate-400">파일을 선택하거나 드래그하세요</span>
                    <input type="file" id="certFile" class="hidden" accept=".pfx,.p12,.der">
                </label>
                <p class="text-sm text-slate-500 mt-1">지원 형식: .pfx, .p12, .der</p>

                <label class="block text-sm font-medium text-slate-200 mb-1.5 mt-3">인증서 비밀번호 <span class="text-amber-500">*</span></label>
                <input type="password" id="certPassword" placeholder="인증서 비밀번호" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30">
                <p class="text-sm text-slate-500 mt-1">비밀번호는 서버에 저장되지 않습니다.</p>
            </div>

            <!-- 간편인증 전용: 서비스 선택 + 사용자 -->
            <div id="certSimpleFields" class="hidden">
                <label class="block text-sm font-medium text-slate-200 mb-1.5">인증 서비스 <span class="text-amber-500">*</span></label>
                <select id="certProvider" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30">
                    <option value="">선택</option>
                    <option value="kakao">카카오 (카카오페이)</option>
                    <option value="pass">PASS (통신사)</option>
                    <option value="naver">네이버</option>
                    <option value="payco">페이코</option>
                    <option value="toss">토스</option>
                </select>

                <label class="block text-sm font-medium text-slate-200 mb-1.5 mt-3">이름 <span class="text-amber-500">*</span></label>
                <input type="text" id="certUserName" placeholder="인증 대상자 이름" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30">

                <label class="block text-sm font-medium text-slate-200 mb-1.5 mt-3">휴대폰 번호 <span class="text-amber-500">*</span></label>
                <input type="tel" id="certPhone" placeholder="010-0000-0000" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30">

                <label class="block text-sm font-medium text-slate-200 mb-1.5 mt-3">생년월일 <span class="text-amber-500">*</span></label>
                <input type="text" id="certBirthday" placeholder="YYYYMMDD" maxlength="8" class="w-full border border-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300/30">
            </div>
        </div>

        <div class="flex gap-2 px-6 pb-5 pt-4 justify-end border-t border-slate-800 sticky bottom-0 bg-slate-900">
            <button onclick="closeCertModal()" class="btn btn-secondary">취소</button>
            <button onclick="submitCert()" class="px-4 py-2 text-sm text-white bg-primary rounded-lg hover:opacity-90 flex items-center gap-1.5">
                <i data-lucide="check" class="w-4 h-4"></i> 등록
            </button>
        </div>
    </div>
</div>

<script>
// 인증서 모달 열기/닫기
function openCertModal() {
    document.getElementById('certModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    // 기본값 리셋
    document.getElementById('certAlias').value = '';
    document.getElementById('certFileLabel').textContent = '파일을 선택하거나 드래그하세요';
    document.getElementById('certFile').value = '';
    document.getElementById('certPassword').value = '';
    document.querySelector('input[name="certTypeModal"][value="공동인증서"]').checked = true;
    toggleCertType('공동인증서');
    if (window.lucide) lucide.createIcons();
}
function closeCertModal() {
    document.getElementById('certModal').classList.add('hidden');
    document.body.style.overflow = '';
}

// 인증서 유형 변경 시 필드 전환
document.querySelectorAll('input[name="certTypeModal"]').forEach(function(radio) {
    radio.addEventListener('change', function() { toggleCertType(this.value); });
});
function toggleCertType(type) {
    const fileFields = document.getElementById('certFileFields');
    const simpleFields = document.getElementById('certSimpleFields');
    if (type === '공동인증서') {
        fileFields.classList.remove('hidden');
        simpleFields.classList.add('hidden');
    } else {
        fileFields.classList.add('hidden');
        simpleFields.classList.remove('hidden');
    }
}

// 파일 선택 시 라벨 업데이트
document.getElementById('certFile').addEventListener('change', function() {
    const name = this.files[0]?.name || '파일을 선택하거나 드래그하세요';
    document.getElementById('certFileLabel').textContent = name;
    if (this.files[0]) {
        document.getElementById('certFileLabel').className = 'text-slate-200 font-medium';
    }
});

// 등록 제출
function submitCert() {
    const type = document.querySelector('input[name="certTypeModal"]:checked').value;
    const alias = document.getElementById('certAlias').value.trim();

    if (!alias) { alert('인증서 별칭을 입력하세요.'); return; }

    if (type === '공동인증서') {
        const file = document.getElementById('certFile').files[0];
        const password = document.getElementById('certPassword').value;
        if (!file) { alert('인증서 파일을 선택하세요.'); return; }
        if (!password) { alert('인증서 비밀번호를 입력하세요.'); return; }
        alert('공동인증서 "' + alias + '" 등록이 완료되었습니다.\n\n(샘플 화면입니다. 실제 저장되지 않습니다.)');
    } else {
        const provider = document.getElementById('certProvider').value;
        const name = document.getElementById('certUserName').value.trim();
        const phone = document.getElementById('certPhone').value.trim();
        const birthday = document.getElementById('certBirthday').value.trim();
        if (!provider) { alert('인증 서비스를 선택하세요.'); return; }
        if (!name) { alert('이름을 입력하세요.'); return; }
        if (!phone) { alert('휴대폰 번호를 입력하세요.'); return; }
        if (!birthday || birthday.length !== 8) { alert('생년월일을 YYYYMMDD 형식으로 입력하세요.'); return; }
        alert('간편인증 "' + alias + '" 등록이 완료되었습니다.\n\n(샘플 화면입니다. 실제 저장되지 않습니다.)');
    }
    closeCertModal();
}

// 상세 보기 / 갱신 / 삭제
function viewCert(id) {
    alert('인증서 상세 정보 (' + id + ')\n\n발급 주체, 시리얼 번호, 지문 등 상세 정보를 표시합니다.');
}
async function renewCert(id) {
    if ((await AppUI.confirm('이 인증서를 갱신하시겠습니까?\n새 인증서 파일을 업로드하여 기존 인증서를 교체합니다.'))) {
        openCertModal();
    }
}
async function deleteCert(id, alias) {
    if ((await AppUI.confirm('"' + alias + '" 인증서를 삭제하시겠습니까?\n\n삭제 후에는 해당 인증서를 사용하는 홈택스 연동이 중단됩니다.'))) {
        alert('삭제되었습니다. (샘플 화면입니다.)');
    }
}

// ESC로 모달 닫기
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeCertModal();
});
</script>
