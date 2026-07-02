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
│   ├── footer.php               # 푸터
│   └── terminology.php          # 용어 설정 V2 헬퍼 (formatPersonDisplay, buildPersonSnapshot 등)
├── api/                         # API 엔드포인트 (12개)
│   ├── approval.php             # 전자결재 CRUD
│   ├── card.php                 # 법인카드 관리
│   ├── organization.php         # 부서/직원 CRUD + 인사발령 자동기록 훅
│   ├── employee_profile.php     # 직원 상세 프로필 CRUD (경력/학력/자격증/언어/가족/수상징계)
│   ├── employee_appointment.php # 인사발령 이력 조회/수동등록/삭제
│   ├── employee_change_request.php # 직원 개인정보 변경요청 CRUD (승인 워크플로우)
│   ├── common_codes.php         # 공통코드 관리
│   ├── calendar_events.php      # 통합 캘린더 이벤트 CRUD (세무/보험/노무/회사행사)
│   ├── reservation.php          # 자원예약
│   ├── settings.php             # API 설정
│   ├── bankapi.php              # 은행 거래내역 연동
│   └── tax_invoice.php          # 세금계산서 CRUD
├── pages/                       # 페이지 뷰 (38개)
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
├── db/                          # DB 스키마 (9개)
│   ├── schema_organization.sql  # 부서/직원 테이블
│   ├── schema_employees_update.sql  # 직원 확장 필드
│   ├── migrate_employee_profile.sql # 직원 프로필 6개 테이블 (경력/학력/자격증/언어/가족/수상)
│   ├── migrate_education_language_enhance.sql # 학력(학교구분/학점) + 어학(시험유형) 필드 확장
│   ├── migrate_employee_military_skills.sql # 병역사항(1:1) + 보유스킬(태그) 테이블
│   ├── migrate_employee_appointments.sql # 인사발령 이력 테이블 + 발령유형 공통코드
│   ├── migrate_employee_change_requests.sql # 직원 개인정보 변경요청 테이블
│   ├── migrate_terminology.sql  # 용어 설정 V2 DDL (org_levels, hr_ranks/duties/positions, display_config)
│   ├── migrate_terminology_data.php # 용어 설정 V2 데이터 시딩 (common_codes→hr_ranks 이관, 백필)
│   ├── schema_approval.sql      # 전자결재 테이블
│   ├── schema_card.sql          # 법인카드 테이블
│   ├── schema_common_codes.sql  # 공통코드 테이블
│   ├── schema_reservation.sql   # 자원예약 테이블
│   ├── migrate_approval_amount_threshold.sql # 금액별 결재선 라우팅 (approval_lines.amount_threshold)
│   ├── migrate_calendar_events.sql # 통합 캘린더 이벤트 (세무/보험/노무/회사) DDL + 2026 시드
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

### 법인카드 규정 ↔ 결재 연동 (2026-06-22)
- **규정 정보 → 결재 문서**: `submitCardExpenseApproval()`에서 `compliance_status`, `regulation_limit`, `exception_reason`을 `approval_documents.metadata` JSON에 포함. `approval_view.php`에서 자동 렌더링 (예외신청=경고박스, 준수=녹색확인).
- **금액별 결재선 자동 라우팅**: `approval_lines.amount_threshold` 컬럼 추가. `resolveApprovalRoute()` 6번째 파라미터 `$amount`로 금액 전달 → `amount_threshold <= $amount` 중 가장 높은 threshold의 결재선 매칭. 결재선 관리 UI에 "금액 기준" 입력 필드.
- **하위호환**: `amount_threshold DEFAULT 0`, `$amount` optional → 기존 호출 코드 변경 불필요.

### 설계 완료 / 개발 중 모듈
| 모듈 | 상태 | 비고 |
|------|------|------|
| 계좌연동(은행) | **샌드박스 연동 완료** | 등록→조회→동기화(DB적재)→자동분류→확정 end-to-end 동작. 실키 입력 시 자동 실연동 승격. 아래 "계좌연동 아키텍처" 참고 |
| 통장 AI 분류 | **DB 연동 완료(규칙기반)** | bank_transactions 기반 분류·확정. LLM 분류 엔진은 후속(현재 규칙기반) |
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

