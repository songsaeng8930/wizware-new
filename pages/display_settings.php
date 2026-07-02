<?php
/**
 * 시스템 관리 > 그룹웨어 관리 > 디자인 설정
 * - 전체 그룹웨어의 디자인 톤(화이트/다크)을 전역으로 변경
 * - 저장소: config/ui_settings.json (api/ui_settings.php 경유)
 * - admin 전용
 */
$pageTitle = '디자인 설정';
$currentPage = 'groupware';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/permissions.php';

// admin 만 접근
requireMenuPermission('groupware.display', 'admin');

$currentTheme = getUiTheme(); // header.php 에서 ui_settings.php 이미 로드됨
?>

<!-- 메인 컨텐츠 영역 -->
<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6">

        <!-- 페이지 헤더 -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-lg font-bold text-slate-100">디자인 설정</h2>
                <p class="text-sm text-slate-400 mt-0.5">그룹웨어 전체에 적용되는 색상 톤을 선택합니다. 저장 시 모든 사용자에게 즉시 적용됩니다.</p>
            </div>
            <button id="saveThemeBtn" disabled
                    class="zm-btn zm-btn-hero disabled:opacity-50 disabled:cursor-not-allowed">
                <i data-lucide="save" class="w-4 h-4"></i>
                저장
            </button>
        </div>

        <!-- 테마 선택 카드 -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-base font-bold text-gray-800 mb-1">색상 톤</h3>
            <p class="text-sm text-gray-500 mb-5">기본 제공 톤은 화이트입니다. 선택 후 우측 상단 저장을 누르세요.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 max-w-3xl" id="themeCards">

                <!-- 화이트 톤 -->
                <label class="theme-card cursor-pointer rounded-xl border-2 p-4 transition-all"
                       data-theme-value="light">
                    <input type="radio" name="uiTheme" value="light" class="sr-only"
                           <?= $currentTheme === 'light' ? 'checked' : '' ?>>
                    <!-- 미니 프리뷰 (고정 색 · 테마 오버라이드 영향 없음) -->
                    <div class="rounded-lg overflow-hidden mb-3" style="border: 1px solid #dde1e8;">
                        <div style="background:#ffffff; padding:10px 12px; display:flex; align-items:center; gap:6px; border-bottom:1px solid #eef0f4;">
                            <span style="width:10px;height:10px;border-radius:9999px;background:var(--zm-primary);display:inline-block;"></span>
                            <span style="width:56px;height:8px;border-radius:4px;background:#d3d8e0;display:inline-block;"></span>
                        </div>
                        <div style="background:#f5f6f8; padding:12px; display:flex; gap:8px;">
                            <div style="flex:1;background:#ffffff;border:1px solid #e3e7ed;border-radius:8px;height:46px;padding:8px;">
                                <div style="width:70%;height:7px;border-radius:4px;background:#cfd5de;"></div>
                                <div style="width:45%;height:7px;border-radius:4px;background:#e3e7ed;margin-top:7px;"></div>
                            </div>
                            <div style="flex:1;background:#ffffff;border:1px solid #e3e7ed;border-radius:8px;height:46px;padding:8px;">
                                <div style="width:60%;height:7px;border-radius:4px;background:#cfd5de;"></div>
                                <div style="width:80%;height:7px;border-radius:4px;background:#e3e7ed;margin-top:7px;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-bold text-gray-800">화이트 톤 <span class="text-xs font-medium text-gray-500">(기본)</span></p>
                            <p class="text-xs text-gray-500 mt-0.5">밝은 배경의 깔끔한 기본 테마</p>
                        </div>
                        <span class="theme-check hidden items-center justify-center w-6 h-6 rounded-full bg-primary text-white">
                            <i data-lucide="check" class="w-4 h-4"></i>
                        </span>
                    </div>
                </label>

                <!-- 다크 톤 -->
                <label class="theme-card cursor-pointer rounded-xl border-2 p-4 transition-all"
                       data-theme-value="dark">
                    <input type="radio" name="uiTheme" value="dark" class="sr-only"
                           <?= $currentTheme === 'dark' ? 'checked' : '' ?>>
                    <div class="rounded-lg overflow-hidden mb-3" style="border: 1px solid #2f2f34;">
                        <div style="background:#232327; padding:10px 12px; display:flex; align-items:center; gap:6px; border-bottom:1px solid #2a2a2f;">
                            <span style="width:10px;height:10px;border-radius:9999px;background:var(--zm-primary);display:inline-block;"></span>
                            <span style="width:56px;height:8px;border-radius:4px;background:#3a3a40;display:inline-block;"></span>
                        </div>
                        <div style="background:#1a1a1d; padding:12px; display:flex; gap:8px;">
                            <div style="flex:1;background:#232327;border:1px solid #2a2a2f;border-radius:8px;height:46px;padding:8px;">
                                <div style="width:70%;height:7px;border-radius:4px;background:#3a3a40;"></div>
                                <div style="width:45%;height:7px;border-radius:4px;background:#2a2a2f;margin-top:7px;"></div>
                            </div>
                            <div style="flex:1;background:#232327;border:1px solid #2a2a2f;border-radius:8px;height:46px;padding:8px;">
                                <div style="width:60%;height:7px;border-radius:4px;background:#3a3a40;"></div>
                                <div style="width:80%;height:7px;border-radius:4px;background:#2a2a2f;margin-top:7px;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-bold text-gray-800">다크 톤</p>
                            <p class="text-xs text-gray-500 mt-0.5">눈이 편한 어두운 테마</p>
                        </div>
                        <span class="theme-check hidden items-center justify-center w-6 h-6 rounded-full bg-primary text-white">
                            <i data-lucide="check" class="w-4 h-4"></i>
                        </span>
                    </div>
                </label>

            </div>

            <!-- 안내 -->
            <div class="bg-gray-100 text-gray-600 rounded-lg px-4 py-3 mt-6 max-w-3xl flex items-start gap-2">
                <i data-lucide="info" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                <p class="text-sm">테마는 회사 전체(모든 사용자)에 공통 적용되는 전역 설정입니다. 저장 직후 페이지가 새로고침되며 적용됩니다.</p>
            </div>
        </div>

    </main>
