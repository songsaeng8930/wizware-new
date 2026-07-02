<?php
/**
 * 종합 데모 더미데이터 시드 (프로필 + 기능 테이블)
 *
 * 안전장치:
 *  - 각 테이블은 "현재 비어있을 때만" 채운다 (COUNT=0 확인). 실데이터가 있으면 건너뜀 → 비파괴/멱등.
 *  - 외래키(employee_id, post_id, resource_item_id)는 실제 존재하는 값만 사용.
 *
 * 채우는 테이블:
 *  프로필: careers, educations, certifications, languages, families, awards, military, skills
 *  연차확장: leave_adjustments, leave_carryovers, leave_promotions, leave_settlements
 *  기타: board_comments, reservations, reservation_resource_config, personal_tasks
 *
 * 제외: board_attachments (실제 파일이 없어 다운로드가 깨지므로 더미 부적합)
 *
 * 실행: php db/seed_demo_all.php
 */

require_once __DIR__ . '/../config/database.php';
$pdo = getDBConnection();
if (!$pdo) { fwrite(STDERR, "DB 연결 실패\n"); exit(1); }
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$year = (int)date('Y');
$report = [];

/** 테이블이 비어있으면 콜백 실행, 아니면 skip */
function seedIfEmpty(PDO $pdo, string $table, callable $fn, array &$report): void
{
    $n = (int)$pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
    if ($n > 0) { $report[$table] = "skip (이미 {$n}행)"; return; }
    $pdo->beginTransaction();
    try {
        $count = $fn($pdo);
        $pdo->commit();
        $report[$table] = "삽입 {$count}행";
    } catch (Throwable $e) {
        $pdo->rollBack();
        $report[$table] = "실패: " . $e->getMessage();
    }
}

