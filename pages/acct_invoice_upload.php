<?php
/**
 * 세금계산서 > 업로드 탭
 * 홈택스 엑셀/CSV 파일 업로드 → tax_invoices 테이블 저장
 */
if (!function_exists('getDBConnection')) {
    require_once __DIR__ . '/../config/database.php';
}
$pdo = getDBConnection();

$curYear  = (int)date('Y');
$curMonth = (int)date('m');
?>

<!-- SheetJS CDN -->
<script src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js"
        integrity="sha384-EnyY0/GSHQGSxSgMwaIPzSESbqoOLSexfnSMN2AP+39Ckmn92stwABZynq1JyzdT"
        crossorigin="anonymous"></script>

<div class="space-y-5">
    <!-- 홈택스 안내 -->
    <div class="bg-blue-50 border border-gray-200 rounded-xl p-5">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2.5">
                <i data-lucide="help-circle" class="w-5 h-5 text-blue-500 flex-shrink-0"></i>
                <h3 class="text-sm font-bold text-slate-100">홈택스에서 엑셀 파일 받는 법</h3>
            </div>
            <a href="https://www.hometax.go.kr" target="_blank" rel="noopener noreferrer"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>홈택스 바로가기
            </a>
        </div>
        <!-- 가로 스텝 카드 4단계 -->
        <div class="grid grid-cols-[1fr_auto_1fr_auto_1fr_auto_1fr] items-stretch gap-0 mb-4">
            <!-- Step 1: 로그인 + 사업자 전환 -->
            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm h-full">
                <div class="flex items-center gap-2 mb-2">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-blue-500 text-white text-xs font-bold flex items-center justify-center">1</span>
                    <p class="text-sm font-semibold text-slate-200">로그인 + 사업자 전환</p>
                </div>
                <p class="text-xs text-slate-400 leading-relaxed">
                    공동인증서 또는 간편인증(카카오·PASS 등)으로 로그인하세요. 개인으로 접속되면 우측 상단 <strong class="text-slate-300">「사업자 전환」</strong>을 눌러 해당 법인을 선택해야 해요.
                </p>
            </div>
            <div class="flex items-center justify-center px-1.5">
                <i data-lucide="chevron-right" class="w-4 h-4 text-blue-300"></i>
            </div>
            <!-- Step 2: 메뉴 이동 -->
            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm h-full">
                <div class="flex items-center gap-2 mb-2">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-blue-500 text-white text-xs font-bold flex items-center justify-center">2</span>
                    <p class="text-sm font-semibold text-slate-200">월/분기별 목록조회 이동</p>
                </div>
                <p class="text-xs text-slate-400 leading-relaxed mb-2">우측 상단 <strong class="text-slate-300">≡ 전체메뉴</strong>를 눌러 아래 순서로 찾아가세요.</p>
                <div class="flex flex-wrap items-center gap-1 text-xs text-slate-400">
                    <span class="px-1.5 py-0.5 bg-blue-50 text-blue-700 rounded font-medium">계산서·영수증·카드</span><span>›</span>
                    <span class="px-1.5 py-0.5 bg-blue-50 text-blue-700 rounded font-medium">전자(세금)계산서 조회</span><span>›</span>
                    <span class="px-1.5 py-0.5 bg-blue-50 text-blue-700 rounded font-medium">조회</span><span>›</span>
                    <strong class="px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded">월/분기별 목록조회</strong>
                </div>
            </div>
            <div class="flex items-center justify-center px-1.5">
                <i data-lucide="chevron-right" class="w-4 h-4 text-blue-300"></i>
            </div>
            <!-- Step 3: 조건 설정 + 조회 -->
            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm h-full">
                <div class="flex items-center gap-2 mb-2">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-blue-500 text-white text-xs font-bold flex items-center justify-center">3</span>
                    <p class="text-sm font-semibold text-slate-200">조건 설정 + 조회</p>
                </div>
                <p class="text-xs text-slate-400 leading-relaxed">
                    분류에서 <strong class="text-slate-300">전자세금계산서</strong> 선택 → <strong class="text-slate-300">매입·매출 구분</strong>에서 매출 또는 매입 → 조회기간 구분은 <strong class="text-slate-300">월별</strong> 추천 → 기간 설정 후 <strong class="text-slate-300">「조회」</strong> 클릭
                </p>
            </div>
            <div class="flex items-center justify-center px-1.5">
                <i data-lucide="chevron-right" class="w-4 h-4 text-blue-300"></i>
            </div>
            <!-- Step 4: 엑셀 다운로드 -->
            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm h-full">
                <div class="flex items-center gap-2 mb-2">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-emerald-500 text-white text-xs font-bold flex items-center justify-center">4</span>
                    <p class="text-sm font-semibold text-emerald-600">엑셀 다운로드</p>
                </div>
                <p class="text-xs text-slate-400 leading-relaxed">
                    목록이 나오면 우측의 <strong class="text-emerald-600">「엑셀내려받기」</strong> 버튼을 클릭하세요. (「목록출력」은 인쇄용이니 <strong class="text-slate-300">엑셀내려받기</strong>를 누르세요!) 받은 파일을 아래에 끌어다 놓으면 끝.
                </p>
            </div>
        </div>
        <!-- 팁 -->
        <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 text-xs text-slate-400">
            <span class="flex items-center gap-1"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i><strong class="text-slate-300">매출·매입 따로</strong> 각각 다운로드 후 올려주세요</span>
            <span class="flex items-center gap-1"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i><strong class="text-slate-300">파일 수정 불필요</strong> · 홈택스 엑셀 그대로 올리면 칼럼 자동 인식</span>
            <span class="flex items-center gap-1"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i><strong class="text-slate-300">중복 걱정 없음</strong> · 같은 승인번호는 최신 데이터로 덮어쓰기</span>
            <span class="flex items-center gap-1"><i data-lucide="check" class="w-3.5 h-3.5 text-blue-500"></i><strong class="text-slate-300">.xlsx .xls .csv</strong> 모두 지원</span>
        </div>
    </div>

    <!-- 설정 영역 -->
    <div class="bg-white border border-gray-200 rounded-xl p-5">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <!-- 연도/월 선택 -->
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1">연도</label>
                <select id="uploadYear" class="w-full border border-gray-300 bg-white text-slate-200 rounded-lg px-3 py-2 text-sm">
                    <?php for ($y = $curYear; $y >= 2024; $y--): ?>
                    <option value="<?= $y ?>" <?= $y === $curYear ? 'selected' : '' ?>><?= $y ?>년</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1">월</label>
                <select id="uploadMonth" class="w-full border border-gray-300 bg-white text-slate-200 rounded-lg px-3 py-2 text-sm">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $m === $curMonth ? 'selected' : '' ?>><?= $m ?>월</option>
                    <?php endfor; ?>
                </select>
            </div>
            <!-- 매출/매입 선택 -->
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1">구분</label>
                <div class="zm-radio-group biz-segment-group" role="radiogroup" aria-label="매출/매입 구분">
                    <label class="cursor-pointer">
                        <input type="radio" name="invoiceType" value="매출" checked class="sr-only peer biz-radio-input">
                        <span class="zm-radio">매출</span>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="invoiceType" value="매입" class="sr-only peer biz-radio-input">
                        <span class="zm-radio">매입</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- 드래그앤드롭 업로드 -->
        <div id="dropZone"
             class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center cursor-pointer hover:border-gray-400 hover:bg-slate-800/50 transition-colors"
             ondragover="event.preventDefault(); this.classList.add('border-gray-400','bg-slate-800/50')"
             ondragleave="this.classList.remove('border-gray-400','bg-slate-800/50')"
             ondrop="handleDrop(event)"
             onclick="document.getElementById('fileInput').click()">
            <input type="file" id="fileInput" class="hidden" accept=".xlsx,.xls,.csv" onchange="handleFileSelect(this)">
            <div id="dropMsg">
                <i data-lucide="upload-cloud" class="w-10 h-10 mx-auto mb-3 text-slate-500"></i>
                <p class="text-slate-400 text-sm">홈택스에서 다운로드한 엑셀 또는 CSV 파일을 여기에 놓으세요</p>
                <p class="text-slate-500 text-xs mt-1">.xlsx, .xls, .csv 지원</p>
            </div>
            <div id="fileInfo" class="hidden">
                <div class="flex items-center justify-center gap-3">
                    <i data-lucide="file-spreadsheet" class="w-8 h-8 text-emerald-500"></i>
                    <div class="text-left">
                        <p class="text-sm font-medium text-slate-200" id="fileName"></p>
                        <p class="text-xs text-slate-400" id="fileSize"></p>
                    </div>
                    <button type="button" onclick="clearFile(event)" class="ml-3 text-slate-500 hover:text-red-400">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 파싱 결과 미리보기 -->
    <div id="previewSection" class="hidden">
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <div class="flex items-center gap-3">
                    <h3 class="text-sm font-bold text-slate-100">미리보기</h3>
                    <span id="previewCount" class="text-xs bg-primary/20 text-primary px-2 py-0.5 rounded-full"></span>
                    <span id="previewErrors" class="text-xs bg-amber-500/20 text-amber-600 px-2 py-0.5 rounded-full hidden"></span>
                </div>
                <button onclick="doUpload()" id="btnUpload"
                        class="inline-flex items-center gap-2 px-5 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:opacity-90 transition-colors">
                    <i data-lucide="upload" class="w-4 h-4"></i> 업로드 확정
                </button>
            </div>
            <div class="overflow-x-auto max-h-96">
                <table class="w-full text-sm">
                    <thead class="bg-slate-800 text-slate-300 sticky top-0">
                        <tr>
                            <th class="px-3 py-2 text-left">#</th>
                            <th class="px-3 py-2 text-left">승인번호</th>
                            <th class="px-3 py-2 text-left">작성일자</th>
                            <th class="px-3 py-2 text-left">공급자</th>
                            <th class="px-3 py-2 text-left">공급받는자</th>
                            <th class="px-3 py-2 text-right">공급가액</th>
                            <th class="px-3 py-2 text-right">세액</th>
                            <th class="px-3 py-2 text-right">합계</th>
                            <th class="px-3 py-2 text-center">과세유형</th>
                        </tr>
                    </thead>
                    <tbody id="previewBody" class="text-slate-300"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 업로드 결과 -->
    <div id="resultSection" class="hidden">
        <div class="bg-white border border-emerald-200 rounded-xl p-5">
            <div class="flex items-center gap-3 mb-3">
                <i data-lucide="check-circle" class="w-6 h-6 text-emerald-500"></i>
                <h3 class="text-sm font-bold text-emerald-600">업로드 완료</h3>
            </div>
            <p id="resultMsg" class="text-sm text-slate-300"></p>
            <div class="flex gap-3 mt-4">
                <button onclick="resetUpload()" class="px-4 py-2 text-sm bg-slate-800 text-slate-300 rounded-lg hover:bg-slate-700">
                    추가 업로드
                </button>
                <a href="?tab=issue" class="px-4 py-2 text-sm bg-primary text-white rounded-lg hover:opacity-90">
                    목록 보기
                </a>
                <a href="?tab=match" class="px-4 py-2 text-sm bg-slate-800 text-slate-300 rounded-lg hover:bg-slate-700">
                    통장 매핑하기
                </a>
            </div>
        </div>
    </div>

    <!-- 업로드 이력 -->
    <div class="bg-white border border-gray-200 rounded-xl p-5">
        <h3 class="text-sm font-bold text-slate-100 mb-3">최근 업로드 이력</h3>
        <div id="syncLogArea" class="text-sm text-slate-400">로딩 중...</div>
    </div>
