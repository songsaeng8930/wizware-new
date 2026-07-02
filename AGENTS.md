# Zaemit 그룹웨어 플러그인 (BMS) 개발 지침

## 프로젝트 개요
한국 기업용 그룹웨어(ERP) 플러그인 시스템 "BMS" (Business Management System).
인사, 근태, 전자결재, 법인카드, 세무/회계, 사업관리, 자원예약, 게시판, 노무관리 등 통합 업무 관리 플랫폼.

> 환경 설치 및 DB 초기화 방법은 [INSTALL.md](INSTALL.md) 참고

## 기술 스택
- **Backend:** PHP 8.4 + PDO (prepared statements)
- **Frontend:** Tailwind CSS (CDN) + custom.css, Lucide 아이콘 (unpkg CDN), Vanilla JS
- **Database:** MySQL 9.6 (utf8mb4, InnoDB)
- **API:** RESTful JSON 엔드포인트 (`/api/[module].php?action=[action]`)
- **설정:** JSON 기반 (`config/api_settings.json`)

## 디렉토리 구조
```
zaemit_plugin/
├── index.php                    # 진입점 → 대시보드 리다이렉트
├── config/
│   ├── database.php             # PDO 연결 (getDBConnection())
│   └── bankapi.php              # 은행 API 연동 설정
├── includes/
│   ├── header.php               # 상단 헤더바 (알림)
│   ├── sidebar.php              # 좌측 사이드바 메뉴
│   └── footer.php               # 푸터
├── api/                         # API 엔드포인트 (8개)
│   ├── approval.php             # 전자결재 CRUD
│   ├── card.php                 # 법인카드 관리
│   ├── organization.php         # 부서/직원 CRUD
│   ├── common_codes.php         # 공통코드 관리
│   ├── reservation.php          # 자원예약
│   ├── settings.php             # API 설정
│   ├── bankapi.php              # 은행 거래내역 연동
│   └── tax_invoice.php          # 세금계산서 CRUD
├── pages/                       # 페이지 뷰 (37개)
│   ├── dashboard.php            # 메인 대시보드
│   ├── employees.php            # 구성원 관리
│   ├── employee_register.php    # 직원 등록
│   ├── organization.php         # 조직도
│   ├── attendance.php           # 근태 현황
│   ├── dept_attendance.php      # 부서 근태
│   ├── commute_history.php      # 출퇴근 이력
│   ├── att_manage.php           # 근태 관리
│   ├── att_manage_detail.php    # 근태 상세
│   ├── approval_draft.php       # 기안중 문서
│   ├── approval_pending.php     # 결재중 문서
│   ├── approval_archive.php     # 문서 보관함
│   ├── approval_line.php        # 결재선 설정
│   ├── approval_forms.php       # 결재 양식 관리
│   ├── card_expenses.php        # 카드 지출내역
│   ├── card_manage.php          # 카드 관리
│   ├── card_regulations.php     # 법인카드 규정
│   ├── card_settlement.php      # 카드 정산
│   ├── schedule.php             # 일정 관리
│   ├── business.php             # 사업 관리
│   ├── business_detail.php      # 사업 상세
│   ├── business_docs.php        # 업무자료실
│   ├── reservation.php          # 자원 예약
│   ├── board.php                # 게시판
│   ├── labor.php                # 노무 관리
│   ├── labor_contract_form.php  # 근로계약서 양식
│   ├── tax_invoice.php          # 세금계산서 통합뷰
│   ├── tax_bank.php             # 통장 AI 분류
│   ├── tax_report.php           # 세무 리포트
│   ├── tax_docs.php             # 서류 요청/알림
│   ├── tax_account.php          # 계좌 조회
│   ├── tax_account_popbill.php  # 팝빌 연동
│   ├── tax_credit.php           # 세액공제 계산
│   ├── payslip.php              # 급여 명세
│   ├── tax_settings.php         # 세무 설정
│   ├── api_settings.php         # API 키 설정
│   └── settings.php             # 공통코드 관리
├── db/                          # DB 스키마 (8개)
│   ├── schema_organization.sql  # 부서/직원 테이블
│   ├── schema_employees_update.sql  # 직원 확장 필드
│   ├── schema_approval.sql      # 전자결재 테이블
│   ├── schema_card.sql          # 법인카드 테이블
│   ├── schema_common_codes.sql  # 공통코드 테이블
│   ├── schema_reservation.sql   # 자원예약 테이블
│   ├── schema_tax.sql           # 세무/회계 테이블
│   └── schema_tax_invoice.sql   # 세금계산서 테이블
├── assets/css/custom.css        # 글로벌 커스텀 스타일
└── meeting/                     # 회의록
```

