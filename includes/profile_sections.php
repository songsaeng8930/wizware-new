<?php
/**
 * 프로필 아코디언 섹션 공통 템플릿
 * my_profile.php / employee_register.php 에서 include
 *
 * 필요 변수: $pfEmpId (int), $pfBasePath (string)
 * 필요 함수: empAccordionCard() (card_helpers.php)
 */

if (!isset($pfEmpId) || !isset($pfBasePath)) return;
require_once __DIR__ . '/card_helpers.php';

$pfSections = [
    ['key'=>'career',        'icon'=>'briefcase',      'title'=>'경력사항',     'priority'=>'required',    'add'=>true],
    ['key'=>'education',     'icon'=>'graduation-cap', 'title'=>'학력',         'priority'=>'required',    'add'=>true],
    ['key'=>'military',      'icon'=>'shield',         'title'=>'병역사항',     'priority'=>'recommended', 'add'=>false],
    ['key'=>'family',        'icon'=>'users',          'title'=>'가족정보',     'priority'=>'recommended', 'add'=>true],
    ['key'=>'language',      'icon'=>'globe',          'title'=>'언어능력',     'priority'=>'optional',    'add'=>true],
    ['key'=>'skills',        'icon'=>'zap',            'title'=>'보유 스킬',    'priority'=>'optional',    'add'=>true],
    ['key'=>'certification', 'icon'=>'award',          'title'=>'자격증',       'priority'=>'recommended', 'add'=>true],
    ['key'=>'award',         'icon'=>'trophy',         'title'=>'수상 / 징계',  'priority'=>'optional',    'add'=>true],
];
?>
<div id="pfCompleteness"></div>
<div id="profileSections"
     data-employee-id="<?= (int)$pfEmpId ?>"
     data-base-path="<?= htmlspecialchars($pfBasePath, ENT_QUOTES) ?>"
     class="space-y-3 mt-4">
    <?php foreach ($pfSections as $s): ?>
        <?php empAccordionCard($s['key'], $s['icon'], $s['title'], $s['priority'], $s['add']); ?>
    <?php endforeach; ?>
</div>