</div>

<script>
// API_BASE는 acct_invoice_issue.php에서 선언됨
let parsedRows = [];

// 홈택스 컬럼 매핑 · 헤더명으로 자동 매칭
const COLUMN_MAP = {
    '승인번호': 'invoice_number',
    '세금계산서승인번호': 'invoice_number',
    '작성일자': 'write_date',
    '발급일자': 'issue_date',
    '전송일자': 'send_date',
    '공급자사업자등록번호': 'supplier_bizno',
    '공급자사업자번호': 'supplier_bizno',
    '공급자등록번호': 'supplier_bizno',
    '사업자등록번호': 'supplier_bizno',
    '공급자상호': 'supplier_name',
    '상호': 'supplier_name',
    '상호(법인명)': 'supplier_name',
    '공급자': 'supplier_name',
    '공급자명': 'supplier_name',
    '공급자대표자': 'supplier_ceo',
    '대표자': 'supplier_ceo',
    '성명(대표자)': 'supplier_ceo',
    '공급받는자사업자등록번호': 'buyer_bizno',
    '공급받는자사업자번호': 'buyer_bizno',
    '공급받는자등록번호': 'buyer_bizno',
    '공급받는자상호': 'buyer_name',
    '공급받는자상호(법인명)': 'buyer_name',
    '공급받는자': 'buyer_name',
    '공급받는자명': 'buyer_name',
    '공급받는자대표자': 'buyer_ceo',
    '공급받는자성명(대표자)': 'buyer_ceo',
    '공급가액': 'supply_amount',
    '세액': 'tax_amount',
    '합계금액': 'total_amount',
    '총금액': 'total_amount',
    '과세유형': 'tax_type',
    '전자세금계산서분류': 'invoice_class',
    '전자세금계산서종류': 'invoice_kind',
    '비고': 'memo',
};

