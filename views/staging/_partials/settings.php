<?php

/**
 * @var string $name
 * @var string $label
 * @var string $description
 * @var string $summary
 * @var bool   $checked
 * @var bool   $disabled
 * @var string $classes
 * @var string $dataId
 * @var string $infoIcon
 * @var string $content
 * @var string $tooltip
 */

use WPStaging\Framework\Facades\Escape;
use WPStaging\Framework\Facades\UI\Checkbox;

$attributes = [
    'isDisabled'   => $disabled,
    'usePrimitive' => true,
];

if (!empty($classes)) {
    $attributes['classes'] = $classes;
}

$dataAttributes = [];
if (!empty($dataId)) {
    $dataAttributes['id'] = $dataId;
}

$descriptionParts   = preg_split('/<br\s*\/?>/i', (string)$description);
$visibleDescription = !empty($summary) ? $summary : (is_array($descriptionParts) && isset($descriptionParts[0]) ? trim(wp_strip_all_tags($descriptionParts[0])) : '');
$hasContent         = !empty(trim((string)$content));
$hasTooltip         = !empty(trim((string)$tooltip));
$rowClass           = 'wpstg-advanced-settings-row wpstg-flex wpstg-items-start wpstg-gap-3 wpstg-border-0 wpstg-border-b wpstg-border-solid wpstg-border-slate-200 wpstg-bg-white wpstg-px-4 wpstg-py-2 last:wpstg-border-b-0 dark:wpstg-border-slate-700 dark:wpstg-bg-slate-900/60';
if ($hasContent) {
    $rowClass .= ' wpstg-advanced-settings-row--expandable wpstg-flex-col wpstg-gap-0';
}

?>
<div class="<?php echo esc_attr($rowClass); ?>">
    <div class="wpstg-flex wpstg-w-full wpstg-items-start wpstg-gap-3">
        <div class="wpstg-mt-0.5 wpstg-flex wpstg-flex-shrink-0 wpstg-items-center">
            <?php Checkbox::render($name, $name, 'true', $checked, $attributes, $dataAttributes); ?>
        </div>
        <label for="<?php echo esc_attr($name) ?>" class="wpstg-min-w-0 wpstg-flex-1 wpstg-cursor-pointer">
            <span class="wpstg-block wpstg-text-sm wpstg-font-bold wpstg-leading-5 wpstg-text-[#001b3d] dark:wpstg-text-slate-100"><?php echo esc_html($label); ?></span>
            <span class="wpstg-advanced-settings-description wpstg-mt-1 wpstg-block wpstg-text-sm wpstg-leading-5 wpstg-text-[#536579] dark:wpstg-text-slate-400"><?php echo esc_html($visibleDescription); ?></span>
        </label>
        <?php if ($hasTooltip) : ?>
            <span class="wpstg--tooltip wpstg-ml-auto wpstg-mt-0.5 wpstg-flex wpstg-h-5 wpstg-w-5 wpstg-flex-shrink-0 wpstg-items-center wpstg-justify-center">
                <img class="wpstg--dashicons wpstg-opacity-60" src="<?php echo esc_url($infoIcon); ?>" alt="info" />
                <span class="wpstg--tooltiptext">
                    <?php echo Escape::escapeHtml($tooltip); ?>
                </span>
            </span>
        <?php endif; ?>
    </div>
    <?php echo $content; // phpcs:ignore ?>
</div>
