-- 결재선에 금액 기준 필드 추가 (Phase 2: 금액별 결재선 자동 라우팅)
-- 이 금액(원) 이상일 때 해당 결재선이 적용됨. 0 = 기본(금액 무관).
-- 기존 결재선은 amount_threshold=0 → 동작 변화 없음.

ALTER TABLE approval_lines
  ADD COLUMN amount_threshold INT NOT NULL DEFAULT 0
  COMMENT '이 금액(원) 이상일 때 이 결재선 적용. 0=기본(금액 무관)'
  AFTER line_data;
