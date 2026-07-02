-- 결산 준비 현황 모듈 스키마
-- 2026-04-14

CREATE TABLE IF NOT EXISTS closing_checklist (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fiscal_year INT NOT NULL COMMENT '귀속연도',
  item_key VARCHAR(50) NOT NULL COMMENT '항목 키 (insurance_paid, fixed_assets 등)',
  is_checked TINYINT(1) DEFAULT 0 COMMENT '체크 여부',
  checked_by VARCHAR(50) NULL COMMENT '체크한 사람',
  checked_at DATETIME NULL COMMENT '체크 일시',
  note TEXT NULL COMMENT '메모',
  UNIQUE KEY uq_fy_item (fiscal_year, item_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='결산 수동 체크리스트';

CREATE TABLE IF NOT EXISTS closing_attachments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fiscal_year INT NOT NULL COMMENT '귀속연도',
  file_name VARCHAR(255) NOT NULL COMMENT '원본 파일명',
  file_path VARCHAR(500) NOT NULL COMMENT '저장 경로',
  file_size INT DEFAULT 0 COMMENT '파일 크기 (bytes)',
  uploaded_by VARCHAR(50) NULL COMMENT '업로드한 사람',
  uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '업로드 일시'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='결산 첨부파일';