## 모듈별 개발 현황

### 완료된 모듈
| 모듈 | 페이지 | API | DB 스키마 |
|------|--------|-----|-----------|
| 대시보드 | dashboard.php | - | - |
| 인사관리 (구성원/조직도) | employees, organization, employee_register | organization.php | schema_organization.sql |
| 근태관리 | attendance, dept_attendance, commute_history, att_manage | - | - |
| 전자결재 | approval_draft, approval_pending, approval_archive, approval_line, approval_forms | approval.php | schema_approval.sql |
| 법인카드 | card_expenses, card_manage, card_regulations, card_settlement | card.php | schema_card.sql |
| 사업관리 | business, business_detail, business_docs | - | - |
| 세무 리포트 | tax_report.php | - | - |
| 세금계산서 (1차) | tax_invoice.php | tax_invoice.php | schema_tax_invoice.sql |
| 공통코드 관리 | settings.php | common_codes.php | schema_common_codes.sql |
| API 설정 | api_settings.php | settings.php | - |

### 접근권한 관리 (2026-04-23)
- **메뉴/기능별 접근권한**: `menu_permissions` 테이블 (menu_key × role_key × department_id × access_level)
- **시스템 관리 > 접근권한 관리** 페이지에서 매트릭스 UI 로 관리 (admin 전용)
- **헬퍼**: `includes/permissions.php`
  - `hasMenuPermission($menuKey, $level)` — true/false
  - `requireMenuPermission(...)` — 페이지 가드 (리다이렉트)
  - `apiRequireMenuPermission(...)` — API 가드 (403 JSON)
  - `sidebarMenuVisible($menuId)` — 사이드바 필터
- **폴백**: 테이블 없으면 기본 안전 모드 (admin 전용 메뉴만 가드, 나머지는 로그인 사용자 전원)
- **시드**: admin 은 `'*'` 와일드카드로 전체 접근. manager/user 는 개별 메뉴 지정.
- **admin 역할**은 런타임에서도 항상 전체 접근 허용 (정책 고정 — UI 에서 변경 불가)

### 재무관리 > 카드 정산 (3본부 뷰)
- `acct_card_settle.php` 는 **회계팀/관리자 전용** (requireMenuPermission('accounting.settle', 'view'))
- 상단 **3본부 KPI 카드**(영업본부 / 기술개발본부 / 경영지원본부) — 각 본부의 미정산/정산완료/총지출
- **본부 필터 칩**으로 리스트 즉시 필터링 (카드 클릭 시에도 동일)
- 본부 매핑은 `acct_card_settle.php` 의 `$divisions` 배열에 정의 — 조직 개편 시 그 배열만 수정

### 설계 완료 / 개발 중 모듈
| 모듈 | 상태 | 비고 |
|------|------|------|
| 통장 AI 분류 | 스키마 완료, UI 완료 | AI 분류 엔진 연동 필요 |
| 계좌 조회 | 스키마 완료, UI 완료 | bankapi.co.kr 연동 |
| 급여 명세 | 스키마 완료, UI 일부 | 급여 계산 로직 필요 |
| 세액공제 계산 | UI 완료 | 시뮬레이션 기능 |
| 서류 요청/알림 | 스키마 완료, UI 완료 | 세무사 요청 워크플로우 |
| 자원예약 | 스키마 완료 | UI 미완 |
| 게시판 | UI 일부 | CRUD 미완 |
| 노무관리 | UI 일부 | 계약서 양식 등 |
| 세금계산서 2차 | 미착수 | 홈택스 실제 API 연동 |

