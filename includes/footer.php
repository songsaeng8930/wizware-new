<script src="<?= rtrim(str_replace('\\','/',str_replace(realpath($_SERVER['DOCUMENT_ROOT']),'',realpath(__DIR__.'/..'))),'/') ?>/assets/js/datepicker.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ───────── Lucide 아이콘 초기화 ─────────
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();

        // DOM 변경 시 자동으로 Lucide 아이콘 재렌더링 (동적 콘텐츠 대응)
        // 주의: 무조건 createIcons()를 호출하면 SVG 교체 → MutationObserver 재발동 → 무한루프.
        // <i data-lucide> 태그(아직 변환 안 된 아이콘)가 있을 때만 호출한다.
        let _lucideTimer;
        new MutationObserver(function() {
            clearTimeout(_lucideTimer);
            _lucideTimer = setTimeout(function() {
                if (document.querySelector('i[data-lucide]')) {
                    lucide.createIcons();
                }
            }, 150);
        }).observe(document.body, { childList: true, subtree: true });
    }

    // ───────── 전역 커스텀 툴팁 ─────────
    (function() {
        var tip = document.createElement('div');
        tip.style.cssText = 'position:fixed;padding:5px 10px;font-size:12px;color:#fff;background:#334155;border-radius:6px;pointer-events:none;z-index:9999;opacity:0;transition:opacity .12s;white-space:nowrap;max-width:280px;word-break:keep-all';
        document.body.appendChild(tip);
        var arrow = document.createElement('div');
        arrow.style.cssText = 'position:absolute;top:100%;left:50%;transform:translateX(-50%);border:4px solid transparent;border-top-color:#334155';
        tip.appendChild(arrow);
        var textNode = document.createElement('span');
        tip.insertBefore(textNode, arrow);

        function show(el) {
            var text = el.getAttribute('title') || el.dataset.tip;
            if (!text) return;
            if (el.getAttribute('title')) {
                el.dataset.tip = text;
                el.removeAttribute('title');
            }
            textNode.textContent = text;
            tip.style.opacity = '1';
            var r = el.getBoundingClientRect();
            var tW = tip.offsetWidth, tH = tip.offsetHeight;
            var left = r.left + r.width / 2 - tW / 2;
            if (left < 4) left = 4;
            if (left + tW > window.innerWidth - 4) left = window.innerWidth - 4 - tW;
            tip.style.left = left + 'px';
            tip.style.top = (r.top - tH - 6) + 'px';
        }
        function hide(el) { tip.style.opacity = '0'; }

        document.addEventListener('mouseover', function(e) {
            var el = e.target.closest('[title],[data-tip]');
            if (el) show(el);
        });
        document.addEventListener('mouseout', function(e) {
            var el = e.target.closest('[title],[data-tip]');
            if (el) hide(el);
        });
    })();

    // 전역 헬퍼: 동적 콘텐츠 삽입 후 아이콘 수동 갱신용
    window.refreshIcons = function() {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    };

    // ───────── 전역 알림 UX ─────────
    // 레거시 페이지의 alert() 호출도 서비스 UI 안의 토스트로 표시한다.
    window.AppUI = window.AppUI || {};
    window.AppUI.toast = function(message, type) {
        var msg = String(message || '').trim();
        if (!msg) return;
        var tone = type || 'info';
        var colors = {
            success: 'bg-emerald-500',
            error: 'bg-rose-500',
            warning: 'bg-amber-500',
            info: 'bg-slate-700'
        };
        var icons = {
            success: 'check-circle',
            error: 'x-circle',
            warning: 'alert-triangle',
            info: 'info'
        };
        var wrap = document.getElementById('globalToastWrap');
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.id = 'globalToastWrap';
            wrap.className = 'fixed right-5 top-20 z-[90] flex w-[min(360px,calc(100vw-2rem))] flex-col gap-2';
            document.body.appendChild(wrap);
        }
        var toast = document.createElement('div');
        toast.className = (colors[tone] || colors.info) + ' flex items-start gap-2 rounded-lg px-4 py-3 text-sm font-medium text-white shadow-lg transition-all duration-200';
        toast.setAttribute('role', tone === 'error' ? 'alert' : 'status');
        toast.innerHTML = '<i data-lucide="' + (icons[tone] || icons.info) + '" class="mt-0.5 h-4 w-4 shrink-0"></i><span class="min-w-0 whitespace-pre-line break-words"></span>';
        toast.querySelector('span').textContent = msg;
        wrap.appendChild(toast);
        window.refreshIcons();
        setTimeout(function() {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-6px)';
            setTimeout(function() { toast.remove(); }, 220);
        }, tone === 'error' ? 4200 : 2600);
    };
    window.alert = function(message) {
        var text = String(message || '');
        var type = /실패|오류|필수|입력|삭제할 수 없습니다|찾을 수 없습니다/.test(text) ? 'error' : 'info';
        window.AppUI.toast(text, type);
    };

    // ───────── 전역 커스텀 confirm / prompt 모달 ─────────
    (function() {
        var DANGER_RE = /삭제|제거|초기화|해제|취소합|되돌|철회|drop|delete|remove|reset/i;

        var el = document.createElement('div');
        el.className = 'appmodal-overlay';
        el.setAttribute('role', 'dialog');
        el.setAttribute('aria-modal', 'true');
        el.innerHTML =
            '<div class="appmodal-backdrop"></div>' +
            '<div class="appmodal-card">' +
                '<div class="appmodal-body">' +
                    '<div style="display:flex;align-items:flex-start;gap:0.75rem">' +
                        '<div id="amIcon" class="appmodal-icon"></div>' +
                        '<div style="flex:1;min-width:0">' +
                            '<div id="amTitle" class="appmodal-title"></div>' +
                            '<div id="amMsg" class="appmodal-msg"></div>' +
                        '</div>' +
                    '</div>' +
                    '<input id="amInput" class="appmodal-input" style="display:none">' +
                '</div>' +
                '<div id="amFoot" class="appmodal-footer"></div>' +
            '</div>';
        document.body.appendChild(el);

        var _resolve = null;
        var _mode = '';
        var btnOk, btnCancel;

        function close(val) {
            el.classList.remove('is-open');
            if (_resolve) { var r = _resolve; _resolve = null; r(val); }
        }

        el.querySelector('.appmodal-backdrop').addEventListener('click', function() {
            if (_mode === 'prompt') close(null);
        });

        function open(mode, msg, opts) {
            _mode = mode;
            opts = opts || {};
            var isDanger = !!(opts.danger || (mode === 'confirm' && DANGER_RE.test(msg)));
            var title, iconCls, iconSvg;

            if (mode === 'confirm') {
                title = opts.title || (isDanger ? '확인' : '확인');
                iconCls = 'appmodal-icon ' + (isDanger ? 'is-danger' : 'is-warn');
                iconSvg = isDanger ? 'trash-2' : 'alert-triangle';
            } else {
                title = opts.title || '입력';
                iconCls = 'appmodal-icon is-info';
                iconSvg = 'pencil';
            }

            document.getElementById('amIcon').className = iconCls;
            document.getElementById('amIcon').innerHTML = '<i data-lucide="' + iconSvg + '" style="width:1.25rem;height:1.25rem"></i>';
            document.getElementById('amTitle').textContent = title;
            document.getElementById('amMsg').textContent = msg;

            var inp = document.getElementById('amInput');
            inp.style.display = mode === 'prompt' ? 'block' : 'none';
            if (mode === 'prompt') inp.value = opts.defaultValue != null ? opts.defaultValue : '';

            var foot = document.getElementById('amFoot');
            foot.innerHTML = '';

            var okLabel = opts.okLabel || (isDanger ? '삭제' : '확인');
            var cancelLabel = opts.cancelLabel || '취소';

            btnCancel = document.createElement('button');
            btnCancel.className = 'appmodal-btn appmodal-btn-cancel';
            btnCancel.textContent = cancelLabel;
            btnCancel.onclick = function() { close(mode === 'confirm' ? false : null); };

            btnOk = document.createElement('button');
            btnOk.className = 'appmodal-btn ' + (isDanger ? 'appmodal-btn-danger' : 'appmodal-btn-primary');
            btnOk.textContent = okLabel;
            btnOk.onclick = function() { close(mode === 'confirm' ? true : inp.value); };

            foot.appendChild(btnCancel);
            foot.appendChild(btnOk);

            el.classList.add('is-open');
            if (window.lucide) lucide.createIcons();

            if (mode === 'prompt') {
                setTimeout(function() { inp.focus(); inp.select(); }, 80);
            } else {
                setTimeout(function() { btnOk.focus(); }, 80);
            }
        }

        el.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                e.stopPropagation();
                close(_mode === 'confirm' ? false : null);
            }
            if (e.key === 'Enter' && _mode === 'prompt' && document.activeElement === document.getElementById('amInput')) {
                e.preventDefault();
                close(document.getElementById('amInput').value);
            }
            if (e.key === 'Enter' && _mode === 'confirm' && document.activeElement === btnOk) {
                close(true);
            }
            if (e.key === 'Tab') {
                var focusable = el.querySelectorAll('input:not([style*="display:none"]),button');
                var arr = Array.prototype.slice.call(focusable);
                if (!arr.length) return;
                var first = arr[0], last = arr[arr.length - 1];
                if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
            }
        });

        window.AppUI.confirm = function(msg, opts) {
            return new Promise(function(resolve) {
                _resolve = resolve;
                open('confirm', msg, opts);
            });
        };

        window.AppUI.prompt = function(msg, opts) {
            if (typeof opts === 'string') opts = { defaultValue: opts };
            return new Promise(function(resolve) {
                _resolve = resolve;
                open('prompt', msg, opts);
            });
        };

        var _nativeConfirm = window.confirm;
        var _nativePrompt = window.prompt;

        window.confirm = function(msg) {
            console.warn('[AppUI] confirm() 동기 호출 감지 — AppUI.confirm() 비동기 사용 권장:', msg);
            return _nativeConfirm.call(window, msg);
        };

        window.prompt = function(msg, def) {
            console.warn('[AppUI] prompt() 동기 호출 감지 — AppUI.prompt() 비동기 사용 권장:', msg);
            return _nativePrompt.call(window, msg, def);
        };
    })();

    // ───────── 커스텀 셀렉트 드롭다운 ─────────
    (function() {
        var SEARCH_THRESHOLD = 8;
        var ARROW_SVG = '<svg class="zm-sel-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>';
        var instances = [];

        function closeAll(except) {
            instances.forEach(function(inst) {
                if (inst !== except && inst.wrap.classList.contains('is-open')) inst.close();
            });
        }

        document.addEventListener('mousedown', function(e) {
            instances.forEach(function(inst) {
                if (inst.wrap.classList.contains('is-open') && !inst.wrap.contains(e.target) && !inst.dropdown.contains(e.target)) inst.close();
            });
        });

        function destroyInstance(inst) {
            inst.close();
            if (inst.dropdown.parentNode) inst.dropdown.parentNode.removeChild(inst.dropdown);
            var idx = instances.indexOf(inst);
            if (idx !== -1) instances.splice(idx, 1);
        }

        function enhance(sel) {
            if (sel._zmSel) return sel._zmSel;
            if (sel.closest('.zm-sel')) return null;
            if (sel.multiple) return null;

            var wrap = document.createElement('div');
            wrap.className = 'zm-sel';
            var layoutClasses = sel.className.split(/\s+/).filter(function(c) {
                return /^(shrink-|grow|flex-|w-|min-w-|max-w-|h-|min-h-|max-h-|m[lrtbxy]?-|gap-|col-|row-|self-|order-|hidden|block|inline)/.test(c);
            }).join(' ');
            if (layoutClasses) wrap.className += ' ' + layoutClasses;

            sel.parentNode.insertBefore(wrap, sel);
            wrap.appendChild(sel);

            var trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.className = 'zm-sel-trigger';
            trigger.setAttribute('tabindex', sel.getAttribute('tabindex') || '0');
            trigger.innerHTML = '<span class="zm-sel-value"></span>' + ARROW_SVG;
            wrap.appendChild(trigger);

            var dropdown = document.createElement('div');
            dropdown.className = 'zm-sel-dropdown';
            var hasSearch = sel.options.length > SEARCH_THRESHOLD;
            if (hasSearch) {
                var search = document.createElement('input');
                search.className = 'zm-sel-search';
                search.placeholder = '검색...';
                search.type = 'text';
                search.autocomplete = 'off';
                dropdown.appendChild(search);
            }
            var optBox = document.createElement('div');
            optBox.className = 'zm-sel-options';
            dropdown.appendChild(optBox);
            document.body.appendChild(dropdown);

            var focusIdx = -1;

            function buildOpts(filter) {
                optBox.innerHTML = '';
                var count = 0;
                filter = (filter || '').toLowerCase();
                for (var i = 0; i < sel.options.length; i++) {
                    var o = sel.options[i];
                    if (o.hidden) continue;
                    var text = o.textContent || o.innerText || '';
                    if (filter && text.toLowerCase().indexOf(filter) === -1) continue;
                    var div = document.createElement('div');
                    div.className = 'zm-sel-opt';
                    if (o.selected) div.className += ' is-selected';
                    div.setAttribute('data-idx', String(i));
                    div.textContent = text;
                    div.addEventListener('click', (function(idx) {
                        return function() { selectOpt(idx); };
                    })(i));
                    optBox.appendChild(div);
                    count++;
                }
                if (!count) {
                    var em = document.createElement('div');
                    em.className = 'zm-sel-empty';
                    em.textContent = '결과 없음';
                    optBox.appendChild(em);
                }
                focusIdx = -1;
            }

            function syncLabel() {
                var valSpan = trigger.querySelector('.zm-sel-value');
                var o = sel.options[sel.selectedIndex];
                if (o && o.value !== '' && o.textContent) {
                    valSpan.textContent = o.textContent;
                    valSpan.classList.remove('zm-sel-placeholder');
                } else if (o) {
                    valSpan.textContent = o.textContent || sel.getAttribute('placeholder') || '선택';
                    valSpan.classList.add('zm-sel-placeholder');
                }
            }

            function selectOpt(idx) {
                sel.selectedIndex = idx;
                sel.dispatchEvent(new Event('change', { bubbles: true }));
                syncLabel();
                inst.close();
            }

            function positionDropdown() {
                var rect = trigger.getBoundingClientRect();
                var spaceBelow = window.innerHeight - rect.bottom;
                var isAbove = spaceBelow < 200 && rect.top > spaceBelow;
                dropdown.classList.toggle('is-above', isAbove);
                // 폭: 트리거보다 좁아지지 않되 옵션 내용에 맞춰 넓어지게. 뷰포트 밖 넘침은 클램프.
                dropdown.style.width = 'auto';
                dropdown.style.minWidth = rect.width + 'px';
                dropdown.style.maxWidth = Math.max(rect.width, Math.min(360, window.innerWidth - 16)) + 'px';
                var dw = dropdown.offsetWidth;
                var left = rect.left;
                if (left + dw > window.innerWidth - 8) left = Math.max(8, window.innerWidth - 8 - dw);
                dropdown.style.left = left + 'px';
                if (isAbove) {
                    dropdown.style.top = 'auto';
                    dropdown.style.bottom = (window.innerHeight - rect.top + 4) + 'px';
                } else {
                    dropdown.style.top = (rect.bottom + 4) + 'px';
                    dropdown.style.bottom = 'auto';
                }
            }

            var _scrollRaf = 0;
            function onScrollOrResize() {
                if (!wrap.classList.contains('is-open')) return;
                cancelAnimationFrame(_scrollRaf);
                _scrollRaf = requestAnimationFrame(positionDropdown);
            }

            function moveFocus(delta) {
                var items = optBox.querySelectorAll('.zm-sel-opt');
                if (!items.length) return;
                focusIdx = Math.max(0, Math.min(items.length - 1, focusIdx + delta));
                items.forEach(function(it, i) { it.classList.toggle('is-focused', i === focusIdx); });
                items[focusIdx].scrollIntoView({ block: 'nearest' });
            }

            var inst = {
                wrap: wrap,
                dropdown: dropdown,
                open: function() {
                    closeAll(inst);
                    buildOpts(hasSearch && search ? search.value : '');
                    positionDropdown();
                    wrap.classList.add('is-open');
                    dropdown.classList.add('is-open');
                    window.addEventListener('scroll', onScrollOrResize, true);
                    window.addEventListener('resize', onScrollOrResize);
                    if (hasSearch) setTimeout(function() { search.focus(); }, 50);
                },
                close: function() {
                    wrap.classList.remove('is-open');
                    dropdown.classList.remove('is-open');
                    window.removeEventListener('scroll', onScrollOrResize, true);
                    window.removeEventListener('resize', onScrollOrResize);
                    if (hasSearch) search.value = '';
                    focusIdx = -1;
                },
                refresh: function() { buildOpts(''); syncLabel(); }
            };

            trigger.addEventListener('click', function(e) {
                e.preventDefault();
                if (wrap.classList.contains('is-open')) inst.close();
                else inst.open();
            });

            if (hasSearch) {
                search.addEventListener('input', function() { buildOpts(search.value); });
            }

            function handleKeydown(e) {
                if (!wrap.classList.contains('is-open')) {
                    if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
                        e.preventDefault(); inst.open(); return;
                    }
                    return;
                }
                if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); inst.close(); trigger.focus(); }
                else if (e.key === 'ArrowDown') { e.preventDefault(); moveFocus(1); }
                else if (e.key === 'ArrowUp') { e.preventDefault(); moveFocus(-1); }
                else if (e.key === 'Enter') {
                    e.preventDefault();
                    var items = optBox.querySelectorAll('.zm-sel-opt');
                    if (focusIdx >= 0 && items[focusIdx]) {
                        selectOpt(parseInt(items[focusIdx].getAttribute('data-idx')));
                    }
                }
                else if (e.key === 'Tab') { inst.close(); }
            }
            wrap.addEventListener('keydown', handleKeydown);
            dropdown.addEventListener('keydown', handleKeydown);

            syncLabel();
            sel._zmSel = inst;
            instances.push(inst);
            return inst;
        }

        window.AppUI.enhanceSelects = function(scope) {
            var root = scope || document;
            var selects = root.querySelectorAll('select:not([data-no-enhance])');
            selects.forEach(function(s) { enhance(s); });
        };

        window.AppUI.refreshSelect = function(sel) {
            if (sel._zmSel) sel._zmSel.refresh();
        };

        setTimeout(function() { window.AppUI.enhanceSelects(); }, 0);

        var _enhanceTimer = 0;
        new MutationObserver(function(mutations) {
            var hasNew = false;
            for (var i = 0; i < mutations.length; i++) {
                var m = mutations[i];
                for (var j = 0; j < m.removedNodes.length; j++) {
                    var r = m.removedNodes[j];
                    if (r.nodeType !== 1) continue;
                    var wraps = r.classList && r.classList.contains('zm-sel') ? [r] : (r.querySelectorAll ? Array.prototype.slice.call(r.querySelectorAll('.zm-sel')) : []);
                    wraps.forEach(function(w) {
                        var s = w.querySelector('select');
                        if (s && s._zmSel) destroyInstance(s._zmSel);
                    });
                }
                var added = m.addedNodes;
                for (var k = 0; k < added.length; k++) {
                    var n = added[k];
                    if (n.nodeType !== 1) continue;
                    if ((n.tagName === 'SELECT' && !n.hasAttribute('data-no-enhance') && !n._zmSel) ||
                        (n.querySelectorAll && n.querySelectorAll('select:not([data-no-enhance])').length)) {
                        hasNew = true; break;
                    }
                }
                if (hasNew) break;
            }
            if (hasNew) {
                clearTimeout(_enhanceTimer);
                _enhanceTimer = setTimeout(function() { window.AppUI.enhanceSelects(); }, 16);
            }
        }).observe(document.body, { childList: true, subtree: true });
    })();

    // ───────── 사이드바 토글 (상태 localStorage 저장) ─────────
    var sidebar = document.getElementById('sidebar');
    var mainContent = document.getElementById('mainContent');
    var sidebarToggle = document.getElementById('sidebarToggle');
    var sidebarBackdrop = document.getElementById('sidebarBackdrop');
    var sidebarDrawerMq = window.matchMedia('(max-width: 1023px)');
    var sidebarOpen = !sidebarDrawerMq.matches;
    var uiHistory = { suppress: false };

    function pushUiHistory(kind, data) {
        if (uiHistory.suppress || !window.history || !history.pushState) return;
        try {
            history.pushState(Object.assign({ bmsUi: true, kind: kind }, data || {}), '', location.href);
        } catch (e) {}
    }

    function closeVisibleModal(modalId) {
        var modal = modalId ? document.getElementById(modalId) : document.querySelector('.fixed.inset-0.z-\\[60\\]:not(.hidden), .fixed.inset-0.z-\\[70\\]:not(.hidden)');
        if (!modal) return false;
        var closeName = modal.id ? 'close' + modal.id.charAt(0).toUpperCase() + modal.id.slice(1) : '';
        if (closeName && typeof window[closeName] === 'function') {
            window[closeName]();
        } else {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        return true;
    }

    function applySidebar() {
        if (!sidebar) return;
        var isDrawer = sidebarDrawerMq.matches;
        if (sidebarOpen) {
            sidebar.classList.remove('-translate-x-full');
            // lg:translate-x-0 유틸리티가 데스크톱에서 -translate-x-full을 덮어쓰므로
            // 실제 열림/닫힘 상태는 인라인 transform으로 최종 고정한다.
            sidebar.style.transform = 'translateX(0)';
            if (mainContent) {
                mainContent.classList.toggle('ml-60', !isDrawer);
                mainContent.classList.toggle('ml-0', isDrawer);
            }
        } else {
            sidebar.classList.add('-translate-x-full');
            sidebar.style.transform = 'translateX(-100%)';
            if (mainContent) {
                mainContent.classList.remove('ml-60');
                mainContent.classList.add('ml-0');
            }
        }
        if (sidebarBackdrop) sidebarBackdrop.classList.toggle('hidden', !isDrawer || !sidebarOpen);
        if (sidebarToggle) {
            var label = sidebarOpen ? '주 메뉴 닫기' : '주 메뉴 열기';
            var icon = sidebarOpen ? 'panel-left-close' : 'panel-left-open';
            sidebarToggle.setAttribute('aria-expanded', sidebarOpen ? 'true' : 'false');
            sidebarToggle.setAttribute('aria-label', label);
            sidebarToggle.setAttribute('title', label);
            sidebarToggle.innerHTML = '<i data-lucide="' + icon + '" class="h-5 w-5"></i>';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    }

    applySidebar();

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebarOpen = !sidebarOpen;
            applySidebar();
            if (sidebarDrawerMq.matches && sidebarOpen) {
                pushUiHistory('sidebar', { open: true });
            }
        });
    }

    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', function() {
            sidebarOpen = false;
            applySidebar();
        });
    }

    if (sidebar) {
        sidebar.querySelectorAll('a[href]').forEach(function(link) {
            link.addEventListener('click', function() {
                if (sidebarDrawerMq.matches) {
                    sidebarOpen = false;
                    applySidebar();
                }
            });
        });
    }

    function handleSidebarBreakpoint() {
        sidebarOpen = !sidebarDrawerMq.matches;
        applySidebar();
    }
    if (typeof sidebarDrawerMq.addEventListener === 'function') {
        sidebarDrawerMq.addEventListener('change', handleSidebarBreakpoint);
    } else if (typeof sidebarDrawerMq.addListener === 'function') {
        sidebarDrawerMq.addListener(handleSidebarBreakpoint);
    }

    // 레거시: 이전 버전에서 localStorage에 저장된 접힘 상태가 남아있으면 정리.
    try { localStorage.removeItem('bms.sidebar.open'); } catch (e) {}

    // ───────── 서브메뉴 토글 (펼침 상태 localStorage 저장) ─────────
    var SM_KEY = 'bms.sidebar.submenus';
    var openSubmenus;
    try { openSubmenus = JSON.parse(localStorage.getItem(SM_KEY) || '[]'); }
    catch (e) { openSubmenus = []; }
    if (!Array.isArray(openSubmenus)) openSubmenus = [];

    // 저장된 펼침 상태 복원 (active로 이미 열린 메뉴는 건드리지 않음)
    openSubmenus.forEach(function(id) {
        var tgt = document.getElementById(id);
        if (tgt && tgt.classList.contains('hidden')) {
            tgt.classList.remove('hidden');
            document.querySelectorAll('.submenu-toggle[data-target="' + id + '"]').forEach(function(b) {
                b.setAttribute('aria-expanded', 'true');
                var a = b.querySelector('.submenu-arrow');
                if (a) a.classList.add('rotate-180');
            });
        }
    });

    function persistSubmenus() {
        var open = [];
        document.querySelectorAll('.submenu-children').forEach(function(el) {
            if (!el.classList.contains('hidden') && el.id) open.push(el.id);
        });
        localStorage.setItem(SM_KEY, JSON.stringify(open));
    }

    document.querySelectorAll('.submenu-toggle').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            // 내부에 <a> 링크가 있으면 링크 클릭은 토글과 분리 · 현재 구조상 별도 버튼이라 문제 없음
            var targetId = this.getAttribute('data-target');
            var target = document.getElementById(targetId);
            if (!target) return;
            var willOpen = target.classList.contains('hidden');
            target.classList.toggle('hidden');
            document.querySelectorAll('.submenu-toggle[data-target="' + targetId + '"]').forEach(function(b) {
                b.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                var a = b.querySelector('.submenu-arrow');
                if (a) a.classList.toggle('rotate-180', willOpen);
            });
            persistSubmenus();
            if (sidebarDrawerMq.matches) {
                pushUiHistory('submenu', { target: targetId, open: willOpen });
            }
        });
    });

    window.addEventListener('popstate', function(e) {
        uiHistory.suppress = true;
        var state = e.state || {};
        if (closeVisibleModal(state.modalId)) {
            uiHistory.suppress = false;
            return;
        }
        if (sidebarDrawerMq.matches && sidebarOpen) {
            sidebarOpen = false;
            applySidebar();
            uiHistory.suppress = false;
            return;
        }
        if (sidebarDrawerMq.matches && state.kind === 'submenu' && state.target) {
            var submenu = document.getElementById(state.target);
            if (submenu) {
                submenu.classList.toggle('hidden', !state.open);
                document.querySelectorAll('.submenu-toggle[data-target="' + state.target + '"]').forEach(function(b) {
                    b.setAttribute('aria-expanded', state.open ? 'true' : 'false');
                    var a = b.querySelector('.submenu-arrow');
                    if (a) a.classList.toggle('rotate-180', !!state.open);
                });
                persistSubmenus();
            }
        }
        uiHistory.suppress = false;
    });

    // 페이지별 showModal(id) 헬퍼가 있는 경우, 모바일 뒤로 가기로 모달을 먼저 닫을 수 있게 연결한다.
    if (typeof window.showModal === 'function' && !window.showModal.__bmsHistoryWrapped) {
        var originalShowModal = window.showModal;
        window.showModal = function(id) {
            originalShowModal.apply(this, arguments);
            var el = document.getElementById(id);
            if (el && !el.classList.contains('hidden')) {
                pushUiHistory('modal', { modalId: id });
            }
        };
        window.showModal.__bmsHistoryWrapped = true;
    }

    // ───────── 페이지 타이틀 자동 주입 ─────────
    // 이미 h1/h2/h3 제목이 있는 페이지는 건너뜀
    if (window.__pageTitle && mainContent && !window.__suppressAutoTitle) {
        var insertTarget = mainContent.querySelector(':scope > main') || mainContent.querySelector(':scope > div');
        if (insertTarget && !insertTarget.querySelector('.page-title-auto')) {
            var firstChild = insertTarget.firstElementChild;
            var hasExistingTitle = insertTarget.querySelector(':scope > h1, :scope > h2')
                || (firstChild && firstChild.querySelector('h1, h2, h3'));
            if (!hasExistingTitle) {
                var titleH1 = document.createElement('h1');
                titleH1.className = 'page-title-auto text-xl font-bold text-slate-100 mb-5';
                titleH1.textContent = window.__pageTitle;
                insertTarget.prepend(titleH1);
            }
        }
    }

    // ───────── 로드 중 트랜지션 차단 해제 (header.php 의 html.bms-preload 가드) ─────────
    // 스타일이 모두 적용된 뒤 두 프레임 지나서 해제 · 해제 자체가 트랜지션을 유발하지 않도록
    requestAnimationFrame(function() {
        requestAnimationFrame(function() {
            document.documentElement.classList.remove('bms-preload');
        });
    });

    // ───────── 전역 ESC 핸들러: 표시 중인 모달 자동 닫기 ─────────
    document.addEventListener('keydown', function(e) {
        if (e.key !== 'Escape') return;
        // BMS 모달 규칙: fixed + z-[60] + 'hidden' 토글 방식을 따르는 모달을 대상으로 함
        var modals = document.querySelectorAll('.fixed.inset-0.z-\\[60\\]:not(.hidden), .fixed.inset-0.z-\\[70\\]:not(.hidden)');
        modals.forEach(function(m) {
            // 컨테이너 자체가 배경인 경우 hidden 추가
            if (m.id && typeof window['close' + m.id.charAt(0).toUpperCase() + m.id.slice(1)] === 'function') {
                window['close' + m.id.charAt(0).toUpperCase() + m.id.slice(1)]();
            } else {
                m.classList.add('hidden');
            }
        });
    });

    // ───────── 전역 백드롭 클릭 모달 닫기 보장 ─────────
    // 이미 onclick="if(event.target===this)..." 규칙이 많이 걸려있지만,
    // 누락된 모달도 자동으로 처리되도록 위임 핸들러 추가
    document.addEventListener('mousedown', function(e) {
        var m = e.target;
        if (!m || !m.classList) return;
        if (m.classList.contains('fixed') && m.classList.contains('inset-0') &&
            (m.classList.contains('z-[60]') || m.classList.contains('z-[70]')) &&
            !m.classList.contains('hidden')) {
            if (e.target === m) m.classList.add('hidden');
        }
    });

});
</script>
</body>
</html>
