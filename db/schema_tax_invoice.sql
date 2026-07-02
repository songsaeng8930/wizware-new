-- =============================================================
-- 세금계산서 (홈택스 연동) DB 스키마
-- 회의록 #3: 홈택스 데이터 연동 - 세금계산서 매출/매입 통합 뷰
-- =============================================================

-- -------------------------------------------------------------
-- 세금계산서 메인 테이블
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tax_invoices (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id       INT UNSIGNED NOT NULL DEFAULT 1,
    invoice_type     ENUM('매출','매입') NOT NULL COMMENT '매출/매입 구분',
    invoice_number   VARCHAR(32)  NOT NULL COMMENT '세금계산서 승인번호',
    issue_date       DATE         NOT NULL COMMENT '작성일자',
    send_date        DATE         NULL COMMENT '전송일자',
    supplier_bizno   VARCHAR(12)  NOT NULL COMMENT '공급자 사업자번호',
    supplier_name    VARCHAR(100) NOT NULL COMMENT '공급자 상호',
    supplier_ceo     VARCHAR(50)  NULL COMMENT '공급자 대표자',
    buyer_bizno      VARCHAR(12)  NOT NULL COMMENT '공급받는자 사업자번호',
    buyer_name       VARCHAR(100) NOT NULL COMMENT '공급받는자 상호',
    buyer_ceo        VARCHAR(50)  NULL COMMENT '공급받는자 대표자',
    supply_amount    BIGINT       NOT NULL DEFAULT 0 COMMENT '공급가액',
    tax_amount       BIGINT       NOT NULL DEFAULT 0 COMMENT '세액',
    total_amount     BIGINT       NOT NULL DEFAULT 0 COMMENT '합계금액',
    tax_type         ENUM('과세','영세율','면세') NOT NULL DEFAULT '과세' COMMENT '과세유형',
    invoice_status   ENUM('정상','수정','취소') NOT NULL DEFAULT '정상',
    hometax_sync     TINYINT(1)  NOT NULL DEFAULT 0 COMMENT '홈택스 동기화 여부',
    synced_at        DATETIME    NULL COMMENT '마지막 동기화 시각',
    memo             VARCHAR(200) NULL,
    created_at       DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_invoice_number (company_id, invoice_number),
    INDEX idx_type_date (invoice_type, issue_date),
    INDEX idx_supplier (supplier_bizno),
    INDEX idx_buyer (buyer_bizno)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='세금계산서';

-- -------------------------------------------------------------
-- 세금계산서 품목
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tax_invoice_items (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id      INT UNSIGNED NOT NULL,
    item_date       DATE         NULL COMMENT '품목 일자',
    item_name       VARCHAR(200) NOT NULL COMMENT '품목명',
    spec            VARCHAR(100) NULL COMMENT '규격',
    quantity        DECIMAL(12,2) NOT NULL DEFAULT 1 COMMENT '수량',
    unit_price      BIGINT       NOT NULL DEFAULT 0 COMMENT '단가',
    supply_amount   BIGINT       NOT NULL DEFAULT 0 COMMENT '공급가액',
    tax_amount      BIGINT       NOT NULL DEFAULT 0 COMMENT '세액',
    FOREIGN KEY (invoice_id) REFERENCES tax_invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='세금계산서 품목';

-- -------------------------------------------------------------
-- 홈택스 동기화 이력
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS hometax_sync_log (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id  INT UNSIGNED NOT NULL DEFAULT 1,
    sync_type   VARCHAR(30) NOT NULL COMMENT 'sales_invoice, purchase_invoice 등',
    sync_count  INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '동기화 건수',
    status      ENUM('성공','실패','진행중') NOT NULL DEFAULT '성공',
    message     VARCHAR(500) NULL,
    started_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    finished_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='홈택스 동기화 이력';

-- -------------------------------------------------------------
-- 샘플 데이터 (매출 세금계산서)
-- -------------------------------------------------------------
INSERT INTO tax_invoices (invoice_type, invoice_number, issue_date, send_date, supplier_bizno, supplier_name, supplier_ceo, buyer_bizno, buyer_name, buyer_ceo, supply_amount, tax_amount, total_amount, tax_type, invoice_status, hometax_sync, synced_at) VALUES
('매출', '20260201-41000001-00000001', '2026-02-01', '2026-02-02', '123-45-67890', '주식회사 재밋', '송승환', '234-56-78901', '(주)테크솔루션', '김태호', 15000000, 1500000, 16500000, '과세', '정상', 1, '2026-02-28 09:00:00'),
('매출', '20260205-41000001-00000002', '2026-02-05', '2026-02-06', '123-45-67890', '주식회사 재밋', '송승환', '345-67-89012', '디자인웍스', '박지연', 8500000, 850000, 9350000, '과세', '정상', 1, '2026-02-28 09:00:00'),
('매출', '20260210-41000001-00000003', '2026-02-10', '2026-02-11', '123-45-67890', '주식회사 재밋', '송승환', '456-78-90123', '(주)스마트커머스', '이준혁', 3200000, 320000, 3520000, '과세', '정상', 1, '2026-02-28 09:00:00'),
('매출', '20260215-41000001-00000004', '2026-02-15', '2026-02-16', '123-45-67890', '주식회사 재밋', '송승환', '567-89-01234', '한국데이터', '최수진', 12000000, 1200000, 13200000, '과세', '정상', 1, '2026-02-28 09:00:00'),
('매출', '20260218-41000001-00000005', '2026-02-18', '2026-02-19', '123-45-67890', '주식회사 재밋', '송승환', '234-56-78901', '(주)테크솔루션', '김태호', 5500000, 550000, 6050000, '과세', '정상', 1, '2026-02-28 09:00:00'),
('매출', '20260220-41000001-00000006', '2026-02-20', '2026-02-21', '123-45-67890', '주식회사 재밋', '송승환', '678-90-12345', '그린에너지(주)', '정민우', 2000000, 0, 2000000, '영세율', '정상', 1, '2026-02-28 09:00:00'),
('매출', '20260225-41000001-00000007', '2026-02-25', '2026-02-26', '123-45-67890', '주식회사 재밋', '송승환', '789-01-23456', '(주)미래건설', '한동훈', 9800000, 980000, 10780000, '과세', '정상', 1, '2026-02-28 09:00:00'),
-- 매입 세금계산서
('매입', '20260203-52000001-00000001', '2026-02-03', '2026-02-04', '890-12-34567', 'NHN클라우드(주)', '김동훈', '123-45-67890', '주식회사 재밋', '송승환', 4200000, 420000, 4620000, '과세', '정상', 1, '2026-02-28 09:00:00'),
('매입', '20260205-52000001-00000002', '2026-02-05', '2026-02-06', '901-23-45678', '(주)오피스허브', '윤서영', '123-45-67890', '주식회사 재밋', '송승환', 1800000, 180000, 1980000, '과세', '정상', 1, '2026-02-28 09:00:00'),
('매입', '20260210-52000001-00000003', '2026-02-10', '2026-02-11', '012-34-56789', '세종사무기기', '강현수', '123-45-67890', '주식회사 재밋', '송승환', 650000, 65000, 715000, '과세', '정상', 1, '2026-02-28 09:00:00'),
('매입', '20260212-52000001-00000004', '2026-02-12', '2026-02-13', '890-12-34567', 'NHN클라우드(주)', '김동훈', '123-45-67890', '주식회사 재밋', '송승환', 3800000, 380000, 4180000, '과세', '정상', 1, '2026-02-28 09:00:00'),
('매입', '20260215-52000001-00000005', '2026-02-15', '2026-02-16', '234-56-00001', '(주)디지털마케팅', '오지훈', '123-45-67890', '주식회사 재밋', '송승환', 3500000, 350000, 3850000, '과세', '정상', 1, '2026-02-28 09:00:00'),
('매입', '20260220-52000001-00000006', '2026-02-20', '2026-02-21', '345-67-00002', '코리아호스팅', '임재현', '123-45-67890', '주식회사 재밋', '송승환', 980000, 98000, 1078000, '과세', '정상', 1, '2026-02-28 09:00:00'),
('매입', '20260222-52000001-00000007', '2026-02-22', NULL, '456-78-00003', '인테리어플러스', '배수진', '123-45-67890', '주식회사 재밋', '송승환', 2500000, 250000, 2750000, '과세', '수정', 0, NULL);

-- -------------------------------------------------------------
-- 세금계산서-통장거래 매핑
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS invoice_bank_mappings (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id      INT UNSIGNED NOT NULL COMMENT 'tax_invoices.id',
    transaction_id  INT UNSIGNED NOT NULL COMMENT 'bank_transactions.id',
    match_type      ENUM('auto','manual') NOT NULL DEFAULT 'auto' COMMENT '매칭 방법',
    confidence      TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '매칭 신뢰도 0-100',
    match_reason    VARCHAR(200) NULL COMMENT '매칭 근거 설명',
    name_warning    TINYINT(1)  NOT NULL DEFAULT 0 COMMENT '거래처명 불완전 일치 경고',
    aggregate_flag  ENUM('none','potential_n1','potential_1n') NOT NULL DEFAULT 'none' COMMENT '합산/분할 가능성 플래그',
    is_confirmed    TINYINT(1)  NOT NULL DEFAULT 0 COMMENT '사용자 확정 여부',
    confirmed_at    DATETIME    NULL,
    created_at      DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_invoice_tx (invoice_id, transaction_id),
    INDEX idx_invoice (invoice_id),
    INDEX idx_transaction (transaction_id),
    INDEX idx_confirmed (is_confirmed),
    FOREIGN KEY (invoice_id) REFERENCES tax_invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (transaction_id) REFERENCES bank_transactions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='세금계산서-통장거래 매핑';

-- -------------------------------------------------------------
-- 매칭 패턴 (AI/규칙 학습 결과)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS match_patterns (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_name    VARCHAR(100) NOT NULL COMMENT '거래처명',
    partner_bizno   VARCHAR(20)  NULL COMMENT '사업자번호',
    pattern_type    VARCHAR(30)  NOT NULL COMMENT 'amount_exact, date_offset, description_keyword, aggregate 등',
    pattern_rule    JSON         NOT NULL COMMENT '패턴 상세 규칙',
    previous_rules  JSON         NULL COMMENT '이전 pattern_rule 이력 배열',
    confidence      DECIMAL(5,2) NOT NULL DEFAULT 0 COMMENT '신뢰도 %',
    hit_count       INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '적중 횟수',
    miss_count      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '미적중 횟수',
    last_matched_at DATETIME     NULL,
    source          ENUM('rule','user','ai') NOT NULL DEFAULT 'rule' COMMENT '패턴 출처',
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_partner (partner_bizno),
    INDEX idx_type (pattern_type),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='매칭 패턴';

-- -------------------------------------------------------------
-- 매칭 이력 (확정/수정/해제 로그)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS match_history (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mapping_id      INT UNSIGNED NULL COMMENT 'invoice_bank_mappings.id',
    invoice_id      INT UNSIGNED NOT NULL,
    transaction_id  INT UNSIGNED NULL,
    action          ENUM('confirm','modify','remove','auto_match','manual_match','pattern_edit','unconfirm') NOT NULL,
    old_transaction_id INT UNSIGNED NULL COMMENT '수정 전 거래내역 (modify 시)',
    pattern_id      INT UNSIGNED NULL COMMENT '적용된 패턴 ID',
    actor           VARCHAR(50)  NULL COMMENT 'user/system/ai',
    memo            VARCHAR(200) NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_invoice (invoice_id),
    INDEX idx_pattern (pattern_id),
    INDEX idx_action (action),
    INDEX idx_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='매칭 이력';

-- 홈택스 동기화 이력 샘플
INSERT INTO hometax_sync_log (sync_type, sync_count, status, message, started_at, finished_at) VALUES
('sales_invoice', 7, '성공', '2026년 2월 매출 세금계산서 7건 동기화 완료', '2026-02-28 09:00:00', '2026-02-28 09:00:12'),
('purchase_invoice', 6, '성공', '2026년 2월 매입 세금계산서 6건 동기화 완료', '2026-02-28 09:00:12', '2026-02-28 09:00:25');