## API 엔드포인트 상세

### 전자결재 API (`api/approval.php`)
- `getDrafts` / `getPending` / `getArchive` / `getDocument`
- `saveDocument` / `deleteDocument` / `approveDocument`
- `getLines` / `saveLine` / `getForms` / `saveForm` / `toggleForm`

### 법인카드 API (`api/card.php`)
- `getCards` / `saveCard` / `deleteCard` / `toggleCard`
- `getExpenses` / `saveExpense` / `deleteExpense`
- `getRegulations` / `saveRegulations`
- `getSettlements` / `updateSettlement` / `batchSettle` / `getApprovals`

### 조직 API (`api/organization.php`)
- `getDepartments` / `getDepartmentTree` / `createDepartment` / `updateDepartment` / `deleteDepartment`
- `getEmployees` / `getEmployeesByDept` / `createEmployee` / `updateEmployee` / `deleteEmployee`
- `getOrgTree`

### 공통코드 API (`api/common_codes.php`)
- `getGroups` / `getGroup` / `saveGroup` / `deleteGroup`
- `saveItems` / `reorderItems` / `toggleItem` / `deleteItem`

### 설정 API (`api/settings.php`)
- `load` / `save` / `test_bankapi`

### 세금계산서 API (`api/tax_invoice.php`)
- 세금계산서 CRUD (매출/매입)

## DB 테이블 요약

### 조직/인사
- `departments` - 부서 (parent_id 계층구조, head_employee_id)
- `employees` - 직원 (소속, 직급, 직책, 고용형태, 재직상태)

### 전자결재
- `approval_documents` - 결재 문서 (기안/진행/승인/반려/임시저장)
- `approval_forms` - 양식 템플릿
- `approval_lines` - 결재선 설정
- `approval_history` - 결재 이력
- `approval_references` - 참조자

### 법인카드
- `cards` - 카드 목록
- `card_expenses` - 지출 내역 (카테고리별)
- `card_regulations` - 사용 규정
- `card_approvals` - 승인 내역

### 공통코드
- `common_code_groups` - 코드 그룹 (모듈별)
- `common_code_items` - 코드 항목

### 세무/회계
- `tax_invoices` - 세금계산서 (매출/매입)
- `tax_invoice_items` - 세금계산서 품목
- `account_categories` - 계정과목 (과세/면세/영세율/불공제)
- `bank_accounts` - 은행 계좌
- `bank_transactions` - 거래내역 (AI 분류)
- `payslips` - 급여 명세
- `tax_credit_simulations` - 세액공제 시뮬레이션
- `doc_requests` / `doc_uploads` - 서류 요청 워크플로우
- `notifications` - 알림
- `hometax_sync_log` - 홈택스 연동 로그

### 자원예약
- `reservations` - 예약 레코드
- `reservation_resource_config` - 자원 용량 설정

## 디자인 시스템 & UI 규칙

### 필수 규칙 (custom.css 글로벌 가이드라인)
1. 콘텐츠 최소 폰트: **14px** (힌트/캡션도 12px 이상)
2. 테이블 행 최소 높이: **py-4** (16px 상하 패딩)
3. 보더 색상: `border-gray-200` 이상 → `border-gray-300` 권장
4. 텍스트 색상: 본문 `text-gray-700`, 헤더 `text-gray-800`, 서브 `text-gray-500` (gray-400 금지)
5. 섹션 제목: `text-base` (16px) `font-bold`
6. **한쪽 면에만 border 금지** — `border-t`/`border-b`/`border-l`/`border-r` 같이 한 변만 선을 긋는 디자인 지양.
   구분이 필요하면 (a) 배경색 대비(`bg-slate-900` vs `bg-slate-950`), (b) 여백(`gap-*`, `mt-*`, `pt-*`),
   (c) 4면 전체 border + `rounded-*`, 또는 (d) `shadow-sm` 로 표현한다.
   **예외**: 테이블 행 구분(`tbody tr border-b`)과 thead 하단 구분선(`thead tr border-b-2`) 은
   목록성 데이터 특성상 허용. 단, 섹션/카드 분리 용도의 단면 border 는 금지.