### 직원 정보 변경요청 API (`api/employee_change_request.php`)
- `getMyRequests` — 본인 변경요청 이력 조회 (status 필터)
- `submitRequest` — 개인정보 변경 요청 제출 (본인, 화이트리스트: birth_date/gender/zipcode/address1/address2)
- `cancelRequest` — 대기 중 요청 취소 (본인)
- `getPendingRequests` — 전체 변경요청 목록 (admin/manager, status 필터 + 건수)
- `reviewRequest` — 승인/반려 처리 (admin/manager, 승인 시 employees 테이블 직접 업데이트)
- `getPendingCount` — 대기 건수 (뱃지용)
- 직원당 대기 요청 1건 제한 (동시 요청 충돌 방지)

### 인사발령 이력 API (`api/employee_appointment.php`)
- `getAppointments` — employee_id로 발령이력 조회 (DESC)
- `saveAppointment` — 수동 발령 등록/수정 (admin/manager, source='manual')
- `deleteAppointment` — 수동 발령만 삭제 가능 (auto는 감사 로그로 삭제 불가)
- 자동 기록: `organization.php`의 `updateEmployee`/`moveEmployee` 훅에서 `includes/appointment_helper.php` 호출

### 직원 프로필 API (`api/employee_profile.php`)
- 6개 엔티티(Career/Education/Certification/Language/Family/Award) × 3 CRUD = 18 액션
- `get{Plural}` / `save{Singular}` / `delete{Singular}` 패턴
- `getMilitary` / `saveMilitary` / `deleteMilitary` — 병역 (1:1 UPSERT)
- `getSkills` / `saveSkill` / `deleteSkill` — 스킬 태그
- `getProfileSummary` — 전체 카운트 조회 (병역/스킬 포함)
- 권한: 본인 또는 admin/manager

### 공통코드 API (`api/common_codes.php`)
- `getGroups` / `getGroup` / `saveGroup` / `deleteGroup`
- `saveItems` / `reorderItems` / `toggleItem` / `deleteItem`

### 캘린더 이벤트 API (`api/calendar_events.php`)
- `getEvents` — 날짜 범위 + 카테고리 필터 조회 (`?start=&end=&category=`)
- `getCategories` — 카테고리 메타 (tax/insurance/labor/company + 색상)
- `getEvent` / `createEvent` / `updateEvent` / `deleteEvent`
- 시스템 이벤트(is_system=1) 수정/삭제는 admin only, 사용자 등록(is_system=0)은 admin/manager
- 새 규약: `apiOk()`/`apiError()` 사용

### 설정 API (`api/settings.php`)
- `load` / `save` / `test_bankapi`

### 세금계산서 API (`api/tax_invoice.php`)
- 세금계산서 CRUD (매출/매입)

## DB 테이블 요약

### 조직/인사
- `departments` - 부서 (parent_id 계층구조, head_employee_id, level_id→org_levels)
- `employees` - 직원 (소속, 직급, 직책, 고용형태, 재직상태, rank_id→hr_ranks, duty_id→hr_duties, position_id→hr_positions)

### 용어 설정 (V2)
- `org_levels` - 조직 계층 단계 정의 (depth, key_name, label, head_title)
- `hr_ranks` - 직급 체계 (사원→대리→과장→부장→이사→대표이사)
- `hr_duties` - 직책 체계 (CEO, 본부장, 팀장 등)
- `hr_positions` - 직위 (Phase 2, 수석연구원 등 대외 호칭)
- `terminology_display_config` - 맥락별 사람 표시 형식 ({name} {rank} 등)

### 직원 프로필 (1:N 이력)
- `employee_careers` - 경력사항
- `employee_educations` - 학력
- `employee_certifications` - 자격증
- `employee_languages` - 언어능력
- `employee_families` - 가족정보
- `employee_awards` - 수상/징계
- `employee_military` - 병역사항 (1:1, UNIQUE employee_id)
- `employee_skills` - 보유 스킬 (자유 태그, UNIQUE employee_id+skill_name)

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

