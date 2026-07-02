-- Zaemit 그룹웨어 - 법인카드 모듈 테이블
-- MySQL 9.6 호환
-- 실행: mysql -u root zaemit_groupware < db/schema_card.sql

-- 법인카드 정보
CREATE TABLE IF NOT EXISTS cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_alias VARCHAR(100) NOT NULL COMMENT '카드별칭',
    card_number VARCHAR(30) NULL COMMENT '카드번호 (마스킹)',
    memo TEXT NULL COMMENT '비고',
    manager_name VARCHAR(50) NOT NULL COMMENT '책임자',
    affiliation VARCHAR(100) NULL COMMENT '소속',
    department VARCHAR(100) NULL COMMENT '부서',
    is_active TINYINT(1) DEFAULT 1 COMMENT '사용여부',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 카드지출내역
CREATE TABLE IF NOT EXISTS card_expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id INT NOT NULL,
    registrant_name VARCHAR(50) NOT NULL COMMENT '등록자',
    approval_number VARCHAR(50) NULL COMMENT '승인번호',
    usage_type VARCHAR(20) NOT NULL DEFAULT '법인' COMMENT '사용구분 (법인/개인)',
    category VARCHAR(50) NOT NULL COMMENT '항목 (식대,교통비,접대비 등)',
    sub_category VARCHAR(50) NULL COMMENT '세부항목 (식사,여비교통비,영업사업비,구입비)',
    amount INT NOT NULL DEFAULT 0 COMMENT '사용금액',
    description TEXT NULL COMMENT '적요',
    business_name VARCHAR(200) NULL COMMENT '사업명',
    business_code VARCHAR(50) NULL COMMENT '사업코드',
    document_number VARCHAR(50) NULL COMMENT '문서번호',
    user_name VARCHAR(50) NULL COMMENT '사용자',
    usage_date DATE NOT NULL COMMENT '사용일',
    is_settled TINYINT(1) DEFAULT 0 COMMENT '정산여부',
    compliance_status VARCHAR(20) DEFAULT '미확인' COMMENT '규정준수여부 (준수/미준수/미확인)',
    settlement_updater VARCHAR(50) NULL COMMENT '정산 업데이트 작성자',
    settlement_date DATETIME NULL COMMENT '최종 업데이트일',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 법인카드 규정 카테고리
