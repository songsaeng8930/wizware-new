/**
 * Zaemit Rich Text Editor (TFEditor 이식본 + 표 기능 추가)
 * TaskFlow 에서 가져와 Zaemit 다크 테마 팔레트에 맞춰 재사용.
 *
 * 사용법:
 *   const editor = new TFEditor({
 *       container: '#editorContainer',
 *       placeholder: '내용을 입력하세요...',
 *       initialContent: '',
 *       showHint: true,
 *       onChange: (html) => console.log(html)
 *   });
 *   editor.getContent();
 *   editor.setContent(html);
 *   editor.isEmpty();
 *   editor.focus();
 *   editor.destroy();
 */

class TFEditor {
    constructor(options = {}) {
        this.options = {
            container: null,
            placeholder: '내용을 입력하세요...',
            initialContent: '',
            showHint: true,
            hintText: '💡 Ctrl+V로 클립보드 이미지를 바로 붙여넣을 수 있습니다',
            onChange: null,
            ...options
        };

        this.id = 'tf-editor-' + Math.random().toString(36).substring(2, 11);

        this.currentTextColor = '#e2e8f0';
        this.currentBgColor = 'transparent';
        this.lastEnterInQuote = false;

        this._undoStack = [];
        this._redoStack = [];

        this.selectedImage = null;
        this.isResizing = false;
        this.isRotating = false;
        this.resizeHandle = null;
        this.startX = 0;
        this.startY = 0;
        this.startWidth = 0;
        this.startHeight = 0;
        this.startRotation = 0;
        this.currentRotation = 0;

        if (typeof this.options.container === 'string') {
            this.container = document.querySelector(this.options.container);
        } else {
            this.container = this.options.container;
        }

        if (!this.container) {
            console.error('TFEditor: container not found');
            return;
        }

        this.init();
    }

    init() {
        this.render();
        this.bindEvents();

        if (this.options.initialContent) {
            setTimeout(() => {
                this.wrapExistingImages();
            }, 100);
        }
    }