### 캘린더 이벤트
- `calendar_events` - 통합 캘린더 이벤트 (세무/보험/노무/회사행사, category ENUM, is_system 플래그, is_deadline 마감일 표시)

### 자원예약
- `reservations` - 예약 레코드
- `reservation_resource_config` - 자원 용량 설정

## 디자인 시스템 & UI 규칙

### 디자인 토큰 (CSS 변수)

모든 색상은 CSS 변수 사용 필수. hex 하드코딩 금지. 새 컴포넌트는 아래 변수만 참조.

| 토큰 | Light | Dark | 용도 |
|------|-------|------|------|
| `--zm-surface-0` | `#f8fafc` | `#1a1a1d` | 페이지 배경 |
| `--zm-surface-1` | `#ffffff` | `#232327` | 카드/헤더 배경 |
| `--zm-surface-2` | `#f1f5f9` | `#2a2a2f` | 인셋 패널/호버 배경 |
| `--zm-surface-3` | `#e5e7eb` | `#3a3a40` | 액티브/포커스 배경 |
| `--zm-border` | `#e5e7eb` | `#3a3a40` | 보더 |
| `--zm-text-strong` | `#111827` | `#f1f5f9` | 강조 텍스트 |
| `--zm-text-default` | `#374151` | `#d1d5db` | 본문 텍스트 |
| `--zm-text-muted` | `#4b5563` | `#9ca3b8` | 보조 텍스트 |
| `--zm-text-subtle` | `#6b7280` | `#6b7280` | 힌트/캡션 |
| `--zm-primary` | `#4F6AFF` | `#4F6AFF` | 브랜드 블루 |
| `--zm-primary-dark` | `#3B54D4` | `#3B54D4` | 버튼 호버 |
| `--zm-primary-tint-12` | `#E8ECFF` | `rgba(79,106,255,.12)` | 연한 틴트 |

**시맨틱 상태 색상** (Radix 기반, `--zm-st-*` 접두사):
- `--zm-st-success-fg/bg/bd` — 성공/승인/완료 (green)
- `--zm-st-warning-fg/bg/bd` — 주의/대기/경고 (amber)
- `--zm-st-danger-fg/bg/bd` — 에러/반려/삭제 (red)
- `--zm-st-info-fg/bg/bd` — 정보/안내 (blue)
- `--zm-st-neutral-fg/bg/bd` — 비활성/보류 (gray)
- `--zm-status-ok-fg`, `--zm-status-warn-fg`, `--zm-status-danger-fg` — 레거시 호환용

### 글로벌 필수 규칙
1. 콘텐츠 최소 폰트: **14px** (힌트/캡션도 12px 이상)
2. 테이블 행 최소 높이: **py-4** (16px 상하 패딩)
3. 보더: `border-[var(--zm-border)]` 사용. Tailwind `border-gray-*` 직접 사용 시 다크 테마 미대응
4. 텍스트: `text-[var(--zm-text-default)]` (본문), `text-[var(--zm-text-strong)]` (헤더), `text-[var(--zm-text-subtle)]` (서브)
5. 섹션 제목: `text-base font-bold` 또는 `.zm-heading-section`
6. **한쪽 면에만 border 금지** — 구분이 필요하면 (a) 배경색 대비, (b) 여백, (c) 4면 border + `rounded-*`, (d) `shadow-sm`.
   **예외**: 테이블 행 구분(`tbody tr border-b`, `thead tr border-b-2`)만 허용.
7. **테마 시스템** — 기본 톤은 **화이트(light)**, 다크는 선택형.
   - **전환**: `<html data-theme="light|dark">`. 설정: `config/ui_settings.json` → **시스템 관리 > 디자인 설정**.
   - **팔레트**: `:root`(화이트) / `html[data-theme="dark"]`(다크). hex 하드코딩 금지 → CSS 변수 사용.
   - **다크 오버라이드**: `html[data-theme="dark"]` 스코프 필수. Tailwind `bg-white`/`text-gray-*`는 다크에서 slate로 자동 재매핑.
   - **회색 띠 주의**: `bg-slate-950/50` 같은 불투명도 변형을 매핑에서 빠뜨리면 화이트에서 검정 띠 발생. 새 변형 추가 시 custom.css 상단 매핑 목록에 추가.
