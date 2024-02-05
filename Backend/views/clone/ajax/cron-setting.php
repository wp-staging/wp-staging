<?php

/**
 * This file is currently being called for the both FREE and PRO version:
 * src/Backend/views/clone/ajax/scan.php:64
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
$cronDisabled   = false;
// If plugin is not pro disable this Option
if (!$isPro) {
    $settingsEnabled = false;
}?>
<div class="wpstg--advance-settings--checkbox wpstg-advanced-setting-container">
    <label for="wpstg_disable_cron"><?php esc_html_e('Disable WP_CRON', 'wp-staging'); ?></label>
    <?php Checkbox::render('wpstg_disable_cron', 'wpstg_disable_cron', 'true', $cronDisabled, ['isDisabled' => !$settingsEnabled]); ?>
    <span class="wpstg--tooltip">
        <img class="wpstg--dashicons" src="<?php echo esc_url($scan->getInfoIcon()); ?>" alt="info" />
        <span class="wpstg--tooltiptext">
            <?php esc_html_e('Will disable WordPress cron on the staging site.', 'wp-staging'); ?>
        </span>
    </span>
</div>