</div>

<style>
/* 선택 상태 · 양 테마 공통으로 보이도록 변수 기반 */
.theme-card { border-color: var(--zm-border); background: var(--zm-surface-1); }
.theme-card:hover { border-color: rgba(0, 0, 0, 0.12); }
.theme-card.selected { border-color: var(--zm-primary); box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.08); }
.theme-card.selected .theme-check { display: inline-flex; }
</style>

<script>
const UI_SETTINGS_API = '<?= $basePath ?>/api/ui_settings.php';
(function () {
    const cards = Array.from(document.querySelectorAll('.theme-card'));
    const saveBtn = document.getElementById('saveThemeBtn');
    const initialTheme = <?= json_encode($currentTheme) ?>;

    function selectedTheme() {
        const checked = document.querySelector('input[name="uiTheme"]:checked');
        return checked ? checked.value : initialTheme;
    }

    function refresh() {
        cards.forEach(card => {
            const input = card.querySelector('input[name="uiTheme"]');
            card.classList.toggle('selected', input.checked);
        });
        saveBtn.disabled = (selectedTheme() === initialTheme);
    }

    cards.forEach(card => {
        card.addEventListener('click', () => {
            card.querySelector('input[name="uiTheme"]').checked = true;
            refresh();
            // 선택 즉시 미리보기 (저장 전 임시 적용 · 저장 안 하면 새로고침 시 원복)
            document.documentElement.setAttribute('data-theme', selectedTheme());
        });
    });

    saveBtn.addEventListener('click', async () => {
        saveBtn.disabled = true;
        try {
            const res = await fetch(UI_SETTINGS_API + '?action=save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ theme: selectedTheme() })
            });
            const json = await res.json();
            if (!res.ok || !json.ok) {
                throw new Error(json.error?.message || '저장에 실패했습니다.');
            }
            location.reload();
        } catch (e) {
            alert(e.message || '저장 중 오류가 발생했습니다.');
            saveBtn.disabled = false;
        }
    });

    refresh();
})();
lucide.createIcons();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
