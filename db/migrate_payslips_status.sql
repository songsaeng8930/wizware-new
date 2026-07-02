-- 임금대장 상태 워크플로우 추가
-- status: draft(작성중) → confirmed(확정) → paid(지급완료)

ALTER TABLE payslips
    ADD COLUMN status ENUM('draft','confirmed','paid') NOT NULL DEFAULT 'draft' COMMENT '상태' AFTER net_pay,
    ADD COLUMN confirmed_at DATETIME NULL COMMENT '확정일시' AFTER status,
    ADD COLUMN confirmed_by INT UNSIGNED NULL COMMENT '확정자 ID' AFTER confirmed_at,
    ADD COLUMN paid_at DATETIME NULL COMMENT '지급일시' AFTER confirmed_by,
    ADD COLUMN memo VARCHAR(200) NOT NULL DEFAULT '' COMMENT '비고' AFTER paid_at;

ALTER TABLE payslips ADD UNIQUE INDEX uq_emp_yearmonth (employee_id, year, month);