7. 다크 테마는 전역 오버라이드 방식으로 적용됨 (`assets/css/custom.css` 하단의
   "다크 테마 전역 오버라이드" 블록). `bg-white`/`text-gray-*`/`border-gray-*` 라이트 클래스는
   자동으로 slate 계열로 재매핑되므로 페이지별 수정 금지 — 새 페이지에도 라이트 계열 유틸리티를
   그대로 사용하면 된다.
8. **`<select>` 드롭다운 화살표는 전역 규칙 하나만** — `custom.css` line 182-191 의 전역
   `select { appearance: none; background-image: linear-gradient(...) !important }` 로 다크
   테마 톤의 커스텀 화살표가 통일 적용되어 있다.
   **금지**: 페이지/컴포넌트 CSS 에서 `appearance: auto` · `-webkit-appearance: auto` ·
   `appearance: menulist` 등으로 네이티브 화살표를 되살리는 행위 — 네이티브+커스텀이 동시에
   찍혀 **더블 화살표**가 발생한다.
   **허용**: 색상/padding/height/border-radius/`background-color` 같은 **비-appearance** 속성
   오버라이드, `@media print` 블록 안에서 프린트용 `appearance: none` 지정.
   새 select 컴포넌트를 만들 때 `grep -niE "appearance:\s*(auto|menulist)" pages/ assets/` 로
   검증 — `@media print` 외에 매칭이 나오면 제거한다.

### 색상 체계
- Primary: `#4F6AFF` (파랑)
- Secondary: `#E8ECFF` (연파랑)
- 사이드바: `#F8F9FA`, Active: `#EEF1FF`

### 공통 UI 컴포넌트 CSS 클래스
- 카드: `.bg-white .rounded-xl .border .border-gray-200`
- 탭: `.approval-tab` + `.tab-badge`
- 정보바: `.list-info-bar` + `.list-per-page`
- 테이블: `.emp-table`
- 페이지네이션: `.pg-btn` / `.pg-active` / `.pg-disabled`
- 필터: `.filter-grid` + `.filter-row` + `.filter-label` + `.filter-value`
- 레이아웃: 사이드바 `w-60` + 메인 콘텐츠 `ml-60`

### 아이콘
- Lucide 아이콘 라이브러리 (unpkg.com/lucide@latest)
- `<i data-lucide="icon-name"></i>` + `lucide.createIcons()` 호출

## 아키텍처 패턴

