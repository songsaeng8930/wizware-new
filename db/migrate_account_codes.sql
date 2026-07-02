-- =============================================================
-- 계정과목 코드 마이그레이션: 3자리 → 5자리 표준 코드
-- 기존 3자리 코드(틀린 번호)를 삭제하고, 5자리 표준 코드로 통일
-- 실행 전 반드시 백업할 것
-- =============================================================

-- 1) 5자리 코드가 이미 있는 3자리 코드: 거래내역을 5자리로 이관 후 삭제
-- 보통예금: 802 → 10300
UPDATE bank_transactions SET account_code = '10300', account_name = '보통예금' WHERE account_code = '802';
DELETE FROM account_categories WHERE code = '802';

-- 외상매출금: 803 → 10800
UPDATE bank_transactions SET account_code = '10800', account_name = '외상매출금' WHERE account_code = '803';
DELETE FROM account_categories WHERE code = '803';

-- 미지급금: 902 → 25300
UPDATE bank_transactions SET account_code = '25300', account_name = '미지급금' WHERE account_code = '902';
DELETE FROM account_categories WHERE code = '902';

-- 퇴직급여: 512 → 80600
UPDATE bank_transactions SET account_code = '80600', account_name = '퇴직급여' WHERE account_code = '512';
DELETE FROM account_categories WHERE code = '512';

-- 복리후생비: 513 → 81100
UPDATE bank_transactions SET account_code = '81100', account_name = '복리후생비' WHERE account_code = '513';
DELETE FROM account_categories WHERE code = '513';

-- 여비교통비: 528 → 81200
UPDATE bank_transactions SET account_code = '81200', account_name = '여비교통비' WHERE account_code = '528';
DELETE FROM account_categories WHERE code = '528';

-- 접대비: 524 → 81300 (과세→불공제도 수정됨)
UPDATE bank_transactions SET account_code = '81300', account_name = '접대비' WHERE account_code = '524';
DELETE FROM account_categories WHERE code = '524';

-- 통신비: 525 → 81400
UPDATE bank_transactions SET account_code = '81400', account_name = '통신비' WHERE account_code = '525';
DELETE FROM account_categories WHERE code = '525';

-- 세금과공과: 533 → 81700
UPDATE bank_transactions SET account_code = '81700', account_name = '세금과공과금' WHERE account_code = '533';
DELETE FROM account_categories WHERE code = '533';

-- 임차료: 521 → 81900
UPDATE bank_transactions SET account_code = '81900', account_name = '지급임차료' WHERE account_code = '521';
DELETE FROM account_categories WHERE code = '521';

-- 보험료: 532 → 82100
UPDATE bank_transactions SET account_code = '82100', account_name = '보험료' WHERE account_code = '532';
DELETE FROM account_categories WHERE code = '532';

-- 교육훈련비: 529 → 82500
UPDATE bank_transactions SET account_code = '82500', account_name = '교육훈련비' WHERE account_code = '529';
DELETE FROM account_categories WHERE code = '529';

-- 소모품비: 526 → 83000
UPDATE bank_transactions SET account_code = '83000', account_name = '소모품비' WHERE account_code = '526';
DELETE FROM account_categories WHERE code = '526';

-- 지급수수료: 522 → 83100
UPDATE bank_transactions SET account_code = '83100', account_name = '지급수수료' WHERE account_code = '522';
DELETE FROM account_categories WHERE code = '522';

-- 광고선전비: 523 → 83300
UPDATE bank_transactions SET account_code = '83300', account_name = '광고선전비' WHERE account_code = '523';
DELETE FROM account_categories WHERE code = '523';

-- 이자수익: 601 → 90100
UPDATE bank_transactions SET account_code = '90100', account_name = '이자수익' WHERE account_code = '601';
DELETE FROM account_categories WHERE code = '601';

-- 잡이익: 602 → 93000
UPDATE bank_transactions SET account_code = '93000', account_name = '잡이익' WHERE account_code = '602';
DELETE FROM account_categories WHERE code = '602';

-- 이자비용: 701 → 93100
UPDATE bank_transactions SET account_code = '93100', account_name = '이자비용' WHERE account_code = '701';
DELETE FROM account_categories WHERE code = '701';

-- 2) 5자리 대응이 없는 3자리 코드: 5자리 추가 후 거래 이관, 3자리 삭제