/* 활성 직원 로드 */
$emps = $pdo->query("SELECT id, name, gender, position, hire_date, department_id
    FROM employees WHERE is_active = 1 AND employment_status <> '퇴사' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$empIds = array_column($emps, 'id');
$approver = (int)($pdo->query("SELECT id FROM employees WHERE position='대표이사' AND is_active=1 LIMIT 1")->fetchColumn() ?: ($empIds[0] ?? 1));

function pick(array $a, int $seed) { return $a[$seed % count($a)]; }

/* ─────────── 프로필: 경력 ─────────── */
seedIfEmpty($pdo, 'employee_careers', function(PDO $pdo) use ($emps) {
    $companies = ['삼성전자','네이버','카카오','LG전자','쿠팡','우아한형제들','토스','당근마켓','SK하이닉스','현대자동차','KT','CJ제일제당'];
    $jobs = ['영업관리','백엔드 개발','프론트엔드 개발','인사운영','회계','마케팅','기획','디자인','품질관리','고객지원'];
    $ins = $pdo->prepare("INSERT INTO employee_careers
        (employee_id, company_name, department, position, job_type, employment_type, start_date, end_date, is_current, leave_reason, description)
        VALUES (?,?,?,?,?,?,?,?,0,?,?)");
    $c = 0;
    foreach ($emps as $i => $e) {
        if ($e['id'] % 6 === 0) continue; // 일부는 경력 없음(신입)
        $eid = (int)$e['id'];
        $start = sprintf('%04d-%02d-01', 2015 + ($eid % 5), 1 + ($eid % 9));
        $end   = sprintf('%04d-%02d-28', 2019 + ($eid % 3), 1 + ($eid % 9));
        $ins->execute([$eid, pick($companies,$eid), pick(['영업본부','개발본부','경영지원본부','마케팅실'],$eid+1),
            pick(['사원','대리','과장'],$eid), pick($jobs,$eid), '정규직', $start, $end, '이직', pick($jobs,$eid).' 담당']);
        $c++;
    }
    return $c;
}, $report);

/* ─────────── 프로필: 학력 ─────────── */
seedIfEmpty($pdo, 'employee_educations', function(PDO $pdo) use ($emps) {
    $schools = ['서울대학교','연세대학교','고려대학교','성균관대학교','한양대학교','중앙대학교','경희대학교','서강대학교','부산대학교','인하대학교','건국대학교','동국대학교'];
    $majors  = ['경영학과','컴퓨터공학과','전자공학과','산업공학과','회계학과','시각디자인학과','국어국문학과','심리학과','경제학과','정보통신공학과'];
    $ins = $pdo->prepare("INSERT INTO employee_educations
        (employee_id, school_name, major, degree, school_type, gpa, gpa_scale, start_date, end_date, status)
        VALUES (?,?,?,?, '대학교(4년)', ?, 4.5, ?, ?, '졸업')");
    $c = 0;
    foreach ($emps as $e) {
        $eid = (int)$e['id'];
        $enroll = 2008 + ($eid % 8);
        $ins->execute([$eid, pick($schools,$eid), pick($majors,$eid), '학사',
            round(3.0 + ($eid % 15) / 10, 2), sprintf('%04d-03-02',$enroll), sprintf('%04d-02-25',$enroll+4)]);
        $c++;
    }
    return $c;
}, $report);

/* ─────────── 프로필: 자격증 ─────────── */
seedIfEmpty($pdo, 'employee_certifications', function(PDO $pdo) use ($emps) {
    $certs = [
        ['정보처리기사','한국산업인력공단'],
        ['컴퓨터활용능력 1급','대한상공회의소'],
        ['SQL 개발자(SQLD)','한국데이터산업진흥원'],
        ['전산세무 2급','한국세무사회'],
        ['사회조사분석사 2급','한국산업인력공단'],
        ['GTQ 1급','한국생산성본부'],
    ];
    $ins = $pdo->prepare("INSERT INTO employee_certifications
        (employee_id, cert_name, issuing_org, cert_number, acquired_date) VALUES (?,?,?,?,?)");
    $c = 0;
    foreach ($emps as $e) {
        $eid = (int)$e['id'];
        if ($eid % 2 === 1) continue; // 절반만 보유
        $cert = pick($certs, $eid);
        $ins->execute([$eid, $cert[0], $cert[1], sprintf('%04d-%06d', 2018 + ($eid%5), $eid*137%999999),
            sprintf('%04d-%02d-15', 2018 + ($eid%5), 1 + ($eid%9))]);
        $c++;
    }
    return $c;
}, $report);

/* ─────────── 프로필: 어학 ─────────── */
seedIfEmpty($pdo, 'employee_languages', function(PDO $pdo) use ($emps) {
    $ins = $pdo->prepare("INSERT INTO employee_languages
        (employee_id, language, level, test_type, test_name, test_score, test_date) VALUES (?,?,?,?,?,?,?)");
    $c = 0;
    foreach ($emps as $e) {
        $eid = (int)$e['id'];
        if ($eid % 3 === 2) continue; // 2/3만 보유
        $score = 700 + ($eid * 13) % 250;
        $level = $score >= 900 ? '상' : ($score >= 800 ? '중상' : '중');
        $ins->execute([$eid, '영어', $level, 'TOEIC', 'TOEIC', (string)$score, sprintf('%04d-%02d-10', 2023 + ($eid%3), 1+($eid%9))]);
        if ($eid % 5 === 0) { // 일부는 일본어 추가
            $ins->execute([$eid, '일본어', '중', 'JLPT', 'JLPT', 'N2', sprintf('%04d-07-05', 2023 + ($eid%3))]);
            $c++;
        }
        $c++;
    }
    return $c;
}, $report);

/* ─────────── 프로필: 가족 ─────────── */
seedIfEmpty($pdo, 'employee_families', function(PDO $pdo) use ($emps) {
    $sur = ['김','이','박','최','정','강','조','윤','장','임'];
    $given = ['서준','하윤','도윤','서연','지호','하은','예준','수아','지우','유진'];
    $ins = $pdo->prepare("INSERT INTO employee_families
        (employee_id, relationship, name, birth_date, is_cohabitant, is_dependent) VALUES (?,?,?,?,?,?)");
    $c = 0;
    foreach ($emps as $e) {
        $eid = (int)$e['id'];
        if ($eid % 4 === 3) continue; // 일부 미등록
        $s = pick($sur, $eid);
        // 배우자
        $ins->execute([$eid, '배우자', $s.pick($given,$eid+2), sprintf('%04d-%02d-%02d', 1985+($eid%8), 1+($eid%12), 1+($eid%27)), 1, 0]);
        $c++;
        // 자녀 (일부)
        if ($eid % 2 === 0) {
            $ins->execute([$eid, '자녀', $s.pick($given,$eid), sprintf('%04d-%02d-%02d', 2015+($eid%6), 1+($eid%12), 1+($eid%27)), 1, 1]);
            $c++;
        }
    }
    return $c;
}, $report);

/* ─────────── 프로필: 수상/징계 ─────────── */
seedIfEmpty($pdo, 'employee_awards', function(PDO $pdo) use ($emps) {
    $titles = ['연간 우수사원상','분기 MVP','장기근속 표창','제안왕','고객만족 대상'];
    $ins = $pdo->prepare("INSERT INTO employee_awards
        (employee_id, type, title, awarded_date, awarding_org, description) VALUES (?,?,?,?, '(주)재밋', ?)");
    $c = 0;
    foreach ($emps as $e) {
        $eid = (int)$e['id'];
        if ($eid % 3 !== 0) continue; // 1/3만 수상
        $ins->execute([$eid, '수상', pick($titles,$eid), sprintf('%04d-12-20', 2022+($eid%4)), pick($titles,$eid).' 수상']);
        $c++;
    }
    return $c;
}, $report);

/* ─────────── 프로필: 병역 (남자만) ─────────── */
seedIfEmpty($pdo, 'employee_military', function(PDO $pdo) use ($emps) {
    $ins = $pdo->prepare("INSERT IGNORE INTO employee_military
        (employee_id, military_status, branch, rank_title, enlist_date, discharge_date, discharge_type) VALUES (?,?,?,?,?,?,?)");
    $c = 0;
    foreach ($emps as $e) {
        $eid = (int)$e['id'];
        if (($e['gender'] ?? '') !== 'M') {
            // 여성: 비대상
            $pdo->prepare("INSERT IGNORE INTO employee_military (employee_id, military_status) VALUES (?, '비대상')")->execute([$eid]);
            $c++; continue;
        }
        $branch = pick(['육군','해군','공군','의무경찰'], $eid);
        $enl = sprintf('%04d-%02d-05', 2008+($eid%6), 1+($eid%9));
        $dis = date('Y-m-d', strtotime($enl.' +18 months'));
        $ins->execute([$eid, '군필', $branch, '병장', $enl, $dis, '만기전역']);
        $c++;
    }
    return $c;
}, $report);

/* ─────────── 프로필: 스킬 태그 ─────────── */
seedIfEmpty($pdo, 'employee_skills', function(PDO $pdo) use ($emps) {
    $pool = ['Excel','PowerPoint','SQL','Python','Java','JavaScript','React','Figma','포토샵','프로젝트관리','데이터분석','회계','영어회화','ERP운영','카피라이팅','재무모델링'];
    $ins = $pdo->prepare("INSERT IGNORE INTO employee_skills (employee_id, skill_name) VALUES (?,?)");
    $c = 0;
    foreach ($emps as $e) {
        $eid = (int)$e['id'];
        $k = 2 + ($eid % 3); // 2~4개
        for ($j = 0; $j < $k; $j++) {
            $ins->execute([$eid, pick($pool, $eid + $j * 5)]);
            $c += $ins->rowCount();
        }
    }
    return $c;
}, $report);

/* ─────────── 연차: 조정 ─────────── */
seedIfEmpty($pdo, 'leave_adjustments', function(PDO $pdo) use ($emps, $year, $approver) {
    $ins = $pdo->prepare("INSERT INTO leave_adjustments
        (employee_id, year, adjust_type, adjust_days, reason, category, created_by) VALUES (?,?,?,?,?,?,?)");
    $c = 0;
    foreach ($emps as $e) {
        $eid = (int)$e['id'];
        if ($eid % 5 !== 0) continue;
        $ins->execute([$eid, $year, 'add', 1.0, '우수사원 포상 연차', '포상', $approver]);
        $c++;
    }
    return $c;
}, $report);

/* ─────────── 연차: 이월 (전년→올해, 승인) ─────────── */
seedIfEmpty($pdo, 'leave_carryovers', function(PDO $pdo) use ($emps, $year, $approver) {
    $ins = $pdo->prepare("INSERT IGNORE INTO leave_carryovers
        (employee_id, from_year, to_year, days, reason, status, agreement_date, approved_by, approved_at)
        VALUES (?,?,?,?,?, '승인', ?, ?, NOW())");
    $c = 0;
    foreach ($emps as $e) {
        $eid = (int)$e['id'];
        if ($eid % 4 !== 1) continue;
        $ins->execute([$eid, $year-1, $year, 2.0, '미사용 연차 이월 합의', sprintf('%04d-01-05',$year), $approver]);
        $c++;
    }
    return $c;
}, $report);

/* ─────────── 연차: 촉진 (올해 1차, 미응답) ─────────── */
seedIfEmpty($pdo, 'leave_promotions', function(PDO $pdo) use ($emps, $year, $approver) {
    $ins = $pdo->prepare("INSERT IGNORE INTO leave_promotions
        (employee_id, year, stage, notified_at, deadline, response_status, created_by)
        VALUES (?,?,1, NOW(), ?, '미응답', ?)");
    $c = 0;
    foreach ($emps as $e) {
        $eid = (int)$e['id'];
        if ($eid % 6 !== 2) continue;
        $ins->execute([$eid, $year, sprintf('%04d-07-31',$year), $approver]);
        $c++;
    }
    return $c;
}, $report);

/* ─────────── 연차: 퇴사자 정산 (resign_date NULL 보정 포함) ─────────── */
seedIfEmpty($pdo, 'leave_settlements', function(PDO $pdo) use ($year, $approver) {
    $basePay = ['대표이사'=>5000000,'이사'=>4500000,'부장'=>4000000,'차장'=>3500000,'과장'=>3000000,'대리'=>2500000,'주임'=>2200000,'사원'=>2000000,'인턴'=>1800000];
    $resigns = [ // 퇴사 상태인데 resign_date NULL 이던 직원 보정값
        23 => '2026-03-31', 24 => '2026-05-31', 25 => '2026-06-30',
    ];
    $ins = $pdo->prepare("INSERT INTO leave_settlements
        (employee_id, year, resign_date, hire_date, worked_months, prorated_days, used_days, remaining_days,
         base_salary, daily_wage, settlement_amount, settled_by, settled_at, memo)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?, NOW(), ?)");
    $c = 0;
    foreach ($resigns as $eid => $resign) {
        $emp = $pdo->prepare("SELECT hire_date, position FROM employees WHERE id=?");
        $emp->execute([$eid]);
        $r = $emp->fetch(PDO::FETCH_ASSOC);
        if (!$r) continue;
        // resign_date NULL 보정 (정합성 수정)
        $pdo->prepare("UPDATE employees SET resign_date=? WHERE id=? AND resign_date IS NULL")->execute([$resign, $eid]);
        $hire = $r['hire_date'] ?: '2022-01-01';
        $months = round((strtotime($resign) - strtotime(sprintf('%04d-01-01',$year))) / (86400*30), 1);
        $months = max(1.0, $months);
        $prorated = round(15 * $months / 12, 1);
        $used = round($prorated * 0.4, 1);
        $remaining = round($prorated - $used, 1);
        $base = $basePay[$r['position']] ?? 2500000;
        $daily = (int)round($base / 30);
        $ins->execute([$eid, $year, $resign, $hire, $months, $prorated, $used, $remaining,
            $base, $daily, (int)round($remaining * $daily), $approver, '퇴사자 연차 정산']);
        $c++;
    }
    return $c;
}, $report);

/* ─────────── 게시판 댓글 ─────────── */
seedIfEmpty($pdo, 'board_comments', function(PDO $pdo) use ($emps) {
    $texts = ['좋은 정보 감사합니다.','확인했습니다.','참고하겠습니다.','도움이 많이 됐어요.','공유 감사합니다!','수고하셨습니다.','잘 봤습니다.','문의드릴 게 있어요.'];
    $posts = $pdo->query("SELECT id FROM board_posts ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
    // 부서명 조회용
    $dept = [];
    foreach ($pdo->query("SELECT e.id, COALESCE(d.name,'') dn FROM employees e LEFT JOIN departments d ON d.id=e.department_id") as $row) $dept[(int)$row['id']] = $row['dn'];
    $ins = $pdo->prepare("INSERT INTO board_comments (post_id, author_id, author_name, author_dept, content, status)
        VALUES (?,?,?,?,?, 'active')");
    $c = 0;
    foreach ($posts as $k => $pid) {
        if ($k % 2 === 1) continue; // 절반 게시글에만
        $n = 1 + ($pid % 3);
        for ($j = 0; $j < $n; $j++) {
            $e = $emps[($pid + $j) % count($emps)];
            $ins->execute([(int)$pid, (int)$e['id'], $e['name'], $dept[(int)$e['id']] ?? '', pick($texts, $pid + $j)]);
            $c++;
        }
    }
    return $c;
}, $report);

/* ─────────── 자원예약 설정 + 예약 ─────────── */
seedIfEmpty($pdo, 'reservation_resource_config', function(PDO $pdo) {
    $items = [58=>1, 59=>1, 60=>3, 61=>2]; // 회의실1, 탕비실1, 노트북3, 태블릿2
    $ins = $pdo->prepare("INSERT IGNORE INTO reservation_resource_config (item_id, max_count) VALUES (?,?)");
    $c = 0;
    foreach ($items as $id => $max) { $ins->execute([$id, $max]); $c++; }
    return $c;
}, $report);

seedIfEmpty($pdo, 'reservations', function(PDO $pdo) use ($emps) {
    $resources = [58,59,60,61];
    $titles = ['주간 팀 회의','1:1 미팅','프로젝트 킥오프','고객사 미팅 준비','디자인 리뷰','스프린트 회고','채용 면접','교육 세션'];
    $ins = $pdo->prepare("INSERT INTO reservations
        (resource_item_id, title, user_name, reservation_date, start_time, end_time, description, status)
        VALUES (?,?,?,?,?,?,?, 'confirmed')");
    $c = 0;
    for ($i = 0; $i < 14; $i++) {
        $e = $emps[$i % count($emps)];
        $res = $resources[$i % count($resources)];
        $date = date('Y-m-d', strtotime("+".($i - 2)." days")); // 과거~미래 섞기
        $sh = 9 + ($i % 7);
        $start = sprintf('%02d:00:00', $sh);
        $end   = sprintf('%02d:00:00', $sh + 1);
        $ins->execute([$res, pick($titles,$i), $e['name'], $date, $start, $end, pick($titles,$i).' 예약']);
        $c++;
    }
    return $c;
}, $report);

/* ─────────── 개인 할일 ─────────── */
seedIfEmpty($pdo, 'personal_tasks', function(PDO $pdo) use ($emps) {
    $tasks = ['주간 보고서 작성','거래처 견적 회신','경비 정산 제출','회의록 정리','신규 입사자 온보딩','월말 마감 점검','디자인 시안 검토','코드 리뷰','계약서 검토','비품 발주'];
    $prio = ['low','normal','high','urgent'];
    $ins = $pdo->prepare("INSERT INTO personal_tasks
        (owner_id, title, description, due_date, priority, status, completed_at)
        VALUES (?,?,?,?,?,?,?)");
    $c = 0;
    foreach ($emps as $e) {
        $eid = (int)$e['id'];
        if ($eid % 2 === 1) continue; // 절반 직원
        $n = 2 + ($eid % 3);
        for ($j = 0; $j < $n; $j++) {
            $done = ($j % 3 === 0);
            $due = date('Y-m-d', strtotime("+".(($eid + $j) % 14 - 3)." days"));
            $ins->execute([$eid, pick($tasks,$eid+$j), pick($tasks,$eid+$j).' 처리', $due,
                pick($prio,$eid+$j), $done ? 'done':'todo', $done ? date('Y-m-d H:i:s') : null]);
            $c++;
        }
    }
    return $c;
}, $report);

/* ─────────── 결과 출력 ─────────── */
echo "=== 종합 시드 결과 ===\n";
foreach ($report as $t => $msg) printf("  %-28s %s\n", $t, $msg);
