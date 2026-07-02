-- 근로계약서 스키마
-- 실행: mysql -u root zaemit_groupware < db/schema_labor_contract.sql

CREATE TABLE IF NOT EXISTS labor_contracts (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT NOT NULL COMMENT 'employees.id',
    contract_status VARCHAR(20) DEFAULT 'draft' COMMENT 'draft/signed/expiring/none',
    version         INT DEFAULT 1 COMMENT '계약 갱신 시 버전 증가',

    -- 사용자(회사) 정보
    company_name    VARCHAR(100) DEFAULT '',
    company_ceo     VARCHAR(50)  DEFAULT '',
    company_address VARCHAR(200) NULL,
    company_bizno   VARCHAR(14)  NULL COMMENT '사업자등록번호',

    -- 계약기간
    contract_type   VARCHAR(20) DEFAULT 'permanent' COMMENT 'permanent/fixed/parttime',
    contract_start  DATE NULL,
    contract_end    DATE NULL COMMENT 'permanent이면 NULL',

    -- 업무/근무지
    job_description VARCHAR(200) NULL COMMENT '종사업무',
    workplace       VARCHAR(200) NULL COMMENT '근무장소',

    -- 근로시간
    work_start      TIME DEFAULT '09:00',
    work_end        TIME DEFAULT '18:00',
    break_start     TIME DEFAULT '12:00',
    break_end       TIME DEFAULT '13:00',
    work_days       VARCHAR(50) DEFAULT '' COMMENT '근무요일',

    -- 휴일/휴가
    weekly_holiday  VARCHAR(50)  DEFAULT '',
    annual_leave    VARCHAR(200) DEFAULT '',

    -- 임금
    base_pay        INT DEFAULT 0 COMMENT '기본급(월)',
    meal_allowance  INT DEFAULT 0 COMMENT '식대',
    car_allowance   INT DEFAULT 0 COMMENT '차량지원비',
    child_allowance INT DEFAULT 0 COMMENT '육아수당',
    extra_pay_1     INT DEFAULT 0 COMMENT '추가수당1',
    extra_pay_2     INT DEFAULT 0 COMMENT '추가수당2',
    extra_pay_3     INT DEFAULT 0 COMMENT '추가수당3',
    monthly_total   INT DEFAULT 0 COMMENT '월 급여 합계',
    annual_total    INT DEFAULT 0 COMMENT '연봉',
    pay_day         INT DEFAULT 25 COMMENT '매월 지급일',
    pay_method      VARCHAR(20) DEFAULT 'transfer' COMMENT 'transfer/cash/other',

    -- 사회보험 (4대보험)
    ins_pension     TINYINT(1) DEFAULT 1 COMMENT '국민연금',
    ins_health      TINYINT(1) DEFAULT 1 COMMENT '건강보험',
    ins_employment  TINYINT(1) DEFAULT 1 COMMENT '고용보험',
    ins_industrial  TINYINT(1) DEFAULT 1 COMMENT '산재보험',

    -- 퇴직금/수습
    retirement_pay  TINYINT(1) DEFAULT 1 COMMENT '1=적용, 0=미적용',
    probation       VARCHAR(20) DEFAULT '3' COMMENT '0/1/3/6 (개월)',

    -- 사용 양식 스냅샷
    template_id      INT NULL COMMENT 'contract_templates.id (작성 시점 스냅샷)',
    template_name    VARCHAR(100) NULL COMMENT '양식 이름 스냅샷',
    template_version VARCHAR(50)  NULL COMMENT '양식 버전 스냅샷',

    -- 기타
    additional_terms TEXT NULL COMMENT '기타 근로조건',
    signed_at       DATETIME NULL COMMENT '체결완료 시점',

    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_employee (employee_id),
    INDEX idx_status (contract_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 기존 테이블에 template 컬럼 없으면 추가
ALTER TABLE labor_contracts
    ADD COLUMN IF NOT EXISTS template_id      INT NULL COMMENT 'contract_templates.id' AFTER probation,
    ADD COLUMN IF NOT EXISTS template_name    VARCHAR(100) NULL COMMENT '양식 이름 스냅샷' AFTER template_id,
    ADD COLUMN IF NOT EXISTS template_version VARCHAR(50)  NULL COMMENT '양식 버전 스냅샷' AFTER template_name;
