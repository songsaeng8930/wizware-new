-- =============================================================
-- 세무 플러그인 DB 스키마
-- 2026.02.26 회의 기반 개발
-- =============================================================

-- -------------------------------------------------------------
-- 계정과목 마스터
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS account_categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(10)  NOT NULL UNIQUE COMMENT '계정과목 코드 (예: 511)',
    name        VARCHAR(100) NOT NULL COMMENT '계정과목명 (예: 급여)',
    parent_code VARCHAR(10)  NULL COMMENT '상위 계정과목 코드',
    type        ENUM('매출','매입','자산','부채','자본','비용','수익') NOT NULL DEFAULT '비용',
    tax_type    ENUM('과세','면세','영세율','불공제') NOT NULL DEFAULT '과세',
    is_active   TINYINT(1)  NOT NULL DEFAULT 1,
    sort_order  INT          NOT NULL DEFAULT 0,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='계정과목 마스터';

-- 계정과목 그룹 (중간 분류 헤더)
INSERT INTO account_categories (code, name, parent_code, type, tax_type, is_active, sort_order) VALUES
('G_CA',  '유동자산',       NULL, '자산', '불공제', 1, 1000),
('G_FA',  '유형자산',       NULL, '자산', '불공제', 1, 1085),
('G_IA',  '무형자산',       NULL, '자산', '불공제', 1, 1115),
('G_OA',  '기타비유동자산', NULL, '자산', '불공제', 1, 1135),
('G_CL',  '유동부채',       NULL, '부채', '불공제', 1, 2000),
('G_NL',  '비유동부채',     NULL, '부채', '불공제', 1, 2105),
('G_EQ',  '자본',           NULL, '자본', '불공제', 1, 3000),
('G_SL',  '매출',           NULL, '매출', '과세',   1, 4000),
('G_CG',  '매출원가',       NULL, '매입', '과세',   1, 4500),
('G_NI',  '영업외수익',     NULL, '수익', '불공제', 1, 4505),
('G_SGA', '판매비와관리비', NULL, '비용', '불공제', 1, 5000),
('G_NE',  '영업외비용',     NULL, '비용', '불공제', 1, 5235)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- 계정과목 데이터 (5자리 한국 표준 계정코드)
INSERT INTO account_categories (code, name, parent_code, type, tax_type, sort_order) VALUES
-- 유동자산
('10100', '현금',             'G_CA', '자산', '불공제', 1001),
('10300', '보통예금',         'G_CA', '자산', '불공제', 1010),
('10800', '외상매출금',       'G_CA', '자산', '불공제', 1020),
('12000', '미수금',           'G_CA', '자산', '불공제', 1030),
('12600', '계좌간거래',       'G_CA', '자산', '불공제', 1040),
('13100', '선급금',           'G_CA', '자산', '불공제', 1050),
('13300', '선급비용',         'G_CA', '자산', '불공제', 1060),
('13500', '부가세대급금',     'G_CA', '자산', '과세',   1070),
('13600', '선납세금',         'G_CA', '자산', '불공제', 1080),
-- 유형자산
('21200', '비품',             'G_FA', '자산', '과세',   1090),
('21300', '감가상각누계액',   'G_FA', '자산', '불공제', 1100),
('21900', '시설장치',         'G_FA', '자산', '과세',   1110),
-- 무형자산
('23200', '특허권',           'G_IA', '자산', '불공제', 1120),
('23900', '개발비',           'G_IA', '자산', '불공제', 1130),
-- 기타비유동자산
('96200', '임차보증금',       'G_OA', '자산', '불공제', 1140),
-- 유동부채
('25100', '외상매입금',       'G_CL', '부채', '불공제', 2001),
('25300', '미지급금',         'G_CL', '부채', '불공제', 2010),
('25400', '예수금',           'G_CL', '부채', '불공제', 2020),
('25500', '부가세예수금',     'G_CL', '부채', '과세',   2030),
('25700', '가수금',           'G_CL', '부채', '불공제', 2040),
('25900', '선수금',           'G_CL', '부채', '불공제', 2050),
('26000', '단기차입금',       'G_CL', '부채', '불공제', 2060),
('26100', '미지급세금',       'G_CL', '부채', '불공제', 2070),
('26200', '미지급비용',       'G_CL', '부채', '불공제', 2080),
('27400', '미지급급여',       'G_CL', '부채', '불공제', 2090),
('27500', '미지급사업소득',   'G_CL', '부채', '불공제', 2100),
-- 비유동부채
('29300', '장기차입금',       'G_NL', '부채', '불공제', 2110),
('31200', '전환사채',         'G_NL', '부채', '불공제', 2120),
-- 자본
('33100', '자본금',           'G_EQ', '자본', '불공제', 3010),
('34100', '주식발행초과금',   'G_EQ', '자본', '불공제', 3020),
('37600', '이월결손금',       'G_EQ', '자본', '불공제', 3030),
-- 매출
('40100', '상품매출',         'G_SL', '매출', '과세',   4010),
('40200', '제품매출',         'G_SL', '매출', '과세',   4020),
('41200', '서비스매출',       'G_SL', '매출', '과세',   4030),
('40400', '임대수입',         'G_SL', '매출', '과세',   4040),
-- 매출원가
('45100', '상품매입',         'G_CG', '매입', '과세',   4510),
('45200', '원재료매입',       'G_CG', '매입', '과세',   4520),
-- 영업외수익
('41300', '정부지원금수익',   'G_NI', '수익', '불공제', 4510),
('90100', '이자수익',         'G_NI', '수익', '불공제', 4520),
('93000', '잡이익',           'G_NI', '수익', '불공제', 4530),
-- 판매비와관리비
('80200', '직원급여',         'G_SGA','비용', '불공제', 5010),
('80300', '상여금',           'G_SGA','비용', '불공제', 5020),
('80600', '퇴직급여',         'G_SGA','비용', '불공제', 5030),
('80900', '감가상각비',       'G_SGA','비용', '불공제', 5035),
('81100', '복리후생비',       'G_SGA','비용', '과세',   5040),
('81200', '여비교통비',       'G_SGA','비용', '과세',   5050),
('81300', '접대비',           'G_SGA','비용', '불공제', 5060),
('81400', '통신비',           'G_SGA','비용', '과세',   5070),
('81600', '전력비',           'G_SGA','비용', '과세',   5080),
('81700', '세금과공과금',     'G_SGA','비용', '불공제', 5090),
('81900', '지급임차료',       'G_SGA','비용', '과세',   5100),
('82000', '수선비',           'G_SGA','비용', '과세',   5110),
('82100', '보험료',           'G_SGA','비용', '불공제', 5120),
('82300', '경상연구개발비',   'G_SGA','비용', '과세',   5130),
('82500', '교육훈련비',       'G_SGA','비용', '과세',   5140),
('82700', '차량유지비',       'G_SGA','비용', '과세',   5145),
('82800', '포장비',           'G_SGA','비용', '과세',   5150),
('82900', '사무용품비',       'G_SGA','비용', '과세',   5160),
('83000', '소모품비',         'G_SGA','비용', '과세',   5170),
('83100', '지급수수료',       'G_SGA','비용', '과세',   5180),
('83300', '광고선전비',       'G_SGA','비용', '과세',   5190),
('85200', '판매수수료',       'G_SGA','비용', '과세',   5200),
('85300', '협회비',           'G_SGA','비용', '불공제', 5210),
('85600', '출장비',           'G_SGA','비용', '과세',   5220),
('85700', '외주용역비',       'G_SGA','비용', '과세',   5230),
-- 영업외비용
('90000', '사업소득수수료',   'G_NE', '비용', '불공제', 5240),
('93100', '이자비용',         'G_NE', '비용', '불공제', 5250),
('93200', '외환차손',         'G_NE', '비용', '불공제', 5260),
('96000', '잡손실',           'G_NE', '비용', '불공제', 5270)
ON DUPLICATE KEY UPDATE parent_code = VALUES(parent_code);

