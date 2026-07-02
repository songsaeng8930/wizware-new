-- 프로필 섹션 필드 확장 (2026-06-21)
-- 경쟁사 조사 기반: 조건부 필드, 그룹웨어 표준 항목 추가

-- 경력: 직무, 고용형태, 퇴직사유
ALTER TABLE employee_careers
  ADD COLUMN job_type VARCHAR(50) NULL AFTER position,
  ADD COLUMN employment_type VARCHAR(20) NULL DEFAULT '정규직' AFTER job_type,
  ADD COLUMN leave_reason VARCHAR(200) NULL AFTER is_current;

-- 학력: 부전공/복수전공
ALTER TABLE employee_educations
  ADD COLUMN minor VARCHAR(100) NULL AFTER major;

-- 자격증: 등급/급수
ALTER TABLE employee_certifications
  ADD COLUMN cert_grade VARCHAR(50) NULL AFTER cert_number;

-- 어학: 유효기간
ALTER TABLE employee_languages
  ADD COLUMN validity_years VARCHAR(20) NULL AFTER test_date;

-- 가족: 건보 피부양자
ALTER TABLE employee_families
  ADD COLUMN is_health_dependent TINYINT(1) DEFAULT 0 AFTER is_dependent;

-- 수상/징계: 징계 단계, 후속 조치일
ALTER TABLE employee_awards
  ADD COLUMN discipline_level VARCHAR(20) NULL AFTER type,
  ADD COLUMN follow_up_date DATE NULL AFTER awarded_date;

-- 병역: 병과, 전역구분
ALTER TABLE employee_military
  ADD COLUMN branch_specialty VARCHAR(50) NULL AFTER branch,
  ADD COLUMN discharge_type VARCHAR(20) NULL AFTER discharge_date;
