<?php
$pageTitle = '직원 일괄 등록';
$currentPage = 'hr';
require_once __DIR__ . '/../includes/permissions.php';
requireMenuPermission('hr', 'view'); // 접근권한 관리 연동 (admin 항상 통과)
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../config/database.php';

$docRoot = realpath($_SERVER['DOCUMENT_ROOT']);
$projectRoot = realpath(__DIR__ . '/..');
$basePath = rtrim(str_replace('\\', '/', str_replace($docRoot, '', $projectRoot)), '/');
?>

<div id="mainContent" class="ml-60 mt-14 transition-all duration-300">
    <main class="p-6 min-h-screen bg-slate-950">

        <!-- 상단 헤더 -->
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-3">
                <a href="<?= $basePath ?>/pages/employees.php" class="btn btn-secondary btn-xs w-8 h-8 flex items-center justify-center">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                <h2 class="text-lg font-bold text-slate-100">직원 일괄 등록</h2>
            </div>
        </div>

        <!-- 1단계: 템플릿 다운로드 -->
        <div class="bg-slate-900 rounded-xl border border-slate-800 p-6 mb-5">
            <h3 class="text-base font-bold text-slate-100 mb-2">1단계. 템플릿 다운로드</h3>
            <p class="text-sm text-slate-300 mb-4">직원 일괄추가 템플릿을 다운로드한 후, 템플릿 형식에 맞춰 직원정보를 입력한 뒤 업로드해주세요.</p>

            <div class="flex items-center gap-2 mb-4 px-4 py-3 bg-slate-950 rounded-lg border border-slate-800 w-fit">
                <i data-lucide="file-spreadsheet" class="w-5 h-5 text-amber-700"></i>
                <span class="text-sm text-slate-200">직원일괄등록_템플릿.xlsx</span>
            </div>

            <button id="btnDownloadTemplate" class="inline-flex items-center gap-1.5 px-5 py-2.5 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-dark transition-colors">
                <i data-lucide="download" class="w-4 h-4"></i>
                템플릿 다운로드
            </button>
        </div>

        <!-- 2단계: 엑셀 업로드 -->
        <div class="bg-slate-900 rounded-xl border border-slate-800 p-6 mb-5">
            <h3 class="text-base font-bold text-slate-100 mb-2">2단계. 엑셀 업로드</h3>
            <p class="text-sm text-slate-300 mb-4">템플릿의 양식에 맞게 입력을 완료했다면, 아래 버튼을 클릭하여 파일을 업로드하세요.</p>

            <div id="uploadArea" class="border-2 border-dashed border-slate-700 rounded-lg p-8 text-center mb-4 transition-colors cursor-pointer hover:border-gray-400 hover:bg-gray-100">
                <div id="uploadPlaceholder">
                    <i data-lucide="upload-cloud" class="w-10 h-10 text-slate-500 mx-auto mb-3"></i>
                    <p class="text-sm text-slate-300 mb-1">파일을 드래그하여 놓거나 클릭하여 선택하세요</p>
                    <p class="text-sm text-slate-500">.csv 파일만 가능 (최대 5MB, 엑셀→CSV 변환 필요)</p>
                </div>
                <div id="uploadFileInfo" class="hidden">
                    <div class="flex items-center justify-center gap-3">
                        <i data-lucide="file-check" class="w-8 h-8 text-amber-500"></i>
                        <div class="text-left">
                            <p id="uploadFileName" class="text-sm font-medium text-slate-100"></p>
                            <p id="uploadFileSize" class="text-sm text-slate-500"></p>
                        </div>
                        <button id="btnRemoveFile" class="ml-4 p-2 text-slate-500 hover:text-amber-500 hover:bg-amber-50 rounded-full transition-colors">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
                <input type="file" id="fileInput" accept=".csv" class="hidden">
            </div>

            <button id="btnUpload" class="inline-flex items-center gap-1.5 px-5 py-2.5 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                <i data-lucide="upload" class="w-4 h-4"></i>
                엑셀 업로드
            </button>
        </div>

    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const basePath = '<?= $basePath ?>';
    let selectedFile = null;

    // === 1단계: 템플릿 다운로드 (CSV 형식) ===
    document.getElementById('btnDownloadTemplate').addEventListener('click', function() {
        const headers = ['이름', '<?= htmlspecialchars(getOrgLabel('division')) ?>', '<?= htmlspecialchars(getOrgLabel('department')) ?>', '직급', '고용형태', '이메일', '연락처', '입사일'];
        const sample = [
            ['홍길동', 'Zaemit', '개발팀', '사원', '정규직', 'hong@example.com', '010-1234-5678', '2025-01-01'],
            ['김철수', 'Zaemit', '마케팅팀', '대리', '정규직', 'kim@example.com', '010-9876-5432', '2025-02-01'],
        ];

        const csvContent = '\uFEFF' + [headers, ...sample]
            .map(row => row.map(cell => '"' + String(cell).replace(/"/g, '""') + '"').join(','))
            .join('\n');

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = '직원일괄등록_템플릿.csv';
        a.click();
        URL.revokeObjectURL(url);
    });

    // === 2단계: 파일 업로드 영역 ===
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('fileInput');

    uploadArea.addEventListener('click', () => fileInput.click());

    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('border-gray-400', 'bg-gray-100');
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('border-gray-400', 'bg-gray-100');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('border-gray-400', 'bg-gray-100');
        const file = e.dataTransfer.files[0];
        if (file) handleFileSelect(file);
    });

    fileInput.addEventListener('change', (e) => {
        if (e.target.files[0]) handleFileSelect(e.target.files[0]);
    });

    function handleFileSelect(file) {
        const ext = '.' + file.name.split('.').pop().toLowerCase();
        if (ext !== '.csv') {
            alert('CSV 파일만 업로드 가능합니다.\n\n엑셀 파일(.xlsx)은 [파일] → [다른 이름으로 저장] → [CSV UTF-8(쉼표로 분리)] 형식으로 변환 후 업로드해주세요.');
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            alert('파일 크기는 5MB 이하만 가능합니다.');
            return;
        }

        selectedFile = file;
        document.getElementById('uploadPlaceholder').classList.add('hidden');
        document.getElementById('uploadFileInfo').classList.remove('hidden');
        document.getElementById('uploadFileName').textContent = file.name;
        document.getElementById('uploadFileSize').textContent = formatFileSize(file.size);
        document.getElementById('btnUpload').disabled = false;
    }

    document.getElementById('btnRemoveFile')?.addEventListener('click', (e) => {
        e.stopPropagation();
        resetFileInput();
    });

    function resetFileInput() {
        selectedFile = null;
        fileInput.value = '';
        document.getElementById('uploadPlaceholder').classList.remove('hidden');
        document.getElementById('uploadFileInfo').classList.add('hidden');
        document.getElementById('btnUpload').disabled = true;
    }

    // === 2단계: 업로드 버튼 → CSV 파싱 후 바로 서버 전송 ===
    document.getElementById('btnUpload').addEventListener('click', function() {
        if (!selectedFile) return;

        const btn = this;
        const reader = new FileReader();
        reader.onload = function(e) {
            const text = e.target.result;
            const employees = parseCSV(text);
            if (!employees) return;

            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> 업로드 중...';

            fetch(basePath + '/api/organization.php?action=bulkCreateEmployees', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ employees: employees })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(`${data.count || employees.length}명의 직원이 등록되었습니다.`);
                    location.href = basePath + '/pages/employees.php';
                } else {
                    alert('등록 실패: ' + (data.message || '알 수 없는 오류'));
                    btn.disabled = false;
                    btn.innerHTML = '<i data-lucide="upload" class="w-4 h-4"></i> 엑셀 업로드';
                    lucide.createIcons();
                }
            })
            .catch(err => {
                alert('서버 오류가 발생했습니다.');
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="upload" class="w-4 h-4"></i> 엑셀 업로드';
                lucide.createIcons();
            });
        };
        reader.readAsText(selectedFile, 'UTF-8');
    });

    function parseCSV(text) {
        const lines = text.replace(/^\uFEFF/, '').split('\n').filter(line => line.trim());
        if (lines.length < 2) {
            alert('데이터가 없습니다. 헤더 아래에 직원 정보를 입력해주세요.');
            return null;
        }

        const rows = lines.slice(1);
        const employees = [];

        rows.forEach((line, i) => {
            const cols = parseCSVLine(line);
            const name = (cols[0] || '').trim();
            if (!name) return;

            employees.push({
                name: name,
                affiliation: (cols[1] || '').trim(),
                department: (cols[2] || '').trim(),
                position: (cols[3] || '').trim(),
                employment_type: (cols[4] || '정규직').trim(),
                email: (cols[5] || '').trim(),
                phone: (cols[6] || '').trim(),
                join_date: (cols[7] || '').trim(),
            });
        });

        if (employees.length === 0) {
            alert('등록할 직원 데이터가 없습니다. 이름이 비어있는 행은 건너뜁니다.');
            return null;
        }

        return employees;
    }

    function parseCSVLine(line) {
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
                else if (ch === ',') { result.push(current); current = ''; }
                else { current += ch; }
            }
        }
        result.push(current);
        return result;
    }

    // === 유틸 ===
    function esc(str) {
        if (!str) return '';
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    lucide.createIcons();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