### MVC-Lite 패턴
- **pages/** = View (HTML + 임베디드 로직)
- **api/** = Controller + Model (비즈니스 로직 + DB 쿼리)
- **config/** = 설정

### 데이터 패턴
- DB 미연결 시 샘플 데이터 fallback (페이지에 임베디드)
- API 응답: JSON (`Content-Type: application/json; charset=utf-8`)
- HTTP 상태 코드: 200, 400, 404, 500

### 보안
- SQL Injection: PDO prepared statements 사용
- XSS: `htmlspecialchars()` 출력 이스케이프
- API 키: 마스킹 처리 (앞 8자리만 표시) + `config/api_settings.json`은 `.gitignore` 필수
- CSRF 토큰: `includes/csrf.php` — 상태 변경 API는 `X-CSRF-Token` 헤더 자동 검증 (header.php의 fetch 래퍼가 주입)
- 인증: 세션 기반 (`includes/auth.php`) — `session_regenerate_id`, UA 지문, 30분 idle 타임아웃, HttpOnly+SameSite Lax
- 권한: 본인 리소스만 수정(IDOR 차단) — 쿼리 파라미터의 `employee_id`를 신뢰하지 말고 세션에서 추출

## 코딩 컨벤션

### PHP
- PDO prepared statements 필수 (직접 쿼리 금지)
- `getDBConnection()` 함수로 DB 연결
- API 응답 형식: `echo json_encode(['success' => true, 'data' => $data])`
- 에러 응답: `http_response_code(400/500)` + JSON

### 프론트엔드
- Tailwind CSS 유틸리티 클래스 우선
- custom.css에 정의된 공통 클래스 활용
- Vanilla JS (프레임워크 미사용)
- `fetch()` API로 비동기 통신

### 페이지 템플릿 구조
```php
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<main class="ml-60 p-8 bg-gray-50 min-h-screen">
  <!-- 페이지 콘텐츠 -->
</main>
<script> lucide.createIcons(); </script>
```

## 외부 연동
- **bankapi.co.kr**: 은행 계좌/거래내역 API
- **홈택스 (Hometax)**: 세금계산서 동기화 (2차 개발 예정)
- **팝빌 (PopBill)**: 계좌 조회 연동

## 샘플 데이터 규모
- 직원 20명 (소속: Zaemit, WEVEN)
- 부서 12개 (계층 구조)
- 결재 문서 13건 (다양한 상태)
- 법인카드 5장 + 지출내역 10건+
- 세금계산서 14건 (매출 7, 매입 7)
- 은행계좌 3개
- 사업 프로젝트 8건

---

## AI 코드 생성 가드레일 (필독)

과거 감사에서 **회귀/일관성 붕괴/속인화/리팩토링 폭탄/기술부채/방어선 부재/민감 영역 위임** 7가지 문제가 실제로 관찰되었다. 아래 규칙은 동일 실수를 차단하기 위한 하드 룰이다.

### ⛔ 절대 금지 (AI에게 위임해서는 안 되는 영역)

| 영역 | 금지 행동 | 이유 |
|---|---|---|
| 인증/세션 | `$_SESSION` 키 스키마 변경, 로그인 로직 리팩토링, 쿠키 파라미터 변경 | 로그아웃 폭발·세션 탈취 취약점 |
| 권한/결재자 | 사람 이름·부서명을 코드에 하드코딩 (`'최민호'`, `'경영진'` 등) | 퇴사·조직개편 시 연쇄 회귀. 반드시 `approval_lines` + `resolveApprovalRoute()` 경유 |
| 금액/세무 | 세율·공제율·급여 계산 로직을 AI가 단독 수정 | 법적 리스크. 수정 시 반드시 근거(고시·개정 링크) 커밋 메시지에 명시 |
| API 키/시크릿 | 설정 파일을 커밋, 예시값을 실제값처럼 보이게 작성 | 유출 사고 |
| 결재 상태 전이 | `status` 값(`대기/진행/승인/반려/임시저장/회수`) 추가·제거 | 타 모듈과 문자열 기반 결합 |
| 문서번호 포맷 | `Zaemit_..._YYYYMMDDHHMMSS` 포맷 수정 | `card_expenses.document_number` ↔ `approval_documents.doc_number` 역참조 JOIN이 소리 없이 깨짐 |

### ✅ 반드시 따를 규칙

1. **권한 체크는 세션에서** — 요청 파라미터의 `employee_id`·`user_id`는 절대 신뢰하지 않는다. `$_SESSION['user_id']`를 사용하고, 타인 조회는 `role in ('admin','manager')` 체크 후 허용.
2. **CSRF** — 새 API는 `includes/api_auth.php`를 반드시 require (이미 CSRF 검증 포함). 프론트는 헤더의 자동 주입에 맡긴다.
3. **문서번호·결재선은 단일 진입점** — `includes/approval_doc.php::buildApprovalDocNumber()`, `resolveApprovalRoute()`만 사용. 인라인 `sprintf` 금지.
4. **공통 헬퍼 우선** — 신규 API는 `includes/api_common.php`의 `apiOk()`, `apiError()`, `apiJsonInput()`, `apiRequireAdmin()`을 사용한다. 기존 API의 로컬 `respond()`/`getJsonInput()` 중복을 늘리지 않는다.
5. **영향 범위 확인** — 수정 전에 grep으로 **문자열 기반 결합**을 확인한다:
   - 테이블에 문자열 상태값(`status='승인'` 등) 변경 시 → 해당 값을 쓰는 모든 파일 확인
   - `doc_number` 포맷 변경 시 → `LEFT JOIN approval_documents ad ON ad.doc_number = ...` 전수 확인
   - 직원/부서명 변경 시 → 샘플 SQL + 페이지 임베디드 fallback 데이터 모두 확인
6. **기존 호출 규약 유지** — 기존 API의 응답 필드명(`error`, `success`, `data`)을 바꾸면 28개 페이지의 fetch 호출이 깨진다. 신규 API만 새 규약(`{ok, data, error:{code,message}}`)을 쓰고, 기존 수정은 보안 이슈 한정.
7. **하드코딩 금지 목록** — 사람 이름, 부서명, 조직명("위즈웨어" 등), 카드번호, 주민번호, 테스트 계정 비밀번호. 샘플 데이터는 DB 시드(`db/init.sql`) 또는 명시적 `sample_*` 네이밍 변수로만.
8. **대형 리팩토링 금지** — 한 번에 여러 모듈의 파일 구조·함수 시그니처를 바꾸는 PR은 올리지 않는다. 공통 헬퍼 도입 시 **신규 파일 추가 + 점진적 이관**을 원칙으로.

### 📋 수정 전 체크리스트

새 API·페이지를 만들거나 기존 코드를 수정할 때 아래를 **스스로 검증**한 뒤 커밋한다:

- [ ] `require_once __DIR__ . '/../includes/api_auth.php'` 포함 (인증+CSRF)
- [ ] 권한 체크 — 쓰기 액션은 세션 ID 강제, 타인 조회는 role 확인
- [ ] `htmlspecialchars()` — 모든 사용자 입력 출력에 적용
- [ ] PDO prepared statement — `"WHERE id={$id}"` 같은 문자열 결합 0건
- [ ] `error_log()` — 예외는 swallow 하지 말고 반드시 로깅
- [ ] 트랜잭션 — 2개 이상 테이블을 쓸 경우 `beginTransaction`/`rollBack` 포함
- [ ] 하드코딩 이름/키 — grep으로 본인 이름·부서·API 키가 포함됐는지 확인
- [ ] `.gitignore` — 새 설정 파일은 민감 정보 포함 여부 확인

### 🗂 프로젝트 향후 로드맵 (방어선 확보)

아래는 감사에서 "즉시 조치 범위 외"로 분류되어 남아있는 기술부채. 작업 시 별도 PR로 진행:

1. **마이그레이션 도구 도입** — Phinx 또는 Laravel Migrations. `db/`의 평문 SQL은 버전 관리되는 마이그레이션으로 이관. 현재 런타임 `CREATE TABLE IF NOT EXISTS`(`api/annual_leave.php`)도 제거 대상.
2. **테스트 골격** — PHPUnit 최소 스위트. 결재 happy-path + 카드 경비 상신 통합 테스트부터.
3. **응답 스키마 통일** — 기존 15개 API를 `{ok, data, error:{code,message}}`로 점진 이관. 모듈 하나 끝낼 때마다 프론트 동반 업데이트.
4. **초대형 페이지 분해** — `organization.php`(1,348줄), `dashboard.php`/`dashboard_v2.php`(각 1,000줄+), `labor.php`(953줄) — PHP/JS 분리 + 컴포넌트화.
5. **dashboard_v2.php 정리** — v1/v2 중 유효본 확정 후 나머지 삭제.
6. **config 환경 분리** — `config/database.local.php` 패턴으로 개발/운영 자격증명 분리.
