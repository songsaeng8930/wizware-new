-- ================================================================
-- 결재양식 본문 템플릿 일괄 등록 (12종)
-- 재실행 가능 (기존 content_template 덮어쓰기)
-- 실행:
--   mysql -u root --default-character-set=utf8 zaemit_groupware < db/seed_approval_forms_content.sql
-- ================================================================
SET NAMES utf8mb4;

-- ─────────────────────────────────────
-- [11] 품의서
-- ─────────────────────────────────────
UPDATE approval_forms SET content_template =
'<p style="margin:0 0 12px 0;font-size:14px;font-weight:600;">■ 품의 내역</p>
<table style="width:100%;border-collapse:collapse;font-size:13px;">
<tbody>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;">제&nbsp;&nbsp;&nbsp;&nbsp;목</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">목&nbsp;&nbsp;&nbsp;&nbsp;적</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">시행일자</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">소요예산</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;">세부내용</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br><br><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">참고사항</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
</tbody>
</table>
<p style="margin:16px 0 0 0;">위와 같이 품의하오니 재가하여 주시기 바랍니다.</p>'
WHERE id = 11;

-- ─────────────────────────────────────
-- [7] 휴가신청서
-- ─────────────────────────────────────
UPDATE approval_forms SET content_template =
'<p style="margin:0 0 12px 0;font-size:14px;font-weight:600;">■ 휴가 신청 내역</p>
<table style="width:100%;border-collapse:collapse;font-size:13px;">
<tbody>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;">신 청 자</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">소속부서</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">휴가종류</th><td style="border:1px solid #d1d5db;padding:10px 12px;">□ 연차&nbsp;&nbsp;&nbsp;□ 반차(오전/오후)&nbsp;&nbsp;&nbsp;□ 병가&nbsp;&nbsp;&nbsp;□ 경조사&nbsp;&nbsp;&nbsp;□ 기타(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">휴가기간</th><td style="border:1px solid #d1d5db;padding:10px 12px;">&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일 ~&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일 (총&nbsp;&nbsp;&nbsp;&nbsp;일)</td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">비상연락처</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;">휴가사유</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">업무인수자</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
</tbody>
</table>
<p style="margin:16px 0 0 0;">상기와 같이 휴가를 신청하오니 승인하여 주시기 바랍니다.</p>'
WHERE id = 7;

-- ─────────────────────────────────────
-- [8] 출장신청서
-- ─────────────────────────────────────
UPDATE approval_forms SET content_template =
'<p style="margin:0 0 12px 0;font-size:14px;font-weight:600;">■ 출장 신청 내역</p>
<table style="width:100%;border-collapse:collapse;font-size:13px;">
<tbody>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;">출 장 자</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">소속부서</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">출 장 지</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">출장기간</th><td style="border:1px solid #d1d5db;padding:10px 12px;">&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일 ~&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일 (&nbsp;&nbsp;박&nbsp;&nbsp;일)</td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">동 행 자</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;">출장목적</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;">세부업무</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br><br><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">교 통 편</th><td style="border:1px solid #d1d5db;padding:10px 12px;">□ 항공&nbsp;&nbsp;&nbsp;□ KTX/기차&nbsp;&nbsp;&nbsp;□ 버스&nbsp;&nbsp;&nbsp;□ 자차&nbsp;&nbsp;&nbsp;□ 기타</td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">예상경비</th><td style="border:1px solid #d1d5db;padding:10px 12px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;원 (교통비:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;, 숙박비:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;, 식대:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;, 기타:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</td></tr>
</tbody>
</table>
<p style="margin:16px 0 0 0;">상기와 같이 출장을 신청하오니 승인하여 주시기 바랍니다.</p>'
WHERE id = 8;

-- ─────────────────────────────────────
-- [9] 외근신청서
-- ─────────────────────────────────────
UPDATE approval_forms SET content_template =
'<p style="margin:0 0 12px 0;font-size:14px;font-weight:600;">■ 외근 신청 내역</p>
<table style="width:100%;border-collapse:collapse;font-size:13px;">
<tbody>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;">외 근 자</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">소속부서</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">외근일자</th><td style="border:1px solid #d1d5db;padding:10px 12px;">&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일</td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">외근시간</th><td style="border:1px solid #d1d5db;padding:10px 12px;">출발&nbsp;&nbsp;&nbsp;&nbsp;시&nbsp;&nbsp;&nbsp;&nbsp;분 ~ 복귀&nbsp;&nbsp;&nbsp;&nbsp;시&nbsp;&nbsp;&nbsp;&nbsp;분</td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">방 문 처</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;">외근목적</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">연 락 처</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">복귀여부</th><td style="border:1px solid #d1d5db;padding:10px 12px;">□ 사무실 복귀&nbsp;&nbsp;&nbsp;□ 직접 퇴근</td></tr>
</tbody>
</table>
<p style="margin:16px 0 0 0;">상기와 같이 외근을 신청하오니 승인하여 주시기 바랍니다.</p>'
WHERE id = 9;

