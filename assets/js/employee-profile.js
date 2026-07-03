/**
 * 직원 상세 프로필 · 아코디언 + 인라인 폼 + 완성도 위젯
 * 모달 없음 — 모든 입력이 아코디언 카드 안에서 인라인으로 동작
 */
(function () {
    'use strict';

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
    function fmtDate(d) { return d ? d.substring(0, 10) : ''; }
    function fmtDateShort(d) { return d ? d.substring(0, 10).replace(/-/g, '.') : ''; }
    function joinParts(parts, sep) {
        return parts.filter(function (p) { return p && p !== '' && p !== '-'; }).join(sep || ' · ');
    }
    function orgDepartmentLabel() {
        return ((window.ORG_LABELS || {}).department || {}).label || '부서';
    }

    var SECTION_LABELS = {
        career: '경력', education: '학력', certification: '자격증',
        language: '어학', family: '가족', award: '수상/징계',
        military: '병역', skills: '스킬'
    };
    var SECTION_ICONS = {
        career: 'briefcase', education: 'graduation-cap', certification: 'award',
        language: 'globe', family: 'users', award: 'trophy',
        military: 'shield', skills: 'zap'
    };
    var SECTION_ORDER = ['career', 'education', 'military', 'family', 'language', 'skills', 'certification', 'award'];

    var EMPTY_PROMPTS = {
        career:        '첫 번째 경력을 등록하세요',
        education:     '학력 정보를 등록하세요',
        certification: '보유 자격증을 등록하세요',
        language:      '어학 능력을 등록하세요',
        family:        '가족 정보를 등록하세요',
        award:         '수상/징계 내역을 등록하세요',
        military:      '병역 정보를 등록하세요',
        skills:        '보유 스킬을 추가하세요'
    };

    // ─── 섹션 필드 정의 ─────────────────────────────────────

    var SECTIONS = {
        career: {
            plural: 'Careers', singular: 'Career',
            fields: [
                { key: 'company_name', label: '회사명', type: 'text', required: true, span: 1 },
                { key: 'employment_type', label: '고용형태', type: 'select', span: 1,
                  options: ['정규직', '계약직', '파견직', '인턴', '프리랜서'] },
                { key: 'department', label: orgDepartmentLabel(), type: 'text', span: 1 },
                { key: 'position', label: '직급/직책', type: 'text', span: 1 },
                { key: 'job_type', label: '직무', type: 'text', span: 2, placeholder: '개발, 마케팅, 영업 등' },
                { key: '_divider_period', type: 'divider', label: '근무 기간' },
                { key: 'start_date', label: '입사일', type: 'date', required: true, span: 1 },
                { key: 'end_date', label: '퇴사일', type: 'date', span: 1 },
                { key: 'leave_reason', label: '퇴직사유', type: 'text', span: 2, group: 'has_end',
                  placeholder: '이직, 계약만료, 개인사유 등' },
                { key: 'description', label: '담당 업무', type: 'textarea', span: 4 }
            ],
            conditions: function (parentEl) {
                var endEl = parentEl.querySelector('[data-pf="end_date"]');
                if (!endEl) return;
                function apply() {
                    var hasEnd = endEl.value !== '';
                    parentEl.querySelectorAll('[data-pf-group="has_end"]').forEach(function (el) {
                        el.style.display = hasEnd ? '' : 'none';
                    });
                }
                endEl.addEventListener('change', apply);
                apply();
            }
        },
        education: {
            plural: 'Educations', singular: 'Education',
            fields: [
                { key: 'school_type', label: '학교구분', type: 'select', span: 1,
                  options: ['고등학교', '대학교(2,3년)', '대학교(4년)', '대학원(석사)', '대학원(박사)'] },
                { key: 'school_name', label: '학교명', type: 'text', required: true, span: 1 },
                { key: 'major', label: '전공', type: 'text', span: 1, group: 'college' },
                { key: 'minor', label: '부전공/복수전공', type: 'text', span: 1, group: 'college' },
                { key: 'degree', label: '학위', type: 'select', required: true, span: 1, group: 'college',
                  options: ['고졸', '전문학사', '학사', '석사', '박사'] },
                { key: 'status', label: '상태', type: 'select', span: 1,
                  options: ['졸업', '재학', '중퇴', '수료', '졸업예정'] },
                { key: '_divider_grade', type: 'divider', label: '성적', group: 'college' },
                { key: 'gpa', label: '학점', type: 'number', span: 1, group: 'college',
                  placeholder: '0.00', step: '0.01', min: '0', max: '4.5' },
                { key: 'gpa_scale', label: '만점', type: 'select', span: 1, group: 'college',
                  options: ['4.5', '4.0'] },
                { key: '_divider_date', type: 'divider', label: '기간' },
                { key: 'start_date', label: '입학일', type: 'date', span: 1 },
                { key: 'end_date', label: '졸업일', type: 'date', span: 1 },
                { key: 'description', label: '비고', type: 'textarea', span: 4 }
            ],
            conditions: function (parentEl) {
                var sel = parentEl.querySelector('[data-pf="school_type"]');
                if (!sel) return;
                function apply() {
                    var isHS = sel.value === '고등학교';
                    parentEl.querySelectorAll('[data-pf-group="college"]').forEach(function (el) {
                        el.style.display = isHS ? 'none' : '';
                    });
                    var degreeEl = parentEl.querySelector('[data-pf="degree"]');
                    if (degreeEl && isHS) degreeEl.value = '고졸';
                }
                sel.addEventListener('change', apply);
                apply();
            }
        },
        certification: {
            plural: 'Certifications', singular: 'Certification',
            fields: [
                { key: 'cert_name', label: '자격증명', type: 'text', required: true, span: 2 },
                { key: 'cert_grade', label: '등급/급수', type: 'text', span: 1, placeholder: '1급, 기사, 산업기사 등' },
                { key: 'issuing_org', label: '발급기관', type: 'text', required: true, span: 1 },
                { key: 'cert_number', label: '자격증 번호', type: 'text', span: 2 },
                { key: '_divider_date', type: 'divider', label: '유효기간' },
                { key: 'acquired_date', label: '취득일', type: 'date', required: true, span: 1 },
                { key: 'expiry_date', label: '만료일', type: 'date', span: 1 }
            ]
        },
        language: {
            plural: 'Languages', singular: 'Language',
            fields: [
                { key: 'language', label: '언어', type: 'text', required: true, span: 1 },
                { key: 'level', label: '수준', type: 'select', required: true, span: 1,
                  options: ['초급', '중급', '고급', '원어민'] },
                { key: '_divider_test', type: 'divider', label: '공인 시험' },
                { key: 'test_type', label: '시험유형', type: 'select', span: 1,
                  options: ['', '공인시험', '회화', '자격'] },
                { key: 'test_name', label: '시험명', type: 'text', span: 1, group: 'test', placeholder: 'TOEIC, JLPT 등' },
                { key: 'test_score', label: '점수/등급', type: 'text', span: 1, group: 'test' },
                { key: 'test_date', label: '시험일', type: 'date', span: 1, group: 'test' },
                { key: 'validity_years', label: '유효기간', type: 'select', span: 1, group: 'test',
                  options: ['', '2년', '5년', '무기한'] }
            ],
            conditions: function (parentEl) {
                var sel = parentEl.querySelector('[data-pf="test_type"]');
                if (!sel) return;
                function apply() {
                    var hasTest = sel.value !== '';
                    parentEl.querySelectorAll('[data-pf-group="test"]').forEach(function (el) {
                        el.style.display = hasTest ? '' : 'none';
                    });
                }
                sel.addEventListener('change', apply);
                apply();
            }
        },
        family: {
            plural: 'Families', singular: 'Family',
            fields: [
                { key: 'relationship', label: '관계', type: 'select', required: true, span: 1,
                  options: ['배우자', '자녀', '부', '모', '형제', '자매', '조부', '조모', '기타'] },
                { key: 'name', label: '이름', type: 'text', required: true, span: 1 },
                { key: 'birth_date', label: '생년월일', type: 'date', span: 1 },
                { key: 'phone', label: '연락처', type: 'text', span: 1 },
                { key: '_divider_tax', type: 'divider', label: '세무/보험' },
                { key: 'is_cohabitant', label: '동거', type: 'checkbox', span: 1 },
                { key: 'is_dependent', label: '부양가족', type: 'checkbox', span: 1 },
                { key: 'is_health_dependent', label: '건강보험 피부양자', type: 'checkbox', span: 2, group: 'dependent' },
                { key: 'memo', label: '비고', type: 'text', span: 2 }
            ],
            conditions: function (parentEl) {
                var chk = parentEl.querySelector('[data-pf="is_dependent"]');
                if (!chk) return;
                function apply() {
                    parentEl.querySelectorAll('[data-pf-group="dependent"]').forEach(function (el) {
                        el.style.display = chk.checked ? '' : 'none';
                    });
                }
                chk.addEventListener('change', apply);
                apply();
            }
        },
        award: {
            plural: 'Awards', singular: 'Award',
            fields: [
                { key: 'type', label: '구분', type: 'select', required: true, span: 1,
                  options: ['수상', '징계'] },
                { key: 'discipline_level', label: '징계 단계', type: 'select', span: 1, group: 'discipline',
                  options: ['', '구두경고', '서면경고', '감봉', '정직', '해고'] },
                { key: 'title', label: '명칭', type: 'text', required: true, span: 2 },
                { key: 'awarded_date', label: '일자', type: 'date', required: true, span: 1 },
                { key: 'follow_up_date', label: '후속 조치일', type: 'date', span: 1, group: 'discipline' },
                { key: 'awarding_org', label: '수여/결정 기관', type: 'text', span: 2 },
                { key: 'description', label: '사유/내용', type: 'textarea', span: 4 }
            ],
            conditions: function (parentEl) {
                var sel = parentEl.querySelector('[data-pf="type"]');
                if (!sel) return;
                function apply() {
                    var isDiscipline = sel.value === '징계';
                    parentEl.querySelectorAll('[data-pf-group="discipline"]').forEach(function (el) {
                        el.style.display = isDiscipline ? '' : 'none';
                    });
                }
                sel.addEventListener('change', apply);
                apply();
            }
        },
        military: {
            plural: 'Military', singular: 'Military',
            isSingleton: true,
            fields: [
                { key: 'military_status', label: '병역구분', type: 'select', required: true, span: 2,
                  options: ['해당없음', '군필', '미필', '면제', '복무중'] },
                { key: '_divider_detail', type: 'divider', label: '복무 정보', group: 'served' },
                { key: 'branch', label: '군별', type: 'select', span: 1, group: 'served',
                  options: ['', '육군', '해군', '공군', '해병대', '의경', '전경', '공익'] },
                { key: 'branch_specialty', label: '병과', type: 'text', span: 1, group: 'served', placeholder: '보병, 포병, 통신 등' },
                { key: 'rank_title', label: '계급', type: 'text', span: 1, group: 'served' },
                { key: 'discharge_type', label: '전역구분', type: 'select', span: 1, group: 'served',
                  options: ['', '만기전역', '의가전역', '소집해제', '의병전역', '기타'] },
                { key: 'enlist_date', label: '입대일', type: 'date', span: 1, group: 'served' },
                { key: 'discharge_date', label: '전역일', type: 'date', span: 1, group: 'served' },
                { key: 'exemption_reason', label: '면제사유', type: 'text', span: 2, group: 'exempt' }
            ],
            conditions: function (parentEl) {
                var sel = parentEl.querySelector('[data-pf="military_status"]');
                if (!sel) return;
                function apply() {
                    var v = sel.value;
                    var showServed = (v === '군필' || v === '복무중');
                    var showExempt = (v === '면제');
                    parentEl.querySelectorAll('[data-pf-group="served"]').forEach(function (el) {
                        el.style.display = showServed ? '' : 'none';
                    });
                    parentEl.querySelectorAll('[data-pf-group="exempt"]').forEach(function (el) {
                        el.style.display = showExempt ? '' : 'none';
                    });
                }
                sel.addEventListener('change', apply);
                apply();
            }
        },
        skills: {
            plural: 'Skills', singular: 'Skill',
            isTagBased: true,
            fields: [
                { key: 'skill_name', label: '스킬명', type: 'text', required: true }
            ]
        }
    };

    // ─── 헤드라인 렌더러 ────────────────────────────────────

    var RENDERERS = {};

    RENDERERS.career = function (item) {
        var isCurrent = !item.end_date;
        var dateStr = fmtDateShort(item.start_date) + ' ~ ' + (isCurrent ? '현재' : fmtDateShort(item.end_date));
        var sub = joinParts([item.department, item.position, item.job_type]);
        var tags = [];
        if (item.employment_type && item.employment_type !== '정규직') tags.push(item.employment_type);
        if (isCurrent) tags.push('재직중');
        var html = '<div class="pf-hl-main"><span class="pf-hl-primary">' + esc(item.company_name) + '</span>'
            + '<span class="pf-hl-date">' + esc(dateStr) + '</span></div>';
        if (sub) html += '<div class="pf-hl-sub">' + esc(sub) + '</div>';
        if (item.description) html += '<div class="pf-hl-desc">' + esc(item.description) + '</div>';
        if (tags.length) html += '<div class="pf-hl-tags">' + tags.map(function (t) { return '<span class="pf-tag">' + esc(t) + '</span>'; }).join('') + '</div>';
        return html;
    };

    RENDERERS.education = function (item) {
        var majors = joinParts([item.major, item.minor ? '(복수/' + item.minor + ')' : '']);
        var primary = joinParts([item.school_name, majors]);
        var gpaStr = item.gpa ? item.gpa + '/' + (item.gpa_scale || '4.5') : '';
        var sub = joinParts([
            fmtDateShort(item.start_date) + (item.end_date ? ' ~ ' + fmtDateShort(item.end_date) : ''),
            item.status, gpaStr
        ]);
        var html = '<div class="pf-hl-main"><span class="pf-hl-primary">' + esc(primary) + '</span>'
            + '<span class="pf-hl-date">' + esc(item.degree || '') + '</span></div>';
        if (sub) html += '<div class="pf-hl-sub">' + esc(sub) + '</div>';
        if (item.description) html += '<div class="pf-hl-desc">' + esc(item.description) + '</div>';
        return html;
    };

    RENDERERS.certification = function (item) {
        var nameWithGrade = item.cert_grade ? item.cert_name + ' (' + item.cert_grade + ')' : item.cert_name;
        var sub = joinParts([item.issuing_org, item.cert_number]);
        var dateStr = fmtDateShort(item.acquired_date);
        var tags = [];
        if (item.expiry_date) {
            dateStr += ' ~ ' + fmtDateShort(item.expiry_date);
            var now = new Date().toISOString().substring(0, 10);
            if (item.expiry_date < now) tags.push({ text: '만료', cls: 'pf-badge-danger' });
            else { var diff = Math.ceil((new Date(item.expiry_date) - new Date()) / 86400000); if (diff <= 90) tags.push({ text: '만료임박', cls: 'pf-badge-danger' }); }
        }
        var html = '<div class="pf-hl-main"><span class="pf-hl-primary">' + esc(nameWithGrade) + '</span>'
            + '<span class="pf-hl-date">' + esc(dateStr) + '</span></div>';
        if (sub) html += '<div class="pf-hl-sub">' + esc(sub) + '</div>';
        if (tags.length) html += '<div class="pf-hl-tags">' + tags.map(function (t) { return '<span class="pf-badge ' + t.cls + '">' + esc(t.text) + '</span>'; }).join('') + '</div>';
        return html;
    };

    RENDERERS.language = function (item) {
        var primary = joinParts([item.language, item.level]);
        var dateRight = joinParts([item.test_name, item.test_score]);
        var html = '<div class="pf-hl-main"><span class="pf-hl-primary">' + esc(primary) + '</span>'
            + '<span class="pf-hl-date">' + esc(dateRight) + '</span></div>';
        var subParts = [];
        if (item.test_date) subParts.push(fmtDateShort(item.test_date));
        if (item.validity_years) subParts.push('유효 ' + item.validity_years);
        var sub = joinParts(subParts);
        if (sub) html += '<div class="pf-hl-sub">' + esc(sub) + '</div>';
        return html;
    };

    RENDERERS.family = function (item) {
        var primary = joinParts([item.relationship, item.name]);
        var tags = [];
        if (item.is_cohabitant == 1) tags.push('동거');
        if (item.is_dependent == 1) tags.push('부양가족');
        if (item.is_health_dependent == 1) tags.push('건보 피부양');
        var sub = joinParts([item.phone].concat(tags));
        var html = '<div class="pf-hl-main"><span class="pf-hl-primary">' + esc(primary) + '</span>'
            + '<span class="pf-hl-date">' + esc(fmtDateShort(item.birth_date)) + '</span></div>';
        if (sub) html += '<div class="pf-hl-sub">' + esc(sub) + '</div>';
        if (item.memo) html += '<div class="pf-hl-desc">' + esc(item.memo) + '</div>';
        return html;
    };

    RENDERERS.award = function (item) {
        var badgeHtml = '';
        if (item.type) {
            var bCls = item.type === '징계' ? 'pf-badge-danger' : 'pf-badge-success';
            badgeHtml = '<span class="pf-badge ' + bCls + '" style="margin-right:6px">' + esc(item.type) + '</span>';
        }
        var html = '<div class="pf-hl-main"><span class="pf-hl-primary">' + badgeHtml + esc(item.title) + '</span>'
            + '<span class="pf-hl-date">' + esc(fmtDateShort(item.awarded_date)) + '</span></div>';
        var subParts = [item.awarding_org];
        if (item.discipline_level) subParts.push(item.discipline_level);
        if (item.follow_up_date) subParts.push('후속 ' + fmtDateShort(item.follow_up_date));
        var sub = joinParts(subParts);
        if (sub) html += '<div class="pf-hl-sub">' + esc(sub) + '</div>';
        if (item.description) html += '<div class="pf-hl-desc">' + esc(item.description) + '</div>';
        return html;
    };

    RENDERERS.military = function (item) {
        var statusBadge = '<span class="pf-badge">' + esc(item.military_status || '') + '</span>';
        var sub = joinParts([item.branch, item.branch_specialty, item.rank_title]);
        var dateStr = '';
        if (item.enlist_date) {
            dateStr = fmtDateShort(item.enlist_date);
            if (item.discharge_date) dateStr += ' ~ ' + fmtDateShort(item.discharge_date);
        }
        var tags = [];
        if (item.discharge_type) tags.push(item.discharge_type);
        var html = '<div class="pf-hl-main"><span class="pf-hl-primary">' + statusBadge + '</span>'
            + '<span class="pf-hl-date">' + esc(dateStr) + '</span></div>';
        if (sub) html += '<div class="pf-hl-sub">' + esc(sub) + '</div>';
        if (item.exemption_reason) html += '<div class="pf-hl-desc">' + esc(item.exemption_reason) + '</div>';
        if (tags.length) html += '<div class="pf-hl-tags">' + tags.map(function (t) { return '<span class="pf-tag">' + esc(t) + '</span>'; }).join('') + '</div>';
        return html;
    };

    // ─── 아코디언 토글 ──────────────────────────────────────

    function toggleAccordion(sectionEl, forceState) {
        var isExpanded = sectionEl.hasAttribute('data-pf-expanded');
        var shouldExpand = forceState !== undefined ? forceState : !isExpanded;
        var body = sectionEl.querySelector('.pf-accordion-body');
        if (!body) return;

        if (shouldExpand && !isExpanded) {
            sectionEl.setAttribute('data-pf-expanded', '');
            body.style.maxHeight = body.scrollHeight + 'px';
            setTimeout(function () { body.style.maxHeight = 'none'; }, 280);
        } else if (!shouldExpand && isExpanded) {
            body.style.maxHeight = body.scrollHeight + 'px';
            body.offsetHeight;
            sectionEl.removeAttribute('data-pf-expanded');
            body.style.maxHeight = '0';
        }
    }

    function initAccordionHeaders() {
        document.querySelectorAll('.pf-accordion-header').forEach(function (header) {
            header.addEventListener('click', function (e) {
                if (e.target.closest('.pf-accordion-add')) return;
                toggleAccordion(header.closest('.pf-accordion'));
            });
            header.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggleAccordion(header.closest('.pf-accordion'));
                }
            });
        });
    }

    // ─── 완성도 위젯 ────────────────────────────────────────

    var COMPLETENESS_MSGS = [
        '프로필을 채워 보세요', '프로필을 채워 보세요', '프로필을 채워 보세요',
        '잘 진행되고 있어요!', '잘 진행되고 있어요!', '잘 진행되고 있어요!',
        '거의 완성!', '거의 완성!', '프로필 완성!'
    ];

    function renderCompleteness() {
        var el = document.getElementById('pfCompleteness');
        if (!el) return;
        el.innerHTML = '<div class="pf-completeness-inner">'
            + '<div class="pf-completeness-header">'
            + '<span class="pf-completeness-label">프로필 완성도</span>'
            + '<span class="pf-completeness-pct" id="pfCompletePct">0%</span></div>'
            + '<div class="pf-completeness-bar" id="pfCompleteBar">'
            + SECTION_ORDER.map(function (k) {
                return '<div class="pf-completeness-segment" data-segment="' + k + '" title="' + esc(SECTION_LABELS[k]) + '"></div>';
            }).join('') + '</div>'
            + '<p class="pf-completeness-msg" id="pfCompleteMsg"></p></div>';
    }

    function updateCompleteness(sectionCounts) {
        var pctEl = document.getElementById('pfCompletePct');
        var msgEl = document.getElementById('pfCompleteMsg');
        var barEl = document.getElementById('pfCompleteBar');
        if (!pctEl || !msgEl || !barEl) return;

        var filled = 0;
        var emptyNames = [];
        SECTION_ORDER.forEach(function (k) {
            var cnt = sectionCounts[k] || 0;
            if (cnt > 0) filled++; else emptyNames.push(SECTION_LABELS[k]);
        });

        // 채운 개수만큼 왼쪽부터 순서대로 채운다(중간 빈칸 없이 좌→우로 차오름).
        var segs = barEl.querySelectorAll('.pf-completeness-segment');
        for (var i = 0; i < segs.length; i++) {
            segs[i].classList.toggle('filled', i < filled);
        }

        var pct = Math.round((filled / SECTION_ORDER.length) * 100);
        pctEl.textContent = pct + '%';
        var msg = COMPLETENESS_MSGS[filled] || '';
        if (filled < SECTION_ORDER.length && emptyNames.length > 0) {
            msg += ' ' + emptyNames.slice(0, 2).join(', ') + ' 정보를 추가해 보세요.';
        }
        msgEl.textContent = msg;
    }

    // ─── 폼 필드 빌더 (인라인용) ────────────────────────────

    function buildFields(cfg, item) {
        var html = '<div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-4">';
        cfg.fields.forEach(function (f) {
            var groupAttr = f.group ? ' data-pf-group="' + f.group + '"' : '';
            if (f.type === 'divider') {
                html += '<div class="col-span-1 sm:col-span-2 pf-modal-divider-wrap"' + groupAttr + '>'
                    + (f.label ? '<span class="pf-divider-label">' + esc(f.label) + '</span>' : '')
                    + '<hr class="pf-modal-divider"></div>';
                return;
            }
            var val = item ? (item[f.key] != null ? item[f.key] : '') : (f.type === 'select' && f.options ? f.options[0] : '');
            var spanCls = (f.span >= 3 || f.type === 'textarea') ? 'col-span-1 sm:col-span-2' : 'col-span-1';

            if (f.type === 'checkbox') {
                html += '<div class="' + spanCls + ' flex items-end pb-1"' + groupAttr + '>'
                    + '<label class="flex items-center gap-2 cursor-pointer">'
                    + '<input type="checkbox" data-pf="' + f.key + '"'
                    + ((item ? val == 1 : (f.key === 'is_cohabitant')) ? ' checked' : '')
                    + ' class="w-4 h-4 rounded" style="accent-color:var(--zm-primary)">'
                    + '<span class="text-sm" style="color:var(--zm-text-default)">' + esc(f.label) + '</span></label></div>';
            } else {
                html += '<div class="' + spanCls + ' pf-modal-field"' + groupAttr + '>';
                html += '<label class="pf-modal-label">' + esc(f.label) + (f.required ? ' <span style="color:var(--zm-primary)">*</span>' : '') + '</label>';
                if (f.type === 'select') {
                    html += '<select data-pf="' + f.key + '" class="reg-select w-full">';
                    (f.options || []).forEach(function (opt) {
                        html += '<option value="' + esc(opt) + '"' + (String(val) === String(opt) ? ' selected' : '') + '>' + esc(opt || '-') + '</option>';
                    });
                    html += '</select>';
                } else if (f.type === 'textarea') {
                    html += '<textarea data-pf="' + f.key + '" class="reg-input w-full resize-none" rows="3">' + esc(val) + '</textarea>';
                } else {
                    html += '<input type="' + f.type + '" data-pf="' + f.key + '" class="reg-input w-full" value="' + esc(val) + '"'
                        + (f.placeholder ? ' placeholder="' + esc(f.placeholder) + '"' : '')
                        + (f.step ? ' step="' + esc(f.step) + '"' : '')
                        + (f.min != null ? ' min="' + esc(f.min) + '"' : '')
                        + (f.max != null ? ' max="' + esc(f.max) + '"' : '') + '>';
                }
                html += '</div>';
            }
        });
        html += '</div>';
        return html;
    }

    function collectFormData(cfg, empId, editingId, formEl) {
        var data = { employee_id: parseInt(empId) };
        if (editingId) data.id = editingId;
        for (var i = 0; i < cfg.fields.length; i++) {
            var f = cfg.fields[i];
            if (f.type === 'divider') continue;
            var el = formEl.querySelector('[data-pf="' + f.key + '"]');
            if (!el) continue;
            data[f.key] = f.type === 'checkbox' ? (el.checked ? 1 : 0) : el.value;
        }
        for (var j = 0; j < cfg.fields.length; j++) {
            var fv = cfg.fields[j];
            if (fv.type === 'divider') continue;
            var wrapper = formEl.querySelector('[data-pf="' + fv.key + '"]');
            if (wrapper) {
                var parentW = wrapper.closest('[data-pf-group]');
                if (parentW && parentW.style.display === 'none') continue;
            }
            if (fv.required && (data[fv.key] === '' || data[fv.key] == null)) {
                alert(fv.label + '은(는) 필수입니다.');
                var elv = formEl.querySelector('[data-pf="' + fv.key + '"]');
                if (elv) elv.focus();
                return null;
            }
        }
        return data;
    }

    // ─── 아코디언 카운트/상태 ────────────────────────────────

    function updateAccordionState(key, count) {
        var accordion = document.querySelector('[data-pf-accordion="' + key + '"]');
        if (!accordion) return;
        var countEl = accordion.querySelector('[data-accordion-count="' + key + '"]');
        var emptyTag = accordion.querySelector('[data-accordion-empty="' + key + '"]');
        if (countEl) {
            countEl.textContent = count > 0 ? count : '';
            countEl.style.display = count > 0 ? '' : 'none';
        }
        if (emptyTag) emptyTag.style.display = count > 0 ? 'none' : '';
    }

    // ─── initSection (멀티 아이템 — 인라인 폼) ──────────────

    function initSection(key, cfg, container, empId, basePath) {
        var items = [];
        var formVisible = false;
        var editingId = null;

        var formWrapEl = document.createElement('div');
        formWrapEl.className = 'pf-section-form';
        formWrapEl.style.display = 'none';

        var listEl = document.createElement('div');
        listEl.className = 'space-y-3';

        container.appendChild(formWrapEl);
        container.appendChild(listEl);

        var headerAddBtn = document.querySelector('[data-profile-add="' + key + '"]');
        if (headerAddBtn) {
            headerAddBtn.addEventListener('click', function () {
                showForm(null);
            });
        }

        function load() {
            fetch(basePath + '/api/employee_profile.php?action=get' + cfg.plural + '&employee_id=' + empId)
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.ok) {
                        items = res.data && res.data.items ? res.data.items : [];
                        renderItems();
                        updateAccordionState(key, items.length);
                        var accordion = document.querySelector('[data-pf-accordion="' + key + '"]');
                        if (accordion && !accordion.hasAttribute('data-pf-init-done')) {
                            accordion.setAttribute('data-pf-init-done', '');
                            toggleAccordion(accordion, true);
                        }
                        if (items.length === 0 && !formVisible) {
                            showForm(null);
                        }
                    }
                })
                .catch(function () {
                    listEl.innerHTML = '<p class="text-sm" style="color:var(--zm-status-warn-fg)">서버 오류</p>';
                });
        }

        function renderItems() {
            if (!items.length) {
                listEl.innerHTML = '';
                return;
            }
            var renderer = RENDERERS[key];
            listEl.innerHTML = items.map(function (item) {
                var bodyHtml = renderer ? renderer(item) : fallbackRender(item, cfg);
                return '<div class="pf-entry-card">'
                    + '<div class="pf-actions">'
                    + '<button type="button" class="pf-action-btn pf-btn-edit" data-id="' + item.id + '" title="수정"><i data-lucide="pencil" class="w-3.5 h-3.5"></i></button>'
                    + '<button type="button" class="pf-action-btn pf-btn-del" data-id="' + item.id + '" title="삭제"><i data-lucide="x" class="w-4 h-4"></i></button></div>'
                    + bodyHtml + '</div>';
            }).join('');
            if (window.lucide) lucide.createIcons();

            listEl.querySelectorAll('.pf-btn-edit').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var it = items.find(function (i) { return i.id == btn.dataset.id; });
                    if (it) showForm(it);
                });
            });
            listEl.querySelectorAll('.pf-btn-del').forEach(function (btn) {
                btn.addEventListener('click', function () { deleteItem(Number(btn.dataset.id)); });
            });
        }

        function fallbackRender(item, c) {
            var fieldsHtml = c.fields
                .filter(function (f) { return f.type !== 'checkbox' && f.type !== 'divider'; })
                .map(function (f) {
                    var raw = item[f.key];
                    var val = f.type === 'date' ? fmtDate(raw) : ((raw != null && raw !== '') ? String(raw) : '');
                    var isEmpty = val === '';
                    return '<div class="col-span-' + (f.span || 2) + ' pf-field-display">'
                        + '<div class="pf-disp-label">' + esc(f.label) + '</div>'
                        + '<div class="pf-disp-value' + (isEmpty ? ' pf-disp-empty' : '') + '">' + (isEmpty ? '-' : esc(val)) + '</div></div>';
                }).join('');
            return '<div class="grid grid-cols-4 gap-x-5 gap-y-3">' + fieldsHtml + '</div>';
        }

        function showForm(item) {
            editingId = item ? item.id : null;
            var isEdit = !!item;
            var label = SECTION_LABELS[key] || key;
            var isEmpty = items.length === 0;
            var title = isEmpty ? (EMPTY_PROMPTS[key] || label + ' 등록')
                : (isEdit ? label + ' 수정' : label + ' 추가');

            var html = '<div class="pf-inline-form">'
                + '<div class="pf-inline-form-title">' + esc(title) + '</div>'
                + buildFields(cfg, item)
                + '<div class="pf-inline-form-actions">';
            if (!isEmpty || isEdit) {
                html += '<button type="button" class="pf-form-cancel px-4 py-2 text-sm rounded-lg" '
                    + 'style="color:var(--zm-text-muted);border:1px solid var(--zm-border);background:var(--zm-surface-1)">취소</button>';
            }
            html += '<button type="button" class="pf-form-save px-4 py-2 text-sm font-medium rounded-lg" '
                + 'style="color:#fff;background:var(--zm-primary)">저장</button>'
                + '</div></div>';

            formWrapEl.innerHTML = html;
            formWrapEl.style.display = '';
            formVisible = true;

            var formEl = formWrapEl.querySelector('.pf-inline-form');
            if (cfg.conditions) cfg.conditions(formEl);
            if (window.lucide) lucide.createIcons();

            var accordion = document.querySelector('[data-pf-accordion="' + key + '"]');
            if (accordion) toggleAccordion(accordion, true);

            formEl.querySelector('.pf-form-save').addEventListener('click', function () {
                var data = collectFormData(cfg, empId, editingId, formEl);
                if (!data) return;
                fetch(basePath + '/api/employee_profile.php?action=save' + cfg.singular, {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (res.ok) { hideForm(true); load(); refreshCounts(basePath, empId); }
                        else alert(res.error && res.error.message || '저장 실패');
                    })
                    .catch(function () { alert('서버 오류'); });
            });

            var cancelBtn = formEl.querySelector('.pf-form-cancel');
            if (cancelBtn) cancelBtn.addEventListener('click', function () { hideForm(false); });

            var first = formEl.querySelector('input[type="text"], select');
            if (first) first.focus();
        }

        function hideForm(force) {
            if (!force && items.length === 0) return;
            formWrapEl.style.display = 'none';
            formWrapEl.innerHTML = '';
            formVisible = false;
            editingId = null;
        }

        async function deleteItem(id) {
            if (!(await AppUI.confirm('삭제하시겠습니까?'))) return;
            fetch(basePath + '/api/employee_profile.php?action=delete' + cfg.singular, {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.ok) { load(); refreshCounts(basePath, empId); }
                    else alert(res.error && res.error.message || '삭제 실패');
                })
                .catch(function () { alert('서버 오류'); });
        }

        load();
    }

    // ─── initSingleton (병역 — 인라인) ──────────────────────

    function initSingleton(key, cfg, container, empId, basePath) {
        var item = null;

        function load() {
            fetch(basePath + '/api/employee_profile.php?action=get' + cfg.plural + '&employee_id=' + empId)
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.ok) {
                        item = (res.data && res.data.items && res.data.items[0]) || null;
                        render();
                        updateAccordionState(key, item ? 1 : 0);
                        var accordion = document.querySelector('[data-pf-accordion="' + key + '"]');
                        if (accordion && !accordion.hasAttribute('data-pf-init-done')) {
                            accordion.setAttribute('data-pf-init-done', '');
                            if (!item) {
                                showForm();
                                toggleAccordion(accordion, true);
                            } else {
                                toggleAccordion(accordion, true);
                            }
                        }
                    }
                });
        }

        function render() {
            if (!item) {
                container.innerHTML = '';
                return;
            }
            var renderer = RENDERERS[key];
            var bodyHtml = renderer ? renderer(item) : '';
            container.innerHTML = '<div class="pf-entry-card" style="cursor:pointer">'
                + '<div class="pf-actions">'
                + '<button type="button" class="pf-action-btn pf-btn-edit" title="수정"><i data-lucide="pencil" class="w-3.5 h-3.5"></i></button>'
                + '<button type="button" class="pf-action-btn pf-btn-del" title="초기화"><i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i></button>'
                + '</div>' + bodyHtml + '</div>';
            container.querySelector('.pf-btn-edit').addEventListener('click', function () { showForm(); });
            container.querySelector('.pf-entry-card').addEventListener('click', function (e) {
                if (!e.target.closest('.pf-action-btn')) showForm();
            });
            container.querySelector('.pf-btn-del').addEventListener('click', async function () {
                if (!(await AppUI.confirm('병역 정보를 초기화하시겠습니까?'))) return;
                fetch(basePath + '/api/employee_profile.php?action=delete' + cfg.singular, {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: item.id })
                })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (res.ok) { item = null; render(); showForm(); refreshCounts(basePath, empId); }
                        else alert(res.error && res.error.message || '삭제 실패');
                    })
                    .catch(function () { alert('서버 오류'); });
            });
            if (window.lucide) lucide.createIcons();

            var headerAddBtn = document.querySelector('[data-profile-add="' + key + '"]');
            if (headerAddBtn) headerAddBtn.style.display = 'none';
        }

        function showForm() {
            var label = SECTION_LABELS[key] || key;
            var title = item ? label + ' 수정' : (EMPTY_PROMPTS[key] || label + ' 등록');

            var html = '<div class="pf-inline-form">'
                + '<div class="pf-inline-form-title">' + esc(title) + '</div>'
                + buildFields(cfg, item)
                + '<div class="pf-inline-form-actions">';
            if (item) {
                html += '<button type="button" class="pf-form-cancel px-4 py-2 text-sm rounded-lg" '
                    + 'style="color:var(--zm-text-muted);border:1px solid var(--zm-border);background:var(--zm-surface-1)">취소</button>';
            }
            html += '<button type="button" class="pf-form-save px-4 py-2 text-sm font-medium rounded-lg" '
                + 'style="color:#fff;background:var(--zm-primary)">저장</button></div></div>';

            container.innerHTML = html;
            var formEl = container.querySelector('.pf-inline-form');
            if (cfg.conditions) cfg.conditions(formEl);
            if (window.lucide) lucide.createIcons();

            var accordion = document.querySelector('[data-pf-accordion="' + key + '"]');
            if (accordion) toggleAccordion(accordion, true);

            formEl.querySelector('.pf-form-save').addEventListener('click', function () {
                var data = { employee_id: parseInt(empId) };
                cfg.fields.forEach(function (f) {
                    if (f.type === 'divider') return;
                    var el = formEl.querySelector('[data-pf="' + f.key + '"]');
                    if (el) data[f.key] = f.type === 'checkbox' ? (el.checked ? 1 : 0) : el.value;
                });
                fetch(basePath + '/api/employee_profile.php?action=save' + cfg.singular, {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (res.ok) { load(); refreshCounts(basePath, empId); }
                        else alert(res.error && res.error.message || '저장 실패');
                    })
                    .catch(function () { alert('서버 오류'); });
            });

            var cancelBtn = formEl.querySelector('.pf-form-cancel');
            if (cancelBtn) cancelBtn.addEventListener('click', function () { render(); });

            var first = formEl.querySelector('select, input[type="text"]');
            if (first) first.focus();
        }

        load();
    }

    // ─── initTagBased (스킬) ──────────────────────────────────

    function initTagBased(key, cfg, container, empId, basePath) {
        var items = [];
        var tagsWrap = document.createElement('div');
        tagsWrap.className = 'flex flex-wrap gap-2 mb-3';

        var inputWrap = document.createElement('div');
        inputWrap.className = 'flex gap-2';
        inputWrap.innerHTML = '<input type="text" class="reg-input flex-1" placeholder="스킬을 입력하세요">'
            + '<button type="button" class="px-4 py-2 text-sm font-medium rounded-lg" style="color:#fff;background:var(--zm-primary);white-space:nowrap">추가</button>';

        container.appendChild(tagsWrap);
        container.appendChild(inputWrap);

        var headerAddBtn = document.querySelector('[data-profile-add="' + key + '"]');
        if (headerAddBtn) headerAddBtn.classList.add('hidden');

        var input = inputWrap.querySelector('input');
        var addBtn = inputWrap.querySelector('button');
        addBtn.addEventListener('click', function () { addSkill(); });
        input.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); addSkill(); } });

        function load() {
            fetch(basePath + '/api/employee_profile.php?action=get' + cfg.plural + '&employee_id=' + empId)
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.ok) {
                        items = res.data && res.data.items ? res.data.items : [];
                        render();
                        updateAccordionState(key, items.length);
                        var accordion = document.querySelector('[data-pf-accordion="' + key + '"]');
                        if (accordion && !accordion.hasAttribute('data-pf-init-done')) {
                            accordion.setAttribute('data-pf-init-done', '');
                            toggleAccordion(accordion, true);
                        }
                    }
                });
        }

        function render() {
            if (!items.length) { tagsWrap.innerHTML = ''; return; }
            tagsWrap.innerHTML = items.map(function (t) {
                return '<span class="pf-tag pf-tag-removable" style="gap:6px;padding-right:6px">'
                    + esc(t.skill_name)
                    + '<button type="button" data-id="' + t.id + '" class="pf-tag-x" style="width:16px;height:16px;display:inline-flex;align-items:center;justify-content:center;border-radius:50%;cursor:pointer;opacity:0.6" title="삭제">&times;</button></span>';
            }).join('');
            tagsWrap.querySelectorAll('.pf-tag-x').forEach(function (btn) {
                btn.addEventListener('click', function () { deleteSkill(Number(btn.dataset.id)); });
            });
        }

        function addSkill() {
            var name = input.value.trim();
            if (!name) return;
            fetch(basePath + '/api/employee_profile.php?action=save' + cfg.singular, {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ employee_id: parseInt(empId), skill_name: name })
            })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.ok) { input.value = ''; load(); refreshCounts(basePath, empId); }
                    else alert(res.error && res.error.message || '추가 실패');
                })
                .catch(function () { alert('서버 오류'); });
        }

        function deleteSkill(id) {
            fetch(basePath + '/api/employee_profile.php?action=delete' + cfg.singular, {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.ok) { load(); refreshCounts(basePath, empId); }
                    else alert(res.error && res.error.message || '삭제 실패');
                })
                .catch(function () { alert('서버 오류'); });
        }

        load();
    }

    // ─── 네비게이션 사이드바 ──────────────────────────────────

    function initNavSidebar(container, empId, basePath) {
        var navAside = document.getElementById('profileNav');
        if (!navAside) return;
        var navInner = navAside.querySelector('div');
        if (!navInner) return;

        navInner.innerHTML = SECTION_ORDER.map(function (key) {
            if (!container.querySelector('[data-profile-section="' + key + '"]')) return '';
            return '<a href="#" data-nav-key="' + key + '" class="pf-nav-link">'
                + '<i data-lucide="' + (SECTION_ICONS[key] || 'file-text') + '" class="w-3.5 h-3.5"></i>'
                + '<span class="flex-1 truncate">' + esc(SECTION_LABELS[key] || key) + '</span>'
                + '<span class="pf-nav-count" data-nav-count="' + key + '"></span></a>';
        }).join('');

        if (window.lucide) lucide.createIcons();

        navInner.querySelectorAll('.pf-nav-link').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                var accordion = document.querySelector('[data-pf-accordion="' + link.dataset.navKey + '"]');
                if (accordion) {
                    toggleAccordion(accordion, true);
                    accordion.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    var accordion = entry.target.closest('[data-pf-accordion]');
                    var k = accordion ? accordion.dataset.pfAccordion : '';
                    navInner.querySelectorAll('.pf-nav-link').forEach(function (l) {
                        l.classList.toggle('pf-nav-active', l.dataset.navKey === k);
                    });
                }
            });
        }, { rootMargin: '-80px 0px -60% 0px', threshold: 0 });

        SECTION_ORDER.forEach(function (key) {
            var el = container.querySelector('[data-profile-section="' + key + '"]');
            if (el) observer.observe(el);
        });
    }

    // ─── 카운트 갱신 ─────────────────────────────────────────

    function refreshCounts(basePath, empId) {
        fetch(basePath + '/api/employee_profile.php?action=getProfileSummary&employee_id=' + empId)
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.ok || !res.data || !res.data.counts) return;
                var counts = res.data.counts;
                var keyMap = {
                    careers: 'career', educations: 'education', certifications: 'certification',
                    languages: 'language', families: 'family', awards: 'award',
                    military: 'military', skills: 'skills'
                };
                var sectionCounts = {};
                Object.keys(counts).forEach(function (plural) {
                    var k = keyMap[plural];
                    if (k) sectionCounts[k] = counts[plural];
                });
                SECTION_ORDER.forEach(function (k) { updateAccordionState(k, sectionCounts[k] || 0); });

                var navWrapper = document.getElementById('profileSections');
                if (navWrapper) {
                    Object.keys(sectionCounts).forEach(function (k) {
                        var badge = navWrapper.parentElement.querySelector('[data-nav-count="' + k + '"]');
                        if (badge) {
                            var cnt = sectionCounts[k];
                            badge.textContent = cnt > 0 ? cnt : '';
                            badge.style.display = cnt > 0 ? '' : 'none';
                        }
                    });
                }
                updateCompleteness(sectionCounts);
            });
    }

    // ─── 초기화 ──────────────────────────────────────────────

    function initAll() {
        var container = document.getElementById('profileSections');
        if (!container) return;
        var empId = container.dataset.employeeId;
        var basePath = container.dataset.basePath || '';
        if (!empId) return;

        initAccordionHeaders();
        renderCompleteness();

        SECTION_ORDER.forEach(function (key) {
            var el = container.querySelector('[data-profile-section="' + key + '"]');
            if (!el) return;
            var cfg = SECTIONS[key];
            if (cfg.isSingleton) initSingleton(key, cfg, el, empId, basePath);
            else if (cfg.isTagBased) initTagBased(key, cfg, el, empId, basePath);
            else initSection(key, cfg, el, empId, basePath);
        });

        initNavSidebar(container, empId, basePath);
        refreshCounts(basePath, empId);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