// 과세유형 판정 · 명시 '과세유형' 우선, 없으면 홈택스 분류/종류로 추론
// 분류: 세금계산서(과세)/계산서(면세), 종류: 일반/영세율
function inferTaxType(r) {
    const explicit = (r.tax_type || '').trim();
    if (explicit) {
        if (explicit.includes('영세')) return '영세율';
        if (explicit.includes('면세')) return '면세';
        return '과세';
    }
    const cls  = (r.invoice_class || '').trim();
    const kind = (r.invoice_kind || '').trim();
    if (cls && !cls.includes('세금계산서') && cls.includes('계산서')) return '면세';
    if (kind.includes('영세')) return '영세율';
    return '과세';
}

function handleDrop(e) {
    e.preventDefault();
    e.currentTarget.classList.remove('border-gray-400', 'bg-slate-800/50');
    const file = e.dataTransfer.files[0];
    if (file) processFile(file);
}

function handleFileSelect(input) {
    const file = input.files[0];
    if (file) processFile(file);
}

function clearFile(e) {
    e.stopPropagation();
    document.getElementById('fileInput').value = '';
    document.getElementById('fileInfo').classList.add('hidden');
    document.getElementById('dropMsg').classList.remove('hidden');
    document.getElementById('previewSection').classList.add('hidden');
    parsedRows = [];
}