-- 현금: 801 → 10100
INSERT IGNORE INTO account_categories (code, name, type, tax_type, sort_order) VALUES ('10100', '현금', '자산', '불공제', 1000);
UPDATE bank_transactions SET account_code = '10100', account_name = '현금' WHERE account_code = '801';
DELETE FROM account_categories WHERE code = '801';

-- 외상매입금: 901 → 25100
INSERT IGNORE INTO account_categories (code, name, type, tax_type, sort_order) VALUES ('25100', '외상매입금', '부채', '불공제', 2000);
UPDATE bank_transactions SET account_code = '25100', account_name = '외상매입금' WHERE account_code = '901';
DELETE FROM account_categories WHERE code = '901';

-- 상품매출: 401 → 40100
INSERT IGNORE INTO account_categories (code, name, type, tax_type, sort_order) VALUES ('40100', '상품매출', '매출', '과세', 4000);
UPDATE bank_transactions SET account_code = '40100', account_name = '상품매출' WHERE account_code = '401';
DELETE FROM account_categories WHERE code = '401';

-- 제품매출: 402 → 40200
INSERT IGNORE INTO account_categories (code, name, type, tax_type, sort_order) VALUES ('40200', '제품매출', '매출', '과세', 4005);
UPDATE bank_transactions SET account_code = '40200', account_name = '제품매출' WHERE account_code = '402';
DELETE FROM account_categories WHERE code = '402';

-- 서비스매출: 403 → 41200 (이미 존재)
UPDATE bank_transactions SET account_code = '41200', account_name = '서비스매출' WHERE account_code = '403';
DELETE FROM account_categories WHERE code = '403';

-- 임대수입: 404 → 40400
INSERT IGNORE INTO account_categories (code, name, type, tax_type, sort_order) VALUES ('40400', '임대수입', '매출', '과세', 4020);
UPDATE bank_transactions SET account_code = '40400', account_name = '임대수입' WHERE account_code = '404';
DELETE FROM account_categories WHERE code = '404';

-- 상품매입: 501 → 45100
INSERT IGNORE INTO account_categories (code, name, type, tax_type, sort_order) VALUES ('45100', '상품매입', '매입', '과세', 4510);
UPDATE bank_transactions SET account_code = '45100', account_name = '상품매입' WHERE account_code = '501';
DELETE FROM account_categories WHERE code = '501';

-- 원재료매입: 502 → 45200
INSERT IGNORE INTO account_categories (code, name, type, tax_type, sort_order) VALUES ('45200', '원재료매입', '매입', '과세', 4520);
UPDATE bank_transactions SET account_code = '45200', account_name = '원재료매입' WHERE account_code = '502';
DELETE FROM account_categories WHERE code = '502';

-- 급여: 511 → 80200 (직원급여, 이미 존재)
UPDATE bank_transactions SET account_code = '80200', account_name = '직원급여' WHERE account_code = '511';
DELETE FROM account_categories WHERE code = '511';

-- 차량유지비: 527 → 82700
INSERT IGNORE INTO account_categories (code, name, type, tax_type, sort_order) VALUES ('82700', '차량유지비', '비용', '과세', 5145);
UPDATE bank_transactions SET account_code = '82700', account_name = '차량유지비' WHERE account_code = '527';
DELETE FROM account_categories WHERE code = '527';

-- 감가상각비: 531 → 80900
INSERT IGNORE INTO account_categories (code, name, type, tax_type, sort_order) VALUES ('80900', '감가상각비', '비용', '불공제', 5035);
UPDATE bank_transactions SET account_code = '80900', account_name = '감가상각비' WHERE account_code = '531';
DELETE FROM account_categories WHERE code = '531';

-- 3) 계정과목 그룹 레코드 추가 + parent_code 설정
INSERT IGNORE INTO account_categories (code, name, parent_code, type, tax_type, is_active, sort_order) VALUES
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
('G_NE',  '영업외비용',     NULL, '비용', '불공제', 1, 5235);

