<?php
/**
 * 은행 BI/CI 브랜드 매칭 + 뱃지 HTML (PHP 서버사이드용)
 */

function getBankBrandPHP(string $name): ?array
{
    static $brands = [
        [['국민'], '#FFBC00', 'KB', 'kb'],
        [['신한'], '#0046FF', '신한', 'shinhan'],
        [['하나'], '#009B8D', '하나', 'hana'],
        [['우리'], '#0056A6', '우리', 'woori'],
        [['기업', 'IBK'], '#005BAC', 'IBK', 'ibk'],
        [['농협', 'NH'], '#02A650', 'NH', 'nh'],
        [['SC제일'], '#0072AA', 'SC', 'sc'],
        [['씨티', 'Citi'], '#003B70', 'Citi', 'citi'],
        [['대구', 'DGB'], '#D4272F', 'DGB', 'dgb'],
        [['부산'], '#0066B3', 'BNK', 'bnk'],
        [['케이뱅크', 'K뱅크'], '#6B4FBB', 'K', 'kbank'],
        [['카카오'], '#FFCD00', '카뱅', 'kakaobank'],
        [['토스'], '#0064FF', '토스', 'tossbank'],
        [['수협'], '#003DA5', '수협', 'suhyup'],
        [['새마을', 'MG'], '#00A651', 'MG', 'saemaul'],
        [['우체국'], '#ED1C24', '우체국', 'post'],
        [['산업', 'KDB'], '#003478', 'KDB', 'kdb'],
        [['경남'], '#D32F2F', 'BNK', 'bnk'],
        [['광주'], '#00A3E0', 'KJB', 'kwangju'],
        [['전북'], '#00875A', 'JBB', 'jeonbuk'],
        [['제주'], '#E96B27', '제주', 'jeju'],
    ];

    foreach ($brands as [$keys, $color, $abbr, $icon]) {
        foreach ($keys as $k) {
            if (mb_strpos($name, $k) !== false) {
                return compact('color', 'abbr', 'icon');
            }
        }
    }
    return null;
}

function bankBadgeHtmlPHP(string $bankName, string $basePath, string $size = 'sm'): string
{
    $brand = getBankBrandPHP($bankName);
    $color = $brand ? $brand['color'] : '#64748b';
    $abbr  = $brand ? htmlspecialchars($brand['abbr']) : htmlspecialchars(mb_substr($bankName ?: '?', 0, 1));
    $icon  = $brand ? $brand['icon'] : null;
    $imgBase = $basePath . '/assets/img/banks';

    $outer = $size === 'lg' ? 'w-10 h-10' : 'w-8 h-8';
    $img   = $size === 'lg' ? 'w-7 h-7'   : 'w-6 h-6';
    $txt   = $size === 'lg' ? 'text-xs'    : 'text-[11px]';
    $alt   = htmlspecialchars($bankName);

    if ($icon) {
        return "<div class=\"{$outer} rounded-lg flex items-center justify-center shrink-0 overflow-hidden bg-white\">"
            . "<img src=\"{$imgBase}/{$icon}.png\" class=\"{$img} object-contain\" "
            . "onerror=\"this.style.display='none';this.nextElementSibling.style.display=''\" alt=\"{$alt}\">"
            . "<span style=\"display:none;color:{$color}\" class=\"{$txt} font-bold leading-none\">{$abbr}</span>"
            . "</div>";
    }
    return "<div class=\"{$outer} rounded-lg flex items-center justify-center shrink-0\" style=\"background:{$color}20\">"
        . "<span style=\"color:{$color}\" class=\"{$txt} font-bold leading-none\">{$abbr}</span>"
        . "</div>";
}