function resetUpload() {
    clearFile(new Event('click'));
    document.getElementById('resultSection').classList.add('hidden');
}

function showFileInfo(file) {
    document.getElementById('fileName').textContent = file.name;
    const sizeKB = (file.size / 1024).toFixed(1);
    document.getElementById('fileSize').textContent = sizeKB > 1024
        ? (sizeKB / 1024).toFixed(1) + ' MB'
        : sizeKB + ' KB';
    document.getElementById('fileInfo').classList.remove('hidden');
    document.getElementById('dropMsg').classList.add('hidden');
    lucide.createIcons();
}

function processFile(file) {
    showFileInfo(file);
    const ext = file.name.split('.').pop().toLowerCase();

    if (ext === 'csv') {
        const reader = new FileReader();
        reader.onload = (e) => {
            const text = e.target.result;
            if (text.includes('승인번호') || text.includes('공급')) {
                const rows = parseCSV(text);
                mapAndPreview(rows);
            } else {
                const readerEuc = new FileReader();
                readerEuc.onload = (e2) => {
                    const rows = parseCSV(e2.target.result);
                    mapAndPreview(rows);
                };
                readerEuc.readAsText(file, 'EUC-KR');
            }
        };
        reader.readAsText(file, 'UTF-8');
    } else {
        const reader = new FileReader();
        reader.onload = (e) => {
            try {
                const wb = XLSX.read(e.target.result, { type: 'array' });
                const ws = wb.Sheets[wb.SheetNames[0]];
                const rows = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '' });
                mapAndPreview(rows);
            } catch (err) {
                alert('엑셀 파일 파싱 오류: ' + err.message);
            }
        };
        reader.readAsArrayBuffer(file);
    }
}

function parseCSV(text) {
    const lines = text.replace(/\r\n/g, '\n').replace(/\r/g, '\n').split('\n');
    return lines.filter(l => l.trim()).map(line => {
        const result = [];
        let current = '';
        let inQuotes = false;
        for (let i = 0; i < line.length; i++) {
            const ch = line[i];
            if (inQuotes) {
                if (ch === '"' && line[i + 1] === '"') { current += '"'; i++; }
                else if (ch === '"') { inQuotes = false; }
                else { current += ch; }
            } else {
                if (ch === '"') { inQuotes = true; }
                else if (ch === ',') { result.push(current.trim()); current = ''; }
                else { current += ch; }
            }
        }
        result.push(current.trim());
        return result;
    });
}