-- ─────────────────────────────────────
-- [10] 야근신청서
-- ─────────────────────────────────────
UPDATE approval_forms SET content_template =
'<p style="margin:0 0 12px 0;font-size:14px;font-weight:600;">■ 야근 신청 내역</p>
<table style="width:100%;border-collapse:collapse;font-size:13px;">
<tbody>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;">신 청 자</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">소속부서</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">야근일자</th><td style="border:1px solid #d1d5db;padding:10px 12px;">&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일</td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">야근시간</th><td style="border:1px solid #d1d5db;padding:10px 12px;">&nbsp;&nbsp;&nbsp;&nbsp;시&nbsp;&nbsp;&nbsp;&nbsp;분 ~&nbsp;&nbsp;&nbsp;&nbsp;시&nbsp;&nbsp;&nbsp;&nbsp;분 (총&nbsp;&nbsp;&nbsp;&nbsp;시간)</td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;">야근사유</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;">업무내용</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br><br><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">식대/교통비</th><td style="border:1px solid #d1d5db;padding:10px 12px;">□ 신청&nbsp;&nbsp;&nbsp;□ 미신청&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;금액:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;원</td></tr>
</tbody>
</table>
<p style="margin:16px 0 0 0;">상기와 같이 야근을 신청하오니 승인하여 주시기 바랍니다.</p>'
WHERE id = 10;

-- ─────────────────────────────────────
-- [1] 법인카드 지출
-- ─────────────────────────────────────
UPDATE approval_forms SET content_template =
'<p style="margin:0 0 12px 0;font-size:14px;font-weight:600;">■ 법인카드 사용 내역</p>
<table style="width:100%;border-collapse:collapse;font-size:13px;">
<tbody>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;">사 용 자</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">소속부서</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">카드번호</th><td style="border:1px solid #d1d5db;padding:10px 12px;">****-****-****-____</td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">사용일자</th><td style="border:1px solid #d1d5db;padding:10px 12px;">&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일</td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">가 맹 점</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">사용금액</th><td style="border:1px solid #d1d5db;padding:10px 12px;">₩&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;원</td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">사용계정</th><td style="border:1px solid #d1d5db;padding:10px 12px;">□ 접대비&nbsp;&nbsp;&nbsp;□ 회의비&nbsp;&nbsp;&nbsp;□ 복리후생비&nbsp;&nbsp;&nbsp;□ 소모품비&nbsp;&nbsp;&nbsp;□ 기타</td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">참 석 자</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;">사용목적</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br><br></td></tr>
</tbody>
</table>
<p style="margin:16px 0 0 0;">※ 영수증(매출전표)을 반드시 첨부해 주시기 바랍니다.</p>'
WHERE id = 1;

-- ─────────────────────────────────────
-- [12] 경비청구서
-- ─────────────────────────────────────
UPDATE approval_forms SET content_template =
'<p style="margin:0 0 12px 0;font-size:14px;font-weight:600;">■ 경비 청구 내역</p>
<table style="width:100%;border-collapse:collapse;font-size:13px;">
<tbody>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;">청 구 자</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">소속부서</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">청구일자</th><td style="border:1px solid #d1d5db;padding:10px 12px;">&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일</td></tr>
</tbody>
</table>
<p style="margin:16px 0 8px 0;font-size:13px;font-weight:600;">■ 사용 내역</p>
<table style="width:100%;border-collapse:collapse;font-size:13px;">
<thead>
<tr style="background:#f3f4f6;">
<th style="border:1px solid #d1d5db;padding:8px;width:100px;">사용일자</th>
<th style="border:1px solid #d1d5db;padding:8px;">사용처</th>
<th style="border:1px solid #d1d5db;padding:8px;">내역</th>
<th style="border:1px solid #d1d5db;padding:8px;width:120px;">금액</th>
</tr>
</thead>
<tbody>
<tr><td style="border:1px solid #d1d5db;padding:8px;"><br></td><td style="border:1px solid #d1d5db;padding:8px;"><br></td><td style="border:1px solid #d1d5db;padding:8px;"><br></td><td style="border:1px solid #d1d5db;padding:8px;text-align:right;">원</td></tr>
<tr><td style="border:1px solid #d1d5db;padding:8px;"><br></td><td style="border:1px solid #d1d5db;padding:8px;"><br></td><td style="border:1px solid #d1d5db;padding:8px;"><br></td><td style="border:1px solid #d1d5db;padding:8px;text-align:right;">원</td></tr>
<tr><td style="border:1px solid #d1d5db;padding:8px;"><br></td><td style="border:1px solid #d1d5db;padding:8px;"><br></td><td style="border:1px solid #d1d5db;padding:8px;"><br></td><td style="border:1px solid #d1d5db;padding:8px;text-align:right;">원</td></tr>
<tr><td colspan="3" style="border:1px solid #d1d5db;padding:8px;text-align:right;background:#f9fafb;font-weight:600;">합&nbsp;&nbsp;&nbsp;&nbsp;계</td><td style="border:1px solid #d1d5db;padding:8px;text-align:right;background:#f9fafb;font-weight:600;">원</td></tr>
</tbody>
</table>
<p style="margin:16px 0 8px 0;font-size:13px;font-weight:600;">■ 입금 계좌</p>
<table style="width:100%;border-collapse:collapse;font-size:13px;">
<tbody>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;">은&nbsp;&nbsp;&nbsp;&nbsp;행</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">계좌번호</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">예 금 주</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
</tbody>
</table>
<p style="margin:16px 0 0 0;">※ 증빙서류(영수증 등)를 반드시 첨부해 주시기 바랍니다.</p>'
WHERE id = 12;

