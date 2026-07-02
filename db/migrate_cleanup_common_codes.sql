-- ============================================
-- 공통코드 정리: 직급/직책 그룹 제거
-- hr_ranks/hr_duties 전용 테이블로 이관 완료된 중복 데이터
-- ============================================

-- 1. 직급 그룹(id=1) 아이템 삭제
DELETE FROM common_code_items WHERE group_id = (
    SELECT id FROM common_code_groups WHERE module = 'hr' AND name = '직급'
);

-- 2. 직책 그룹(id=2) 아이템 삭제
DELETE FROM common_code_items WHERE group_id = (
    SELECT id FROM common_code_groups WHERE module = 'hr' AND name = '직책'
);

-- 3. 그룹 자체 삭제
DELETE FROM common_code_groups WHERE module = 'hr' AND name IN ('직급', '직책');