8. **`<select>` 화살표** — 전역 `select { appearance: none }` + 커스텀 화살표 적용됨. `appearance: auto/menulist`로 네이티브 화살표 되살리기 **금지** (더블 화살표 발생).

### 타이포그래피 클래스

| 클래스 | 용도 | 스타일 |
|--------|------|--------|
| `.zm-heading-page` | 페이지 제목 (H1) | 700, -0.025em |
| `.zm-heading-section` | 섹션 제목 (H2, 카드 헤더) | 600, -0.015em |
| `.zm-label` | 폼 라벨, 테이블 헤더 | 500, 0.01em |
| `.zm-stat` | KPI/통계 큰 숫자 | 700, tabular-nums |
| `.zm-numeric` | 테이블 내 금액/수치 | 500, tabular-nums |
| `.zm-document` | 공식 문서 (계약서, 명세서) | 400, line-height 1.8, word-break: keep-all |
| `.zm-nav` | 사이드바/네비게이션 | 500, -0.005em |

### 폼 컴포넌트

| 클래스 | 용도 | 특성 |
|--------|------|------|
| `.reg-input` | 일반 텍스트 입력 | min-height 40px, border-radius 8px |
| `.reg-select` | 일반 셀렉트 | min-height 40px, 커스텀 화살표 자동 |
| `.reg-input-sm` | 소형 인풋 (테이블 셀 내) | min-height 34px, border-radius 6px |
| `.reg-file-sm` | 소형 파일 입력 | reg-input-sm 베이스 |
| `.reg-label` | 폼 필드 라벨 | 14px, font-weight 500 |
| `.reg-hint` | 필드 하단 보조 설명 | 12px, 서브텍스트 |
| `.zm-sel` | **커스텀 셀렉트 시스템** | `.zm-sel-trigger` + `.zm-sel-dropdown` + `.zm-sel-opt` + `.zm-sel-search` |

### 버튼 시스템

**3티어 구조:**
| 티어 | 클래스 | 용도 |
|------|--------|------|
| CTA | `.zm-btn-cta` | 페이지 주요 액션 (저장, 제출) |
| Hero | `.zm-btn-hero` | 강조 버튼 (카드 내 주요 액션) |
| Utility | `.zm-btn-utility` | 보조 버튼 (필터, 정렬) |
| Ghost | `.zm-btn-ghost` | 배경 없는 텍스트 버튼 |
| Link | `.zm-link` | 인라인 텍스트 링크 |

**특수 버튼:**
- `.zm-btn-action-primary` / `.zm-btn-action-outline` — 대시보드 액션
- `.zm-btn-glass` — Glass morphism 칩 (backdrop-blur)
- `.zm-btn-widget` — 위젯 내부 버튼
- `.zm-btn-bottom-action` — 전폭 dashed 하단 버튼
- `.pg-btn` / `.pg-active` / `.pg-disabled` — 페이지네이션

### 테이블/목록 컴포넌트

| 클래스 | 용도 |
|--------|------|
| `.emp-table` | 메인 데이터 테이블 (자동 tabular-nums) |
| `.form-table` / `.form-th` / `.form-td` | 폼 레이아웃 테이블 |
| `.table-striped` | 줄무늬 테이블 |
| `.list-info-bar` + `.list-per-page` | 목록 상단 정보바 |
| `.filter-grid` + `.filter-row` + `.filter-label` + `.filter-value` | 필터 그리드 |
| `.sort-icon` / `.sort-active` / `.sort-up` / `.sort-down` | 정렬 화살표 |

### 탭/네비게이션

| 클래스 | 용도 |
|--------|------|
| `.main-tab` / `.main-tab.active` | 페이지 상단 메인 탭 (employee_register, my_profile 등) |
| `.profile-tab` / `.profile-tab.active` | 프로필 내부 서브 탭 |
| `.approval-tab` / `.approval-tab.active` | 결재 모듈 탭 |
| `.zm-tab` / `.zm-tab-active` | 범용 탭 |
| `.board-tab-btn` | 게시판 탭 |
| `.tab-badge` | 탭 내부 건수 배지 (행동 필요 항목에만) |
| `.appt-filter-chip` / `.appt-filter-chip.active` | 발령 유형 필터 칩 |

