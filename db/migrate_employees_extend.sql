-- Zaemit 그룹웨어 - 직원 테이블 확장 (인사관리 체계 개선)
-- MySQL 9.6 호환
-- 실행: mysql -u root zaemit_groupware < db/migrate_employees_extend.sql

-- 사번
ALTER TABLE employees
    ADD COLUMN IF NOT EXISTS employee_no VARCHAR(20) NULL COMMENT '사번' AFTER id;

-- 개인정보
ALTER TABLE employees
    ADD COLUMN IF NOT EXISTS birth_date DATE NULL COMMENT '생년월일' AFTER phone;

ALTER TABLE employees
    ADD COLUMN IF NOT EXISTS gender CHAR(1) NULL COMMENT '성별 (M/F)' AFTER birth_date;

-- 주소
ALTER TABLE employees
    ADD COLUMN IF NOT EXISTS zipcode VARCHAR(10) NULL COMMENT '우편번호' AFTER gender;

ALTER TABLE employees
    ADD COLUMN IF NOT EXISTS address1 VARCHAR(200) NULL COMMENT '기본주소' AFTER zipcode;

ALTER TABLE employees
    ADD COLUMN IF NOT EXISTS address2 VARCHAR(200) NULL COMMENT '상세주소' AFTER address1;

-- 퇴사일
ALTER TABLE employees
    ADD COLUMN IF NOT EXISTS resign_date DATE NULL COMMENT '퇴사일' AFTER hire_date;

-- 메모
ALTER TABLE employees
    ADD COLUMN IF NOT EXISTS memo TEXT NULL COMMENT '메모' AFTER employment_status;

-- 사번 유니크 인덱스 (NULL 허용이므로 중복 NULL은 OK)
ALTER TABLE employees
    ADD UNIQUE INDEX IF NOT EXISTS idx_employee_no (employee_no);

-- 기존 샘플 직원에 사번 부여
UPDATE employees SET employee_no = CONCAT(YEAR(COALESCE(hire_date, created_at)), '-', LPAD(id, 3, '0'))
WHERE employee_no IS NULL AND is_active = 1;

-- 샘플 직원 개인정보 시드 (birth_date, gender, address)
UPDATE employees SET birth_date='1975-03-15', gender='M', zipcode='06236', address1='서울시 강남구 테헤란로 427', address2='위워크 10층' WHERE id=1 AND birth_date IS NULL;
UPDATE employees SET birth_date='1978-07-22', gender='M', zipcode='06140', address1='서울시 강남구 논현로 508', address2='GS타워 13층' WHERE id=2 AND birth_date IS NULL;
UPDATE employees SET birth_date='1985-11-03', gender='M', zipcode='13529', address1='경기도 성남시 분당구 판교역로 235', address2='에이치스퀘어 N동 8층' WHERE id=3 AND birth_date IS NULL;
UPDATE employees SET birth_date='1982-04-18', gender='M', zipcode='04538', address1='서울시 중구 세종대로 110', address2='서울시청 별관 5층' WHERE id=4 AND birth_date IS NULL;
UPDATE employees SET birth_date='1990-09-12', gender='F', zipcode='06194', address1='서울시 강남구 선릉로 525', address2='삼성2동 현대아파트 103동 702호' WHERE id=5 AND birth_date IS NULL;
UPDATE employees SET birth_date='1988-02-28', gender='F', zipcode='04104', address1='서울시 마포구 월드컵북로 396', address2='상암 누리꿈스퀘어 7층' WHERE id=6 AND birth_date IS NULL;
UPDATE employees SET birth_date='1980-06-05', gender='M', zipcode='07236', address1='서울시 영등포구 의사당대로 1', address2='여의도 파크원 1401호' WHERE id=7 AND birth_date IS NULL;
UPDATE employees SET birth_date='1992-01-14', gender='M', zipcode='13494', address1='경기도 성남시 분당구 대왕판교로 660', address2='유스페이스 A동 12층' WHERE id=8 AND birth_date IS NULL;
UPDATE employees SET birth_date='1993-08-30', gender='M', zipcode='16514', address1='경기도 수원시 영통구 광교중앙로 170', address2='광교 엘포레 209동 1503호' WHERE id=9 AND birth_date IS NULL;
UPDATE employees SET birth_date='1991-12-07', gender='F', zipcode='06035', address1='서울시 강남구 가로수길 53', address2='신사동 한진타운 302호' WHERE id=10 AND birth_date IS NULL;
UPDATE employees SET birth_date='1987-05-25', gender='M', zipcode='05510', address1='서울시 송파구 올림픽로 300', address2='잠실엘스 103동 1204호' WHERE id=11 AND birth_date IS NULL;
UPDATE employees SET birth_date='1989-10-19', gender='F', zipcode='03925', address1='서울시 마포구 양화로 45', address2='메세나폴리스 1012호' WHERE id=12 AND birth_date IS NULL;
UPDATE employees SET birth_date='1994-03-08', gender='F', zipcode='06581', address1='서울시 서초구 반포대로 45', address2='래미안 퍼스티지 502동 1801호' WHERE id=13 AND birth_date IS NULL;
UPDATE employees SET birth_date='1992-07-16', gender='F', zipcode='04378', address1='서울시 용산구 이태원로 200', address2='한남더힐 A동 901호' WHERE id=14 AND birth_date IS NULL;
UPDATE employees SET birth_date='1995-11-21', gender='M', zipcode='13561', address1='경기도 성남시 분당구 정자일로 95', address2='네이버 그린팩토리 옆 오피스텔 801호' WHERE id=15 AND birth_date IS NULL;
UPDATE employees SET birth_date='1996-04-09', gender='M', zipcode='16942', address1='경기도 용인시 기흥구 보정동 1189', address2='죽전역 자이 아파트 305동 1202호' WHERE id=16 AND birth_date IS NULL;
UPDATE employees SET birth_date='1993-06-27', gender='M', zipcode='12925', address1='경기도 하남시 미사강변중앙로 190', address2='미사역 파라곤 112동 403호' WHERE id=17 AND birth_date IS NULL;
UPDATE employees SET birth_date='1997-02-14', gender='M', zipcode='05345', address1='서울시 강동구 천호대로 1077', address2='래미안 솔베뉴 305동 801호' WHERE id=18 AND birth_date IS NULL;
UPDATE employees SET birth_date='1994-09-03', gender='F', zipcode='08378', address1='서울시 구로구 디지털로 300', address2='G밸리 비즈타워 902호' WHERE id=19 AND birth_date IS NULL;
UPDATE employees SET birth_date='1991-01-30', gender='M', zipcode='07299', address1='서울시 영등포구 여의대방로65길 20', address2='신길뉴타운 e편한세상 108동 1503호' WHERE id=20 AND birth_date IS NULL;
