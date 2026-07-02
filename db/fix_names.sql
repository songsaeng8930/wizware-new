SET NAMES utf8mb4;

-- cards 테이블 이름 수정
UPDATE cards SET manager_name = '최영업' WHERE id = 1;
UPDATE cards SET manager_name = '박기술' WHERE id = 2;
UPDATE cards SET manager_name = '정지원' WHERE id = 3;
UPDATE cards SET manager_name = '김대표' WHERE id = 4;
UPDATE cards SET manager_name = '한인사' WHERE id = 5;

-- card_expenses 테이블 이름 수정
UPDATE card_expenses SET registrant_name = '최영업', user_name = '최영업' WHERE id = 1;
UPDATE card_expenses SET registrant_name = '최영업', user_name = '최영업' WHERE id = 2;
UPDATE card_expenses SET registrant_name = '박기술', user_name = '박기술' WHERE id = 3;
UPDATE card_expenses SET registrant_name = '정지원', user_name = '정지원' WHERE id = 4;
UPDATE card_expenses SET registrant_name = '한인사', user_name = '한인사' WHERE id = 5;
UPDATE card_expenses SET registrant_name = '박기술', user_name = '박기술' WHERE id = 6;
UPDATE card_expenses SET registrant_name = '김대표', user_name = '김대표' WHERE id = 7;
UPDATE card_expenses SET registrant_name = '정지원', user_name = '정지원' WHERE id = 8;
UPDATE card_expenses SET registrant_name = '최영업', user_name = '최영업' WHERE id = 9;
UPDATE card_expenses SET registrant_name = '이본부장', user_name = '이본부장' WHERE id = 10;

-- settlement_updater 수정
UPDATE card_expenses SET settlement_updater = '정지원' WHERE id IN (3, 4, 7);
