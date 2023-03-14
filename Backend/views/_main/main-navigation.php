<?php

use WPStaging\Pro\License\License;

$classStagingPageActive = '';
$classBackupPageActive  = '';
$classSystemInfoActive  = isset($isActiveSystemInfoPage) ? 'wpstg--tab--active' : '';
$classSettingsActive    = isset($isActiveSettingsPage) ? 'wpstg--tab--active' : '';
$classLicenseActive     = isset($isActiveLicensePage) ? 'wpstg--tab--active' : '';

?>
<div class="wpstg--tab--header">
    <ul>
        <li>
            <a href="<?php echo esc_url(get_admin_url()) . 'admin.php?page=wpstg_clone'; ?>" class="wpstg--tab--content <?php echo esc_attr($classStagingPageActive); ?> wpstg-button" data-target="">
                <?php esc_html_e('Staging', 'wp-staging') ?>
            </a>
        </li>
        <li>
            <a href="<?php echo esc_url(get_admin_url()) . 'admin.php?page=wpstg_backup'; ?>" class="wpstg-button <?php echo esc_attr($classBackupPageActive); ?>" data-target="" id="wpstg--tab--toggle--backup">
                <?php esc_html_e('Backup & Migration', 'wp-staging') ?>
            </a>
        </li>
        <li>
            <a href="<?php echo esc_url(get_admin_url()) . 'admin.php?page=wpstg-settings'; ?>" class="wpstg-button <?php echo esc_attr($classSettingsActive); ?>" data-target="" id="wpstg--tab--toggle--settings">
                <?php esc_html_e('Settings', 'wp-staging') ?>
            </a>
        </li>
        <li>
            <a href="<?php echo esc_url(get_admin_url()) . 'admin.php?page=wpstg-tools'; ?>" class="wpstg-button <?php echo esc_attr($classSystemInfoActive); ?>" data-target="" id="wpstg--tab--toggle--systeminfo">
                <?php esc_html_e('System Info', 'wp-staging') ?>
            </a>
        </li>
        <?php
        if (defined('WPSTGPRO_VERSION')) {
            $licenseMessage = isset($license->license) && $license->license === 'valid' ? '' : __('(Unregistered)', 'wp-staging');
            ?>
            <li>
                <a href="<?php echo esc_url(get_admin_url()) . 'admin.php?page=wpstg-license'; ?>" class="wpstg-button <?php echo esc_attr($classLicenseActive); ?>" data-target="" id="wpstg--tab--toggle--license">
                    <?php esc_html_e('License', 'wp-staging'); ?> <span class="wpstg--red-warning"><?php echo esc_html($licenseMessage); ?></span>
                </a>
            </li>
            <?php
        } else {
            ?>
            <li>
                <a href="https://wp-staging.com" target="_blank" class="wpstg-button <?php echo esc_attr($classLicenseActive); ?>" data-target="" id="wpstg--tab--toggle--license">
                    <span class="wpstg--red-warning" style=""><?php echo esc_html(__('Upgrade to Pro', 'wp-staging')); ?> </span>
                </a>
            </li>
            <?php
        }
        ?>
        <li class="wpstg-tab-item--vert-center">
            <span class="wpstg-loader"></span>
        </li>
    </ul>
</div>