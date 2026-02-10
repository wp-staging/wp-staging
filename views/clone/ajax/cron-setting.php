<?php

/**
 * This file is currently being called for the both FREE and PRO version:
 * src/views/clone/ajax/scan.php:64
 *
 * @var \WPStaging\Backend\Modules\Jobs\Scan $scan
 * @var stdClass                             $options
 * @var boolean                              $isPro
 *
 * @see \WPStaging\Backend\Modules\Jobs\Scan::start For details on $options.
 */

// Settings Enabled by default
use WPStaging\Framework\Facades\UI\Checkbox;

$settingsEnabled = true;
$isCronEnabled     = true;
// If plugin is not pro disable this Option
if (!$isPro) {
    $settingsEnabled = false;
}?>
<div class="wpstg--advanced-settings--checkbox">
    <label for="wpstg_enable_cron"><?php esc_html_e('Enable Cron Jobs', 'wp-staging'); ?></label>
    <?php Checkbox::render('wpstg_enable_cron', 'wpstg_enable_cron', 'true', $isCronEnabled, ['isDisabled' => !$settingsEnabled]); ?>
    <span class="wpstg--tooltip">
        <img class="wpstg--dashicons" src="<?php echo esc_url($scan->getInfoIcon()); ?>" alt="info" />
        <span class="wpstg--tooltiptext">
            <?php esc_html_e('Will enable WordPress cron on the staging site.', 'wp-staging'); ?>
        </span>
    </span>
</div>