function mapAndPreview(rawRows) {
    if (!rawRows || rawRows.length < 2) {
        alert('데이터가 없거나 너무 적습니다.');
        return;
    }

    // 헤더 행 찾기 · "승인번호" 포함 행
    let headerIdx = -1;
    for (let i = 0; i < Math.min(rawRows.length, 10); i++) {
        const row = rawRows[i].map(c => String(c).replace(/\s+/g, ''));
        if (row.some(c => c.includes('승인번호'))) {
            headerIdx = i;
            break;
        }
    }
    if (headerIdx === -1) {
        // fallback: 첫 행을 헤더로
        headerIdx = 0;
    }

    const headers = rawRows[headerIdx].map(h => String(h).replace(/[\s\u00a0]+/g, ''));
    const colMapping = {};

    let inBuyerSection = false;
    headers.forEach((h, idx) => {
        const clean = h.replace(/\(.*?\)/g, '');

        // 공급받는자 섹션 진입 · 약식('공급받는자등록번호')·풀네임 모두 인식
        if (h.includes('공급받는자') && (h.includes('등록번호') || h.includes('사업자'))) {
            inBuyerSection = true;
        }

        if ((h === '상호' || clean === '상호') && !inBuyerSection) {
            colMapping[idx] = 'supplier_name'; return;
        }
        if ((h === '상호' || clean === '상호') && inBuyerSection) {
            colMapping[idx] = 'buyer_name'; return;
        }
        if ((h === '대표자명' || h === '대표자' || clean === '대표자명' || clean === '대표자') && !inBuyerSection) {
            colMapping[idx] = 'supplier_ceo'; return;
        }
        if ((h === '대표자명' || h === '대표자' || clean === '대표자명' || clean === '대표자') && inBuyerSection) {
            colMapping[idx] = 'buyer_ceo'; return;
        }

        let mapped = COLUMN_MAP[h];
        if (!mapped) mapped = COLUMN_MAP[clean];
        if (mapped && !Object.values(colMapping).includes(mapped)) colMapping[idx] = mapped;
    });

    if (!Object.values(colMapping).includes('invoice_number')) {
        alert('승인번호 컬럼을 찾을 수 없습니다. 홈택스에서 다운로드한 파일인지 확인해주세요.');
        return;
    }

    parsedRows = [];
    let errorCount = 0;

    for (let i = headerIdx + 1; i < rawRows.length; i++) {
        const cells = rawRows[i];
        if (!cells || cells.every(c => String(c).trim() === '')) continue;

        const row = {};
        for (const [colIdx, field] of Object.entries(colMapping)) {
            row[field] = String(cells[colIdx] ?? '').trim();
        }

        if (!row.invoice_number) continue;

        if (!row.issue_date && row.write_date) {
            row.issue_date = row.write_date;
        }

        row._rowNum = i + 1;
        row._hasError = !row.issue_date;
        if (row._hasError) errorCount++;

        parsedRows.push(row);
    }

    renderPreview(parsedRows, errorCount);
}

function fmt(n) {
    return Number(String(n).replace(/[^0-9\-]/g, '') || 0).toLocaleString();
}

function renderPreview(rows, errorCount) {
    const body = document.getElementById('previewBody');
    body.innerHTML = '';

    rows.forEach((r, i) => {
        const cls = r._hasError ? 'bg-amber-50' : '';
        body.innerHTML += `
            <tr class="border-b border-gray-200 ${cls}">
                <td class="px-3 py-2">${i + 1}</td>
                <td class="px-3 py-2 font-mono text-xs">${esc(r.invoice_number)}</td>
                <td class="px-3 py-2">${esc(r.issue_date)}</td>
                <td class="px-3 py-2">${esc(r.supplier_name || '-')}<br><span class="text-xs text-slate-500">${esc(r.supplier_bizno || '')}</span></td>
                <td class="px-3 py-2">${esc(r.buyer_name || '-')}<br><span class="text-xs text-slate-500">${esc(r.buyer_bizno || '')}</span></td>
                <td class="px-3 py-2 text-right">${fmt(r.supply_amount)}</td>
                <td class="px-3 py-2 text-right">${fmt(r.tax_amount)}</td>
                <td class="px-3 py-2 text-right font-medium">${fmt(r.total_amount)}</td>
                <td class="px-3 py-2 text-center">${esc(inferTaxType(r))}</td>
            </tr>`;
    });

    document.getElementById('previewCount').textContent = rows.length + '건';
    const errEl = document.getElementById('previewErrors');
    if (errorCount > 0) {
        errEl.textContent = '오류 ' + errorCount + '건';
        errEl.classList.remove('hidden');
    } else {
        errEl.classList.add('hidden');
    }

    document.getElementById('previewSection').classList.remove('hidden');
}

