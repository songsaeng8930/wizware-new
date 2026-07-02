SET NAMES utf8mb4;

DELETE FROM approval_history;
DELETE FROM approval_documents;

INSERT INTO approval_documents (doc_number, title, content, doc_type, drafter_name, drafter_dept, status, draft_date, complete_date) VALUES

-- 대기 1: 휴가신청서
('Zaemit_개발1_휴가_20260320100000', '연차 사용 신청 (3/28~3/29)',
'<table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;width:120px;font-weight:600;">휴가 종류</td><td style="padding:8px 12px;border:1px solid #d1d5db;">연차</td><td style="padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;width:120px;font-weight:600;">잔여 연차</td><td style="padding:8px 12px;border:1px solid #d1d5db;">12일</td></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600;">시작일</td><td style="padding:8px 12px;border:1px solid #d1d5db;">2026-03-28 (토)</td><td style="padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600;">종료일</td><td style="padding:8px 12px;border:1px solid #d1d5db;">2026-03-29 (일)</td></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600;">사용일수</td><td style="padding:8px 12px;border:1px solid #d1d5db;">2일</td><td style="padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600;">비상연락처</td><td style="padding:8px 12px;border:1px solid #d1d5db;">010-1234-5678</td></tr>
</table>
<p><b>사유:</b> 개인 사유로 인한 연차 사용 신청합니다. 업무 인수인계는 같은 팀 강개발 부장님께 완료하였습니다.</p>',
'휴가신청서', '박프론트', '개발1팀', '대기', '2026-03-20', NULL),

-- 대기 2: 외근신청서
('Zaemit_영업_외근_20260321090000', '거래처 방문 외근 신청',
'<table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;width:120px;font-weight:600;">외근일</td><td style="padding:8px 12px;border:1px solid #d1d5db;">2026-03-25 (수)</td></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600;">외근 시간</td><td style="padding:8px 12px;border:1px solid #d1d5db;">14:00 ~ 18:00</td></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600;">외근지</td><td style="padding:8px 12px;border:1px solid #d1d5db;">서울특별시 강남구 역삼동 823-7 (주)그린테크 본사</td></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600;">목적</td><td style="padding:8px 12px;border:1px solid #d1d5db;">신규 거래처 미팅 및 서비스 데모 시연</td></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600;">동행인</td><td style="padding:8px 12px;border:1px solid #d1d5db;">최영업 이사</td></tr>
</table>
<p>그린테크 IT팀 이상훈 팀장과 사전 협의된 미팅입니다. Zaemit 그룹웨어 패키지 도입 관련 데모 시연 및 견적 논의 예정입니다.</p>',
'외근신청서', '배영업', '국내영업팀', '대기', '2026-03-21', NULL),

-- 진행 1: 품의서 (서버비용)
('Zaemit_개발1_품의_20260312110000', 'NHN클라우드 2026년 02월 서버 비용',
'<p>NHN클라우드 서버 운영에 따른 2026년 2월분 비용을 아래와 같이 품의합니다.</p>
<table style="width:100%;border-collapse:collapse;margin:16px 0;">
<tr style="background:#f9fafb;"><th style="padding:8px 12px;border:1px solid #d1d5db;text-align:left;">항목</th><th style="padding:8px 12px;border:1px solid #d1d5db;text-align:left;">사양</th><th style="padding:8px 12px;border:1px solid #d1d5db;text-align:right;">금액</th></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;">Server (g3 Standard)</td><td style="padding:8px 12px;border:1px solid #d1d5db;">vCPU 8 / Memory 32GB x 2대</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:right;">1,240,000원</td></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;">Object Storage</td><td style="padding:8px 12px;border:1px solid #d1d5db;">500GB</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:right;">85,000원</td></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;">CDN / Load Balancer</td><td style="padding:8px 12px;border:1px solid #d1d5db;">트래픽 기반</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:right;">320,000원</td></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;">DB (CloudDB for MySQL)</td><td style="padding:8px 12px;border:1px solid #d1d5db;">Standard / 100GB</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:right;">205,000원</td></tr>
<tr style="background:#f0f9ff;font-weight:600;"><td style="padding:8px 12px;border:1px solid #d1d5db;" colspan="2">합계 (VAT 별도)</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:right;">1,850,000원</td></tr>
</table>
<p>전월 대비 동일 수준이며, 서비스 안정 운영을 위해 승인 부탁드립니다.</p>',
'품의서', '강개발', '개발1팀', '진행', '2026-03-12', NULL),

