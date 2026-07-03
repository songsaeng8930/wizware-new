<?php
$pageTitle = '사용자 매뉴얼';
$currentPage = 'manual';
$suppressAutoTitle = true;
require_once __DIR__ . '/../includes/permissions.php';
requireMenuPermission('groupware', 'view'); // 접근권한 관리 연동 (admin 항상 통과)
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$basePath = rtrim(str_replace('\\', '/', str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', realpath(__DIR__ . '/..'))), '/');
$imgBase = $basePath . '/assets/manual';
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
<main class="p-6 bg-slate-900" style="min-height: calc(100vh - 3.5rem)">

<!-- 매뉴얼 상단 -->
<div class="max-w-5xl mx-auto">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-100 mb-2">Zaemit 그룹웨어 사용자 매뉴얼</h1>
        <p class="text-sm text-slate-400">이 매뉴얼은 Zaemit 그룹웨어의 모든 기능을 처음 사용하는 분도 쉽게 이해할 수 있도록 작성되었습니다.</p>
    </div>

    <!-- 목차 -->
    <div class="bg-slate-950 rounded-xl p-6 mb-10">
        <h2 class="text-sm font-bold text-slate-100 mb-4">목차</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-2">
            <a href="#sec-dashboard" class="text-sm text-primary hover:underline">1. 대시보드</a>
            <a href="#sec-schedule" class="text-sm text-primary hover:underline">2. 일정 관리</a>
            <a href="#sec-attendance" class="text-sm text-primary hover:underline">3. 근태 관리</a>
            <a href="#sec-approval" class="text-sm text-primary hover:underline">4. 전자결재</a>
            <a href="#sec-board" class="text-sm text-primary hover:underline">5. 게시판</a>
            <a href="#sec-hr" class="text-sm text-primary hover:underline">6. 인사관리</a>
            <a href="#sec-accounting" class="text-sm text-primary hover:underline">7. 재무관리</a>
            <a href="#sec-labor" class="text-sm text-primary hover:underline">8. 노무관리</a>
            <a href="#sec-payslip" class="text-sm text-primary hover:underline">9. 급여명세</a>
            <a href="#sec-business-docs" class="text-sm text-primary hover:underline">10. 업무자료실/의뢰</a>
            <a href="#sec-settings" class="text-sm text-primary hover:underline">11. 시스템 설정</a>
        </div>
    </div>

    <!-- ===== 1. 대시보드 ===== -->
    <section id="sec-dashboard" class="mb-14 scroll-mt-20">
        <h2 class="text-lg font-bold text-slate-100 mb-1 flex items-center gap-2">
            <span class="w-7 h-7 bg-primary text-white rounded-lg flex items-center justify-center text-sm font-bold">1</span>
            대시보드
        </h2>
        <p class="text-sm text-slate-400 mb-4">로그인 후 가장 먼저 보이는 메인 화면입니다. 오늘 해야 할 일을 한눈에 파악할 수 있습니다.</p>
        <img src="<?= $imgBase ?>/dashboard.png" alt="대시보드 화면" class="rounded-xl border border-slate-800 shadow-sm mb-6 w-full">

        <div class="space-y-4">
            <div class="bg-slate-950 rounded-lg p-4">
                <h3 class="text-sm font-bold text-slate-100 mb-2">핵심 지표 카드 (상단)</h3>
                <ul class="text-sm text-slate-300 space-y-1.5 list-disc list-inside">
                    <li><strong>결재 대기</strong> · 나에게 도착한 결재 문서 수. 클릭하면 결재문서함으로 이동합니다.</li>
                    <li><strong>연차 잔여</strong> · 올해 남은 연차 일수. 클릭하면 연차관리 페이지로 이동합니다.</li>
                    <li><strong>이번 주 근무</strong> · 이번 주 누적 근무 시간과 목표 시간 대비 진행률을 보여줍니다.</li>
                    <li><strong>오늘 일정</strong> · 오늘 등록된 일정 수. 클릭하면 전체 캘린더로 이동합니다.</li>
                </ul>
            </div>

            <div class="bg-slate-950 rounded-lg p-4">
                <h3 class="text-sm font-bold text-slate-100 mb-2">월간 캘린더 (좌측)</h3>
                <ul class="text-sm text-slate-300 space-y-1.5 list-disc list-inside">
                    <li>이번 달 전체 일정이 달력 형태로 표시됩니다.</li>
                    <li><strong>날짜 클릭</strong> &rarr; 해당 날짜에 새 일정을 등록하는 팝업이 열립니다.</li>
                    <li><strong>일정 바(색깔 막대) 클릭</strong> &rarr; 일정 상세 정보를 확인하고 수정/삭제할 수 있습니다.</li>
                    <li>색상별로 카테고리가 구분됩니다. 카테고리는 시스템 관리 > 공통코드 관리에서 편집할 수 있습니다.</li>
                </ul>
            </div>

            <div class="bg-slate-950 rounded-lg p-4">
                <h3 class="text-sm font-bold text-slate-100 mb-2">출퇴근 및 근태 신청 (우측 상단)</h3>
                <ul class="text-sm text-slate-300 space-y-1.5 list-disc list-inside">
                    <li><strong>출근 버튼</strong>을 누르면 출근 시간이 기록됩니다. 출근 후에는 <strong>퇴근 버튼</strong>으로 바뀝니다.</li>
                    <li>휴가, 출장, 외근, 야근 바로가기 버튼으로 각 신청 페이지로 빠르게 이동할 수 있습니다.</li>
                </ul>
            </div>

            <div class="bg-slate-950 rounded-lg p-4">
                <h3 class="text-sm font-bold text-slate-100 mb-2">게시판 위젯 (우측 하단)</h3>
                <ul class="text-sm text-slate-300 space-y-1.5 list-disc list-inside">
                    <li>공지사항, 자유게시판, 업무자료실의 최신 글 5건이 표시됩니다.</li>
                    <li>빨간/주황 점(<span class="inline-block w-1.5 h-1.5 rounded-full bg-amber-100 align-middle"></span>)이 있으면 새 글입니다.</li>
                    <li>"전체보기" 링크로 해당 게시판 페이지로 이동합니다.</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- ===== 2. 일정 관리 ===== -->
    <section id="sec-schedule" class="mb-14 scroll-mt-20">
        <h2 class="text-lg font-bold text-slate-100 mb-1 flex items-center gap-2">
            <span class="w-7 h-7 bg-primary text-white rounded-lg flex items-center justify-center text-sm font-bold">2</span>
            일정 관리
        </h2>
        <p class="text-sm text-slate-400 mb-4">회사 전체 일정을 월간 달력으로 관리합니다. 회의, 출장, 교육, 외근 등 다양한 유형의 일정을 등록할 수 있습니다.</p>
        <img src="<?= $imgBase ?>/schedule.png" alt="일정 관리 화면" class="rounded-xl border border-slate-800 shadow-sm mb-6 w-full">

        <div class="space-y-4">
            <div class="bg-slate-950 rounded-lg p-4">
                <h3 class="text-sm font-bold text-slate-100 mb-2">일정 등록하기</h3>
                <ol class="text-sm text-slate-300 space-y-1.5 list-decimal list-inside">
                    <li>달력에서 원하는 <strong>날짜를 클릭</strong>합니다.</li>
                    <li>팝업 창에서 <strong>일정 제목</strong>, <strong>시작/종료 날짜와 시간</strong>을 입력합니다.</li>
                    <li><strong>카테고리</strong>(회의/외부미팅/출장/교육/외근/면담/행사/마감/기타)를 선택합니다.</li>
                    <li>필요하면 <strong>참석자</strong>를 검색하여 추가합니다.</li>
                    <li>"등록" 버튼을 누르면 일정이 저장됩니다.</li>
                </ol>
            </div>

            <div class="bg-slate-950 rounded-lg p-4">
                <h3 class="text-sm font-bold text-slate-100 mb-2">일정 수정/삭제</h3>
                <ul class="text-sm text-slate-300 space-y-1.5 list-disc list-inside">
                    <li>달력에서 <strong>일정 바(색깔 막대)를 클릭</strong>하면 상세 정보 팝업이 열립니다.</li>
                    <li>"수정" 버튼을 누르면 내용을 변경할 수 있습니다.</li>
                    <li>"삭제" 버튼을 누르면 확인 후 일정이 삭제됩니다.</li>
                </ul>
            </div>

            <div class="bg-slate-950 rounded-lg p-4">
                <h3 class="text-sm font-bold text-slate-100 mb-2">우측 패널</h3>
                <ul class="text-sm text-slate-300 space-y-1.5 list-disc list-inside">
                    <li><strong>다가오는 일정</strong>: 오늘 이후 예정된 일정 목록을 시간순으로 보여줍니다.</li>
                    <li><strong>카테고리 범례</strong>: 각 색상이 어떤 유형인지 표시합니다. 카테고리를 클릭하면 해당 유형만 필터링할 수 있습니다.</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- ===== 3. 근태 관리 ===== -->
    <section id="sec-attendance" class="mb-14 scroll-mt-20">
        <h2 class="text-lg font-bold text-slate-100 mb-1 flex items-center gap-2">
            <span class="w-7 h-7 bg-primary text-white rounded-lg flex items-center justify-center text-sm font-bold">3</span>
            근태 관리
        </h2>
        <p class="text-sm text-slate-400 mb-4">출퇴근 기록과 근무 현황을 확인하고 관리합니다.</p>
        <img src="<?= $imgBase ?>/attendance.png" alt="근태 관리 화면" class="rounded-xl border border-slate-800 shadow-sm mb-6 w-full">

        <div class="space-y-4">
            <div class="bg-slate-950 rounded-lg p-4">
                <h3 class="text-sm font-bold text-slate-100 mb-2">출퇴근 기록</h3>
                <ul class="text-sm text-slate-300 space-y-1.5 list-disc list-inside">
                    <li>대시보드 우측 또는 근태 페이지에서 <strong>출근/퇴근 버튼</strong>을 눌러 시간을 기록합니다.</li>
                    <li>출근 기록 후 퇴근 버튼으로 자동 전환됩니다.</li>
                    <li>하루에 한 번만 기록할 수 있습니다.</li>
                </ul>
            </div>

            <div class="bg-slate-950 rounded-lg p-4">
                <h3 class="text-sm font-bold text-slate-100 mb-2">근무 현황 확인</h3>
                <ul class="text-sm text-slate-300 space-y-1.5 list-disc list-inside">
                    <li>날짜별 출근/퇴근 시간, 근무 시간, 근무 상태(정상/지각/조퇴 등)를 확인할 수 있습니다.</li>
                    <li>월별/주별로 필터링하여 원하는 기간의 근태를 조회합니다.</li>
                    <li>각 직원의 근무 현황 상세 정보를 확인할 수 있습니다.</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- ===== 4. 전자결재 ===== -->
    <section id="sec-approval" class="mb-14 scroll-mt-20">
        <h2 class="text-lg font-bold text-slate-100 mb-1 flex items-center gap-2">
            <span class="w-7 h-7 bg-primary text-white rounded-lg flex items-center justify-center text-sm font-bold">4</span>
            전자결재
        </h2>
        <p class="text-sm text-slate-400 mb-4">휴가신청, 출장신청, 야근신청 등 각종 결재 문서를 온라인으로 작성하고 승인받을 수 있습니다.</p>
        <img src="<?= $imgBase ?>/approval_draft.png" alt="결재문서함 화면" class="rounded-xl border border-slate-800 shadow-sm mb-6 w-full">

        <div class="space-y-4">
            <div class="bg-slate-950 rounded-lg p-4">
                <h3 class="text-sm font-bold text-slate-100 mb-2">결재 문서 작성하기</h3>
                <ol class="text-sm text-slate-300 space-y-1.5 list-decimal list-inside">
                    <li>좌측 메뉴 "전자결재" &rarr; "결재문서함"에서 상단의 <strong>"새 결재 작성"</strong> 버튼을 클릭합니다.</li>
                    <li>또는 대시보드의 <strong>휴가/출장/외근/야근 바로가기</strong>를 클릭합니다.</li>
                    <li>결재 양식을 선택하고 내용을 작성합니다.</li>
                    <li>결재선(승인자)을 지정한 후 "제출" 버튼을 누릅니다.</li>
                </ol>
            </div>

            <div class="bg-slate-950 rounded-lg p-4">
                <h3 class="text-sm font-bold text-slate-100 mb-2">결재 처리하기</h3>
                <ul class="text-sm text-slate-300 space-y-1.5 list-disc list-inside">
                    <li><strong>결재문서함</strong>: 내가 작성했거나 결재해야 할 문서 목록입니다.</li>
                    <li><strong>결재완료함</strong>: 결재가 완료(승인/반려)된 문서를 확인합니다.</li>
                    <li><strong>문서보관함</strong>: 모든 결재 문서를 보관합니다.</li>
                    <li>문서를 클릭하면 상세 내용을 확인하고, 승인 또는 반려할 수 있습니다.</li>
                </ul>
            </div>

            <div class="bg-slate-950 rounded-lg p-4">
                <h3 class="text-sm font-bold text-slate-100 mb-2">결재선 설정 / 양식 관리</h3>
                <ul class="text-sm text-slate-300 space-y-1.5 list-disc list-inside">
                    <li><strong>결재선설정</strong>: 자주 사용하는 결재 경로(기안자 &rarr; 팀장 &rarr; 본부장 등)를 미리 저장해둘 수 있습니다.</li>
                    <li><strong>결재양식관리</strong>: 회사에서 사용하는 결재 양식을 추가하거나 수정합니다. (관리자 기능)</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- ===== 5. 게시판 ===== -->
    <section id="sec-board" class="mb-14 scroll-mt-20">
        <h2 class="text-lg font-bold text-slate-100 mb-1 flex items-center gap-2">
            <span class="w-7 h-7 bg-primary text-white rounded-lg flex items-center justify-center text-sm font-bold">5</span>
            게시판
        </h2>
        <p class="text-sm text-slate-400 mb-4">공지사항, 자유게시판, 자료실, 부서게시판 등 사내 소통 공간입니다.</p>
        <img src="<?= $imgBase ?>/board_notice.png" alt="게시판 화면" class="rounded-xl border border-slate-800 shadow-sm mb-6 w-full">

        <div class="space-y-4">
            <div class="bg-slate-950 rounded-lg p-4">
                <h3 class="text-sm font-bold text-slate-100 mb-2">게시판 종류</h3>
                <ul class="text-sm text-slate-300 space-y-1.5 list-disc list-inside">
                    <li><strong>공지사항</strong> · 회사 전체 공지를 게시합니다. 관리자만 작성할 수 있습니다.</li>
                    <li><strong>자유게시판</strong> · 직원들이 자유롭게 소통하는 공간입니다.</li>
                    <li><strong>자료실</strong> · 업무 관련 문서, 양식, 가이드라인 등을 공유합니다.</li>
                    <li><strong>부서게시판</strong> · 소속 부서원들만 열람할 수 있는 게시판입니다.</li>
                </ul>
            </div>

            <div class="bg-slate-950 rounded-lg p-4">
                <h3 class="text-sm font-bold text-slate-100 mb-2">글 작성하기</h3>
                <ol class="text-sm text-slate-300 space-y-1.5 list-decimal list-inside">
                    <li>원하는 게시판 탭(공지사항/자유/자료실/부서)을 선택합니다.</li>
                    <li>우측 상단 <strong>"글쓰기"</strong> 버튼을 클릭합니다.</li>
                    <li>제목과 내용을 작성하고, 필요하면 파일을 첨부합니다.</li>
                    <li>"등록" 버튼을 눌러 게시합니다.</li>
                </ol>
            </div>
        </div>
    </section>

    <!-- ===== 6. 인사관리 ===== -->
    <section id="sec-hr" class="mb-14 scroll-mt-20">
        <h2 class="text-lg font-bold text-slate-100 mb-1 flex items-center gap-2">
            <span class="w-7 h-7 bg-primary text-white rounded-lg flex items-center justify-center text-sm font-bold">6</span>
            인사관리
        </h2>
        <p class="text-sm text-slate-400 mb-4">직원 정보를 등록/수정하고, 조직도를 확인합니다.</p>

        <h3 class="text-sm font-bold text-slate-100 mt-6 mb-3">6-1. 직원관리</h3>
        <img src="<?= $imgBase ?>/employees.png" alt="직원관리 화면" class="rounded-xl border border-slate-800 shadow-sm mb-4 w-full">
        <div class="bg-slate-950 rounded-lg p-4 mb-6">
            <ul class="text-sm text-slate-300 space-y-1.5 list-disc list-inside">
                <li>전체 직원 목록을 테이블 형태로 확인합니다.</li>
                <li><strong>검색 및 필터</strong>: 이름, 부서, 직급으로 검색하거나 재직/퇴직 상태로 필터링합니다.</li>
                <li><strong>"직원 등록"</strong> 버튼으로 새 직원 정보를 입력합니다. (이름, 부서, 직급, 입사일, 연락처 등)</li>
                <li>직원 이름이나 "수정" 버튼을 클릭하면 정보를 변경할 수 있습니다.</li>
                <li>직원 정보는 급여, 연차, 근로계약서 등 다른 기능에서 자동으로 연동됩니다.</li>
            </ul>
        </div>

        <h3 class="text-sm font-bold text-slate-100 mt-6 mb-3">6-2. 조직도</h3>
        <img src="<?= $imgBase ?>/organization.png" alt="조직도 화면" class="rounded-xl border border-slate-800 shadow-sm mb-4 w-full">
        <div class="bg-slate-950 rounded-lg p-4">
            <ul class="text-sm text-slate-300 space-y-1.5 list-disc list-inside">
                <li>회사 전체 조직 구조를 트리 형태로 보여줍니다.</li>
                <li>본부 &rarr; 팀 &rarr; 직원 순서로 계층적으로 표시됩니다.</li>
                <li>각 부서를 클릭하면 소속 직원 정보가 표시됩니다.</li>
            </ul>
        </div>
    </section>

    <!-- ===== 7. 재무관리 ===== -->
    <section id="sec-accounting" class="mb-14 scroll-mt-20">
        <h2 class="text-lg font-bold text-slate-100 mb-1 flex items-center gap-2">
            <span class="w-7 h-7 bg-primary text-white rounded-lg flex items-center justify-center text-sm font-bold">7</span>
            재무관리
        </h2>
        <p class="text-sm text-slate-400 mb-4">법인카드, 계좌, 세금계산서, 세무리포트 등 회사 재무를 통합 관리합니다.</p>
        <img src="<?= $imgBase ?>/acct_dashboard.png" alt="재무 대시보드 화면" class="rounded-xl border border-slate-800 shadow-sm mb-6 w-full">

        <div class="space-y-4">
            <div class="bg-slate-950 rounded-lg p-4">
                <h3 class="text-sm font-bold text-slate-100 mb-2">재무 대시보드</h3>
                <ul class="text-sm text-slate-300 space-y-1.5 list-disc list-inside">
                    <li>좌측 메뉴 "재무관리"를 클릭하면 재무 대시보드로 이동합니다.</li>
                    <li>매출/매입 현황, 카드 사용 현황, 계좌 잔액 등을 한눈에 파악합니다.</li>
                </ul>
            </div>

            <div class="bg-slate-950 rounded-lg p-4">
                <h3 class="text-sm font-bold text-slate-100 mb-2">카드관리</h3>
                <img src="<?= $imgBase ?>/acct_card.png" alt="카드관리 화면" class="rounded-lg border border-slate-800 shadow-sm mb-3 w-full">
                <ul class="text-sm text-slate-300 space-y-1.5 list-disc list-inside">
                    <li>법인카드 목록과 각 카드의 사용 내역을 확인합니다.</li>
                    <li>카드 등록, 사용 내역 조회, 정산 처리를 할 수 있습니다.</li>
                    <li>카드별 사용 한도와 잔여 한도를 확인합니다.</li>
                </ul>
            </div>

            <div class="bg-slate-950 rounded-lg p-4">
                <h3 class="text-sm font-bold text-slate-100 mb-2">계좌관리</h3>
                <img src="<?= $imgBase ?>/acct_bank.png" alt="계좌관리 화면" class="rounded-lg border border-slate-800 shadow-sm mb-3 w-full">
                <ul class="text-sm text-slate-300 space-y-1.5 list-disc list-inside">
                    <li>법인 계좌 목록과 잔액을 확인합니다.</li>
                    <li>거래 내역을 날짜별로 조회할 수 있습니다.</li>
                </ul>
            </div>

            <div class="bg-slate-950 rounded-lg p-4">
                <h3 class="text-sm font-bold text-slate-100 mb-2">세금계산서 / 세무리포트</h3>
                <ul class="text-sm text-slate-300 space-y-1.5 list-disc list-inside">
                    <li><strong>세금계산서</strong>: 매출/매입 세금계산서를 조회하고 발행합니다.</li>
                    <li><strong>세무리포트</strong>: 부가세, 원천세 등 세무 관련 리포트를 확인합니다.</li>
                    <li><strong>환경설정</strong>: 홈택스 연동, 인증서 관리 등을 설정합니다.</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- ===== 8. 노무관리 ===== -->
    <section id="sec-labor" class="mb-14 scroll-mt-20">
        <h2 class="text-lg font-bold text-slate-100 mb-1 flex items-center gap-2">
            <span class="w-7 h-7 bg-primary text-white rounded-lg flex items-center justify-center text-sm font-bold">8</span>
            노무관리
        </h2>
        <p class="text-sm text-slate-400 mb-4">근로계약서, 근로자명부, 임금대장, 연차관리, 취업규칙 등 노무 관련 업무를 관리합니다.</p>

        <div class="space-y-6">
            <div>
                <h3 class="text-sm font-bold text-slate-100 mb-3">8-1. 근로계약서</h3>
                <img src="<?= $imgBase ?>/labor_contract.png" alt="근로계약서 화면" class="rounded-xl border border-slate-800 shadow-sm mb-4 w-full">
                <div class="bg-slate-950 rounded-lg p-4">
                    <ul class="text-sm text-slate-300 space-y-1.5 list-disc list-inside">
                        <li>직원별 근로계약서 목록을 확인합니다.</li>
                        <li><strong>"계약서 작성"</strong> 버튼으로 새 근로계약서를 생성합니다.</li>
                        <li>직원을 선택하면 인사 정보(이름, 부서, 직급, 입사일 등)가 자동으로 채워집니다.</li>
                        <li>근무 시간, 급여, 계약 기간 등 세부 항목을 입력합니다.</li>
                        <li>작성 완료 후 "체결" 버튼으로 계약을 확정합니다.</li>
                    </ul>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-bold text-slate-100 mb-3">8-2. 연차관리</h3>
                <img src="<?= $imgBase ?>/labor_annual.png" alt="연차관리 화면" class="rounded-xl border border-slate-800 shadow-sm mb-4 w-full">
                <div class="bg-slate-950 rounded-lg p-4">
                    <ul class="text-sm text-slate-300 space-y-1.5 list-disc list-inside">
                        <li>직원별 연차 발생/사용/잔여 현황을 한눈에 확인합니다.</li>
                        <li>올해 연차 일수는 입사일 기준으로 자동 계산됩니다.</li>
                        <li>연차 사용 내역(날짜, 유형)을 상세하게 확인할 수 있습니다.</li>
                    </ul>
                </div>
            </div>

            <div class="bg-slate-950 rounded-lg p-4">
                <h3 class="text-sm font-bold text-slate-100 mb-2">기타 노무관리 메뉴</h3>
                <ul class="text-sm text-slate-300 space-y-1.5 list-disc list-inside">
                    <li><strong>근로자명부</strong>: 법정 근로자명부 양식으로 직원 정보를 관리합니다.</li>
                    <li><strong>임금대장</strong>: 월별 급여 지급 내역을 기록하고 관리합니다.</li>
                    <li><strong>취업규칙</strong>: 회사 취업규칙을 등록하고 관리합니다.</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- ===== 9. 급여명세 ===== -->
    <section id="sec-payslip" class="mb-14 scroll-mt-20">
        <h2 class="text-lg font-bold text-slate-100 mb-1 flex items-center gap-2">
            <span class="w-7 h-7 bg-primary text-white rounded-lg flex items-center justify-center text-sm font-bold">9</span>
            급여명세
        </h2>
        <p class="text-sm text-slate-400 mb-4">직원별 월 급여명세서를 조회합니다.</p>
        <img src="<?= $imgBase ?>/payslip.png" alt="급여명세 화면" class="rounded-xl border border-slate-800 shadow-sm mb-6 w-full">

        <div class="bg-slate-950 rounded-lg p-4">
            <ul class="text-sm text-slate-300 space-y-1.5 list-disc list-inside">
                <li>직원을 선택하고 월을 지정하면 해당 월의 급여명세서가 표시됩니다.</li>
                <li><strong>지급 항목</strong>: 기본급, 각종 수당 등 받는 금액을 확인합니다.</li>
                <li><strong>공제 항목</strong>: 국민연금, 건강보험, 고용보험, 소득세 등 공제 금액을 확인합니다.</li>
                <li><strong>실지급액</strong>: 지급 합계에서 공제 합계를 뺀 실제 수령 금액입니다.</li>
            </ul>
        </div>
    </section>

    <!-- ===== 10. 업무자료실/의뢰 ===== -->
    <section id="sec-business-docs" class="mb-14 scroll-mt-20">
        <h2 class="text-lg font-bold text-slate-100 mb-1 flex items-center gap-2">
            <span class="w-7 h-7 bg-primary text-white rounded-lg flex items-center justify-center text-sm font-bold">10</span>
            업무자료실/의뢰
        </h2>
        <p class="text-sm text-slate-400 mb-4">외부 전문가에게 업무를 의뢰하고, 관련 자료를 관리하는 공간입니다.</p>
        <img src="<?= $imgBase ?>/business_docs.png" alt="업무자료실 화면" class="rounded-xl border border-slate-800 shadow-sm mb-6 w-full">

        <div class="bg-slate-950 rounded-lg p-4">
            <h3 class="text-sm font-bold text-slate-100 mb-2">카테고리별 업무</h3>
            <ul class="text-sm text-slate-300 space-y-1.5 list-disc list-inside">
                <li><strong>인사/노무</strong> · 인사노무 관련 의뢰 및 자료 관리</li>
                <li><strong>기업연구소/벤처</strong> · 기업부설연구소, 벤처인증 관련 업무</li>
                <li><strong>특허/상표/디자인</strong> · 지식재산권 관련 업무</li>
                <li><strong>감정평가</strong> · 자산/부동산 감정평가 업무</li>
                <li><strong>법무(등기)</strong> · 법인등기 등 법무 관련 업무</li>
                <li><strong>주주총회</strong> · 주주총회 관련 문서 및 업무</li>
                <li><strong>절세</strong> · 세무 절세 전략 관련 업무</li>
                <li><strong>고용지원금</strong> · 정부 고용지원금 관련 업무</li>
            </ul>
            <p class="text-sm text-slate-300 mt-3">각 탭에서 의뢰 건을 등록하고 진행 상황을 추적할 수 있습니다.</p>
        </div>
    </section>

    <!-- ===== 11. 시스템 설정 ===== -->
    <section id="sec-settings" class="mb-14 scroll-mt-20">
        <h2 class="text-lg font-bold text-slate-100 mb-1 flex items-center gap-2">
            <span class="w-7 h-7 bg-primary text-white rounded-lg flex items-center justify-center text-sm font-bold">11</span>
            시스템 설정
        </h2>
        <p class="text-sm text-slate-400 mb-4">그룹웨어 시스템의 기본 설정을 관리합니다. (관리자 전용)</p>
        <img src="<?= $imgBase ?>/settings.png" alt="시스템 설정 화면" class="rounded-xl border border-slate-800 shadow-sm mb-6 w-full">

        <div class="space-y-4">
            <div class="bg-slate-950 rounded-lg p-4">
                <h3 class="text-sm font-bold text-slate-100 mb-2">공통코드 관리</h3>
                <ul class="text-sm text-slate-300 space-y-1.5 list-disc list-inside">
                    <li>직급, 부서, 일정 카테고리 등 시스템에서 사용하는 <strong>코드 값</strong>을 관리합니다.</li>
                    <li>새로운 코드를 추가하거나 기존 코드를 수정/비활성화할 수 있습니다.</li>
                    <li>예: 직급에 "수석" 추가, 일정 카테고리에 "세미나" 추가 등</li>
                </ul>
            </div>

            <div class="bg-slate-950 rounded-lg p-4">
                <h3 class="text-sm font-bold text-slate-100 mb-2">API 설정</h3>
                <ul class="text-sm text-slate-300 space-y-1.5 list-disc list-inside">
                    <li>외부 서비스(홈택스, 은행 API 등)와의 연동 설정을 관리합니다.</li>
                    <li>API 키, 인증 정보 등을 등록하고 관리합니다.</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- ===== 자주 묻는 질문 ===== -->
    <section id="sec-faq" class="mb-14 scroll-mt-20">
        <h2 class="text-lg font-bold text-slate-100 mb-4 flex items-center gap-2">
            <span class="w-7 h-7 bg-slate-300 text-white rounded-lg flex items-center justify-center text-sm font-bold">?</span>
            자주 묻는 질문 (FAQ)
        </h2>

        <div class="space-y-3">
            <details class="bg-slate-950 rounded-lg group">
                <summary class="px-4 py-3 text-sm font-medium text-slate-100 cursor-pointer hover:bg-slate-800 rounded-lg transition-colors">출근 버튼을 눌렀는데 반응이 없어요</summary>
                <div class="px-4 pb-3 text-sm text-slate-300">
                    이미 오늘 출퇴근이 완료된 상태일 수 있습니다. 대시보드에서 출근/퇴근 시간이 모두 표시되어 있다면 하루에 한 번만 기록 가능합니다. 수정이 필요하면 관리자에게 문의해주세요.
                </div>
            </details>

            <details class="bg-slate-950 rounded-lg group">
                <summary class="px-4 py-3 text-sm font-medium text-slate-100 cursor-pointer hover:bg-slate-800 rounded-lg transition-colors">일정을 등록했는데 달력에 안 보여요</summary>
                <div class="px-4 pb-3 text-sm text-slate-300">
                    페이지를 새로고침(F5)해보세요. 일정이 다른 달에 등록되었을 수도 있으니, 시작일과 종료일을 확인해주세요. 전체 캘린더에서 월을 이동하여 확인할 수 있습니다.
                </div>
            </details>

            <details class="bg-slate-950 rounded-lg group">
                <summary class="px-4 py-3 text-sm font-medium text-slate-100 cursor-pointer hover:bg-slate-800 rounded-lg transition-colors">결재를 잘못 제출했어요. 취소할 수 있나요?</summary>
                <div class="px-4 pb-3 text-sm text-slate-300">
                    아직 상위 결재자가 승인하지 않았다면 결재문서함에서 해당 문서를 열고 "회수" 버튼을 눌러 취소할 수 있습니다. 이미 승인된 경우에는 관리자에게 문의해주세요.
                </div>
            </details>

            <details class="bg-slate-950 rounded-lg group">
                <summary class="px-4 py-3 text-sm font-medium text-slate-100 cursor-pointer hover:bg-slate-800 rounded-lg transition-colors">비밀번호를 변경하고 싶어요</summary>
                <div class="px-4 pb-3 text-sm text-slate-300">
                    현재 비밀번호 변경 기능은 관리자를 통해 처리됩니다. 시스템 관리자에게 비밀번호 변경을 요청해주세요.
                </div>
            </details>

            <details class="bg-slate-950 rounded-lg group">
                <summary class="px-4 py-3 text-sm font-medium text-slate-100 cursor-pointer hover:bg-slate-800 rounded-lg transition-colors">화면이 깨져 보이거나 버튼이 작동하지 않아요</summary>
                <div class="px-4 pb-3 text-sm text-slate-300">
                    브라우저 캐시를 지우고(Ctrl+Shift+Delete) 새로고침해보세요. Chrome, Edge 등 최신 브라우저 사용을 권장합니다. 문제가 지속되면 사용 중인 브라우저와 오류 화면을 캡처하여 관리자에게 문의해주세요.
                </div>
            </details>
        </div>
    </section>

    <!-- 하단 -->
    <div class="border-t border-slate-800 pt-6 pb-10 text-center">
        <p class="text-sm text-slate-500">Zaemit 그룹웨어 사용자 매뉴얼 · 문의사항은 시스템 관리자에게 연락해주세요.</p>
    </div>

</div>

</main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
