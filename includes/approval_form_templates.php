<?php
/**
 * 실무형 전자결재 양식 카탈로그.
 * - 화면 샘플, 신규 양식 등록, 기본 양식 보강 API가 함께 사용한다.
 * - 외부 공개 그룹웨어/양식 사례(티그리스 기본 12종, 다우오피스 지출품의/지출결의 작성 항목)를 참고해
 *   실제 회사에서 자주 쓰는 결재 흐름 중심으로 구성했다.
 */

function approval_form_var(string $name): string
{
    // 결재양식 템플릿에서는 값 필드 칩을 노출하지 않는다.
    // 사용자에게 의미 없는 파란 칩 대신 표 셀만 빈칸으로 남긴다.
    return '';
}

function approval_form_table_html(string $heading, array $rows, string $closing = '', array $sections = []): string
{
    $html = '<p style="margin:0 0 12px 0;font-size:15px;font-weight:700;color:#111827;">■ ' . htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') . '</p>';
    $html .= '<table style="width:100%;border-collapse:collapse;font-size:13px;color:#111827;">';
    $html .= '<tbody>';
    foreach ($rows as $row) {
        [$label, $value] = $row;
        $html .= '<tr>';
        $html .= '<th style="border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:140px;color:#374151;font-weight:700;">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</th>';
        $html .= '<td style="border:1px solid #d1d5db;padding:10px 12px;min-height:32px;">' . ($value !== '' ? $value : '<br>') . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';

    foreach ($sections as $section) {
        $html .= $section;
    }

    if ($closing !== '') {
        $html .= '<p style="margin:18px 0 0 0;color:#111827;">' . htmlspecialchars($closing, ENT_QUOTES, 'UTF-8') . '</p>';
    }
    return $html;
}

function approval_form_items_table(string $heading, array $headers, int $rows = 3, string $footerLabel = ''): string
{
    $html = '<p style="margin:18px 0 8px 0;font-size:13px;font-weight:700;color:#111827;">■ ' . htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') . '</p>';
    $html .= '<table style="width:100%;border-collapse:collapse;font-size:13px;color:#111827;">';
    $html .= '<thead><tr>';
    foreach ($headers as $header) {
        $html .= '<th style="border:1px solid #d1d5db;padding:8px 10px;text-align:center;color:#374151;font-weight:700;">' . htmlspecialchars($header, ENT_QUOTES, 'UTF-8') . '</th>';
    }
    $html .= '</tr></thead><tbody>';
    for ($i = 0; $i < $rows; $i++) {
        $html .= '<tr>';
        foreach ($headers as $header) {
            $align = str_contains($header, '금액') || str_contains($header, '단가') || str_contains($header, '수량') ? 'right' : 'left';
            $html .= '<td style="border:1px solid #d1d5db;padding:8px 10px;text-align:' . $align . ';"><br></td>';
        }
        $html .= '</tr>';
    }
    if ($footerLabel !== '') {
        $colspan = max(1, count($headers) - 1);
        $html .= '<tr><td colspan="' . $colspan . '" style="border:1px solid #d1d5db;padding:8px 10px;text-align:right;font-weight:700;">' . htmlspecialchars($footerLabel, ENT_QUOTES, 'UTF-8') . '</td>';
        $html .= '<td style="border:1px solid #d1d5db;padding:8px 10px;text-align:right;font-weight:700;">원</td></tr>';
    }
    $html .= '</tbody></table>';
    return $html;
}

function approval_form_note(string $text): string
{
    return '<p style="margin:12px 0 0 0;padding:10px 12px;border:1px solid #e5e7eb;border-radius:8px;background:#f9fafb;color:#4b5563;font-size:13px;">※ ' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</p>';
}

function approval_form_templates(): array
{
    return [
        [
            'id' => 'blank',
            'name' => '빈 양식',
            'category' => '직접작성',
            'icon' => 'file-plus',
            'doc_type' => '',
            'title' => '',
            'desc' => '처음부터 직접 작성',
            'html' => '',
        ],
        [
            'id' => 'internal_approval',
            'name' => '내부결재 / 품의서',
            'category' => '공통',
            'icon' => 'file-text',
            'doc_type' => '품의서',
            'title' => '품의서',
            'desc' => '예산, 정책, 프로젝트 진행 전 사전 승인용',
            'html' => approval_form_table_html('품의 내역', [
                ['기안자', approval_form_var('기안자')],
                ['기안부서', approval_form_var('기안부서')],
                ['제목', ''],
                ['품의 목적', ''],
                ['배경 / 필요성', '<br><br>'],
                ['주요 내용', '<br><br><br>'],
                ['소요 예산', '₩&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;원'],
                ['시행 일정', ''],
                ['기대 효과', '<br><br>'],
                ['첨부 / 참고', '견적서, 제안서, 관련 문서 등'],
            ], '위와 같이 품의드리오니 검토 후 재가하여 주시기 바랍니다.'),
        ],
        [
            'id' => 'business_report',
            'name' => '업무보고서',
            'category' => '공통',
            'icon' => 'clipboard-list',
            'doc_type' => '업무보고서',
            'title' => '업무보고서',
            'desc' => '업무/프로젝트 결과, 진행 상황, 이슈 보고용',
            'html' => approval_form_table_html('업무 보고 내역', [
                ['보고자', approval_form_var('보고자')],
                ['보고부서', approval_form_var('부서')],
                ['보고기간', ''],
                ['보고구분', '□ 정기보고&nbsp;&nbsp;□ 수시보고&nbsp;&nbsp;□ 완료보고&nbsp;&nbsp;□ 이슈보고'],
                ['주요 성과', '<br><br><br>'],
                ['진행 현황', '<br><br>'],
                ['이슈 / 리스크', '<br><br>'],
                ['대응 계획', '<br><br>'],
                ['협조 요청', '<br><br>'],
            ], '상기와 같이 업무 내용을 보고드립니다.'),
        ],
        [
            'id' => 'resignation',
            'name' => '사직서',
            'category' => '인사/근태',
            'icon' => 'log-out',
            'doc_type' => '사직서',
            'title' => '사직서',
            'desc' => '퇴사 의사, 예정일, 인수인계 사항을 공식 기록하는 양식',
            'html' => approval_form_table_html('사직 신청 내역', [
                ['신청자', approval_form_var('신청자')],
                ['소속부서', approval_form_var('부서')],
                ['직위/직책', ''],
                ['입사일자', ''],
                ['퇴사희망일', ''],
                ['사직사유', '<br><br>'],
                ['인수인계 대상자', ''],
                ['인수인계 내용', '<br><br><br>'],
                ['회사 자산 반납', '□ 노트북&nbsp;&nbsp;□ 출입카드&nbsp;&nbsp;□ 법인카드&nbsp;&nbsp;□ 기타'],
            ], '상기와 같이 사직을 신청하오니 검토 후 처리하여 주시기 바랍니다.', [
                approval_form_note('퇴사 처리는 회사 내규와 면담 절차를 우선하며, 최종 퇴사일은 승인 결과에 따라 확정됩니다.'),
            ]),
        ],
        [
            'id' => 'expense_proposal',
            'name' => '지출품의서',
            'category' => '재무',
            'icon' => 'wallet-cards',
            'doc_type' => '지출품의서',
            'title' => '지출품의서',
            'desc' => '지출 전 예산/구매/집행 가능 여부 사전 승인용',
            'html' => approval_form_table_html('지출 품의 내역', [
                ['기안자', approval_form_var('기안자')],
                ['사용부서', approval_form_var('부서')],
                ['지출 목적', ''],
                ['지출 예정일', ''],
                ['예산 계정', ''],
                ['예상 금액', '₩&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;원 (부가세 포함 / 별도)'],
                ['거래처 / 구매처', ''],
                ['산출 근거', '<br><br>'],
                ['첨부 자료', '견적서, 비교견적, 계약서 초안 등'],
            ], '상기 지출 건에 대해 사전 승인을 요청드립니다.', [
                approval_form_items_table('예상 지출 상세', ['품목', '규격/내용', '수량', '단가', '금액'], 4, '예상 합계'),
                approval_form_note('지출품의서는 지출 전 사전 승인, 지출결의서는 지출 후 증빙·회계 처리 근거로 구분해 사용합니다.'),
            ]),
        ],
        [
            'id' => 'expense_resolution',
            'name' => '지출결의서',
            'category' => '재무',
            'icon' => 'receipt-text',
            'doc_type' => '지출결의서',
            'title' => '지출결의서',
            'desc' => '지출 후 증빙과 전표 처리 근거를 남기는 양식',
            'html' => approval_form_table_html('지출 결의 내역', [
                ['작성자', approval_form_var('작성자')],
                ['소속부서', approval_form_var('부서')],
                ['지출일자', ''],
                ['지출구분', '□ 법인카드&nbsp;&nbsp;□ 계좌이체&nbsp;&nbsp;□ 현금&nbsp;&nbsp;□ 기타'],
                ['계정과목', ''],
                ['거래처', ''],
                ['총 지출금액', '₩&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;원'],
                ['증빙자료', '□ 영수증&nbsp;&nbsp;□ 세금계산서&nbsp;&nbsp;□ 카드전표&nbsp;&nbsp;□ 거래명세서&nbsp;&nbsp;□ 기타'],
            ], '상기 지출에 대하여 결의하오니 승인하여 주시기 바랍니다.', [
                approval_form_items_table('지출 상세 내역', ['일자', '거래처', '계정과목', '사용 내역', '금액'], 4, '합계'),
            ]),
        ],
        [
            'id' => 'purchase_request',
            'name' => '구매요청서',
            'category' => '재무',
            'icon' => 'shopping-cart',
            'doc_type' => '구매요청서',
            'title' => '구매요청서',
            'desc' => '물품/서비스 구매 전 필요성, 예산, 납기 검토용',
            'html' => approval_form_table_html('구매 요청 내역', [
                ['요청자', approval_form_var('요청자')],
                ['요청부서', approval_form_var('부서')],
                ['구매 목적', ''],
                ['희망 납기', ''],
                ['예산 계정', ''],
                ['구매 방식', '□ 단일견적&nbsp;&nbsp;□ 비교견적&nbsp;&nbsp;□ 기존거래처&nbsp;&nbsp;□ 신규거래처'],
                ['선정 사유', '<br><br>'],
                ['첨부 자료', '견적서, 사양서, 비교표 등'],
            ], '상기 물품/서비스 구매를 요청하오니 검토 후 승인하여 주시기 바랍니다.', [
                approval_form_items_table('구매 품목', ['품목명', '규격/모델', '수량', '단가', '금액'], 4, '구매 합계'),
            ]),
        ],
        [
            'id' => 'contract_review',
            'name' => '계약검토요청서',
            'category' => '법무/계약',
            'icon' => 'file-check-2',
            'doc_type' => '계약검토요청서',
            'title' => '계약검토요청서',
            'desc' => '계약 체결 전 거래조건, 리스크, 검토 요청사항 정리',
            'html' => approval_form_table_html('계약 검토 요청 내역', [
                ['요청자', approval_form_var('요청자')],
                ['계약명', ''],
                ['계약상대방', ''],
                ['계약기간', ''],
                ['계약금액', '₩&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;원'],
                ['계약유형', '□ 매출&nbsp;&nbsp;□ 매입&nbsp;&nbsp;□ 용역&nbsp;&nbsp;□ NDA&nbsp;&nbsp;□ 기타'],
                ['주요 조건', '<br><br><br>'],
                ['검토 요청사항', '<br><br>'],
                ['첨부 문서', '계약서 초안, 견적서, 제안서, 거래처 정보 등'],
            ], '상기 계약 건에 대한 검토 및 승인을 요청드립니다.'),
        ],
        [
            'id' => 'corporate_card',
            'name' => '법인카드 지출',
            'category' => '재무',
            'icon' => 'credit-card',
            'doc_type' => '법인카드 지출',
            'title' => '법인카드 지출',
            'desc' => '법인카드 사용내역, 참석자, 증빙 첨부 확인용',
            'html' => approval_form_table_html('법인카드 사용 내역', [
                ['사용자', approval_form_var('사용자')],
                ['소속부서', approval_form_var('부서')],
                ['사용일자', ''],
                ['카드번호', '****-****-****-____'],
                ['가맹점', ''],
                ['사용금액', '₩&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;원'],
                ['사용계정', '□ 접대비&nbsp;&nbsp;□ 회의비&nbsp;&nbsp;□ 복리후생비&nbsp;&nbsp;□ 소모품비&nbsp;&nbsp;□ 기타'],
                ['참석자', ''],
                ['사용목적', '<br><br>'],
                ['증빙첨부', '□ 카드전표&nbsp;&nbsp;□ 영수증&nbsp;&nbsp;□ 참석자명단&nbsp;&nbsp;□ 기타'],
            ], '상기 법인카드 사용 건에 대해 확인 및 승인 요청드립니다.'),
        ],
        [
            'id' => 'expense_claim',
            'name' => '경비청구서',
            'category' => '재무',
            'icon' => 'receipt',
            'doc_type' => '경비청구서',
            'title' => '경비청구서',
            'desc' => '개인 선지출/출장 경비 등 정산 청구용',
            'html' => approval_form_table_html('경비 청구 내역', [
                ['청구자', approval_form_var('청구자')],
                ['소속부서', approval_form_var('부서')],
                ['청구일자', ''],
                ['청구구분', '□ 개인 선지출&nbsp;&nbsp;□ 출장비&nbsp;&nbsp;□ 교통비&nbsp;&nbsp;□ 기타'],
                ['청구금액', '₩&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;원'],
                ['입금계좌', '은행:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 계좌번호:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 예금주:'],
            ], '증빙 서류를 첨부하여 상기 금액을 청구합니다.', [
                approval_form_items_table('사용 내역', ['사용일자', '사용처', '내용', '금액'], 4, '청구 합계'),
                approval_form_note('영수증, 카드전표, 세금계산서 등 증빙을 첨부해 주세요.'),
            ]),
        ],
        [
            'id' => 'vacation',
            'name' => '휴가신청서',
            'category' => '인사/근태',
            'icon' => 'palmtree',
            'doc_type' => '휴가신청서',
            'title' => '휴가신청서',
            'desc' => '연차, 반차, 병가, 경조사 등 휴가 신청용',
            'html' => approval_form_table_html('휴가 신청 내역', [
                ['신청자', approval_form_var('신청자')],
                ['소속부서', approval_form_var('부서')],
                ['휴가종류', '□ 연차&nbsp;&nbsp;□ 반차(오전/오후)&nbsp;&nbsp;□ 병가&nbsp;&nbsp;□ 경조사&nbsp;&nbsp;□ 기타'],
                ['휴가기간', '&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;월&nbsp;&nbsp;일 ~ &nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;월&nbsp;&nbsp;일 (총&nbsp;&nbsp;일)'],
                ['비상연락처', ''],
                ['휴가사유', '<br><br>'],
                ['업무인수자', ''],
                ['인수인계 내용', '<br><br>'],
            ], '상기와 같이 휴가를 신청하오니 승인하여 주시기 바랍니다.'),
        ],
        [
            'id' => 'overtime',
            'name' => '초과근무신청서',
            'category' => '인사/근태',
            'icon' => 'moon',
            'doc_type' => '초과근무신청서',
            'title' => '초과근무신청서',
            'desc' => '야근/휴일근무 사전 승인과 보상 방식 기록용',
            'html' => approval_form_table_html('초과근무 신청 내역', [
                ['신청자', approval_form_var('신청자')],
                ['소속부서', approval_form_var('부서')],
                ['근무일자', ''],
                ['근무구분', '□ 연장근무&nbsp;&nbsp;□ 야간근무&nbsp;&nbsp;□ 휴일근무'],
                ['예상시간', '&nbsp;&nbsp;시&nbsp;&nbsp;분 ~ &nbsp;&nbsp;시&nbsp;&nbsp;분 (총&nbsp;&nbsp;시간)'],
                ['근무장소', ''],
                ['근무사유', '<br><br>'],
                ['업무내용', '<br><br>'],
                ['보상방식', '□ 수당&nbsp;&nbsp;□ 대체휴무&nbsp;&nbsp;□ 해당없음'],
            ], '상기와 같이 초과근무를 신청하오니 승인하여 주시기 바랍니다.'),
        ],
        [
            'id' => 'outside_work',
            'name' => '외근신청서',
            'category' => '인사/근태',
            'icon' => 'map-pin',
            'doc_type' => '외근신청서',
            'title' => '외근신청서',
            'desc' => '외부 미팅/고객 방문의 목적, 장소, 복귀 여부 기록',
            'html' => approval_form_table_html('외근 신청 내역', [
                ['외근자', approval_form_var('외근자')],
                ['소속부서', approval_form_var('부서')],
                ['외근일자', ''],
                ['외근시간', '출발&nbsp;&nbsp;시&nbsp;&nbsp;분 ~ 복귀&nbsp;&nbsp;시&nbsp;&nbsp;분'],
                ['방문처', ''],
                ['외근목적', '<br><br>'],
                ['연락처', ''],
                ['복귀여부', '□ 사무실 복귀&nbsp;&nbsp;□ 직접 퇴근'],
            ], '상기와 같이 외근을 신청하오니 승인하여 주시기 바랍니다.'),
        ],
        [
            'id' => 'early_leave',
            'name' => '조퇴신청서',
            'category' => '인사/근태',
            'icon' => 'door-open',
            'doc_type' => '조퇴신청서',
            'title' => '조퇴신청서',
            'desc' => '조퇴 사유와 업무 인수인계 기록용',
            'html' => approval_form_table_html('조퇴 신청 내역', [
                ['신청자', approval_form_var('신청자')],
                ['소속부서', approval_form_var('부서')],
                ['조퇴일자', ''],
                ['조퇴시각', '&nbsp;&nbsp;시&nbsp;&nbsp;분'],
                ['조퇴사유', '<br><br>'],
                ['잔여업무 처리', '<br><br>'],
                ['비상연락처', ''],
            ], '상기와 같이 조퇴를 신청하오니 승인하여 주시기 바랍니다.'),
        ],
        [
            'id' => 'late_reason',
            'name' => '지각사유서',
            'category' => '인사/근태',
            'icon' => 'alarm-clock',
            'doc_type' => '지각사유서',
            'title' => '지각사유서',
            'desc' => '지각 발생 경위와 재발 방지 계획 기록용',
            'html' => approval_form_table_html('지각 사유 내역', [
                ['작성자', approval_form_var('작성자')],
                ['소속부서', approval_form_var('부서')],
                ['지각일자', ''],
                ['출근시각', '&nbsp;&nbsp;시&nbsp;&nbsp;분'],
                ['지각사유', '<br><br>'],
                ['업무 영향', '<br><br>'],
                ['재발방지 계획', '<br><br>'],
            ], '상기와 같이 지각 사유를 제출합니다.'),
        ],
        [
            'id' => 'business_trip',
            'name' => '출장신청서',
            'category' => '출장',
            'icon' => 'plane',
            'doc_type' => '출장신청서',
            'title' => '출장신청서',
            'desc' => '출장 목적, 일정, 비용을 사전 승인받는 양식',
            'html' => approval_form_table_html('출장 신청 내역', [
                ['출장자', approval_form_var('출장자')],
                ['소속부서', approval_form_var('부서')],
                ['출장지', ''],
                ['출장기간', '&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;월&nbsp;&nbsp;일 ~ &nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;월&nbsp;&nbsp;일 (&nbsp;&nbsp;박&nbsp;&nbsp;일)'],
                ['동행자', ''],
                ['출장목적', '<br><br>'],
                ['주요 일정', '<br><br><br>'],
                ['교통편', '□ 항공&nbsp;&nbsp;□ 기차&nbsp;&nbsp;□ 버스&nbsp;&nbsp;□ 자차&nbsp;&nbsp;□ 기타'],
                ['예상경비', '교통비:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 숙박비:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 식대:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 기타:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 합계:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;원'],
            ], '상기와 같이 출장을 신청하오니 승인하여 주시기 바랍니다.'),
        ],
        [
            'id' => 'trip_report',
            'name' => '출장보고서',
            'category' => '출장',
            'icon' => 'route',
            'doc_type' => '출장보고서',
            'title' => '출장보고서',
            'desc' => '출장 후 방문 결과, 성과, 후속 조치 보고용',
            'html' => approval_form_table_html('출장 보고 내역', [
                ['보고자', approval_form_var('보고자')],
                ['출장지', ''],
                ['출장기간', ''],
                ['방문처 / 참석자', ''],
                ['출장목적', '<br><br>'],
                ['수행내용', '<br><br><br>'],
                ['주요 결과', '<br><br>'],
                ['후속 조치', '<br><br>'],
                ['첨부 자료', '회의록, 명함, 사진, 제안서 등'],
            ], '상기와 같이 출장 결과를 보고드립니다.'),
        ],
        [
            'id' => 'trip_expense',
            'name' => '출장여비청구서',
            'category' => '출장',
            'icon' => 'luggage',
            'doc_type' => '출장여비청구서',
            'title' => '출장여비청구서',
            'desc' => '출장 중 발생한 교통/숙박/식대 비용 정산용',
            'html' => approval_form_table_html('출장여비 청구 내역', [
                ['청구자', approval_form_var('청구자')],
                ['출장명', ''],
                ['출장기간', ''],
                ['청구금액', '₩&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;원'],
                ['입금계좌', '은행:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 계좌번호:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 예금주:'],
            ], '상기 출장여비를 청구하오니 확인 후 지급하여 주시기 바랍니다.', [
                approval_form_items_table('출장여비 상세', ['일자', '구분', '사용처/구간', '내용', '금액'], 5, '청구 합계'),
                approval_form_note('교통비, 숙박비, 식대 등 지출 증빙을 첨부해 주세요.'),
            ]),
        ],
        [
            'id' => 'meeting_minutes',
            'name' => '회의록',
            'category' => '공통',
            'icon' => 'notebook-tabs',
            'doc_type' => '회의록',
            'title' => '회의록',
            'desc' => '회의 안건, 결정사항, 액션아이템 승인/공유용',
            'html' => approval_form_table_html('회의 기본 정보', [
                ['회의명', ''],
                ['일시', ''],
                ['장소', ''],
                ['주관부서', approval_form_var('부서')],
                ['참석자', ''],
                ['안건', '<br><br>'],
                ['결정사항', '<br><br><br>'],
                ['이슈 / 리스크', '<br><br>'],
            ], '상기 회의 내용을 공유 및 승인 요청드립니다.', [
                approval_form_items_table('후속 조치', ['담당자', '조치 내용', '기한', '상태'], 4),
            ]),
        ],
        [
            'id' => 'education_request',
            'name' => '교육신청서',
            'category' => '인사/총무',
            'icon' => 'graduation-cap',
            'doc_type' => '교육신청서',
            'title' => '교육신청서',
            'desc' => '외부 교육/세미나 참가 신청 및 비용 승인용',
            'html' => approval_form_table_html('교육 신청 내역', [
                ['신청자', approval_form_var('신청자')],
                ['소속부서', approval_form_var('부서')],
                ['교육명', ''],
                ['교육기관', ''],
                ['교육기간', ''],
                ['교육비', '₩&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;원'],
                ['신청목적', '<br><br>'],
                ['업무 활용 계획', '<br><br>'],
                ['첨부자료', '교육 안내문, 견적서 등'],
            ], '상기 교육 참석을 신청하오니 승인하여 주시기 바랍니다.'),
        ],
        [
            'id' => 'hiring_request',
            'name' => '채용요청서',
            'category' => '인사/총무',
            'icon' => 'user-plus',
            'doc_type' => '채용요청서',
            'title' => '채용요청서',
            'desc' => '인력 충원 필요성, 직무, 예산 승인용',
            'html' => approval_form_table_html('채용 요청 내역', [
                ['요청부서', approval_form_var('부서')],
                ['요청자', approval_form_var('요청자')],
                ['채용직무', ''],
                ['채용형태', '□ 정규직&nbsp;&nbsp;□ 계약직&nbsp;&nbsp;□ 인턴&nbsp;&nbsp;□ 파트타임'],
                ['채용인원', '&nbsp;&nbsp;명'],
                ['희망입사일', ''],
                ['요청 사유', '<br><br>'],
                ['주요 업무', '<br><br><br>'],
                ['필수 요건', '<br><br>'],
                ['예상 인건비', '월&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;원 / 연&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;원'],
            ], '상기와 같이 인력 채용을 요청하오니 검토 후 승인하여 주시기 바랍니다.'),
        ],
        [
            'id' => 'incident_report',
            'name' => '경위서 / 시말서',
            'category' => '인사/총무',
            'icon' => 'triangle-alert',
            'doc_type' => '경위서',
            'title' => '경위서',
            'desc' => '사고/오류 발생 경위와 재발 방지 대책 기록용',
            'html' => approval_form_table_html('경위 보고 내역', [
                ['작성자', approval_form_var('작성자')],
                ['소속부서', approval_form_var('부서')],
                ['발생일시', ''],
                ['발생장소', ''],
                ['관련자', ''],
                ['발생 경위', '<br><br><br>'],
                ['원인 분석', '<br><br>'],
                ['조치 내용', '<br><br>'],
                ['재발 방지 대책', '<br><br><br>'],
            ], '상기와 같이 경위를 보고드리며 재발 방지를 위해 노력하겠습니다.'),
        ],
        [
            'id' => 'draft_cancel',
            'name' => '기안취소 요청서',
            'category' => '공통',
            'icon' => 'undo-2',
            'doc_type' => '기안취소',
            'title' => '기안취소 요청서',
            'desc' => '이미 상신한 기안의 취소/철회 사유 기록용',
            'html' => approval_form_table_html('기안 취소 요청 내역', [
                ['원기안 문서번호', ''],
                ['원기안 제목', ''],
                ['원기안 기안자', ''],
                ['원기안 일자', ''],
                ['현재 진행상태', '□ 결재 진행중&nbsp;&nbsp;□ 결재 완료&nbsp;&nbsp;□ 시행 전'],
                ['취소 요청 사유', '<br><br><br>'],
                ['후속 조치', '<br><br>'],
            ], '상기 기안에 대해 취소를 요청드리오니 검토 후 승인하여 주시기 바랍니다.'),
        ],
    ];
}

function approval_form_seed_templates(): array
{
    return array_values(array_filter(
        approval_form_templates(),
        static fn(array $tpl): bool => ($tpl['id'] ?? '') !== 'blank' && ($tpl['doc_type'] ?? '') !== ''
    ));
}