-- 진행 2: 품의서 (사무용품)
('Zaemit_경영_품의_20260310140000', '사무용품 일괄 구매 품의',
'<p>2026년 1분기 사무용품이 소진되어 아래와 같이 일괄 구매를 품의합니다.</p>
<table style="width:100%;border-collapse:collapse;margin:16px 0;">
<tr style="background:#f9fafb;"><th style="padding:8px 12px;border:1px solid #d1d5db;text-align:left;">품목</th><th style="padding:8px 12px;border:1px solid #d1d5db;text-align:center;">수량</th><th style="padding:8px 12px;border:1px solid #d1d5db;text-align:right;">단가</th><th style="padding:8px 12px;border:1px solid #d1d5db;text-align:right;">금액</th></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;">A4 복사용지 (80g)</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:center;">20박스</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:right;">12,000원</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:right;">240,000원</td></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;">레이저 프린터 토너 (HP)</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:center;">3개</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:right;">45,000원</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:right;">135,000원</td></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;">볼펜 / 형광펜 세트</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:center;">30세트</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:right;">3,500원</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:right;">105,000원</td></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;">포스트잇 / 바인더 등</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:center;">일괄</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:right;">-</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:right;">40,000원</td></tr>
<tr style="background:#f0f9ff;font-weight:600;"><td style="padding:8px 12px;border:1px solid #d1d5db;" colspan="3">합계 (VAT 포함)</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:right;">520,000원</td></tr>
</table>
<p>구매처: 오피스디포 온라인몰 (최저가 비교 완료). 납품 예정일: 주문 후 2영업일.</p>',
'품의서', '김경영', '경영지원팀', '진행', '2026-03-10', NULL),

-- 진행 3: 출장신청서
('Zaemit_개발2_출장_20260305090000', '부산 고객사 기술 지원 출장',
'<table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;width:120px;font-weight:600;">출장지</td><td style="padding:8px 12px;border:1px solid #d1d5db;">부산광역시 해운대구 센텀중앙로 48 (주)마린시스템즈</td></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600;">출장 기간</td><td style="padding:8px 12px;border:1px solid #d1d5db;">2026-03-10 (화) ~ 2026-03-12 (목) / 2박 3일</td></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600;">출장 목적</td><td style="padding:8px 12px;border:1px solid #d1d5db;">고객사 시스템 구축 현장 지원 및 기술 교육</td></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600;">동행자</td><td style="padding:8px 12px;border:1px solid #d1d5db;">없음 (단독 출장)</td></tr>
</table>
<p><b>예상 경비:</b></p>
<table style="width:100%;border-collapse:collapse;margin:8px 0 16px;">
<tr style="background:#f9fafb;"><th style="padding:8px 12px;border:1px solid #d1d5db;text-align:left;">항목</th><th style="padding:8px 12px;border:1px solid #d1d5db;text-align:right;">금액</th></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;">교통비 (KTX 왕복)</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:right;">118,600원</td></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;">숙박비 (2박)</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:right;">160,000원</td></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;">식비 (일비 포함)</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:right;">90,000원</td></tr>
<tr style="background:#f0f9ff;font-weight:600;"><td style="padding:8px 12px;border:1px solid #d1d5db;">합계</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:right;">368,600원</td></tr>
</table>
<p>마린시스템즈 IT인프라팀 김해준 차장과 사전 일정 조율 완료. 현장 서버 환경 구성 및 운영자 교육 진행 예정입니다.</p>',
'출장신청서', '조풀스택', '개발2팀', '진행', '2026-03-05', NULL),

