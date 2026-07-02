<?php
$pageTitle = '병원 전용 출력';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$pdo = getDBConnection();
if (!$pdo) {
    http_response_code(500);
    echo '데이터베이스 연결 실패';
    exit;
}

$type = $_GET['type'] ?? 'shifts';
$date = $_GET['date'] ?? date('Y-m-d');
$allowedTypes = ['dashboard', 'shifts', 'checks', 'closing', 'assets', 'training'];
if (!in_array($type, $allowedTypes, true)) {
    $type = 'shifts';
}

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function won($value): string
{
    return number_format((int)$value) . '원';
}

function statusClass($status): string
{
    if (in_array($status, ['확정', '승인', '승인완료', '마감완료', '정상', '유효', '완료', '발주완료'], true)) return 'ok';
    if (in_array($status, ['신청', '요청', '임시', '작성중', '점검예정', '만료예정', '변경필요', '부족'], true)) return 'warn';
    if (in_array($status, ['반려', '취소', '고장', '만료', '미이수'], true)) return 'danger';
    return 'muted';
}

function weekRange(string $date): array
{
    $dt = new DateTime($date);
    $day = (int)$dt->format('N');
    $from = (clone $dt)->modify('-' . ($day - 1) . ' days');
    $to = (clone $from)->modify('+6 days');
    return [$from->format('Y-m-d'), $to->format('Y-m-d')];
}

[$weekFrom, $weekTo] = weekRange($date);
$user = getCurrentUser();

