<?php
/**
 * 공유 카드 UI 헬퍼 · empCardOpen / empCardClose
 *
 * employee_register.php, my_profile.php 등에서 공통 사용.
 */

if (!function_exists('empCardOpen')) {
    function empCardOpen(string $icon, string $title, string $desc = '', string $safeActionHtml = ''): void {
        $iconSafe  = htmlspecialchars($icon, ENT_QUOTES, 'UTF-8');
        $titleSafe = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $descHtml  = '';
        echo <<<HTML
<section class="bg-[var(--zm-surface-1)] rounded-xl border border-[var(--zm-border)] overflow-hidden shadow-sm">
    <header class="flex items-center justify-between gap-3 px-5 py-3 bg-[var(--zm-surface-2)]">
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-primary/15 text-primary">
                <i data-lucide="{$iconSafe}" class="w-3.5 h-3.5"></i>
            </span>
            <div>
                <h3 class="text-sm font-semibold text-[var(--zm-text-strong)]">{$titleSafe}</h3>
                {$descHtml}
            </div>
        </div>
        <div class="flex items-center gap-2">{$safeActionHtml}</div>
    </header>
    <div class="p-5">
HTML;
    }

    function empCardClose(): void { echo "</div></section>"; }
}

if (!function_exists('empAccordionCard')) {
    function empAccordionCard(string $key, string $icon, string $title, string $priority = 'optional', bool $showAdd = true): void {
        $iconSafe  = htmlspecialchars($icon, ENT_QUOTES, 'UTF-8');
        $titleSafe = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $keySafe   = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
        $prioSafe  = htmlspecialchars($priority, ENT_QUOTES, 'UTF-8');
        $addHtml   = $showAdd
            ? '<button type="button" data-profile-add="' . $keySafe . '" class="pf-accordion-add" onclick="event.stopPropagation()"><i data-lucide="plus" class="w-3 h-3"></i>추가</button>'
            : '';
        echo <<<HTML
<section class="pf-accordion pf-priority-{$prioSafe}" data-pf-accordion="{$keySafe}">
    <div class="pf-accordion-header" role="button" tabindex="0">
        <span class="pf-accordion-icon"><i data-lucide="{$iconSafe}"></i></span>
        <span class="pf-accordion-title">{$titleSafe}</span>
        <span class="pf-accordion-count badge-info" data-accordion-count="{$keySafe}" style="display:none"></span>
        <span class="pf-accordion-empty-tag" data-accordion-empty="{$keySafe}">미입력</span>
        <div class="pf-accordion-right">
            {$addHtml}
            <span class="pf-accordion-chevron"><i data-lucide="chevron-down"></i></span>
        </div>
    </div>
    <div class="pf-accordion-body">
        <div data-profile-section="{$keySafe}"></div>
    </div>
</section>
HTML;
    }
}