-- ─────────────────────────────────────
-- [6] 휴일근무
-- ─────────────────────────────────────
UPDATE approval_forms SET content_template =
'<p style="margin:0 0 12px 0;font-size:14px;font-weight:600;">■ 휴일근무 신청 내역</p>
<table style="width:100%;border-collapse:collapse;font-size:13px;">
<tbody>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;">근 무 자</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">소속부서</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">근무일자</th><td style="border:1px solid #d1d5db;padding:10px 12px;">&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일 (&nbsp;&nbsp;요일)</td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">근무시간</th><td style="border:1px solid #d1d5db;padding:10px 12px;">&nbsp;&nbsp;&nbsp;&nbsp;시&nbsp;&nbsp;&nbsp;&nbsp;분 ~&nbsp;&nbsp;&nbsp;&nbsp;시&nbsp;&nbsp;&nbsp;&nbsp;분 (총&nbsp;&nbsp;&nbsp;&nbsp;시간)</td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">근무구분</th><td style="border:1px solid #d1d5db;padding:10px 12px;">□ 토요일&nbsp;&nbsp;&nbsp;□ 일요일&nbsp;&nbsp;&nbsp;□ 법정공휴일&nbsp;&nbsp;&nbsp;□ 기타</td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;">근무사유</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;">업무내용</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">보&nbsp;&nbsp;&nbsp;&nbsp;상</th><td style="border:1px solid #d1d5db;padding:10px 12px;">□ 대체휴무&nbsp;&nbsp;&nbsp;□ 휴일수당&nbsp;&nbsp;&nbsp;□ 기타</td></tr>
</tbody>
</table>
<p style="margin:16px 0 0 0;">상기와 같이 휴일근무를 신청하오니 승인하여 주시기 바랍니다.</p>'
WHERE id = 6;

-- ─────────────────────────────────────
-- [5] 기안취소
-- ─────────────────────────────────────
UPDATE approval_forms SET content_template =
'<p style="margin:0 0 12px 0;font-size:14px;font-weight:600;">■ 기안 취소 내역</p>
<table style="width:100%;border-collapse:collapse;font-size:13px;">
<tbody>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:140px;">원기안 문서번호</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">원기안 제목</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">원기안 일자</th><td style="border:1px solid #d1d5db;padding:10px 12px;">&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일</td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">원기안 기안자</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">진행상태</th><td style="border:1px solid #d1d5db;padding:10px 12px;">□ 결재 진행중&nbsp;&nbsp;&nbsp;□ 결재 완료&nbsp;&nbsp;&nbsp;□ 시행 전</td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;">취소사유</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br><br><br></td></tr>
</tbody>
</table>
<p style="margin:16px 0 0 0;">상기 기안에 대해 아래의 사유로 취소하고자 하오니 재가하여 주시기 바랍니다.</p>'
WHERE id = 5;

-- ─────────────────────────────────────
-- [4] 발의품의서
-- ─────────────────────────────────────
UPDATE approval_forms SET content_template =
'<p style="margin:0 0 12px 0;font-size:14px;font-weight:600;">■ 발의 내역</p>
<table style="width:100%;border-collapse:collapse;font-size:13px;">
<tbody>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;">제&nbsp;&nbsp;&nbsp;&nbsp;목</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">발 의 자</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;">발의배경</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;">제안내용</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br><br><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;">기대효과</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">소요예산</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">추진일정</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">참고자료</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
</tbody>
</table>
<p style="margin:16px 0 0 0;">위와 같이 발의하오니 검토 후 재가하여 주시기 바랍니다.</p>'
WHERE id = 4;

