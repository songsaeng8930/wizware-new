-- Zaemit 그룹웨어 · attendance_records 에 출근 업무계획 / 퇴근 특이사항 컬럼 추가
-- 실행: mysql -u root zaemit_groupware < db/migrate_attendance_plan_note.sql
--
-- 배경: 출근 버튼 클릭 시 "오늘의 업무", 퇴근 버튼 클릭 시 "특이사항"을 모달로 받아
--       attendance_records 에 저장하도록 UX 가 변경됨 (2026-04-23).
--       멱등 마이그레이션 · 컬럼이 이미 있으면 건너뛴다.

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'attendance_records' AND COLUMN_NAME = 'work_plan');
SET @sql := IF(@c = 0,
    "ALTER TABLE attendance_records ADD COLUMN work_plan TEXT NULL COMMENT '출근 시 작성한 오늘의 업무 계획' AFTER clock_in",
    'SELECT "attendance_records.work_plan 이미 존재" AS note'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'attendance_records' AND COLUMN_NAME = 'leave_note');
SET @sql := IF(@c = 0,
    "ALTER TABLE attendance_records ADD COLUMN leave_note TEXT NULL COMMENT '퇴근 시 작성한 특이사항 메모' AFTER clock_out",
    'SELECT "attendance_records.leave_note 이미 존재" AS note'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
