/**
 * 은행 BI/CI 브랜드 매칭 + 뱃지 렌더링
 * 사용 전 window.BANK_IMG 경로 설정 필요
 */

const BANK_BRANDS = [
    { keys: ['국민'], color: '#FFBC00', abbr: 'KB', icon: 'kb' },
    { keys: ['신한'], color: '#0046FF', abbr: '신한', icon: 'shinhan' },
    { keys: ['하나'], color: '#009B8D', abbr: '하나', icon: 'hana' },
    { keys: ['우리'], color: '#0056A6', abbr: '우리', icon: 'woori' },
    { keys: ['기업', 'IBK'], color: '#005BAC', abbr: 'IBK', icon: 'ibk' },
    { keys: ['농협', 'NH'], color: '#02A650', abbr: 'NH', icon: 'nh' },
    { keys: ['SC제일'], color: '#0072AA', abbr: 'SC', icon: 'sc' },
    { keys: ['씨티', 'Citi'], color: '#003B70', abbr: 'Citi', icon: 'citi' },
    { keys: ['대구', 'DGB'], color: '#D4272F', abbr: 'DGB', icon: 'dgb' },
    { keys: ['부산'], color: '#0066B3', abbr: 'BNK', icon: 'bnk' },
    { keys: ['케이뱅크', 'K뱅크'], color: '#6B4FBB', abbr: 'K', icon: 'kbank' },
    { keys: ['카카오'], color: '#FFCD00', abbr: '카뱅', icon: 'kakaobank' },
    { keys: ['토스'], color: '#0064FF', abbr: '토스', icon: 'tossbank' },
    { keys: ['수협'], color: '#003DA5', abbr: '수협', icon: 'suhyup' },
    { keys: ['새마을', 'MG'], color: '#00A651', abbr: 'MG', icon: 'saemaul' },
    { keys: ['우체국'], color: '#ED1C24', abbr: '우체국', icon: 'post' },
    { keys: ['산업', 'KDB'], color: '#003478', abbr: 'KDB', icon: 'kdb' },
    { keys: ['경남'], color: '#D32F2F', abbr: 'BNK', icon: 'bnk' },
    { keys: ['광주'], color: '#00A3E0', abbr: 'KJB', icon: 'kwangju' },
    { keys: ['전북'], color: '#00875A', abbr: 'JBB', icon: 'jeonbuk' },
    { keys: ['제주'], color: '#E96B27', abbr: '제주', icon: 'jeju' },
];

function getBankBrand(name) {
    if (!name) return null;
    for (const b of BANK_BRANDS) {
        if (b.keys.some(k => name.includes(k))) return b;
    }
    return null;
}

function _escBank(s) {
    return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function bankBadgeHtml(bankName, size) {
    const brand = getBankBrand(bankName);
    const color = brand ? brand.color : '#64748b';
    const abbr = brand ? brand.abbr : (bankName || '?').charAt(0);
    const icon = brand ? brand.icon : null;
    const imgBase = window.BANK_IMG || '';

    const sz = size || 'sm';
    const outer = sz === 'lg' ? 'w-10 h-10' : 'w-8 h-8';
    const img   = sz === 'lg' ? 'w-7 h-7'   : 'w-6 h-6';
    const txt   = sz === 'lg' ? 'text-xs'    : 'text-[11px]';

    if (icon && imgBase) {
        return '<div class="' + outer + ' rounded-lg flex items-center justify-center shrink-0 overflow-hidden bg-white">'
            + '<img src="' + imgBase + '/' + icon + '.png" class="' + img + ' object-contain" '
            + 'onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'\'" alt="' + _escBank(bankName) + '">'
            + '<span style="display:none;color:' + color + '" class="' + txt + ' font-bold leading-none">' + _escBank(abbr) + '</span>'
            + '</div>';
    }
    return '<div class="' + outer + ' rounded-lg flex items-center justify-center shrink-0" style="background:' + color + '20">'
        + '<span style="color:' + color + '" class="' + txt + ' font-bold leading-none">' + _escBank(abbr) + '</span>'
        + '</div>';
}