-- ─────────────────────────────────────
-- [3] 지급품의서 (비용지급품의)
-- ─────────────────────────────────────
UPDATE approval_forms SET content_template =
'<p style="margin:0 0 12px 0;font-size:14px;font-weight:600;">■ 비용 지급 내역</p>
<table style="width:100%;border-collapse:collapse;font-size:13px;">
<tbody>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;">지급대상</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">지급항목</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">지급금액</th><td style="border:1px solid #d1d5db;padding:10px 12px;">₩&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;원 (부가세 포함/별도)</td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">지급일자</th><td style="border:1px solid #d1d5db;padding:10px 12px;">&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일</td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">지급방법</th><td style="border:1px solid #d1d5db;padding:10px 12px;">□ 계좌이체&nbsp;&nbsp;&nbsp;□ 법인카드&nbsp;&nbsp;&nbsp;□ 현금&nbsp;&nbsp;&nbsp;□ 어음/수표</td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">사용계정</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;">지급사유</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">증빙서류</th><td style="border:1px solid #d1d5db;padding:10px 12px;">□ 세금계산서&nbsp;&nbsp;&nbsp;□ 계산서&nbsp;&nbsp;&nbsp;□ 현금영수증&nbsp;&nbsp;&nbsp;□ 카드전표&nbsp;&nbsp;&nbsp;□ 기타</td></tr>
</tbody>
</table>
<p style="margin:16px 0 0 0;">상기와 같이 비용 지급을 품의하오니 재가하여 주시기 바랍니다.</p>'
WHERE id = 3;

-- ─────────────────────────────────────
-- [2] 변경품의서 (원가변경품의)
-- ─────────────────────────────────────
UPDATE approval_forms SET content_template =
'<p style="margin:0 0 12px 0;font-size:14px;font-weight:600;">■ 원가 변경 내역</p>
<table style="width:100%;border-collapse:collapse;font-size:13px;">
<tbody>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:140px;">대상 품목/프로젝트</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;">변경일자</th><td style="border:1px solid #d1d5db;padding:10px 12px;">&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일</td></tr>
</tbody>
</table>
<p style="margin:16px 0 8px 0;font-size:13px;font-weight:600;">■ 변경 전/후 비교</p>
<table style="width:100%;border-collapse:collapse;font-size:13px;">
<thead>
<tr style="background:#f3f4f6;">
<th style="border:1px solid #d1d5db;padding:8px;">구&nbsp;&nbsp;&nbsp;&nbsp;분</th>
<th style="border:1px solid #d1d5db;padding:8px;">변경 전</th>
<th style="border:1px solid #d1d5db;padding:8px;">변경 후</th>
<th style="border:1px solid #d1d5db;padding:8px;">차&nbsp;&nbsp;&nbsp;&nbsp;이</th>
</tr>
</thead>
<tbody>
<tr><td style="border:1px solid #d1d5db;padding:8px;background:#f9fafb;font-weight:600;">금&nbsp;&nbsp;&nbsp;&nbsp;액</td><td style="border:1px solid #d1d5db;padding:8px;text-align:right;">원</td><td style="border:1px solid #d1d5db;padding:8px;text-align:right;">원</td><td style="border:1px solid #d1d5db;padding:8px;text-align:right;">원</td></tr>
<tr><td style="border:1px solid #d1d5db;padding:8px;background:#f9fafb;font-weight:600;">수&nbsp;&nbsp;&nbsp;&nbsp;량</td><td style="border:1px solid #d1d5db;padding:8px;text-align:right;"><br></td><td style="border:1px solid #d1d5db;padding:8px;text-align:right;"><br></td><td style="border:1px solid #d1d5db;padding:8px;text-align:right;"><br></td></tr>
<tr><td style="border:1px solid #d1d5db;padding:8px;background:#f9fafb;font-weight:600;">기&nbsp;&nbsp;&nbsp;&nbsp;타</td><td style="border:1px solid #d1d5db;padding:8px;"><br></td><td style="border:1px solid #d1d5db;padding:8px;"><br></td><td style="border:1px solid #d1d5db;padding:8px;"><br></td></tr>
</tbody>
</table>
<p style="margin:16px 0 8px 0;font-size:13px;font-weight:600;">■ 변경 사유 및 영향</p>
<table style="width:100%;border-collapse:collapse;font-size:13px;">
<tbody>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;vertical-align:top;">변경사유</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br><br></td></tr>
<tr><th style="background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;">영 향 도</th><td style="border:1px solid #d1d5db;padding:10px 12px;"><br></td></tr>
</tbody>
</table>
<p style="margin:16px 0 0 0;">상기와 같이 원가 변경을 품의하오니 재가하여 주시기 바랍니다.</p>'
WHERE id = 2;

-- ================================================================
-- 완료: 12개 양식 content_template 등록
-- ================================================================
