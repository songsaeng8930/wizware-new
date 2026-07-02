-- ============================================
-- 근무유형 공통코드 확장: 야근/휴일근무 추가
-- 근태 페이지에서 하드코딩돼 있던 값을 공통코드로 통합
-- ============================================

INSERT INTO common_code_items (group_id, code, name, sort_order)
SELECT cg.id, 'OT', '야근', 5
FROM common_code_groups cg
WHERE cg.module = 'attendance' AND cg.name = '근무유형'
AND NOT EXISTS (SELECT 1 FROM common_code_items ci WHERE ci.group_id = cg.id AND ci.code = 'OT');

INSERT INTO common_code_items (group_id, code, name, sort_order)
SELECT cg.id, 'HOL', '휴일근무', 6
FROM common_code_groups cg
WHERE cg.module = 'attendance' AND cg.name = '근무유형'
AND NOT EXISTS (SELECT 1 FROM common_code_items ci WHERE ci.group_id = cg.id AND ci.code = 'HOL');