    render() {
        const html = `
            <div class="tf-editor-container" id="${this.id}">
                <div class="tf-editor-toolbar">
                    <div class="tf-editor-toolbar-group">
                        <div class="tf-editor-dropdown">
                            <button type="button" class="tf-editor-btn tf-heading-btn" title="문단 스타일" style="min-width:64px;gap:4px">
                                <span class="tf-heading-label">본문</span>
                                <i data-lucide="chevron-down" class="tf-editor-icon tf-editor-icon-sm"></i>
                            </button>
                            <div class="tf-editor-dropdown-content">
                                <button type="button" class="tf-editor-dropdown-item" data-cmd="formatBlock" data-value="p">본문</button>
                                <button type="button" class="tf-editor-dropdown-item" data-cmd="formatBlock" data-value="h1" style="font-size:18px;font-weight:700">제목 1</button>
                                <button type="button" class="tf-editor-dropdown-item" data-cmd="formatBlock" data-value="h2" style="font-size:16px;font-weight:700">제목 2 (장)</button>
                                <button type="button" class="tf-editor-dropdown-item" data-cmd="formatBlock" data-value="h3" style="font-size:14px;font-weight:600">제목 3 (조문)</button>
                            </div>
                        </div>
                    </div>
                    <div class="tf-editor-toolbar-group">
                        <button type="button" class="tf-editor-btn" data-cmd="bold" title="굵게 (Ctrl+B)"><i data-lucide="bold" class="tf-editor-icon"></i></button>
                        <button type="button" class="tf-editor-btn" data-cmd="italic" title="기울임 (Ctrl+I)"><i data-lucide="italic" class="tf-editor-icon"></i></button>
                        <button type="button" class="tf-editor-btn" data-cmd="underline" title="밑줄 (Ctrl+U)"><i data-lucide="underline" class="tf-editor-icon"></i></button>
                        <button type="button" class="tf-editor-btn" data-cmd="strikeThrough" title="취소선"><i data-lucide="strikethrough" class="tf-editor-icon"></i></button>
                        <button type="button" class="tf-editor-btn" data-cmd="superscript" title="위첨자"><i data-lucide="superscript" class="tf-editor-icon"></i></button>
                        <button type="button" class="tf-editor-btn" data-cmd="subscript" title="아래첨자"><i data-lucide="subscript" class="tf-editor-icon"></i></button>
                    </div>
                    <div class="tf-editor-toolbar-group">
                        <div class="tf-editor-dropdown">
                            <button type="button" class="tf-editor-btn" title="글자 크기"><i data-lucide="type" class="tf-editor-icon"></i></button>
                            <div class="tf-editor-dropdown-content">
                                <button type="button" class="tf-editor-dropdown-item" data-cmd="fontSize" data-value="1">작게</button>
                                <button type="button" class="tf-editor-dropdown-item" data-cmd="fontSize" data-value="3">보통</button>
                                <button type="button" class="tf-editor-dropdown-item" data-cmd="fontSize" data-value="5">크게</button>
                                <button type="button" class="tf-editor-dropdown-item" data-cmd="fontSize" data-value="7">아주 크게</button>
                            </div>
                        </div>
                        <div class="tf-editor-dropdown">
                            <button type="button" class="tf-editor-btn tf-text-color-btn" title="글자 색상" style="font-weight: bold;"><span style="border-bottom: 3px solid #e2e8f0;">A</span></button>
                            <div class="tf-editor-dropdown-content">
                                <div class="tf-color-picker-grid tf-text-colors">
                                    <button type="button" class="tf-color-picker-item" style="background: #e2e8f0;" data-color="#e2e8f0"></button>
                                    <button type="button" class="tf-color-picker-item" style="background: #f43f5e;" data-color="#f43f5e"></button>
                                    <button type="button" class="tf-color-picker-item" style="background: #f97316;" data-color="#f97316"></button>
                                    <button type="button" class="tf-color-picker-item" style="background: #eab308;" data-color="#eab308"></button>
                                    <button type="button" class="tf-color-picker-item" style="background: #22c55e;" data-color="#22c55e"></button>
                                    <button type="button" class="tf-color-picker-item" style="background: #4F6AFF;" data-color="#4F6AFF"></button>
                                    <button type="button" class="tf-color-picker-item" style="background: #a855f7;" data-color="#a855f7"></button>
                                    <button type="button" class="tf-color-picker-item" style="background: #ec4899;" data-color="#ec4899"></button>
                                    <button type="button" class="tf-color-picker-item" style="background: #94a3b8;" data-color="#94a3b8"></button>
                                    <button type="button" class="tf-color-picker-item" style="background: #64748b;" data-color="#64748b"></button>
                                    <button type="button" class="tf-color-picker-item" style="background: #475569;" data-color="#475569"></button>
                                    <button type="button" class="tf-color-picker-item" style="background: #334155;" data-color="#334155"></button>
                                </div>
                            </div>
                        </div>
                        <div class="tf-editor-dropdown">
                            <button type="button" class="tf-editor-btn tf-bg-color-btn" title="배경 색상" style="font-weight: bold;"><span class="tf-bg-color-box" style="background: transparent; padding: 1px 4px; border: 1px dashed #64748b; border-radius: 2px;">A</span></button>
                            <div class="tf-editor-dropdown-content">
                                <div class="tf-color-picker-grid tf-bg-colors">
                                    <button type="button" class="tf-color-picker-item" style="background: transparent; border: 1px dashed #64748b;" data-color="transparent" title="없음"></button>
                                    <button type="button" class="tf-color-picker-item" style="background: rgba(244, 63, 94, 0.3);" data-color="rgba(244, 63, 94, 0.3)"></button>
                                    <button type="button" class="tf-color-picker-item" style="background: rgba(249, 115, 22, 0.3);" data-color="rgba(249, 115, 22, 0.3)"></button>
                                    <button type="button" class="tf-color-picker-item" style="background: rgba(234, 179, 8, 0.3);" data-color="rgba(234, 179, 8, 0.3)"></button>
                                    <button type="button" class="tf-color-picker-item" style="background: rgba(34, 197, 94, 0.3);" data-color="rgba(34, 197, 94, 0.3)"></button>
                                    <button type="button" class="tf-color-picker-item" style="background: rgba(79, 106, 255, 0.3);" data-color="rgba(79, 106, 255, 0.3)"></button>
                                    <button type="button" class="tf-color-picker-item" style="background: rgba(168, 85, 247, 0.3);" data-color="rgba(168, 85, 247, 0.3)"></button>
                                    <button type="button" class="tf-color-picker-item" style="background: rgba(236, 72, 153, 0.3);" data-color="rgba(236, 72, 153, 0.3)"></button>
                                    <button type="button" class="tf-color-picker-item" style="background: rgba(148, 163, 184, 0.3);" data-color="rgba(148, 163, 184, 0.3)"></button>
                                    <button type="button" class="tf-color-picker-item" style="background: rgba(100, 116, 139, 0.5);" data-color="rgba(100, 116, 139, 0.5)"></button>
                                    <button type="button" class="tf-color-picker-item" style="background: rgba(71, 85, 105, 0.5);" data-color="rgba(71, 85, 105, 0.5)"></button>
                                    <button type="button" class="tf-color-picker-item" style="background: rgba(51, 65, 85, 0.5);" data-color="rgba(51, 65, 85, 0.5)"></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tf-editor-toolbar-group">
                        <button type="button" class="tf-editor-btn" data-cmd="justifyLeft" title="왼쪽 정렬"><i data-lucide="align-left" class="tf-editor-icon"></i></button>
                        <button type="button" class="tf-editor-btn" data-cmd="justifyCenter" title="가운데 정렬"><i data-lucide="align-center" class="tf-editor-icon"></i></button>
                        <button type="button" class="tf-editor-btn" data-cmd="justifyRight" title="오른쪽 정렬"><i data-lucide="align-right" class="tf-editor-icon"></i></button>
                        <button type="button" class="tf-editor-btn" data-cmd="justifyFull" title="양쪽 맞춤"><i data-lucide="align-justify" class="tf-editor-icon"></i></button>
                    </div>
                    <div class="tf-editor-toolbar-group">
                        <button type="button" class="tf-editor-btn" data-cmd="insertUnorderedList" title="글머리 기호"><i data-lucide="list" class="tf-editor-icon"></i></button>
                        <button type="button" class="tf-editor-btn" data-cmd="insertOrderedList" title="번호 매기기"><i data-lucide="list-ordered" class="tf-editor-icon"></i></button>
                        <button type="button" class="tf-editor-btn" data-cmd="outdent" title="내어쓰기"><i data-lucide="list-indent-decrease" class="tf-editor-icon"></i></button>
                        <button type="button" class="tf-editor-btn" data-cmd="indent" title="들여쓰기"><i data-lucide="list-indent-increase" class="tf-editor-icon"></i></button>
                    </div>
                    <div class="tf-editor-toolbar-group">
                        <button type="button" class="tf-editor-btn tf-insert-link" title="링크 삽입"><i data-lucide="link-2" class="tf-editor-icon"></i></button>
                        <button type="button" class="tf-editor-btn tf-insert-image" title="이미지 업로드"><i data-lucide="image" class="tf-editor-icon"></i></button>
                        <div class="tf-editor-dropdown tf-table-dropdown">
                            <button type="button" class="tf-editor-btn" title="표 삽입"><i data-lucide="table-2" class="tf-editor-icon"></i></button>
                            <div class="tf-editor-dropdown-content tf-table-picker">
                                <div class="tf-table-grid-label">표 크기를 선택하세요</div>
                                <div class="tf-table-grid" data-rows="10" data-cols="10"></div>
                                <div class="tf-table-grid-info">0 × 0</div>
                            </div>
                        </div>
                        ${this.options.showVariableButton !== false ? `
                        <button type="button" class="tf-editor-btn tf-insert-var" title="값 필드 (계약서 작성 시 채울 자리)" style="font-weight:600;color:var(--tf-accent)">{ }</button>
                        ` : ''}
                    </div>
                    <div class="tf-editor-toolbar-group">
                        <div class="tf-editor-dropdown">
                            <button type="button" class="tf-editor-btn" title="인용구"><i data-lucide="quote" class="tf-editor-icon"></i></button>
                            <div class="tf-editor-dropdown-content">
                                <button type="button" class="tf-editor-dropdown-item" data-cmd="formatBlock" data-value="blockquote">│ 왼쪽 선</button>
                                <button type="button" class="tf-editor-dropdown-item tf-quote-box">" 큰따옴표</button>
                                <button type="button" class="tf-editor-dropdown-item tf-quote-single">' 작은따옴표</button>
                            </div>
                        </div>
                        <button type="button" class="tf-editor-btn tf-insert-hr" title="구분선"><i data-lucide="minus" class="tf-editor-icon"></i></button>
                        <button type="button" class="tf-editor-btn tf-insert-code" title="코드 블록"><i data-lucide="code-2" class="tf-editor-icon"></i></button>
                        <button type="button" class="tf-editor-btn" data-cmd="removeFormat" title="서식 지우기"><i data-lucide="eraser" class="tf-editor-icon"></i></button>
                    </div>
                    <div class="tf-editor-toolbar-group">
                        <button type="button" class="tf-editor-btn" data-cmd="undo" title="실행 취소 (Ctrl+Z)"><i data-lucide="undo-2" class="tf-editor-icon"></i></button>
                        <button type="button" class="tf-editor-btn" data-cmd="redo" title="다시 실행 (Ctrl+Shift+Z)"><i data-lucide="redo-2" class="tf-editor-icon"></i></button>
                    </div>
                </div>
                <div class="tf-editor-content" contenteditable="true" data-placeholder="${this.options.placeholder}">${this.options.initialContent}</div>
                ${this.options.showHint ? `<div class="tf-editor-hint">${this.options.hintText}</div>` : ''}
                <input type="file" class="tf-image-input" accept="image/*" style="display: none;">
            </div>
        `;

        this.container.innerHTML = html;
        this.editorEl = this.container.querySelector('.tf-editor-content');
        this.imageInput = this.container.querySelector('.tf-image-input');
        this.buildTablePicker();
        if (window.lucide) lucide.createIcons();
    }

    /* ─────────── 표 크기 선택 그리드 ─────────── */
    buildTablePicker() {
        const grid = this.container.querySelector('.tf-table-grid');
        const info = this.container.querySelector('.tf-table-grid-info');
        if (!grid) return;
        const rows = parseInt(grid.dataset.rows, 10) || 10;
        const cols = parseInt(grid.dataset.cols, 10) || 10;
        let html = '';
        for (let r = 1; r <= rows; r++) {
            for (let c = 1; c <= cols; c++) {
                html += `<div class="tf-table-cell" data-r="${r}" data-c="${c}"></div>`;
            }
        }
        grid.innerHTML = html;
        grid.style.gridTemplateColumns = `repeat(${cols}, 1fr)`;

        grid.addEventListener('mousemove', (e) => {
            const cell = e.target.closest('.tf-table-cell');
            if (!cell) return;
            const r = parseInt(cell.dataset.r, 10);
            const c = parseInt(cell.dataset.c, 10);
            grid.querySelectorAll('.tf-table-cell').forEach(el => {
                const cr = parseInt(el.dataset.r, 10);
                const cc = parseInt(el.dataset.c, 10);
                el.classList.toggle('hovered', cr <= r && cc <= c);
            });
            if (info) info.textContent = `${r} × ${c}`;
        });
        grid.addEventListener('mouseleave', () => {
            grid.querySelectorAll('.tf-table-cell.hovered').forEach(el => el.classList.remove('hovered'));
            if (info) info.textContent = '0 × 0';
        });
        grid.addEventListener('click', (e) => {
            const cell = e.target.closest('.tf-table-cell');
            if (!cell) return;
            const r = parseInt(cell.dataset.r, 10);
            const c = parseInt(cell.dataset.c, 10);
            this.insertTable(r, c);
            const dropdown = grid.closest('.tf-editor-dropdown');
            if (dropdown) {
                // 드롭다운 숨기기 (hover 해제)
                dropdown.blur();
                document.activeElement?.blur();
            }
        });
    }