### 상태/배지

| 클래스 | 용도 |
|--------|------|
| `.zm-status-pill` | 상태 필 (`.confirmed`, `.review`, `.muted`, `.danger`, `.info` 변형) |
| `.zm-action-btn` | 액션 상태 버튼 (`.zm-action-primary/warning/success/live/paused/danger`) |
| `.zm-badge-day` | 요일 배지 |
| `.badge-*` | 범용 배지 (`.badge-success`, `.badge-warning`, `.badge-danger`, `.badge-info`, `.badge-muted`) |
| `.zm-pill-ok/warn/danger/muted` | 간단 상태 필 |

### 카드/패널

| 클래스 | 용도 |
|--------|------|
| `.zm-card` | 기본 카드 (`bg-[var(--zm-surface-1)] border rounded-xl shadow-sm`) |
| `.zm-card-interactive` | 호버 시 살짝 떠오르는 인터랙티브 카드 |
| `.zm-panel` | 인셋 패널 (`bg-[var(--zm-surface-2)]`) |

### 모달

| 클래스 | 용도 |
|--------|------|
| `.appmodal-overlay` / `.appmodal-overlay.is-open` | 전역 모달 오버레이 (AppUI.confirm/prompt) |
| `.appmodal-card` | 모달 본체 (scale 애니메이션) |
| `.appmodal-body` | 모달 콘텐츠 영역 |
| `.appmodal-icon` | 아이콘 원형 (`.is-warn`, `.is-danger`, `.is-info`) |
| `.appmodal-btn` | 모달 버튼 (`.is-primary`, `.is-danger`, `.is-cancel`) |
| `.appmodal-input` | 모달 내 인풋 |

### 기타 컴포넌트

| 클래스 | 용도 |
|--------|------|
| `.zm-toggle` | 토글 스위치 (checkbox 기반) |
| `.zm-radio` / `.zm-radio-group` | 라디오 버튼 |
| `.zm-radio-group.zm-segmented` | 세그먼트 컨트롤 |
| `.zm-skeleton` / `.zm-skeleton-text` | 스켈레톤 로딩 |
| `.empty-state` | 빈 상태 메시지 |
| `.clock-widget` / `.clock-time` / `.clock-btn` | 출퇴근 위젯 |
| `.org-tree` / `.org-emp-card` / `.org-head-badge` | 조직도 |

### 아이콘
- Lucide 아이콘 라이브러리 (unpkg.com/lucide@latest)
- `<i data-lucide="icon-name"></i>` + `lucide.createIcons()` 호출

### 레이아웃
- 사이드바 `w-60` + 메인 콘텐츠 `ml-60 mt-14`
- 글로벌 폰트: Pretendard Variable

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

### 계좌연동 아키텍처 (2026-06-12)
**제공자 어댑터(seam)로 실연동↔샌드박스 전환.** 키 없이도 전 과정이 동작한다.
- **모드 토글**: `config/api_settings.json` 의 `bank_provider_mode` = `auto`(기본·키 있으면 실연동, 없으면 샌드박스) / `real` / `mock`. 설정 UI: 그룹웨어 관리 > API 설정 > 연동 모드.
- **단일 진입점** `includes/bank/provider.php::bank_provider_request($method,$path,$data,$query)` — `bankapi_request()`(실연동, config/bankapi.php) 또는 `bank_mock_request()`(샌드박스, includes/bank/mock_provider.php)로 디스패치. 반환 형태는 항상 `['ok','status','data']` 동일.
- **흐름**: 등록/조회(`api/bankapi.php`, 기존 `{success,message,data}` 규약 유지) → **동기화**(`api/bank_sync.php?action=sync` — 조회결과를 `bank_transactions` 에 트랜잭션·멱등 UPSERT + 규칙분류, 신규 `{ok,data,error}` 규약) → **분류/확정**(`api/bank_classify.php` list/confirm/reclassify).
- **분류 엔진**: `includes/bank/classify.php` 규칙기반(적요 키워드→`account_categories.code` 제안 메타만). **세무·금액 계산 로직은 불변** — `account_code/name/ai_confidence/is_confirmed` 만 다룬다. is_confirmed=0 으로 저장되고 사람이 확정. (LLM 엔진은 후속 PR 로 이 인터페이스 교체)
- **민감정보**: 계좌비밀번호·주민번호는 제공자 호출에만 쓰고 **저장/로깅 금지**.

