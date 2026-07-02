-- 부서게시판: department_id 컬럼 추가
-- 실행: mysql -u root zaemit_groupware < db/migrate_board_department.sql

-- MySQL 8.0+ IF NOT EXISTS 지원
ALTER TABLE board_posts
    ADD COLUMN IF NOT EXISTS department_id INT NULL COMMENT '부서게시판용 departments.id' AFTER author_dept;

CREATE INDEX IF NOT EXISTS idx_board_dept ON board_posts (department_id);

-- 기존 부서게시판 샘플 데이터에 department_id 매핑 (author_dept 기준 best-effort)
UPDATE board_posts bp
  JOIN departments d ON d.name = bp.author_dept
   SET bp.department_id = d.id
 WHERE bp.board_type = 'department'
   AND bp.department_id IS NULL;

-- 기존 하드코딩 카테고리(기획/개발/디자인) 비우기 — 부서게시판은 카테고리 미사용
UPDATE board_posts SET category = '' WHERE board_type = 'department' AND category IN ('기획', '개발', '디자인');
