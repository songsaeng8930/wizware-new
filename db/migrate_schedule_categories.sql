-- ============================================
-- 일정 카테고리 개선: 중복 제거 + 실무 항목 추가
-- ============================================

-- 1. 기존 '미팅'(MEET) → '외부미팅'(EXT)으로 변경
UPDATE common_code_items
SET code = 'EXT', name = '외부미팅'
WHERE group_id = (
    SELECT id FROM common_code_groups
    WHERE module = 'schedule' AND name = '일정유형'
)
AND code = 'MEET';

-- 2. 신규 카테고리 추가 (외근, 면담, 행사, 마감)
INSERT INTO common_code_items (group_id, code, name, sort_order)
SELECT g.id, v.code, v.name, v.sort_order
FROM common_code_groups g
CROSS JOIN (
    SELECT 'OUT'  AS code, '외근' AS name, 6 AS sort_order UNION ALL
    SELECT 'INTV',         '면담',         7              UNION ALL
    SELECT 'EVT',          '행사',         8              UNION ALL
    SELECT 'DUE',          '마감',         9
) v
WHERE g.module = 'schedule' AND g.name = '일정유형'
AND NOT EXISTS (
    SELECT 1 FROM common_code_items ci
    WHERE ci.group_id = g.id AND ci.code = v.code
);

-- 3. 신규 항목 색상 매핑 (item_id는 서브쿼리로 동적 참조)
INSERT IGNORE INTO schedule_category_config (item_id, color_code)
SELECT ci.id, CASE ci.code
    WHEN 'OUT'  THEN 'orange'
    WHEN 'INTV' THEN 'teal'
    WHEN 'EVT'  THEN 'pink'
    WHEN 'DUE'  THEN 'gray'
END
FROM common_code_items ci
JOIN common_code_groups cg ON ci.group_id = cg.id
WHERE cg.module = 'schedule' AND cg.name = '일정유형'
AND ci.code IN ('OUT', 'INTV', 'EVT', 'DUE');
