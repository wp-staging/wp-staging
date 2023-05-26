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
$settingsEnabled = true;
$cronDisabled   = false;
// If plugin is not pro disable this Option
if (!$isPro) {
    $settingsEnabled = false;
}?>
<p class="wpstg--advance-settings--checkbox">
    <label for="wpstg_disable_cron"><?php esc_html_e('Disable WP_CRON', 'wp-staging'); ?></label>
    <input type="checkbox" class="wpstg-checkbox" id="wpstg_disable_cron" name="wpstg_disable_cron" value="true" <?php echo $cronDisabled === true ? 'checked' : '' ?> <?php echo $settingsEnabled === false ? 'disabled' : '' ?> />
    <span class="wpstg--tooltip">
        <img class="wpstg--dashicons" src="<?php echo esc_url($scan->getInfoIcon()); ?>" alt="info" />
        <span class="wpstg--tooltiptext">
            <?php esc_html_e('Will disable WordPress cron on the staging site.', 'wp-staging'); ?>
        </span>
    </span>
</p>