-- 진행 4: 품의서 (교육예산)
('Zaemit_인사_품의_20260315100000', '2026년 상반기 직무교육 예산',
'<p>2026년 상반기 직무역량 강화를 위한 교육 예산을 아래와 같이 품의합니다.</p>
<table style="width:100%;border-collapse:collapse;margin:16px 0;">
<tr style="background:#f9fafb;"><th style="padding:8px 12px;border:1px solid #d1d5db;text-align:left;">교육과정</th><th style="padding:8px 12px;border:1px solid #d1d5db;text-align:center;">대상</th><th style="padding:8px 12px;border:1px solid #d1d5db;text-align:center;">인원</th><th style="padding:8px 12px;border:1px solid #d1d5db;text-align:right;">비용</th></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;">정보보안 인식 교육</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:center;">전 직원</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:center;">32명</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:right;">960,000원</td></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;">React / Next.js 심화</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:center;">개발팀</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:center;">8명</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:right;">1,600,000원</td></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;">클라우드 인프라 운영</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:center;">개발팀</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:center;">4명</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:right;">1,200,000원</td></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;">리더십 역량 과정</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:center;">팀장급 이상</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:center;">6명</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:right;">1,240,000원</td></tr>
<tr style="background:#f0f9ff;font-weight:600;"><td style="padding:8px 12px;border:1px solid #d1d5db;" colspan="3">합계</td><td style="padding:8px 12px;border:1px solid #d1d5db;text-align:right;">5,000,000원</td></tr>
</table>
<p>교육 기관: 패스트캠퍼스 B2B / 한국정보보호진흥원. 교육 일정은 4월~6월 중 부서별 협의 후 확정 예정.</p>',
'품의서', '이인사', '인사팀', '진행', '2026-03-15', NULL),

-- 승인완료: 야근신청서
('Zaemit_개발2_야근_20260224140000', '긴급 배포 대응 야근 신청',
'<table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;width:120px;font-weight:600;">야근일</td><td style="padding:8px 12px;border:1px solid #d1d5db;">2026-02-26 (목)</td></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600;">야근 시간</td><td style="padding:8px 12px;border:1px solid #d1d5db;">18:00 ~ 23:00 (5시간)</td></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600;">야근 사유</td><td style="padding:8px 12px;border:1px solid #d1d5db;">고객사 긴급 핫픽스 배포 및 모니터링</td></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600;">업무 내용</td><td style="padding:8px 12px;border:1px solid #d1d5db;">마린시스템즈 결제 모듈 오류 긴급 수정 → 스테이징 테스트 → 프로덕션 배포 → 배포 후 1시간 모니터링</td></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600;">식대 청구</td><td style="padding:8px 12px;border:1px solid #d1d5db;">12,000원 (저녁 식대)</td></tr>
</table>
<p>마린시스템즈 프로덕션 환경에서 결제 실패 이슈가 발생하여 당일 긴급 대응이 필요합니다. 배포 후 안정화 확인까지 야근이 불가피합니다.</p>',
'야근신청서', '조풀스택', '개발2팀', '승인', '2026-02-24', '2026-02-25'),