$data = [];
if ($type === 'shifts') {
    $st = $pdo->prepare('SELECT * FROM hospital_shift_slots WHERE slot_date BETWEEN ? AND ? ORDER BY slot_date, start_time, id');
    $st->execute([$weekFrom, $weekTo]);
    $data['shifts'] = $st->fetchAll();

    $st = $pdo->prepare('SELECT * FROM hospital_leave_requests WHERE start_date <= ? AND end_date >= ? ORDER BY start_date, id');
    $st->execute([$weekTo, $weekFrom]);
    $data['leaves'] = $st->fetchAll();
} elseif ($type === 'checks') {
    $st = $pdo->prepare('SELECT * FROM hospital_daily_checks WHERE check_date = ? ORDER BY shift_type, category, id');
    $st->execute([$date]);
    $data['checks'] = $st->fetchAll();
} elseif ($type === 'closing') {
    $st = $pdo->prepare('SELECT * FROM hospital_cash_closings WHERE closing_date = ?');
    $st->execute([$date]);
    $data['closing'] = $st->fetch();
} elseif ($type === 'assets') {
    $data['assets'] = $pdo->query('SELECT * FROM hospital_assets ORDER BY asset_type, status DESC, name')->fetchAll();
    $data['purchases'] = $pdo->query('SELECT * FROM hospital_purchase_requests ORDER BY FIELD(status, "요청", "승인", "발주완료", "취소"), created_at DESC')->fetchAll();
} elseif ($type === 'training') {
    $data['credentials'] = $pdo->query('SELECT * FROM hospital_staff_credentials ORDER BY expire_date IS NULL, expire_date, employee_name')->fetchAll();
} else {
    $st = $pdo->prepare('SELECT COUNT(*) FROM hospital_shift_slots WHERE slot_date = ?');
    $st->execute([$date]);
    $data['shift_count'] = (int)$st->fetchColumn();

    $st = $pdo->prepare('SELECT COUNT(*) total, COALESCE(SUM(is_done = 1),0) done FROM hospital_daily_checks WHERE check_date = ?');
    $st->execute([$date]);
    $data['checks'] = $st->fetch();

    $st = $pdo->prepare('SELECT COALESCE(cash_amount + card_amount + transfer_amount - refund_amount, 0) FROM hospital_cash_closings WHERE closing_date = ?');
    $st->execute([$date]);
    $data['closing_total'] = (int)$st->fetchColumn();
    $data['low_stock_count'] = (int)$pdo->query("SELECT COUNT(*) FROM hospital_assets WHERE asset_type='재고' AND current_qty IS NOT NULL AND min_qty IS NOT NULL AND current_qty <= min_qty")->fetchColumn();
    $data['credential_due_count'] = (int)$pdo->query("SELECT COUNT(*) FROM hospital_staff_credentials WHERE expire_date IS NOT NULL AND expire_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();
}

$titleMap = [
    'dashboard' => '병원 운영 대시보드',
    'shifts' => '주간 근무표',
    'checks' => '일일점검표',
    'closing' => '수납마감표',
    'assets' => '재고/장비 및 발주 현황',
    'training' => '직원 교육/자격 현황',
];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($titleMap[$type]) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/variable/pretendardvariable-dynamic-subset.min.css">
    <style>
        *{box-sizing:border-box}
        body{margin:0;background:#eef2f7;color:#111827;font-family:'Pretendard Variable',Pretendard,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;letter-spacing:-0.01em;-webkit-font-smoothing:antialiased}
        .toolbar{position:sticky;top:0;z-index:2;display:flex;justify-content:space-between;align-items:center;gap:12px;padding:14px 24px;background:#111827;color:white}
        .toolbar button{border:0;border-radius:8px;padding:9px 14px;background:#4f6aff;color:white;font-weight:700;cursor:pointer}
        .page{max-width:1080px;margin:28px auto;padding:42px;background:white;box-shadow:0 20px 60px rgba(15,23,42,.12);line-height:1.8;word-break:keep-all}
        .header{display:flex;justify-content:space-between;gap:24px;border-bottom:2px solid #111827;padding-bottom:18px;margin-bottom:24px}
        .brand{font-size:13px;color:#4b5563;font-weight:700}
        h1{margin:6px 0 0;font-size:28px}
        .meta{font-size:13px;color:#4b5563;text-align:right;line-height:1.7}
        .grid{display:grid;gap:12px}
        .grid-2{grid-template-columns:repeat(2,1fr)}
        .grid-3{grid-template-columns:repeat(3,1fr)}
        .card{border:1px solid #d1d5db;border-radius:10px;padding:16px;background:#fff}
        .label{font-size:12px;color:#6b7280}
        .value{margin-top:6px;font-size:22px;font-weight:700;letter-spacing:-0.02em;font-feature-settings:"tnum" 1;font-variant-numeric:tabular-nums}
        table{width:100%;border-collapse:collapse;margin-top:14px;font-size:13px}
        th,td{border:1px solid #d1d5db;padding:9px 10px;text-align:left;vertical-align:top}
        th{background:#f3f4f6;font-weight:800}
        .status{display:inline-block;border-radius:999px;padding:2px 8px;font-size:12px;font-weight:800}
        .ok{background:#d1fae5;color:#065f46}.warn{background:#fef3c7;color:#92400e}.danger{background:#fee2e2;color:#991b1b}.muted{background:#e5e7eb;color:#374151}
        .section{margin-top:28px}
        .section h2{font-size:17px;margin:0 0 10px}
        .sign{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-top:36px}
        .sign div{height:74px;border:1px solid #d1d5db;border-radius:8px;padding:10px;font-size:12px;color:#4b5563}
        .empty{padding:34px;border:1px dashed #cbd5e1;border-radius:10px;text-align:center;color:#64748b}
        @media print{@page{margin:0}body{background:white;padding:0}.toolbar{display:none}.page{margin:0;max-width:none;box-shadow:none;padding:15mm}.card,table,.sign div{break-inside:avoid}}
    </style>
</head>
<body>
<div class="toolbar">
    <div><?= h($titleMap[$type]) ?></div>
    <button onclick="window.print()">인쇄 / PDF 저장</button>
</div>
<main class="page">
    <div class="header">
        <div>
            <div class="brand">Zaemit Groupware · 병원 전용</div>
            <h1><?= h($titleMap[$type]) ?></h1>
        </div>
        <div class="meta">
            기준일: <?= h($date) ?><br>
            출력자: <?= h($user['name'] ?? '-') ?><br>
            출력일시: <?= h(date('Y-m-d H:i')) ?>
        </div>
    </div>

    <?php if ($type === 'dashboard'): ?>
        <div class="grid grid-3">
            <div class="card"><div class="label">오늘 근무</div><div class="value"><?= h($data['shift_count']) ?>명</div></div>
            <div class="card"><div class="label">점검 완료</div><div class="value"><?= h((int)($data['checks']['done'] ?? 0)) ?>/<?= h((int)($data['checks']['total'] ?? 0)) ?></div></div>
            <div class="card"><div class="label">수납 마감</div><div class="value"><?= h(won($data['closing_total'])) ?></div></div>
            <div class="card"><div class="label">부족 재고</div><div class="value"><?= h($data['low_stock_count']) ?>건</div></div>
            <div class="card"><div class="label">교육/자격 만료 예정</div><div class="value"><?= h($data['credential_due_count']) ?>건</div></div>
        </div>
    <?php elseif ($type === 'shifts'): ?>
        <div class="section"><h2><?= h($weekFrom) ?> ~ <?= h($weekTo) ?></h2>
        <?php if (!$data['shifts']): ?><div class="empty">등록된 근무가 없습니다.</div><?php else: ?>
        <table><thead><tr><th>일자</th><th>구분</th><th>역할</th><th>직원</th><th>시간</th><th>상태</th><th>비고</th></tr></thead><tbody>
        <?php foreach ($data['shifts'] as $row): ?><tr><td><?= h($row['slot_date']) ?></td><td><?= h($row['shift_type']) ?></td><td><?= h($row['role_name']) ?></td><td><?= h($row['employee_name']) ?></td><td><?= h(substr($row['start_time'],0,5)) ?>-<?= h(substr($row['end_time'],0,5)) ?></td><td><span class="status <?= h(statusClass($row['status'])) ?>"><?= h($row['status']) ?></span></td><td><?= h($row['note']) ?></td></tr><?php endforeach; ?>
        </tbody></table><?php endif; ?></div>
        <div class="section"><h2>휴가/대체근무</h2>
        <?php if (!$data['leaves']): ?><div class="empty">휴가/대체근무 신청이 없습니다.</div><?php else: ?>
        <table><thead><tr><th>직원</th><th>구분</th><th>기간</th><th>대체근무자</th><th>상태</th><th>사유</th></tr></thead><tbody>
        <?php foreach ($data['leaves'] as $row): ?><tr><td><?= h($row['employee_name']) ?></td><td><?= h($row['leave_type']) ?></td><td><?= h($row['start_date']) ?> ~ <?= h($row['end_date']) ?></td><td><?= h($row['substitute_name'] ?: '-') ?></td><td><span class="status <?= h(statusClass($row['status'])) ?>"><?= h($row['status']) ?></span></td><td><?= h($row['reason']) ?></td></tr><?php endforeach; ?>
        </tbody></table><?php endif; ?></div>
    <?php elseif ($type === 'checks'): ?>
        <?php if (!$data['checks']): ?><div class="empty">점검 항목이 없습니다.</div><?php else: ?>
        <table><thead><tr><th>구분</th><th>분류</th><th>항목</th><th>상태</th><th>확인자</th><th>확인시각</th><th>비고</th></tr></thead><tbody>
        <?php foreach ($data['checks'] as $row): $done = (int)$row['is_done'] === 1; ?><tr><td><?= h($row['shift_type']) ?></td><td><?= h($row['category']) ?></td><td><?= h($row['item_name']) ?></td><td><span class="status <?= $done ? 'ok' : 'warn' ?>"><?= $done ? '완료' : '대기' ?></span></td><td><?= h($row['checked_by'] ?: '-') ?></td><td><?= h($row['checked_at'] ?: '-') ?></td><td><?= h($row['note']) ?></td></tr><?php endforeach; ?>
        </tbody></table><?php endif; ?>
    <?php elseif ($type === 'closing'): $row = $data['closing']; ?>
        <?php if (!$row): ?><div class="empty">수납마감 데이터가 없습니다.</div><?php else: $total = (int)$row['cash_amount'] + (int)$row['card_amount'] + (int)$row['transfer_amount'] - (int)$row['refund_amount']; ?>
        <div class="grid grid-3">
            <div class="card"><div class="label">총 수납</div><div class="value"><?= h(won($total)) ?></div></div>
            <div class="card"><div class="label">내원 수</div><div class="value"><?= h($row['patient_count']) ?>명</div></div>
            <div class="card"><div class="label">상태</div><div class="value"><span class="status <?= h(statusClass($row['status'])) ?>"><?= h($row['status']) ?></span></div></div>
        </div>
        <table><tbody><tr><th>현금</th><td><?= h(won($row['cash_amount'])) ?></td><th>카드</th><td><?= h(won($row['card_amount'])) ?></td></tr><tr><th>계좌이체</th><td><?= h(won($row['transfer_amount'])) ?></td><th>환불</th><td><?= h(won($row['refund_amount'])) ?></td></tr><tr><th>미수</th><td><?= h(won($row['unpaid_amount'])) ?></td><th>마감자</th><td><?= h($row['closed_by'] ?: '-') ?></td></tr><tr><th>메모</th><td colspan="3"><?= h($row['memo']) ?></td></tr></tbody></table>
        <?php endif; ?>
    <?php elseif ($type === 'assets'): ?>
        <div class="section"><h2>재고/장비</h2><?php if (!$data['assets']): ?><div class="empty">등록된 재고/장비가 없습니다.</div><?php else: ?><table><thead><tr><th>구분</th><th>명칭</th><th>분류</th><th>재고</th><th>위치</th><th>거래처</th><th>상태</th><th>일자</th></tr></thead><tbody><?php foreach ($data['assets'] as $row): ?><tr><td><?= h($row['asset_type']) ?></td><td><?= h($row['name']) ?></td><td><?= h($row['category']) ?></td><td><?= h(($row['current_qty'] ?? '-') . '/' . ($row['min_qty'] ?? '-') . ' ' . ($row['unit'] ?? '')) ?></td><td><?= h($row['location']) ?></td><td><?= h($row['vendor']) ?></td><td><span class="status <?= h(statusClass($row['status'])) ?>"><?= h($row['status']) ?></span></td><td><?= h($row['expire_date'] ?: $row['next_due_date'] ?: '-') ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></div>
        <div class="section"><h2>발주 요청</h2><?php if (!$data['purchases']): ?><div class="empty">발주 요청이 없습니다.</div><?php else: ?><table><thead><tr><th>품목</th><th>수량</th><th>요청자</th><th>거래처</th><th>상태</th><th>사유</th></tr></thead><tbody><?php foreach ($data['purchases'] as $row): ?><tr><td><?= h($row['item_name']) ?></td><td><?= h($row['requested_qty'] . ($row['unit'] ?? '')) ?></td><td><?= h($row['requester_name']) ?></td><td><?= h($row['vendor']) ?></td><td><span class="status <?= h(statusClass($row['status'])) ?>"><?= h($row['status']) ?></span></td><td><?= h($row['reason']) ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></div>
    <?php else: ?>
        <?php if (!$data['credentials']): ?><div class="empty">등록된 교육/자격이 없습니다.</div><?php else: ?><table><thead><tr><th>직원</th><th>구분</th><th>교육/자격</th><th>발급일</th><th>만료일</th><th>상태</th><th>비고</th></tr></thead><tbody><?php foreach ($data['credentials'] as $row): ?><tr><td><?= h($row['employee_name']) ?></td><td><?= h($row['credential_type']) ?></td><td><?= h($row['credential_name']) ?></td><td><?= h($row['issue_date'] ?: '-') ?></td><td><?= h($row['expire_date'] ?: '-') ?></td><td><span class="status <?= h(statusClass($row['status'])) ?>"><?= h($row['status']) ?></span></td><td><?= h($row['memo']) ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?>
    <?php endif; ?>

    <div class="sign">
        <div>작성자 서명</div>
        <div>검토자 서명</div>
        <div>대표자 서명</div>
    </div>
</main>
</body>
</html>