function esc(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

async function doUpload() {
    if (!parsedRows.length) { alert('업로드할 데이터가 없습니다.'); return; }

    const btn = document.getElementById('btnUpload');
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin"></i> 업로드 중...';
    lucide.createIcons();

    try {
        const res = await fetch(API_BASE + '?action=uploadInvoices', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                invoice_type: document.querySelector('input[name="invoiceType"]:checked').value,
                year: parseInt(document.getElementById('uploadYear').value),
                month: parseInt(document.getElementById('uploadMonth').value),
                invoices: parsedRows,
            }),
        });
        const data = await res.json();

        if (data.success) {
            document.getElementById('resultMsg').textContent = data.message;
            document.getElementById('resultSection').classList.remove('hidden');
            document.getElementById('previewSection').classList.add('hidden');
            loadSyncLog();
        } else {
            alert('업로드 실패: ' + (data.error || '알 수 없는 오류'));
        }
    } catch (e) {
        alert('네트워크 오류: ' + e.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="upload" class="w-4 h-4"></i> 업로드 확정';
        lucide.createIcons();
    }
}

// 동기화 유형 코드 → 한글 라벨 + 매출(파랑)/매입(주황) 색 구분
const SYNC_TYPE_META = {
    upload_sales:     { label: '매출 업로드', sub: '엑셀',   cls: 'bg-blue-100 text-blue-700' },
    upload_purchase:  { label: '매입 업로드', sub: '엑셀',   cls: 'bg-amber-100 text-amber-700' },
    sales_invoice:    { label: '매출 동기화', sub: '홈택스', cls: 'bg-blue-100 text-blue-700' },
    purchase_invoice: { label: '매입 동기화', sub: '홈택스', cls: 'bg-amber-100 text-amber-700' },
};
function syncTypeMeta(t) {
    return SYNC_TYPE_META[t] || { label: t, sub: '', cls: 'bg-slate-100 text-slate-700' };
}

async function loadSyncLog() {
    try {
        const res = await fetch(API_BASE + '?action=getSyncLog');
        const data = await res.json();
        const logs = data.logs || [];
        const area = document.getElementById('syncLogArea');

        if (!logs.length) {
            area.textContent = '업로드 이력이 없습니다.';
            return;
        }

        area.innerHTML = '<table class="w-full"><thead><tr class="text-slate-400 text-xs">'
            + '<th class="text-left pb-2">구분</th><th class="text-left pb-2">건수</th>'
            + '<th class="text-left pb-2">상태</th><th class="text-left pb-2">내용</th>'
            + '<th class="text-left pb-2">일시</th></tr></thead><tbody>'
            + logs.slice(0, 10).map(l => {
                const m = syncTypeMeta(l.sync_type);
                return `
                <tr class="border-t border-gray-200">
                    <td class="py-2">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium ${m.cls}">${esc(m.label)}</span>
                        ${m.sub ? `<span class="ml-1 text-xs text-slate-400">${esc(m.sub)}</span>` : ''}
                    </td>
                    <td class="py-2">${l.sync_count}건</td>
                    <td class="py-2"><span class="px-2 py-0.5 rounded-full text-xs ${l.status === '성공' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'}">${l.status}</span></td>
                    <td class="py-2 text-slate-500 text-xs">${esc(l.message || '')}</td>
                    <td class="py-2 text-slate-500 text-xs">${l.started_at || ''}</td>
                </tr>`;
            }).join('') + '</tbody></table>';
    } catch (e) {
        document.getElementById('syncLogArea').textContent = '이력 로드 실패';
    }
}


document.addEventListener('DOMContentLoaded', () => {
    loadSyncLog();
    lucide.createIcons();
});
</script>