-- -------------------------------------------------------------
-- 계좌 정보
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bank_accounts (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id       INT UNSIGNED NOT NULL DEFAULT 1,
    bank_code        VARCHAR(10)  NULL COMMENT '은행코드 (KB, NH 등 · Bank API용)',
    bank_name        VARCHAR(50)  NOT NULL COMMENT '은행명',
    account_no       VARCHAR(30)  NOT NULL COMMENT '계좌번호 (암호화 권장)',
    account_alias    VARCHAR(50)  NULL COMMENT '계좌 별칭',
    account_type     ENUM('운영','급여','세금','예비','기타') NOT NULL DEFAULT '운영' COMMENT '계좌 용도',
    owner_name       VARCHAR(50)  NOT NULL COMMENT '예금주',
    account_password VARCHAR(255) NULL COMMENT '계좌 비밀번호 (AES-256-GCM 암호화)',
    memo             TEXT         NULL COMMENT '관리 메모/비고',
    consent_agreed   TINYINT(1)  NOT NULL DEFAULT 0 COMMENT '자동수집 동의 여부',
    consent_agreed_at DATETIME   NULL,
    is_active        TINYINT(1)  NOT NULL DEFAULT 1,
    sort_order       INT          NOT NULL DEFAULT 0 COMMENT '표시 순서 (낮을수록 위)',
    created_at       DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_bank_account (bank_code, account_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='등록 계좌 정보';

-- 더미 데이터
INSERT INTO bank_accounts (bank_code, bank_name, account_no, account_alias, account_type, owner_name, consent_agreed, consent_agreed_at, sort_order) VALUES
('KB', '국민은행',   '123-456-789012', '운영계좌',   '운영', '주식회사 재밋', 1, '2026-01-15 10:00:00', 1),
('SH', '신한은행',   '234-567-890123', '급여계좌',   '급여', '주식회사 재밋', 1, '2026-01-15 10:00:00', 2),
('IBK', '기업은행',   '345-678-901234', '세금납부계좌', '세금', '주식회사 재밋', 0, NULL, 3);

-- -------------------------------------------------------------
-- 통장 입출금 내역 (AI 분류 결과 포함)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bank_transactions (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_id       INT UNSIGNED NOT NULL COMMENT 'bank_accounts.id',
    transaction_date DATE         NOT NULL,
    transaction_time VARCHAR(10)  NULL COMMENT '거래 시간 (HH:MM)',
    description      VARCHAR(200) NOT NULL COMMENT '거래 적요',
    counterparty     VARCHAR(200) NULL COMMENT '거래처',
    amount           BIGINT       NOT NULL COMMENT '금액 (양수)',
    tx_type          ENUM('입금','출금') NOT NULL,
    balance          BIGINT       NOT NULL DEFAULT 0 COMMENT '거래 후 잔액',
    account_code     VARCHAR(10)  NULL COMMENT '분류된 계정과목 코드',
    account_name     VARCHAR(100) NULL COMMENT '분류된 계정과목명',
    ai_confidence    TINYINT UNSIGNED NULL COMMENT 'AI 신뢰도 0-100',
    is_confirmed     TINYINT(1)  NOT NULL DEFAULT 0 COMMENT '사용자 확정 여부',
    memo             VARCHAR(200) NULL,
    uploaded_at      DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tx_dedup (account_id, transaction_date, description(100), amount, tx_type, balance),
    FOREIGN KEY (account_id) REFERENCES bank_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='통장 입출금 내역';

-- -------------------------------------------------------------
-- 분류 패턴 (적요 키워드 → 계정과목 학습)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS classification_patterns (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    keyword         VARCHAR(100) NOT NULL COMMENT '적요 키워드',
    tx_type         ENUM('입금','출금','전체') NOT NULL DEFAULT '전체',
    account_code    VARCHAR(20)  NOT NULL COMMENT '계정과목 코드',
    account_name    VARCHAR(50)  NOT NULL COMMENT '계정과목명',
    amount_min      BIGINT       NULL COMMENT '최소 금액',
    amount_max      BIGINT       NULL COMMENT '최대 금액',
    counterparty    VARCHAR(100) NULL COMMENT '거래처 키워드',
    recurrence      ENUM('none','daily','weekly','monthly','quarterly','semi_annual','annual') NOT NULL DEFAULT 'none' COMMENT '반복 주기',
    recurrence_day  TINYINT      NULL COMMENT '반복일 (월:1-31, 주:1-7)',
    priority        TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '우선순위 (조건 수 기반)',
    confidence      DECIMAL(5,2) NOT NULL DEFAULT 70 COMMENT '신뢰도 %',
    hit_count       INT UNSIGNED NOT NULL DEFAULT 0,
    miss_count      INT UNSIGNED NOT NULL DEFAULT 0,
    source          ENUM('rule','user','ai') NOT NULL DEFAULT 'user',
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_keyword_type (keyword, tx_type, account_code),
    INDEX idx_keyword (keyword),
    INDEX idx_account (account_code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='분류 패턴';

-- -------------------------------------------------------------
-- 분류 이력 (확정/수정/자동분류 로그)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS classification_history (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id   INT UNSIGNED NOT NULL,
    old_account_code VARCHAR(20)  NULL,
    new_account_code VARCHAR(20)  NOT NULL,
    new_account_name VARCHAR(50)  NULL,
    action           ENUM('auto_classify','manual_classify','confirm','modify','pattern_edit') NOT NULL,
    pattern_id       INT UNSIGNED NULL,
    actor            VARCHAR(50)  NULL COMMENT 'user/system/ai',
    memo             VARCHAR(200) NULL,
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tx (transaction_id),
    INDEX idx_pattern (pattern_id),
    INDEX idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='분류 이력';

-- -------------------------------------------------------------
-- 서류 요청
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS doc_requests (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    requester_id INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '요청자(세무사) ID',
    company_id   INT UNSIGNED NOT NULL DEFAULT 1,
    doc_name     VARCHAR(200) NOT NULL COMMENT '요청 서류명',
    description  TEXT         NULL COMMENT '요청 상세 설명',
    due_date     DATE         NULL COMMENT '제출 기한',
    status       ENUM('요청중','업로드완료','확인완료','취소') NOT NULL DEFAULT '요청중',
    requested_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME     NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='세무 서류 요청';

-- 더미 데이터
INSERT INTO doc_requests (doc_name, description, due_date, status) VALUES
('2025년 4분기 통장거래내역',    '국민은행 운영계좌 전체 내역 PDF', '2026-03-10', '요청중'),
('2025년 12월 급여대장',         '전직원 급여 지급 내역',             '2026-03-07', '업로드완료'),
('사업자등록증 사본',             '최신 발급본',                       '2026-03-15', '확인완료'),
('2025년 부가세 신고용 매입세금계산서 목록', '엑셀 또는 PDF 형식',  '2026-03-20', '요청중');

-- -------------------------------------------------------------
-- 서류 업로드 파일
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS doc_uploads (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_id  INT UNSIGNED NOT NULL,
    file_name   VARCHAR(255) NOT NULL,
    file_path   VARCHAR(500) NOT NULL,
    file_size   INT UNSIGNED NULL COMMENT 'bytes',
    uploaded_by INT UNSIGNED NOT NULL DEFAULT 1,
    uploaded_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES doc_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='서류 업로드 파일';

-- -------------------------------------------------------------
-- 알림
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL DEFAULT 1,
    type       VARCHAR(50)  NOT NULL COMMENT '알림 유형 (doc_request, doc_upload 등)',
    title      VARCHAR(200) NOT NULL,
    message    TEXT         NULL,
    link_url   VARCHAR(500) NULL COMMENT '클릭 시 이동 URL',
    is_read    TINYINT(1)  NOT NULL DEFAULT 0,
    created_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='알림';

-- 더미 데이터
INSERT INTO notifications (type, title, message, link_url) VALUES
('doc_upload',   '서류 업로드 완료', '2025년 12월 급여대장이 업로드되었습니다.',      '/pages/tax_docs.php'),
('doc_request',  '새 서류 요청',    '세무사가 사업자등록증 사본을 요청했습니다.',     '/pages/tax_docs.php'),
('doc_confirmed','서류 확인 완료',  '사업자등록증 사본 확인이 완료되었습니다.',       '/pages/tax_docs.php');

-- -------------------------------------------------------------
-- 급여 명세
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS payslips (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED NOT NULL,
    employee_name   VARCHAR(50)  NOT NULL,
    year            SMALLINT UNSIGNED NOT NULL,
    month           TINYINT UNSIGNED  NOT NULL,
    base_salary     BIGINT NOT NULL DEFAULT 0 COMMENT '기본급',
    overtime_hours  DECIMAL(5,1) NOT NULL DEFAULT 0 COMMENT '초과근무시간',
    overtime_pay    BIGINT NOT NULL DEFAULT 0 COMMENT '초과수당',
    meal_allowance  BIGINT NOT NULL DEFAULT 0 COMMENT '식대',
    car_allowance   BIGINT NOT NULL DEFAULT 0 COMMENT '차량지원',
    child_allowance BIGINT NOT NULL DEFAULT 0 COMMENT '육아수당',
    gross_pay       BIGINT NOT NULL DEFAULT 0 COMMENT '총지급액',
    national_pension BIGINT NOT NULL DEFAULT 0 COMMENT '국민연금',
    health_insurance BIGINT NOT NULL DEFAULT 0 COMMENT '건강보험',
    emp_insurance   BIGINT NOT NULL DEFAULT 0 COMMENT '고용보험',
    income_tax      BIGINT NOT NULL DEFAULT 0 COMMENT '소득세',
    total_deduction BIGINT NOT NULL DEFAULT 0 COMMENT '총공제액',
    net_pay         BIGINT NOT NULL DEFAULT 0 COMMENT '실수령액',
    generated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_payslip_month_employee (year, month, employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='급여 명세';

-- -------------------------------------------------------------
-- 세액공제 시뮬레이션
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tax_credit_simulations (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id              INT UNSIGNED NOT NULL DEFAULT 1,
    sim_year                SMALLINT UNSIGNED NOT NULL COMMENT '공제 적용 연도',
    base_employee_count     INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '기준년도 상시근로자 수',
    current_employee_count  INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '당해년도 상시근로자 수',
    youth_count             INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '청년 증가 인원',
    elder_count             INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '장년 증가 인원',
    region                  ENUM('수도권','비수도권') NOT NULL DEFAULT '수도권',
    youth_credit_per        BIGINT NOT NULL DEFAULT 11000000 COMMENT '청년 인당 공제액',
    elder_credit_per        BIGINT NOT NULL DEFAULT 7700000  COMMENT '장년 인당 공제액',
    total_credit            BIGINT NOT NULL DEFAULT 0 COMMENT '총 세액공제액',
    memo                    TEXT NULL,
    created_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='세액공제 시뮬레이션 이력';

-- -------------------------------------------------------------
-- 월 마감 (결산 Lock)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS closing_locks (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    year      SMALLINT UNSIGNED NOT NULL,
    month     TINYINT UNSIGNED  NOT NULL,
    is_locked TINYINT(1) NOT NULL DEFAULT 1,
    locked_by INT UNSIGNED NULL COMMENT '마감 실행 사용자 ID',
    closed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_year_month (year, month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='월별 결산 마감 상태';