CREATE TABLE IF NOT EXISTS card_regulation_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    color VARCHAR(20) NOT NULL DEFAULT 'gray' COMMENT 'Tailwind 색상키 (green, blue, purple 등)',
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 법인카드 규정
CREATE TABLE IF NOT EXISTS card_regulations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL COMMENT '항목',
    sub_category VARCHAR(100) NOT NULL COMMENT '세부항목',
    limit_amount INT DEFAULT 0 COMMENT '한도 (원)',
    required_fields TEXT NULL COMMENT '입력시 필수사항',
    guide TEXT NULL COMMENT '세부항목 가이드',
    use_in_register TINYINT(1) DEFAULT 1 COMMENT '지출등록 폼 노출 여부',
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 카드 승인내역 (정산 페이지용)
CREATE TABLE IF NOT EXISTS card_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_id INT NULL COMMENT '관련 지출내역 ID',
    card_id INT NOT NULL,
    approval_number VARCHAR(50) NOT NULL COMMENT '승인번호',
    merchant_name VARCHAR(200) NULL COMMENT '가맹점명',
    approval_amount INT NOT NULL DEFAULT 0 COMMENT '승인금액',
    approval_date DATETIME NOT NULL COMMENT '승인일시',
    approval_status VARCHAR(20) DEFAULT '승인' COMMENT '승인상태 (승인/취소)',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE,
    FOREIGN KEY (expense_id) REFERENCES card_expenses(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 샘플 데이터: 법인카드 (employees 테이블의 실제 직원명 사용)
INSERT INTO cards (card_alias, card_number, memo, manager_name, affiliation, department, is_active) VALUES
('영업팀 법인카드', '9410-****-****-1234', '영업팀 업무용', '최영업', '위즈웨어', '영업팀', 1),
('개발팀 법인카드', '9410-****-****-5678', '개발팀 업무용', '박기술', '위즈웨어', '개발팀', 1),
('경영지원 법인카드', '5412-****-****-9012', '경영지원실 업무용', '정지원', '위즈웨어', '경영지원실', 1),
('대표이사 법인카드', '4532-****-****-3456', '대표이사 전용', '김대표', '위즈웨어', '경영진', 1),
('마케팅팀 법인카드', '9410-****-****-7890', '마케팅 업무용', '한인사', '위즈웨어', '마케팅팀', 0);

-- 샘플 데이터: 지출내역
INSERT INTO card_expenses (card_id, registrant_name, approval_number, usage_type, category, sub_category, amount, description, business_name, document_number, user_name, usage_date, is_settled, compliance_status, settlement_date, settlement_updater) VALUES
(1, '최영업', 'AP-2026-001', '법인', '식대', '식사', 45000, '거래처 미팅 식사', 'A프로젝트', 'DOC-2026-0101', '최영업', '2026-02-20', 0, '미확인', NULL, NULL),
(1, '최영업', 'AP-2026-002', '법인', '교통비', '여비교통비', 32000, '거래처 방문 택시비', 'A프로젝트', 'DOC-2026-0102', '최영업', '2026-02-21', 0, '미확인', NULL, NULL),
(2, '박기술', 'AP-2026-003', '법인', '소모품', '구입비', 128000, '개발용 장비 구입', '', '', '박기술', '2026-02-19', 1, '준수', '2026-02-25 10:00:00', '정지원'),
(3, '정지원', 'AP-2026-004', '법인', '접대비', '영업사업비', 85000, '고객사 접대', 'B프로젝트', 'DOC-2026-0201', '정지원', '2026-02-18', 1, '준수', '2026-02-25 10:00:00', '정지원'),
(1, '한인사', 'AP-2026-005', '개인', '교통비', '여비교통비', 15000, '외근 교통비', '', '', '한인사', '2026-02-22', 0, '미확인', NULL, NULL),
(2, '박기술', 'AP-2026-006', '법인', '식대', '식사', 62000, '팀 회식', '', '', '박기술', '2026-02-17', 0, '미준수', NULL, NULL),
(4, '김대표', 'AP-2026-007', '법인', '접대비', '영업사업비', 250000, 'VIP 고객 미팅', 'C프로젝트', 'DOC-2026-0301', '김대표', '2026-02-15', 1, '준수', '2026-02-25 10:00:00', '정지원'),
(3, '정지원', 'AP-2026-008', '법인', '기타', '구입비', 35000, '사무용품 구입', '', '', '정지원', '2026-02-16', 0, '미확인', NULL, NULL),
(1, '최영업', 'AP-2026-009', '법인', '식대', '식사', 55000, '프로젝트 킥오프 식사', 'D프로젝트', 'DOC-2026-0401', '최영업', '2026-02-23', 0, '미확인', NULL, NULL),
(2, '이본부장', 'AP-2026-010', '개인', '교통비', '여비교통비', 28000, '출장 교통비', 'A프로젝트', '', '이본부장', '2026-02-24', 0, '미확인', NULL, NULL);

-- 샘플 데이터: 규정 카테고리
INSERT INTO card_regulation_categories (name, color, sort_order) VALUES
('식사', 'green', 1),
('여비교통비', 'blue', 2),
('영업사업비', 'purple', 3),
('구입비', 'orange', 4);

-- 샘플 데이터: 규정
INSERT INTO card_regulations (category, sub_category, limit_amount, required_fields, guide, sort_order) VALUES
('식사', '중식/석식', 15000, '영수증, 참석자명단', '1인당 15,000원 이내. 4인 이상 시 사전 승인 필요', 1),
('식사', '회식', 50000, '영수증, 참석자명단, 사유서', '1인당 50,000원 이내. 팀장 사전승인 필수', 2),
('식사', '간식/음료', 10000, '영수증', '1인당 10,000원 이내', 3),
('여비교통비', '시내교통', 0, '영수증, 출발/도착지', '실비 정산. 택시 이용 시 사유 기재 필수', 4),
('여비교통비', '출장교통', 0, '영수증, 출장보고서', '실비 정산. KTX 이상 시 사전승인 필요', 5),
('여비교통비', '주차비/톨비', 0, '영수증', '실비 정산', 6),
('영업사업비', '거래처 접대', 100000, '영수증, 접대보고서', '1건당 100,000원 이내. 초과 시 부서장 승인', 7),
('영업사업비', '경조사비', 50000, '경조사 증빙', '건당 50,000원. 경조사 규정 참조', 8),
('영업사업비', '선물/기념품', 30000, '영수증, 사유서', '1건당 30,000원 이내', 9),
('구입비', '사무용품', 50000, '영수증, 구매요청서', '건당 50,000원 이내. 초과 시 구매부서 경유', 10),
('구입비', '소프트웨어', 0, '영수증, 구매요청서, 라이선스 정보', 'IT부서 사전 승인 필수', 11),
('구입비', '장비/비품', 0, '영수증, 구매요청서, 자산등록', '50만원 이상 시 자산등록 필수', 12);

-- 샘플 데이터: 승인내역
INSERT INTO card_approvals (expense_id, card_id, approval_number, merchant_name, approval_amount, approval_date, approval_status) VALUES
(1, 1, 'AP-2026-001', '한우마을 강남점', 45000, '2026-02-20 12:30:00', '승인'),
(2, 1, 'AP-2026-002', '카카오택시', 32000, '2026-02-21 09:15:00', '승인'),
(3, 2, 'AP-2026-003', '쿠팡', 128000, '2026-02-19 14:20:00', '승인'),
(4, 3, 'AP-2026-004', '스시오마카세', 85000, '2026-02-18 18:45:00', '승인'),
(5, 1, 'AP-2026-005', '카카오택시', 15000, '2026-02-22 10:00:00', '승인'),
(6, 2, 'AP-2026-006', '고깃집 서초점', 62000, '2026-02-17 19:30:00', '승인'),
(7, 4, 'AP-2026-007', '르씨엘 레스토랑', 250000, '2026-02-15 12:00:00', '승인'),
(8, 3, 'AP-2026-008', '오피스디포', 35000, '2026-02-16 11:10:00', '승인'),
(9, 1, 'AP-2026-009', '더플레이스 역삼', 55000, '2026-02-23 12:15:00', '승인'),
(10, 2, 'AP-2026-010', 'KTX', 28000, '2026-02-24 08:00:00', '승인'),
(NULL, 1, 'AP-2026-011', 'GS25 역삼점', 8500, '2026-02-25 15:30:00', '승인'),
(NULL, 3, 'AP-2026-012', '네이버페이', 42000, '2026-02-25 16:00:00', '취소');
