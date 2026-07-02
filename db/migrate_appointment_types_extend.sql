-- 인사발령 유형 확장 (파견/전출/전입/휴직/복직)
-- 기존 DB에 한 번만 실행

SET @appt_grp = (SELECT id FROM common_code_groups WHERE module = 'hr' AND name = '발령유형' LIMIT 1);

INSERT IGNORE INTO common_code_items (group_id, code, name, sort_order) VALUES
(@appt_grp, 'DISPATCH',     '파견',   7),
(@appt_grp, 'TRANSFER_OUT', '전출',   8),
(@appt_grp, 'TRANSFER_IN',  '전입',   9),
(@appt_grp, 'LEAVE',        '휴직',  10),
(@appt_grp, 'RETURN',       '복직',  11);

-- appointment_type 컬럼 코멘트 갱신
ALTER TABLE employee_appointments
    MODIFY COLUMN appointment_type VARCHAR(30) NOT NULL
    COMMENT '발령유형 (신규입사/전보/승진/직급변경/직책변경/고용형태변경/파견/전출/전입/휴직/복직/상태변경/복합발령/퇴사)';
