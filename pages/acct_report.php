<?php
$pageTitle = '세무리포트';
$currentPage = 'accounting';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/permissions.php';
requireMenuPermission('accounting', 'view'); // 접근권한 관리 연동 (admin 항상 통과)
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$tab = $_GET['tab'] ?? 'dashboard';
$tabs = [
    'dashboard' => '대시보드',
    'ledger' => '계정별원장',
    'trial' => '시산표',
    'balance' => '재무상태표',
    'closing' => '결산',
];

$rpt_pt_allow = ['', 'month', 'quarter', 'half', 'year'];
$rpt_raw = $_GET['rpt_pt'] ?? '';
$rpt_pt  = in_array($rpt_raw, $rpt_pt_allow, true) ? $rpt_raw : '';
$rpt_y   = $_GET['rpt_y'] ?? '';
$rpt_sub = $_GET['rpt_sub'] ?? '';
$rpt_ytd = $_GET['rpt_ytd'] ?? '0';
$periodQuery = '';
if ($rpt_pt !== '') $periodQuery .= '&rpt_pt=' . urlencode($rpt_pt);
if ($rpt_y !== '') $periodQuery .= '&rpt_y=' . urlencode($rpt_y);
if ($rpt_sub !== '') $periodQuery .= '&rpt_sub=' . urlencode($rpt_sub);
if ($rpt_ytd === '1') $periodQuery .= '&rpt_ytd=1';
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6 min-h-screen bg-slate-950">
        <div class="mb-4">
            <h2 class="text-lg font-bold text-slate-100">세무리포트</h2>
        </div>
        <div class="zm-tab-container mb-5">
            <?php foreach ($tabs as $key => $label): ?>
            <a href="?tab=<?= $key ?><?= $periodQuery ?>"
               class="zm-tab <?= $tab === $key ? 'zm-tab-active' : '' ?>">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>

        <script>
        window.AcctReportPeriod = {
            pt:  <?= json_encode($rpt_pt, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            y:   <?= (int)($rpt_y ?: date('Y')) ?>,
            sub: <?= (int)($rpt_sub ?: 0) ?>,
            ytd: <?= $rpt_ytd === '1' ? 1 : 0 ?>,

            save(pt, y, sub, ytd) {
                this.pt = pt; this.y = y; this.sub = sub;
                if (ytd !== undefined) this.ytd = ytd;
                try { sessionStorage.setItem('acctRptPeriod', JSON.stringify({pt, y, sub, ytd: this.ytd})); } catch(e) {}
                this._updateTabLinks();
            },

            load() {
                if (this.pt) return {pt: this.pt, y: this.y, sub: this.sub, ytd: this.ytd};
                try {
                    const s = JSON.parse(sessionStorage.getItem('acctRptPeriod') || '{}');
                    if (s.pt) { this.pt = s.pt; this.y = s.y; this.sub = s.sub; this.ytd = s.ytd || 0; return s; }
                } catch(e) {}
                return null;
            },

            _updateTabLinks() {
                document.querySelectorAll('.zm-tab').forEach(a => {
                    const url = new URL(a.href, location.origin);
                    url.searchParams.set('rpt_pt', this.pt);
                    url.searchParams.set('rpt_y', this.y);
                    url.searchParams.set('rpt_sub', this.sub);
                    if (this.ytd) url.searchParams.set('rpt_ytd', '1');
                    else url.searchParams.delete('rpt_ytd');
                    a.href = url.pathname + url.search;
                });
            }
        };
        </script>

        <?php
        if ($tab === 'dashboard') include __DIR__ . '/acct_report_dashboard.php';
        elseif ($tab === 'ledger') include __DIR__ . '/acct_report_ledger.php';
        elseif ($tab === 'trial') include __DIR__ . '/acct_report_trial.php';
        elseif ($tab === 'balance') include __DIR__ . '/acct_report_balance.php';
        elseif ($tab === 'closing') include __DIR__ . '/acct_report_closing.php';
        ?>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