#### 계좌연동 후속 PR (별도 작업 — 가드레일상 분리)
1. **멀티테넌시 격리** — 현재 `bank_sync.php` 의 `company_id=1` 하드코딩 + 계좌 소유회사 검증 없음. **단일조직이라 현재는 비취약**이나 multi-tenant 활성화 시 IDOR. 수정엔 **세션 스키마에 company_id 도입**이 선행돼야 하므로(인증/세션 = AI 단독수정 금지 영역) 별도 설계 PR.
2. **오픈뱅킹 실조회** — `config/openbanking.php`(OAuth2) 는 사용자 access_token 저장 테이블이 없어 미통합. 토큰 저장소 설계 선행.
3. **LLM 분류 엔진** — 규칙기반→LLM(taskflow ollama 등) 교체. `bank_classify_one()` 인터페이스 유지.
4. **rate limiting / 날짜·tx_type 정규화 강화** — 방어 강화(리뷰 medium 항목).

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

1. ~~**런타임 CREATE TABLE 제거 (P1)**~~ — 해결됨 (2026-06-21). 10개 파일(`annual_leave`, `bankapi`, `board`, `closing`, `labor_contract`, `labor_contract_template`, `labor_rules`, `payslip`, `org_hierarchy`, `settings` + 페이지 2개)에서 런타임 CREATE TABLE 제거. `closing_checklist`/`closing_attachments` DDL은 `db/schema_closing.sql`로 이관.
2. **응답 스키마 통일 (P2)** — 전체 33개 API 중 16개 NEW 패턴(`apiOk`/`apiError`) 전환 완료, 4개 혼합(`approval`, `docs`, `hospital`, `permissions` — 권한 체크만 NEW, 응답은 OLD), 13개 OLD(`respond()` 로컬 정의). 프론트가 각 API 형식에 맞게 코딩되어 있어 당장 깨지는 건 거의 없으나, `acct_invoice_match.php` 벌크 AI 매칭에서 응답 경로 불일치 버그 1건 확인(2026-06-21). 모듈 단위로 프론트 동반 업데이트.
3. **초대형 페이지 분해 (P3)** — ~~`labor.php`(3,157줄)~~ → 714줄로 리팩토링 완료(PHP 탭별 `pages/labor/_tab_*.php` 5개 분리 + JS → `assets/js/labor-*.js` 5개 분리, 2026-06-22). `acct_bank_history.php`(2,043줄), `attendance.php`(1,707줄), `acct_bank_closing.php`(1,504줄), `acct_bank_pattern.php`(1,489줄) 등 20개 페이지 800줄+. 인라인 JS 합산 약 11,200줄. PHP/JS 분리 + 컴포넌트화. ~~`organization.php`(1,348줄)~~ → 666줄로 리팩토링 완료. ~~`dashboard.php`(1,170줄)~~ → 212줄로 축소 완료(JS → `assets/js/dashboard.js` 분리).
4. **테스트 골격 (P3)** — `tests/`에 수동 스크립트 2개(`payslip_personal_scope_test.php`, `org_hierarchy_connectivity_test.php`)만 존재, 자동화 0%. Composer/PHPUnit 미도입 상태. CI/CD 설정 없음.
5. **config 환경 분리 (P4)** — `.gitignore`에 `config/*.local.php` 규칙은 존재하나, `database.php`에서 local 파일을 오버라이드하는 코드 미구현(죽은 규칙). DB credentials는 하드코딩(`root`/빈 비밀번호) + git 추적 중. API 키(`api_settings.json`)는 `.gitignore`로 보호됨. 프로덕션 배포 전 `database.php`에 local 오버라이드 로직 추가 필수.
6. ~~**dashboard_v2.php 정리**~~ — 해결됨 (파일 삭제 완료, 2026-06 확인).
