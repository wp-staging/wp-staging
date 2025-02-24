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
// New staging site. Mails Sending is checked by default.
if (!$isPro) {
    $settingsEnabled = false;
}

// Only change default check status when clone options exists plugin is PRO
$isAutoUpdatePlugins = false;
if ($isPro && !empty($options->current)) {
    $isAutoUpdatePlugins = isset($options->existingClones[$options->current]['isAutoUpdatePlugins']) ? (bool) $options->existingClones[$options->current]['isAutoUpdatePlugins'] : false;
} ?>
<div class="wpstg--advanced-settings--checkbox">
    <label for="wpstg_auto_update_plugins"><?php esc_html_e('Auto Update Plugins', 'wp-staging'); ?></label>
    <?php Checkbox::render('wpstg_auto_update_plugins', 'wpstg_auto_update_plugins', 'true', $isAutoUpdatePlugins, ['isDisabled' => !$settingsEnabled]); ?>
    <span class="wpstg--tooltip">
        <img class="wpstg--dashicons" src="<?php echo esc_url($scan->getInfoIcon()); ?>" alt="info" />
        <span class="wpstg--tooltiptext">
            <?php esc_html_e('Automatically updates all plugins on the staging site to the latest versions immediately after you have created the staging site. You can then test the staging site to find out if all plugins work as expected and if your site does not trigger any errors.', 'wp-staging'); ?>
            <br /> <br />
            <b><?php esc_html_e('You will receive an e-mail and slack notification as soon as the staging site has been updated. (If activated in WP Staging settings)', 'wp-staging');?>
        </span>
    </span>
</div>