    insertTable(rows, cols) {
        rows = Math.max(1, Math.min(20, rows));
        cols = Math.max(1, Math.min(10, cols));
        let html = '<table class="tf-editor-table"><thead><tr>';
        for (let c = 0; c < cols; c++) html += '<th>제목</th>';
        html += '</tr></thead><tbody>';
        for (let r = 0; r < rows - 1; r++) {
            html += '<tr>';
            for (let c = 0; c < cols; c++) html += '<td>&nbsp;</td>';
            html += '</tr>';
        }
        html += '</tbody></table><p><br></p>';
        this.insertHtml(html);
    }

    bindEvents() {
        const container = document.getElementById(this.id);
        if (!container) return;

        const toolbar = container.querySelector('.tf-editor-toolbar');
        if (toolbar) toolbar.addEventListener('mousedown', (e) => {
            if (e.target.closest('button, .tf-color-picker-item, select')) e.preventDefault();
        });

        container.querySelectorAll('[data-cmd]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const cmd = btn.dataset.cmd;
                const value = btn.dataset.value || null;
                this.execCmd(cmd, value);
            });
        });

        container.querySelectorAll('.tf-text-colors .tf-color-picker-item').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.setTextColor(btn.dataset.color);
            });
        });

        container.querySelectorAll('.tf-bg-colors .tf-color-picker-item').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.setBgColor(btn.dataset.color);
            });
        });

        container.querySelector('.tf-insert-link').addEventListener('click', (e) => {
            e.preventDefault();
            this.insertLink();
        });

        container.querySelector('.tf-insert-image').addEventListener('click', (e) => {
            e.preventDefault();
            this.imageInput.click();
        });

        this.imageInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                this.insertImageFromFile(file);
            }
            e.target.value = '';
        });

        const quoteBox = container.querySelector('.tf-quote-box');
        if (quoteBox) quoteBox.addEventListener('click', (e) => { e.preventDefault(); this.insertQuoteBox(); });
        const quoteSingle = container.querySelector('.tf-quote-single');
        if (quoteSingle) quoteSingle.addEventListener('click', (e) => { e.preventDefault(); this.insertQuoteSingle(); });

        container.querySelector('.tf-insert-hr').addEventListener('click', (e) => { e.preventDefault(); this.insertHorizontalRule(); });
        container.querySelector('.tf-insert-code').addEventListener('click', (e) => { e.preventDefault(); this.insertCodeBlock(); });
        const varBtn = container.querySelector('.tf-insert-var');
        if (varBtn) varBtn.addEventListener('click', (e) => { e.preventDefault(); this.insertVariable(); });

        this.editorEl.addEventListener('paste', (e) => this.handlePaste(e));
        this.editorEl.addEventListener('keydown', (e) => this.handleKeydown(e));
        this.editorEl.addEventListener('click', () => this.updateToolbarColors());
        this.editorEl.addEventListener('keyup', () => this.updateToolbarColors());

        this._initTableEditing();

        document.addEventListener('selectionchange', () => {
            if (document.activeElement === this.editorEl) {
                this.updateToolbarColors();
            }
        });

        if (this.options.onChange) {
            this.editorEl.addEventListener('input', () => {
                this.options.onChange(this.getContent());
            });
        }
    }

    execCmd(command, value = null) {
        if (command === 'undo') { if (this._undo()) return; }
        if (command === 'redo') { if (this._redo()) return; }
        document.execCommand(command, false, value);
        this.editorEl.focus();
    }

    _saveUndo(table) {
        if (table) {
            const idx = Array.from(this.editorEl.querySelectorAll('table')).indexOf(table);
            this._undoStack.push({ idx, inner: table.innerHTML, style: table.getAttribute('style') || '' });
        } else {
            this._undoStack.push({ idx: -1, html: this.editorEl.innerHTML });
        }
        if (this._undoStack.length > 20) this._undoStack.shift();
        this._redoStack = [];
    }

    _restoreEntry(entry, saveToStack) {
        if (entry.idx === -1) {
            saveToStack.push({ idx: -1, html: this.editorEl.innerHTML });
            this.editorEl.innerHTML = entry.html;
        } else {
            const table = this.editorEl.querySelectorAll('table')[entry.idx];
            if (!table) return false;
            saveToStack.push({ idx: entry.idx, inner: table.innerHTML, style: table.getAttribute('style') || '' });
            table.innerHTML = entry.inner;
            if (entry.style) table.setAttribute('style', entry.style);
            else table.removeAttribute('style');
        }
        this.editorEl.focus();
        return true;
    }

    _undo() {
        if (!this._undoStack.length) return false;
        return this._restoreEntry(this._undoStack.pop(), this._redoStack);
    }

    _redo() {
        if (!this._redoStack.length) return false;
        return this._restoreEntry(this._redoStack.pop(), this._undoStack);
    }

    setTextColor(color) {
        this.currentTextColor = color;
        document.execCommand('foreColor', false, color);
        const btn = document.getElementById(this.id)?.querySelector('.tf-text-color-btn');
        if (btn) btn.innerHTML = `<span style="border-bottom: 3px solid ${color};">A</span>`;
        this.editorEl.focus();
    }

    setBgColor(color) {
        this.currentBgColor = color;
        document.execCommand('hiliteColor', false, color);
        const box = document.getElementById(this.id)?.querySelector('.tf-bg-color-box');
        if (box) {
            if (color === 'transparent') {
                box.style.background = 'transparent';
                box.style.border = '1px dashed #64748b';
            } else {
                box.style.background = color;
                box.style.border = 'none';
            }
        }
        this.editorEl.focus();
    }

    updateToolbarColors() {
        const selection = window.getSelection();
        if (!selection.rangeCount) return;
        const range = selection.getRangeAt(0);
        let node = range.startContainer;
        if (node.nodeType === 3) node = node.parentElement;
        if (!node) return;
        const computedStyle = window.getComputedStyle(node);
        const textColor = computedStyle.color;
        const bgColor = computedStyle.backgroundColor;
        const textColorHex = this.rgbToHex(textColor);
        this.currentTextColor = textColorHex || '#e2e8f0';
        const textBtn = document.getElementById(this.id)?.querySelector('.tf-text-color-btn');
        if (textBtn) textBtn.innerHTML = `<span style="border-bottom: 3px solid ${this.currentTextColor};">A</span>`;

        const bgBox = document.getElementById(this.id)?.querySelector('.tf-bg-color-box');
        if (bgBox) {
            if (bgColor === 'rgba(0, 0, 0, 0)' || bgColor === 'transparent') {
                this.currentBgColor = 'transparent';
                bgBox.style.background = 'transparent';
                bgBox.style.border = '1px dashed #64748b';
            } else {
                this.currentBgColor = bgColor;
                bgBox.style.background = bgColor;
                bgBox.style.border = 'none';
            }
        }
    }

    rgbToHex(rgb) {
        if (!rgb) return null;
        const match = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
        if (!match) return rgb;
        const r = parseInt(match[1]);
        const g = parseInt(match[2]);
        const b = parseInt(match[3]);
        return '#' + [r, g, b].map(x => {
            const hex = x.toString(16);
            return hex.length === 1 ? '0' + hex : hex;
        }).join('');
    }

    handleKeydown(e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z') {
            if (e.shiftKey) { if (this._redo()) { e.preventDefault(); return; } }
            else { if (this._undo()) { e.preventDefault(); return; } }
        }
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'y') {
            if (this._redo()) { e.preventDefault(); return; }
        }
        if (e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
            const selection = window.getSelection();
            if (selection.isCollapsed) {
                if (this.currentTextColor && this.currentTextColor !== '#e2e8f0') {
                    document.execCommand('foreColor', false, this.currentTextColor);
                }
                if (this.currentBgColor && this.currentBgColor !== 'transparent') {
                    document.execCommand('hiliteColor', false, this.currentBgColor);
                }
            }
        }
        if (e.key === 'Enter') {
            const selection = window.getSelection();
            if (!selection.rangeCount) return;
            const range = selection.getRangeAt(0);
            let node = range.startContainer;
            while (node && node !== this.editorEl) {
                if (node.nodeType === 1) {
                    const tagName = node.tagName.toLowerCase();
                    const className = node.className || '';
                    if (tagName === 'blockquote' || className.includes('quote-box') || className.includes('quote-single')) {
                        const textContent = range.startContainer.textContent || '';
                        const isEmpty = textContent.trim() === '' || textContent === '\n';
                        if (this.lastEnterInQuote && isEmpty) {
                            e.preventDefault();
                            const p = document.createElement('p');
                            p.innerHTML = '<br>';
                            node.parentNode.insertBefore(p, node.nextSibling);
                            const newRange = document.createRange();
                            newRange.setStart(p, 0);
                            newRange.collapse(true);
                            selection.removeAllRanges();
                            selection.addRange(newRange);
                            this.lastEnterInQuote = false;
                            return;
                        }
                        this.lastEnterInQuote = true;
                        return;
                    }
                }
                node = node.parentNode;
            }
            this.lastEnterInQuote = false;
        }
    }

    handlePaste(e) {
        const items = e.clipboardData?.items;
        if (!items) return;
        for (const item of items) {
            if (item.type.startsWith('image/')) {
                e.preventDefault();
                const file = item.getAsFile();
                if (file) this.insertImageFromFile(file);
                break;
            }
        }
        setTimeout(() => { this.wrapExistingImages(); }, 100);
    }

    insertLink() {
        const url = prompt('링크 URL을 입력하세요:', 'https://');
        if (url) this.execCmd('createLink', url);
    }

    insertImageFromFile(file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            const wrapperHtml = this.createImageWrapper(e.target.result);
            this.insertHtml(wrapperHtml + '<br>');
            this.bindImageEvents();
        };
        reader.readAsDataURL(file);
    }

    createImageWrapper(src, rotation = 0) {
        const id = 'tf-img-' + Math.random().toString(36).substring(2, 11);
        return `
            <span class="tf-image-wrapper" contenteditable="false" data-rotation="${rotation}" data-img-id="${id}">
                <img src="${src}" style="max-width: 100%; transform: rotate(${rotation}deg);">
                <span class="tf-rotate-line"></span>
                <span class="tf-rotate-handle" title="드래그하여 회전"></span>
                <span class="tf-resize-handle tf-nw"></span>
                <span class="tf-resize-handle tf-n"></span>
                <span class="tf-resize-handle tf-ne"></span>
                <span class="tf-resize-handle tf-w"></span>
                <span class="tf-resize-handle tf-e"></span>
                <span class="tf-resize-handle tf-sw"></span>
                <span class="tf-resize-handle tf-s"></span>
                <span class="tf-resize-handle tf-se"></span>
                <span class="tf-image-size-info"></span>
                <span class="tf-image-actions">
                    <button type="button" class="tf-image-action-btn reset" title="초기화">↺</button>
                    <button type="button" class="tf-image-action-btn delete" title="삭제">✕</button>
                </span>
            </span>
        `.trim();
    }

    bindImageEvents() {
        const container = document.getElementById(this.id);
        if (!container) return;
        container.querySelectorAll('.tf-image-wrapper').forEach(wrapper => {
            if (wrapper.dataset.bound) return;
            wrapper.dataset.bound = 'true';
            const img = wrapper.querySelector('img');
            const sizeInfo = wrapper.querySelector('.tf-image-size-info');
            const updateSizeInfo = () => {
                if (sizeInfo && img) sizeInfo.textContent = `${Math.round(img.offsetWidth)} × ${Math.round(img.offsetHeight)}`;
            };
            wrapper.setAttribute('draggable', 'false');
            img.setAttribute('draggable', 'false');
            wrapper.addEventListener('dragstart', (e) => { e.preventDefault(); return false; });
            wrapper.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); this.selectImage(wrapper); });
            wrapper.querySelectorAll('.tf-resize-handle').forEach(handle => {
                handle.addEventListener('mousedown', (e) => { e.preventDefault(); e.stopPropagation(); this.startResize(e, wrapper, handle); });
            });
            const rotateHandle = wrapper.querySelector('.tf-rotate-handle');
            if (rotateHandle) rotateHandle.addEventListener('mousedown', (e) => { e.preventDefault(); e.stopPropagation(); this.startRotate(e, wrapper); });
            const resetBtn = wrapper.querySelector('.tf-image-action-btn.reset');
            if (resetBtn) resetBtn.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); this.resetImage(wrapper); });
            const deleteBtn = wrapper.querySelector('.tf-image-action-btn.delete');
            if (deleteBtn) deleteBtn.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); this.deleteImage(wrapper); });
            img.onload = updateSizeInfo;
            updateSizeInfo();
        });
        if (!this._docClickBound) {
            this._docClickBound = true;
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.tf-image-wrapper') && this.selectedImage) this.deselectImage();
            });
            document.addEventListener('mousemove', (e) => this.handleMouseMove(e));
            document.addEventListener('mouseup', () => this.handleMouseUp());
        }
    }

    selectImage(wrapper) {
        this.deselectImage();
        wrapper.classList.add('selected');
        this.selectedImage = wrapper;
        const img = wrapper.querySelector('img');
        const sizeInfo = wrapper.querySelector('.tf-image-size-info');
        if (sizeInfo && img) sizeInfo.textContent = `${Math.round(img.offsetWidth)} × ${Math.round(img.offsetHeight)}`;
    }

    deselectImage() {
        if (this.selectedImage) {
            this.selectedImage.classList.remove('selected');
            this.selectedImage = null;
        }
        document.querySelectorAll('.tf-image-wrapper.selected').forEach(w => w.classList.remove('selected'));
    }

    startResize(e, wrapper, handle) {
        this.isResizing = true;
        this.resizeHandle = handle;
        this.startX = e.clientX;
        this.startY = e.clientY;
        const img = wrapper.querySelector('img');
        this.startWidth = img.offsetWidth;
        this.startHeight = img.offsetHeight;
        this.resizingWrapper = wrapper;
        wrapper.classList.add('resizing');
    }

    startRotate(e, wrapper) {
        this.isRotating = true;
        this.rotatingWrapper = wrapper;
        const rect = wrapper.getBoundingClientRect();
        this.rotateCenter = { x: rect.left + rect.width / 2, y: rect.top + rect.height / 2 };
        const currentRotation = parseFloat(wrapper.dataset.rotation) || 0;
        this.startRotation = currentRotation;
        this.startAngle = Math.atan2(e.clientY - this.rotateCenter.y, e.clientX - this.rotateCenter.x) * (180 / Math.PI);
        wrapper.classList.add('rotating');
    }

    handleMouseMove(e) {
        if (this.isResizing && this.resizingWrapper) this.doResize(e);
        if (this.isRotating && this.rotatingWrapper) this.doRotate(e);
    }

    handleMouseUp() {
        if (this.isResizing) {
            this.isResizing = false;
            if (this.resizingWrapper) this.resizingWrapper.classList.remove('resizing');
            this.resizingWrapper = null;
            this.resizeHandle = null;
        }
        if (this.isRotating) {
            this.isRotating = false;
            if (this.rotatingWrapper) this.rotatingWrapper.classList.remove('rotating');
            this.rotatingWrapper = null;
        }
    }

    doResize(e) {
        const wrapper = this.resizingWrapper;
        const img = wrapper.querySelector('img');
        const handle = this.resizeHandle;
        const sizeInfo = wrapper.querySelector('.tf-image-size-info');
        const deltaX = e.clientX - this.startX;
        const deltaY = e.clientY - this.startY;
        let newWidth = this.startWidth;
        let newHeight = this.startHeight;
        const aspectRatio = this.startWidth / this.startHeight;
        if (handle.classList.contains('tf-se'))      { newWidth = this.startWidth + deltaX; newHeight = newWidth / aspectRatio; }
        else if (handle.classList.contains('tf-sw')) { newWidth = this.startWidth - deltaX; newHeight = newWidth / aspectRatio; }
        else if (handle.classList.contains('tf-ne')) { newWidth = this.startWidth + deltaX; newHeight = newWidth / aspectRatio; }
        else if (handle.classList.contains('tf-nw')) { newWidth = this.startWidth - deltaX; newHeight = newWidth / aspectRatio; }
        else if (handle.classList.contains('tf-e'))  { newWidth = this.startWidth + deltaX; newHeight = newWidth / aspectRatio; }
        else if (handle.classList.contains('tf-w'))  { newWidth = this.startWidth - deltaX; newHeight = newWidth / aspectRatio; }
        else if (handle.classList.contains('tf-s'))  { newHeight = this.startHeight + deltaY; newWidth = newHeight * aspectRatio; }
        else if (handle.classList.contains('tf-n'))  { newHeight = this.startHeight - deltaY; newWidth = newHeight * aspectRatio; }
        if (newWidth >= 50 && newHeight >= 50) {
            img.style.width = newWidth + 'px';
            img.style.height = newHeight + 'px';
            img.style.maxWidth = 'none';
            if (sizeInfo) sizeInfo.textContent = `${Math.round(newWidth)} × ${Math.round(newHeight)}`;
        }
    }

    doRotate(e) {
        const wrapper = this.rotatingWrapper;
        const img = wrapper.querySelector('img');
        const currentAngle = Math.atan2(e.clientY - this.rotateCenter.y, e.clientX - this.rotateCenter.x) * (180 / Math.PI);
        let rotation = this.startRotation + (currentAngle - this.startAngle);
        if (e.shiftKey) rotation = Math.round(rotation / 15) * 15;
        wrapper.dataset.rotation = rotation;
        img.style.transform = `rotate(${rotation}deg)`;
    }

    resetImage(wrapper) {
        const img = wrapper.querySelector('img');
        const sizeInfo = wrapper.querySelector('.tf-image-size-info');
        img.style.width = '';
        img.style.height = '';
        img.style.maxWidth = '100%';
        wrapper.dataset.rotation = '0';
        img.style.transform = 'rotate(0deg)';
        setTimeout(() => {
            if (sizeInfo && img) sizeInfo.textContent = `${Math.round(img.offsetWidth)} × ${Math.round(img.offsetHeight)}`;
        }, 50);
    }

    deleteImage(wrapper) {
        if (confirm('이미지를 삭제하시겠습니까?')) {
            wrapper.remove();
            this.selectedImage = null;
        }
    }

    wrapExistingImages() {
        const container = document.getElementById(this.id);
        if (!container) return;
        container.querySelectorAll('.tf-editor-content img').forEach(img => {
            if (img.closest('.tf-image-wrapper')) return;
            const src = img.src;
            const wrapperHtml = this.createImageWrapper(src);
            const temp = document.createElement('div');
            temp.innerHTML = wrapperHtml;
            const wrapper = temp.firstElementChild;
            if (img.style.width) wrapper.querySelector('img').style.width = img.style.width;
            if (img.style.height) wrapper.querySelector('img').style.height = img.style.height;
            img.replaceWith(wrapper);
        });
        container.querySelectorAll('.tf-image-wrapper').forEach(wrapper => {
            if (wrapper.dataset.bound) return;
            wrapper.dataset.imgId = 'tf-img-' + Math.random().toString(36).substring(2, 11);
        });
        this.bindImageEvents();
    }

    insertQuoteBox() {
        const text = window.getSelection().toString() || '인용구를 입력하세요';
        this.insertHtml(`<div class="quote-box">${text}</div><br>`);
    }

    insertQuoteSingle() {
        const text = window.getSelection().toString() || '인용구를 입력하세요';
        this.insertHtml(`<div class="quote-single">${text}</div><br>`);
    }

    insertHorizontalRule() { this.insertHtml('<hr><br>'); }

    /* ─────────── 값 필드(변수) 삽입 ───────────
     * 계약서 양식에서 "채워 넣을 자리"를 표시. 실제 계약서 작성 화면에서는
     * 이 span 안의 값만 편집 가능하도록 운용된다.
     *   <span class="tf-var" data-var-name="급여" data-var-placeholder="금액 입력">(금액 입력)</span>
     */
    insertVariable() {
        const selection = window.getSelection();
        const hasSelection = selection && selection.rangeCount > 0 && !selection.isCollapsed
                             && this.editorEl.contains(selection.anchorNode);
        const selectedText = hasSelection ? selection.toString().trim() : '';

        const label = prompt('값 필드 이름을 입력하세요 (예: 급여, 계약기간, 근무장소)', selectedText || '항목명');
        if (!label) return;
        const placeholder = prompt('기본 안내 문구 (계약 작성 시 빈 상태로 표시될 자리)', selectedText || label + ' 입력');
        if (placeholder === null) return;

        const safeLabel = label.replace(/[<>"&]/g, '').trim();
        const safePh    = (placeholder || label).replace(/[<>"&]/g, '').trim();
        const varHtml = `<span class="tf-var" data-var-name="${safeLabel}" data-var-placeholder="${safePh}" contenteditable="true">${safePh}</span>`;

        if (hasSelection) {
            // 선택 영역을 값 필드로 감싸기
            const range = selection.getRangeAt(0);
            range.deleteContents();
            const temp = document.createElement('div');
            temp.innerHTML = varHtml + '&nbsp;';
            const frag = document.createDocumentFragment();
            let node, lastNode;
            while ((node = temp.firstChild)) lastNode = frag.appendChild(node);
            range.insertNode(frag);
            if (lastNode) {
                const newRange = document.createRange();
                newRange.setStartAfter(lastNode);
                newRange.collapse(true);
                selection.removeAllRanges();
                selection.addRange(newRange);
            }
        } else {
            this.insertHtml(varHtml + '&nbsp;');
        }
        if (this.options.onChange) this.options.onChange(this.getContent());
    }

    insertCodeBlock() {
        const code = prompt('코드를 입력하세요:');
        if (code) {
            const pre = document.createElement('pre');
            pre.textContent = code;
            this.insertHtml(pre.outerHTML + '<br>');
        }
    }

    insertHtml(html) {
        this.editorEl.focus();
        const selection = window.getSelection();
        if (selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            range.deleteContents();
            const temp = document.createElement('div');
            temp.innerHTML = html;
            const frag = document.createDocumentFragment();
            let node, lastNode;
            while ((node = temp.firstChild)) { lastNode = frag.appendChild(node); }
            range.insertNode(frag);
            if (lastNode) {
                const newRange = document.createRange();
                newRange.setStartAfter(lastNode);
                newRange.collapse(true);
                selection.removeAllRanges();
                selection.addRange(newRange);
            }
        } else {
            // 선택 영역이 없으면 맨 끝에 삽입
            this.editorEl.insertAdjacentHTML('beforeend', html);
        }
        if (this.options.onChange) this.options.onChange(this.getContent());
    }

    /* ─────────── 공개 API ─────────── */
    getContent() {
        const clone = this.editorEl.cloneNode(true);
        clone.querySelectorAll('.tf-image-wrapper').forEach(wrapper => {
            const img = wrapper.querySelector('img');
            if (img) {
                const newImg = document.createElement('img');
                newImg.src = img.src;
                if (img.style.width) newImg.style.width = img.style.width;
                if (img.style.height) newImg.style.height = img.style.height;
                if (img.style.transform) newImg.style.transform = img.style.transform;
                newImg.style.maxWidth = img.style.maxWidth || '100%';
                wrapper.replaceWith(newImg);
            }
        });
        return clone.innerHTML.trim();
    }

    setContent(html) {
        this.editorEl.innerHTML = html;
        setTimeout(() => this.wrapExistingImages(), 50);
    }

    isEmpty() {
        const textContent = this.editorEl.textContent.trim();
        const hasImages = this.editorEl.querySelector('img');
        const hasTables = this.editorEl.querySelector('table');
        return !textContent && !hasImages && !hasTables;
    }

    focus() { this.editorEl.focus(); }

    /* ═══════════════════════════════════════════════════════════
       표 편집 · 셀 선택 · 컨텍스트 메뉴 · 병합/분할 · 행/열 추가·삭제
       ═══════════════════════════════════════════════════════════ */

    _initTableEditing() {
        this._tblSel = [];
        this._tblSelecting = false;
        this._tblAnchor = null;

        this.editorEl.addEventListener('mousedown', (e) => this._tblMouseDown(e));
        this.editorEl.addEventListener('mouseover', (e) => this._tblMouseOver(e));
        document.addEventListener('mouseup', () => this._tblMouseUp());

        this.editorEl.addEventListener('contextmenu', (e) => {
            const cell = e.target.closest('td, th');
            if (cell && this.editorEl.contains(cell)) {
                e.preventDefault();
                this._showTableMenu(e.clientX, e.clientY, cell);
            }
        });

        document.addEventListener('click', (e) => {
            const menu = document.getElementById('tf-table-ctx');
            if (menu && !menu.contains(e.target)) menu.remove();
        });
    }

    _cellPos(cell) {
        const table = cell.closest('table');
        if (!table) return null;
        const grid = this._buildGrid(table);
        for (let r = 0; r < grid.length; r++)
            for (let c = 0; c < grid[r].length; c++)
                if (grid[r][c] === cell) return { r, c, table, grid };
        return null;
    }

    _buildGrid(table) {
        const rows = Array.from(table.rows);
        const maxCols = rows.reduce((mx, row) => {
            let w = 0;
            for (const c of row.cells) w += (c.colSpan || 1);
            return Math.max(mx, w);
        }, 0);
        const grid = rows.map(() => new Array(maxCols).fill(null));
        rows.forEach((row, ri) => {
            let ci = 0;
            for (const cell of row.cells) {
                while (grid[ri][ci]) ci++;
                const rs = cell.rowSpan || 1, cs = cell.colSpan || 1;
                for (let dr = 0; dr < rs; dr++)
                    for (let dc = 0; dc < cs; dc++)
                        if (grid[ri + dr]) grid[ri + dr][ci + dc] = cell;
                ci += cs;
            }
        });
        return grid;
    }

    _tblMouseDown(e) {
        const cell = e.target.closest('td, th');
        if (!cell || !this.editorEl.contains(cell)) { this._clearSel(); return; }
        if (e.button !== 0) return;
        this._clearSel();
        this._tblSelecting = true;
        this._tblAnchor = cell;
        this._tblDragged = false;
        this._addSel(cell);
    }

    _tblMouseOver(e) {
        if (!this._tblSelecting) return;
        const cell = e.target.closest('td, th');
        if (!cell || !this.editorEl.contains(cell)) return;
        const table = cell.closest('table');
        const anchorTable = this._tblAnchor?.closest('table');
        if (table !== anchorTable) return;
        if (cell !== this._tblAnchor && !this._tblDragged) {
            this._tblDragged = true;
            this.editorEl.classList.add('tf-selecting');
            window.getSelection()?.removeAllRanges();
        }
        if (this._tblDragged) {
            e.preventDefault();
            window.getSelection()?.removeAllRanges();
            this._selectRange(this._tblAnchor, cell);
        }
    }

    _tblMouseUp() {
        this._tblSelecting = false;
        this.editorEl.classList.remove('tf-selecting');
    }

    _selectRange(a, b) {
        const posA = this._cellPos(a), posB = this._cellPos(b);
        if (!posA || !posB || posA.table !== posB.table) return;
        const grid = posA.grid;
        let r1 = Math.min(posA.r, posB.r), r2 = Math.max(posA.r, posB.r);
        let c1 = Math.min(posA.c, posB.c), c2 = Math.max(posA.c, posB.c);
        let changed = true;
        while (changed) {
            changed = false;
            for (let r = r1; r <= r2; r++) {
                for (let c = c1; c <= c2; c++) {
                    const cell = grid[r]?.[c];
                    if (!cell) continue;
                    const p = this._cellPos(cell);
                    if (!p) continue;
                    const rs = cell.rowSpan || 1, cs = cell.colSpan || 1;
                    if (p.r < r1) { r1 = p.r; changed = true; }
                    if (p.r + rs - 1 > r2) { r2 = p.r + rs - 1; changed = true; }
                    if (p.c < c1) { c1 = p.c; changed = true; }
                    if (p.c + cs - 1 > c2) { c2 = p.c + cs - 1; changed = true; }
                }
            }
        }
        this._clearSel();
        const seen = new Set();
        for (let r = r1; r <= r2; r++) {
            for (let c = c1; c <= c2; c++) {
                const cell = grid[r]?.[c];
                if (cell && !seen.has(cell)) { seen.add(cell); this._addSel(cell); }
            }
        }
    }

    _addSel(cell) {
        cell.classList.add('tf-cell-selected');
        if (!this._tblSel.includes(cell)) this._tblSel.push(cell);
    }

    _clearSel() {
        this._tblSel.forEach(c => c.classList.remove('tf-cell-selected'));
        this._tblSel = [];
    }

    _showTableMenu(x, y, cell) {
        let old = document.getElementById('tf-table-ctx');
        if (old) old.remove();

        const selCount = this._tblSel.length;
        const canMerge = selCount >= 2;

        const _i = (d) => `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${d}</svg>`;
        const items = [
            { label: '위에 행 추가', icon: _i('<path d="M12 5v14"/><path d="M5 12h14"/>'), action: () => this._insertRow(cell, 'before') },
            { label: '아래에 행 추가', icon: _i('<path d="M12 5v14"/><path d="M5 12h14"/>'), action: () => this._insertRow(cell, 'after') },
            { sep: true },
            { label: '왼쪽에 열 추가', icon: _i('<path d="M12 5v14"/><path d="M5 12h14"/>'), action: () => this._insertCol(cell, 'before') },
            { label: '오른쪽에 열 추가', icon: _i('<path d="M12 5v14"/><path d="M5 12h14"/>'), action: () => this._insertCol(cell, 'after') },
            { sep: true },
            { label: '셀 병합', icon: _i('<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/><path d="M3 9h18"/>'), action: () => this._mergeCells(), disabled: !canMerge },
            { label: '셀 분할', icon: _i('<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M12 3v18"/><path d="M3 12h18"/>'), action: () => this._showSplitDialog(cell) },
            { sep: true },
            { label: '행 삭제', icon: _i('<path d="M18 6L6 18"/><path d="M6 6l12 12"/>'), action: () => this._deleteRow(cell), danger: true },
            { label: '열 삭제', icon: _i('<path d="M18 6L6 18"/><path d="M6 6l12 12"/>'), action: () => this._deleteCol(cell), danger: true },
            { sep: true },
            { label: '표 삭제', icon: _i('<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>'), action: () => this._deleteTable(cell), danger: true },
        ];

        const menu = document.createElement('div');
        menu.id = 'tf-table-ctx';
        menu.className = 'tf-table-ctx';
        items.forEach(item => {
            if (item.sep) { menu.appendChild(Object.assign(document.createElement('div'), { className: 'tf-table-ctx-sep' })); return; }
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'tf-table-ctx-item' + (item.danger ? ' danger' : '');
            btn.disabled = !!item.disabled;
            btn.innerHTML = `<span class="tf-table-ctx-icon">${item.icon}</span>${item.label}`;
            btn.addEventListener('click', () => { menu.remove(); item.action(); });
            menu.appendChild(btn);
        });

        document.body.appendChild(menu);
        const vw = window.innerWidth, vh = window.innerHeight;
        menu.style.left = Math.max(8, Math.min(x, vw - menu.offsetWidth - 8)) + 'px';
        menu.style.top = Math.max(8, Math.min(y, vh - menu.offsetHeight - 8)) + 'px';
    }

    _insertRow(cell, where) {
        this._saveUndo(cell.closest('table'));
        const pos = this._cellPos(cell);
        if (!pos) return;
        const { table, grid } = pos;
        const targetR = where === 'before' ? pos.r : pos.r + (cell.rowSpan || 1) - 1;
        const cols = grid[0]?.length || 1;
        const cellInfos = [];
        const seen = new Set();
        for (let c = 0; c < cols; c++) {
            const existing = grid[targetR]?.[c];
            if (existing && seen.has(existing)) continue;
            if (existing) seen.add(existing);
            const eRS = existing ? (existing.rowSpan || 1) : 1;
            const eCS = existing ? (existing.colSpan || 1) : 1;
            const originR = existing ? (() => { for (let r = 0; r <= targetR; r++) { if (grid[r]?.[c] === existing) return r; } return targetR; })() : targetR;
            cellInfos.push({ el: existing, originR, rs: eRS, cs: eCS });
            c += eCS - 1;
        }
        const newRow = table.insertRow(targetR + (where === 'after' ? 1 : 0));
        for (const info of cellInfos) {
            if (info.el && info.originR !== targetR && info.originR + info.rs - 1 >= targetR) {
                info.el.rowSpan = info.rs + 1;
            } else {
                const td = newRow.insertCell();
                td.innerHTML = '&nbsp;';
                if (info.cs > 1) td.colSpan = info.cs;
            }
        }
    }

    _insertCol(cell, where) {
        this._saveUndo(cell.closest('table'));
        const pos = this._cellPos(cell);
        if (!pos) return;
        const { table, grid } = pos;
        const targetC = where === 'before' ? pos.c : pos.c + (cell.colSpan || 1);
        const rows = grid.length;
        const checkC = where === 'before' ? targetC : targetC - 1;
        const cellInfos = [];
        const seen = new Set();
        for (let r = 0; r < rows; r++) {
            const existing = grid[r]?.[checkC];
            if (existing && seen.has(existing)) continue;
            if (existing) seen.add(existing);
            const eCS = existing ? (existing.colSpan || 1) : 1;
            const eRS = existing ? (existing.rowSpan || 1) : 1;
            const originC = existing ? (() => { const row = grid[r]; for (let c = 0; c <= checkC; c++) { if (row?.[c] === existing) return c; } return checkC; })() : checkC;
            cellInfos.push({ el: existing, r, originC, cs: eCS, rs: eRS });
            r += eRS - 1;
        }
        for (const info of cellInfos) {
            if (info.el && info.originC < targetC && info.originC + info.cs > targetC) {
                info.el.colSpan = info.cs + 1;
            } else {
                const row = table.rows[info.r];
                if (!row) continue;
                const tag = row.closest('thead') ? 'th' : 'td';
                const newCell = document.createElement(tag);
                newCell.innerHTML = '&nbsp;';
                if (info.rs > 1) newCell.rowSpan = info.rs;
                let refNode = null;
                for (const c of row.cells) {
                    const cp = this._cellPos(c);
                    if (cp && cp.c >= targetC) { refNode = c; break; }
                }
                row.insertBefore(newCell, refNode);
            }
        }
    }

    _mergeCells() {
        if (this._tblSel.length < 2) return;
        this._saveUndo(this._tblSel[0].closest('table'));
        const first = this._tblSel[0];
        const table = first.closest('table');
        if (!table) return;
        const grid = this._buildGrid(table);
        let r1 = Infinity, r2 = -1, c1 = Infinity, c2 = -1;
        this._tblSel.forEach(cell => {
            const p = this._cellPos(cell);
            if (!p) return;
            r1 = Math.min(r1, p.r);
            c1 = Math.min(c1, p.c);
            r2 = Math.max(r2, p.r + (cell.rowSpan || 1) - 1);
            c2 = Math.max(c2, p.c + (cell.colSpan || 1) - 1);
        });
        let content = '';
        const toRemove = [];
        const seen = new Set();
        for (let r = r1; r <= r2; r++) {
            for (let c = c1; c <= c2; c++) {
                const cell = grid[r]?.[c];
                if (!cell || seen.has(cell)) continue;
                seen.add(cell);
                const txt = cell.innerHTML.trim();
                if (txt && txt !== '&nbsp;' && txt !== '<br>') {
                    if (content) content += '<br>';
                    content += txt;
                }
                if (cell !== first) toRemove.push(cell);
            }
        }
        toRemove.forEach(c => c.remove());
        first.rowSpan = r2 - r1 + 1;
        first.colSpan = c2 - c1 + 1;
        first.innerHTML = content || '&nbsp;';
        this._clearSel();
    }

    _showSplitDialog(cell) {
        const isMerged = (cell.colSpan || 1) > 1 || (cell.rowSpan || 1) > 1;
        const defR = isMerged ? (cell.rowSpan || 1) : 1;
        const defC = isMerged ? (cell.colSpan || 1) : 2;
        const rect = cell.getBoundingClientRect();

        const dlg = document.createElement('div');
        dlg.className = 'tf-table-ctx';
        dlg.style.cssText = 'padding:12px;min-width:160px;position:fixed;z-index:100000';
        dlg.innerHTML = `
            <div style="font-size:13px;font-weight:600;margin-bottom:8px">셀 분할</div>
            <div style="display:flex;gap:8px;align-items:center;margin-bottom:6px">
                <label style="font-size:12px;width:20px">행</label>
                <input type="number" min="1" max="20" value="${defR}" id="tf-sp-r"
                    style="width:48px;padding:3px 6px;border:1px solid var(--zm-chip-bg,#475569);border-radius:4px;background:transparent;color:inherit;font-size:13px;text-align:center">
            </div>
            <div style="display:flex;gap:8px;align-items:center;margin-bottom:10px">
                <label style="font-size:12px;width:20px">열</label>
                <input type="number" min="1" max="20" value="${defC}" id="tf-sp-c"
                    style="width:48px;padding:3px 6px;border:1px solid var(--zm-chip-bg,#475569);border-radius:4px;background:transparent;color:inherit;font-size:13px;text-align:center">
            </div>
            <div style="display:flex;gap:6px;justify-content:flex-end">
                <button type="button" id="tf-sp-no" style="padding:3px 10px;border:1px solid var(--zm-chip-bg,#475569);border-radius:4px;background:transparent;color:inherit;font-size:12px;cursor:pointer">취소</button>
                <button type="button" id="tf-sp-ok" style="padding:3px 10px;border:none;border-radius:4px;background:#4F6AFF;color:#fff;font-size:12px;cursor:pointer">분할</button>
            </div>`;
        document.body.appendChild(dlg);
        dlg.style.left = Math.min(rect.right + 4, innerWidth - dlg.offsetWidth - 8) + 'px';
        dlg.style.top = Math.min(rect.top, innerHeight - dlg.offsetHeight - 8) + 'px';

        const close = () => dlg.remove();
        dlg.querySelector('#tf-sp-no').onclick = close;
        dlg.querySelector('#tf-sp-ok').onclick = () => {
            const nr = Math.max(1, parseInt(dlg.querySelector('#tf-sp-r').value) || 1);
            const nc = Math.max(1, parseInt(dlg.querySelector('#tf-sp-c').value) || 1);
            close();
            if (nr <= 1 && nc <= 1) return;
            this._applySplit(cell, nr, nc);
        };
        dlg.querySelector('#tf-sp-r').focus();
        dlg.querySelector('#tf-sp-r').select();
        setTimeout(() => {
            const handler = (e) => { if (!dlg.contains(e.target)) { close(); document.removeEventListener('mousedown', handler); } };
            document.addEventListener('mousedown', handler);
        }, 0);
    }

    _applySplit(cell, numRows, numCols) {
        this._saveUndo(cell.closest('table'));
        const table = cell.closest('table');
        if (!table) return;
        const pos = this._cellPos(cell);
        if (!pos) return;
        const { grid } = pos;
        const origRS = cell.rowSpan || 1;
        const origCS = cell.colSpan || 1;
        const cellR = pos.r, cellC = pos.c;
        const totalCols = grid[0]?.length || 1;

        if (numCols > 1) {
            if (origCS >= numCols && origCS % numCols === 0) {
                const subCS = origCS / numCols;
                cell.colSpan = subCS;
                const next = cell.nextSibling;
                for (let i = 1; i < numCols; i++) {
                    const td = document.createElement(cell.tagName.toLowerCase());
                    td.colSpan = subCS;
                    td.rowSpan = cell.rowSpan || 1;
                    td.innerHTML = '&nbsp;';
                    cell.parentElement.insertBefore(td, next);
                }
            } else {
                const colWidths = [];
                for (let c = 0; c < totalCols; c++) {
                    for (let r = 0; r < grid.length; r++) {
                        const el = grid[r]?.[c];
                        if (el && (el.colSpan || 1) === 1) { colWidths[c] = el.getBoundingClientRect().width; break; }
                    }
                    if (!colWidths[c]) {
                        for (let r = 0; r < grid.length; r++) {
                            const el = grid[r]?.[c];
                            if (el) { colWidths[c] = el.getBoundingClientRect().width / (el.colSpan || 1); break; }
                        }
                    }
                }
                const tableW = table.offsetWidth;
                const seen = new Set();
                for (const row of table.rows) {
                    for (const c of row.cells) {
                        if (seen.has(c) || c === cell) continue;
                        seen.add(c);
                        const cp = this._cellPos(c);
                        if (!cp) continue;
                        const cs = c.colSpan || 1;
                        const overlap = Math.max(0, Math.min(cp.c + cs - 1, cellC + origCS - 1) - Math.max(cp.c, cellC) + 1);
                        if (overlap > 0) c.colSpan = cs + overlap * (numCols - 1);
                    }
                }
                cell.colSpan = origCS;
                const next = cell.nextSibling;
                for (let i = 1; i < numCols; i++) {
                    const td = document.createElement(cell.tagName.toLowerCase());
                    td.colSpan = origCS;
                    td.rowSpan = cell.rowSpan || 1;
                    td.innerHTML = '&nbsp;';
                    cell.parentElement.insertBefore(td, next);
                }
                let cg = table.querySelector('colgroup');
                if (!cg) { cg = document.createElement('colgroup'); table.insertBefore(cg, table.firstChild); }
                cg.innerHTML = '';
                for (let c = 0; c < totalCols; c++) {
                    if (c >= cellC && c < cellC + origCS) {
                        const subW = (colWidths[c] || tableW / totalCols) / numCols;
                        for (let i = 0; i < numCols; i++) {
                            const col = document.createElement('col');
                            col.style.width = subW + 'px';
                            cg.appendChild(col);
                        }
                    } else {
                        const col = document.createElement('col');
                        col.style.width = (colWidths[c] || tableW / totalCols) + 'px';
                        cg.appendChild(col);
                    }
                }
                table.style.tableLayout = 'fixed';
                table.style.width = tableW + 'px';
            }
        }

        if (numRows > 1) {
            let targetRowIdx = Array.from(table.rows).findIndex(r => Array.from(r.cells).includes(cell));
            const currentRS = cell.rowSpan || 1;

            if (currentRS >= numRows && currentRS % numRows === 0) {
                const subRS = currentRS / numRows;
                cell.rowSpan = subRS;
                const currentCS = cell.colSpan || 1;
                const numColCells = numCols > 1 ? numCols : 1;
                for (let r = 1; r < numRows; r++) {
                    const rowIdx = targetRowIdx + r * subRS;
                    const row = table.rows[rowIdx];
                    if (!row) continue;
                    for (let c = 0; c < numColCells; c++) {
                        const td = document.createElement(cell.tagName.toLowerCase());
                        td.rowSpan = subRS;
                        td.colSpan = currentCS;
                        td.innerHTML = '&nbsp;';
                        let refNode = null;
                        const freshGrid = this._buildGrid(table);
                        const freshPos = this._cellPos(cell);
                        if (freshPos) {
                            const targetCol = freshPos.c + c * currentCS;
                            for (const existing of row.cells) {
                                const ep = this._cellPos(existing);
                                if (ep && ep.c >= targetCol) { refNode = existing; break; }
                            }
                        }
                        row.insertBefore(td, refNode);
                    }
                }
            } else {
                const rowHeights = [];
                for (let r = 0; r < grid.length; r++) {
                    const row = table.rows[r];
                    if (row) rowHeights[r] = row.getBoundingClientRect().height;
                }
                const seen = new Set();
                for (const row of table.rows) {
                    for (const c of row.cells) {
                        if (seen.has(c) || c === cell) continue;
                        seen.add(c);
                        const cp = this._cellPos(c);
                        if (!cp) continue;
                        const rs = c.rowSpan || 1;
                        const overlap = Math.max(0, Math.min(cp.r + rs - 1, cellR + origRS - 1) - Math.max(cp.r, cellR) + 1);
                        if (overlap > 0) c.rowSpan = rs + overlap * (numRows - 1);
                    }
                }
                const origRowCount = table.rows.length;
                for (let i = origRowCount - 1; i >= 0; i--) {
                    if (i >= cellR && i < cellR + origRS) {
                        for (let j = 0; j < numRows - 1; j++) table.insertRow(i + 1);
                    }
                }
                const newTargetRowIdx = targetRowIdx + (targetRowIdx >= cellR ? (targetRowIdx - cellR) * (numRows - 1) : 0);
                cell.rowSpan = origRS;
                const currentCS = cell.colSpan || 1;
                const numColCells = numCols > 1 ? numCols : 1;
                for (let r = 1; r < numRows; r++) {
                    const rowIdx = newTargetRowIdx + r * origRS;
                    const row = table.rows[rowIdx];
                    if (!row) continue;
                    for (let c = 0; c < numColCells; c++) {
                        const td = document.createElement(cell.tagName.toLowerCase());
                        td.rowSpan = origRS;
                        td.colSpan = currentCS;
                        td.innerHTML = '&nbsp;';
                        row.appendChild(td);
                    }
                }
            }
        }
        this._clearSel();
    }

    _deleteRow(cell) {
        this._saveUndo(cell.closest('table'));
        const table = cell.closest('table');
        if (!table || table.rows.length <= 1) return;
        const pos = this._cellPos(cell);
        if (!pos) return;
        const { grid } = pos;
        const targetR = pos.r;
        const cols = grid[0]?.length || 0;
        const seen = new Set();
        for (let c = 0; c < cols; c++) {
            const el = grid[targetR]?.[c];
            if (!el || seen.has(el)) continue;
            seen.add(el);
            const originR = (() => { for (let r = 0; r <= targetR; r++) { if (grid[r]?.[c] === el) return r; } return targetR; })();
            const rs = el.rowSpan || 1;
            if (rs > 1) {
                el.rowSpan = rs - 1;
                if (originR === targetR) {
                    const nextRow = table.rows[targetR + 1];
                    if (nextRow) {
                        let refNode = null;
                        for (const nc of nextRow.cells) {
                            const np = this._cellPos(nc);
                            if (np && np.c > c) { refNode = nc; break; }
                        }
                        nextRow.insertBefore(el, refNode);
                    }
                }
            } else if (originR === targetR) {
                el.remove();
            }
        }
        if (table.rows[targetR] && table.rows[targetR].cells.length === 0) {
            table.deleteRow(targetR);
        }
        if (table.rows.length === 0) table.remove();
        this._clearSel();
    }

    _deleteCol(cell) {
        this._saveUndo(cell.closest('table'));
        const pos = this._cellPos(cell);
        if (!pos) return;
        const { table, grid } = pos;
        const targetC = pos.c;
        const rows = grid.length;
        const seen = new Set();
        for (let r = 0; r < rows; r++) {
            const el = grid[r]?.[targetC];
            if (!el || seen.has(el)) continue;
            seen.add(el);
            const cs = el.colSpan || 1;
            if (cs > 1) {
                el.colSpan = cs - 1;
            } else {
                el.remove();
            }
        }
        if (!table.querySelector('td, th')) table.remove();
        this._clearSel();
    }

    _deleteTable(cell) {
        this._saveUndo();
        const table = cell.closest('table');
        if (table) {
            const p = document.createElement('p');
            p.innerHTML = '<br>';
            table.replaceWith(p);
            const range = document.createRange();
            range.setStart(p, 0);
            range.collapse(true);
            const sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);
        }
    }

    destroy() { this._clearSel?.(); this.container.innerHTML = ''; }
}

window.TFEditor = TFEditor;
