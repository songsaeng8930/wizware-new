# 세금계산서 (홈택스 연동) 개발 플랜

## 배경
- 회의록(2026.02.26) 핵심 기능 #3: "홈택스 데이터 연동 - 세금계산서 매출/매입 통합 뷰 제공"
- 영세 사업자가 가장 관심 있는 세금은 부가세 → 세금계산서 매출/매입 통합 관리가 핵심
- 기존 세무 리포트(tax_report.php)의 부가세 데이터와 연계

## 기능 범위

### 1차 개발 (현재)
- 세금계산서 매출/매입 통합 리스트 뷰
- 기간별 필터 (월별, 분기별)
- 매출/매입 탭 분리 + 전체 통합 뷰
- 요약 카드 (매출세액, 매입세액, 예상 납부/환급액)
- 거래처별 집계 테이블
- 세금계산서 상세 목록 테이블 (검색/페이지네이션)
- 홈택스 연동 상태 표시 (동기화 버튼 UI)
- 샘플 데이터 기반 프론트엔드 완성

### 2차 개발 (향후)
- 실제 홈택스 API 연동 (공인인증 필요)
- 세금계산서 자동 수집 스케줄러
- 매출/매입 불일치 알림
- PDF 다운로드 및 출력

## 파일 구조

```
db/schema_tax_invoice.sql    -- 세금계산서 테이블 스키마
api/tax_invoice.php          -- API 엔드포인트
pages/tax_invoice.php        -- 메인 페이지 (매출/매입 통합 뷰)
includes/sidebar.php         -- 메뉴 추가 (세무 > 세금계산서)
```

## DB 테이블

### tax_invoices (세금계산서)
| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | INT PK | 자동증가 |
| invoice_type | ENUM('매출','매입') | 매출/매입 구분 |
| invoice_number | VARCHAR(24) | 세금계산서 번호 (승인번호) |
| issue_date | DATE | 작성일자 |
| send_date | DATE | 전송일자 |
| supplier_bizno | VARCHAR(12) | 공급자 사업자번호 |
| supplier_name | VARCHAR(100) | 공급자 상호 |
| supplier_ceo | VARCHAR(50) | 공급자 대표자 |
| buyer_bizno | VARCHAR(12) | 공급받는자 사업자번호 |
| buyer_name | VARCHAR(100) | 공급받는자 상호 |
| buyer_ceo | VARCHAR(50) | 공급받는자 대표자 |
| supply_amount | BIGINT | 공급가액 |
| tax_amount | BIGINT | 세액 |
| total_amount | BIGINT | 합계금액 |
| tax_type | ENUM('과세','영세율','면세') | 과세유형 |
| invoice_status | ENUM('정상','수정','취소') | 상태 |
| hometax_sync | TINYINT(1) | 홈택스 동기화 여부 |
| synced_at | DATETIME | 동기화 시각 |
| memo | VARCHAR(200) | 비고 |

### tax_invoice_items (세금계산서 품목)
| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | INT PK | 자동증가 |
| invoice_id | INT FK | tax_invoices.id |
| item_date | DATE | 품목 일자 |
| item_name | VARCHAR(200) | 품목명 |
| spec | VARCHAR(100) | 규격 |
| quantity | DECIMAL(12,2) | 수량 |
| unit_price | BIGINT | 단가 |
| supply_amount | BIGINT | 공급가액 |
| tax_amount | BIGINT | 세액 |

## 페이지 구성

### 헤더 영역
- 타이틀: "세금계산서"
- 홈택스 동기화 버튼 (마지막 동기화 시각 표시)

### 기간 선택 필터
- 월별/분기별 토글
- 연도 셀렉트, 월 셀렉트
- 현재 선택 기간 텍스트 표시

### 요약 카드 (4열)
1. 매출세액 합계 (건수 포함)
2. 매입세액 합계 (건수 포함)
3. 예상 납부/환급세액 (매출세액 - 매입세액)
4. 거래처 수

### 탭 + 리스트 통합 카드
- 탭: 전체 / 매출 세금계산서 / 매입 세금계산서
- 정보바: 총 건수 + 페이지당 건수 셀렉트
- 검색 필터: 거래처명, 사업자번호, 기간
- 테이블 컬럼: 작성일자, 세금계산서번호, 거래처, 사업자번호, 공급가액, 세액, 합계, 과세유형, 상태
- 페이지네이션

### 거래처별 집계
- 거래처명, 건수, 공급가액 합계, 세액 합계 테이블

## 디자인 패턴
- 기존 프로젝트 패턴 준수 (Tailwind CSS + custom.css)
- 통합 카드 구조: `.bg-white .rounded-xl .border .border-gray-200`
- 탭: `.approval-tab` + `.tab-badge`
- 정보바: `.list-info-bar` + `.list-per-page`
- 테이블: `.emp-table`
- 페이지네이션: `.pg-btn` / `.pg-active` / `.pg-disabled`
- 필터: `.filter-grid` + `.filter-row` + `.filter-label` + `.filter-value`