-- 반려 1: 경비청구서
('Zaemit_영업_경비_20260301150000', '3월 거래처 접대비 경비 청구',
'<p>거래처 미팅 후 접대비를 아래와 같이 청구합니다.</p>
<table style="width:100%;border-collapse:collapse;margin:16px 0;">
<tr style="background:#f9fafb;"><th style="padding:8px 12px;border:1px solid #d1d5db;text-align:left;">항목</th><th style="padding:8px 12px;border:1px solid #d1d5db;text-align:left;">내용</th></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;font-weight:600;">사용일</td><td style="padding:8px 12px;border:1px solid #d1d5db;">2026-02-28 (금)</td></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;font-weight:600;">사용처</td><td style="padding:8px 12px;border:1px solid #d1d5db;">트라토리아 디 마레 (역삼동)</td></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;font-weight:600;">참석자</td><td style="padding:8px 12px;border:1px solid #d1d5db;">당사: 서영업, 최영업 이사 / 거래처: (주)한빛소프트 김정우 부장 외 1명</td></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;font-weight:600;">미팅 목적</td><td style="padding:8px 12px;border:1px solid #d1d5db;">2026년 연간 유지보수 계약 갱신 논의</td></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;font-weight:600;">청구 금액</td><td style="padding:8px 12px;border:1px solid #d1d5db;font-weight:600;color:#1d4ed8;">186,000원</td></tr>
<tr><td style="padding:8px 12px;border:1px solid #d1d5db;font-weight:600;">결제 수단</td><td style="padding:8px 12px;border:1px solid #d1d5db;">법인카드 (서영업)</td></tr>
</table>
<p>영수증 및 법인카드 매출전표를 첨부합니다.</p>',
'경비청구서', '서영업', '국내영업팀', '반려', '2026-03-01', '2026-03-03');

-- 결재이력: 대기 문서 (전원 대기)
-- document_id는 위 INSERT 순서에 따라 LAST_INSERT_ID() 기준으로 결정됨
-- 첫 번째 INSERT 시작 ID를 변수로 잡아서 사용
SET @first_id = LAST_INSERT_ID();

INSERT INTO approval_history (document_id, approver_name, approver_dept, step_order, action, comment, action_date) VALUES
-- 대기 1: 박프론트 휴가 (first_id + 0)
(@first_id + 0, '강개발', '개발1팀', 1, '대기', NULL, NULL),
(@first_id + 0, '박기술', '기술개발본부', 2, '대기', NULL, NULL),
-- 대기 2: 배영업 외근 (first_id + 1)
(@first_id + 1, '서영업', '국내영업팀', 1, '대기', NULL, NULL),
(@first_id + 1, '최영업', '영업본부', 2, '대기', NULL, NULL),
-- 진행 1: 강개발 서버비용 (first_id + 2)
(@first_id + 2, '박기술', '기술개발본부', 1, '승인', '확인했습니다.', '2026-03-13 10:00:00'),
(@first_id + 2, '김대표', '(주)재밋', 2, '대기', NULL, NULL),
-- 진행 2: 김경영 사무용품 (first_id + 3)
(@first_id + 3, '정지원', '경영지원팀', 1, '승인', '승인합니다.', '2026-03-11 14:00:00'),
(@first_id + 3, '오재무', '재무회계팀', 2, '대기', NULL, NULL),
(@first_id + 3, '이본부장', '경영지원본부', 3, '대기', NULL, NULL),
-- 진행 3: 조풀스택 출장 (first_id + 4)
(@first_id + 4, '윤개발', '개발2팀', 1, '승인', '확인.', '2026-03-05 15:00:00'),
(@first_id + 4, '박기술', '기술개발본부', 2, '승인', '승인합니다.', '2026-03-06 09:00:00'),
(@first_id + 4, '김대표', '(주)재밋', 3, '대기', NULL, NULL),
-- 진행 4: 이인사 교육예산 (first_id + 5)
(@first_id + 5, '한인사', '인사팀', 1, '승인', '교육 예산 승인합니다.', '2026-03-16 11:00:00'),
(@first_id + 5, '이본부장', '경영지원본부', 2, '대기', NULL, NULL),
-- 승인완료: 조풀스택 야근 (first_id + 6)
(@first_id + 6, '윤개발', '개발2팀', 1, '승인', '긴급 건이니 승인합니다.', '2026-02-24 16:00:00'),
(@first_id + 6, '박기술', '기술개발본부', 2, '승인', '고생 많습니다. 승인.', '2026-02-25 09:30:00'),
-- 반려: 서영업 접대비 (first_id + 7)
(@first_id + 7, '최영업', '영업본부', 1, '승인', '확인했습니다.', '2026-03-02 10:00:00'),
(@first_id + 7, '오재무', '재무회계팀', 2, '반려', '증빙서류 부족합니다.', '2026-03-03 14:00:00');