-- 유동자산
UPDATE account_categories SET parent_code = 'G_CA' WHERE code IN ('10100','10300','10800','12000','12600','13100','13300','13500','13600') AND parent_code IS NULL;
-- 유형자산
UPDATE account_categories SET parent_code = 'G_FA' WHERE code IN ('21200','21300','21900') AND parent_code IS NULL;
-- 무형자산
UPDATE account_categories SET parent_code = 'G_IA' WHERE code IN ('23200','23900') AND parent_code IS NULL;
-- 기타비유동자산
UPDATE account_categories SET parent_code = 'G_OA' WHERE code IN ('96200') AND parent_code IS NULL;
-- 유동부채
UPDATE account_categories SET parent_code = 'G_CL' WHERE code IN ('25100','25300','25400','25500','25700','25900','26000','26100','26200','27400','27500') AND parent_code IS NULL;
-- 비유동부채
UPDATE account_categories SET parent_code = 'G_NL' WHERE code IN ('29300','31200') AND parent_code IS NULL;
-- 자본
UPDATE account_categories SET parent_code = 'G_EQ' WHERE code IN ('33100','34100','37600') AND parent_code IS NULL;
-- 매출
UPDATE account_categories SET parent_code = 'G_SL' WHERE code IN ('40100','40200','41200','40400') AND parent_code IS NULL;
-- 매출원가
UPDATE account_categories SET parent_code = 'G_CG' WHERE code IN ('45100','45200') AND parent_code IS NULL;
-- 영업외수익
UPDATE account_categories SET parent_code = 'G_NI' WHERE code IN ('41300','90100','93000') AND parent_code IS NULL;
-- 판매비와관리비
UPDATE account_categories SET parent_code = 'G_SGA' WHERE code IN ('80200','80300','80600','80900','81100','81200','81300','81400','81600','81700','81900','82000','82100','82300','82500','82700','82800','82900','83000','83100','83300','85200','85300','85600','85700') AND parent_code IS NULL;
-- 영업외비용
UPDATE account_categories SET parent_code = 'G_NE' WHERE code IN ('90000','93100','93200','96000') AND parent_code IS NULL;

-- 4) classification_patterns 테이블도 같은 코드 이관
UPDATE classification_patterns SET account_code = '10300' WHERE account_code = '802';
UPDATE classification_patterns SET account_code = '10800' WHERE account_code = '803';
UPDATE classification_patterns SET account_code = '25300' WHERE account_code = '902';
UPDATE classification_patterns SET account_code = '80600' WHERE account_code = '512';
UPDATE classification_patterns SET account_code = '81100' WHERE account_code = '513';
UPDATE classification_patterns SET account_code = '81200' WHERE account_code = '528';
UPDATE classification_patterns SET account_code = '81300' WHERE account_code = '524';
UPDATE classification_patterns SET account_code = '81400' WHERE account_code = '525';
UPDATE classification_patterns SET account_code = '81700' WHERE account_code = '533';
UPDATE classification_patterns SET account_code = '81900' WHERE account_code = '521';
UPDATE classification_patterns SET account_code = '82100' WHERE account_code = '532';
UPDATE classification_patterns SET account_code = '82500' WHERE account_code = '529';
UPDATE classification_patterns SET account_code = '83000' WHERE account_code = '526';
UPDATE classification_patterns SET account_code = '83100' WHERE account_code = '522';
UPDATE classification_patterns SET account_code = '83300' WHERE account_code = '523';
UPDATE classification_patterns SET account_code = '90100' WHERE account_code = '601';
UPDATE classification_patterns SET account_code = '93000' WHERE account_code = '602';
UPDATE classification_patterns SET account_code = '93100' WHERE account_code = '701';
UPDATE classification_patterns SET account_code = '10100' WHERE account_code = '801';
UPDATE classification_patterns SET account_code = '25100' WHERE account_code = '901';
UPDATE classification_patterns SET account_code = '40100' WHERE account_code = '401';
UPDATE classification_patterns SET account_code = '40200' WHERE account_code = '402';
UPDATE classification_patterns SET account_code = '41200' WHERE account_code = '403';
UPDATE classification_patterns SET account_code = '40400' WHERE account_code = '404';
UPDATE classification_patterns SET account_code = '45100' WHERE account_code = '501';
UPDATE classification_patterns SET account_code = '45200' WHERE account_code = '502';
UPDATE classification_patterns SET account_code = '80200' WHERE account_code = '511';
UPDATE classification_patterns SET account_code = '82700' WHERE account_code = '527';
UPDATE classification_patterns SET account_code = '80900' WHERE account_code = '531';
