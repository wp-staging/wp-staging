<?php

use WPStaging\Core\WPStaging;

$numberOfLoadingBars = 15;
include(WPSTG_PLUGIN_DIR . 'views/_main/loading-placeholder.php');
?>

<div class="wpstg-tab-temporary-logins">
    <div class="wpstg-temporary-logins-heading">
        <strong class="wpstg-fs-18"><?php esc_html_e('Temporary Logins Without Password', 'wp-staging');?></strong>
        <?php if (!WPStaging::isValidLicense()) :?>
            <a href="https://wp-staging.com" target="_blank" class="wpstg-button wpstg-button--primary">
                <?php echo esc_html__('PREVIEW - ', 'wp-staging'); ?>
                <span class="wpstg--red-warning"><?php echo esc_html('Get WP Staging Pro'); ?> </span>
            </a>
        <?php endif;?>
    </div>
    <p>
        <?php esc_html_e('Create and share temporary login links for this website that do not require a password and automatically expire after a specific time.', 'wp-staging') ?>
        <br>
        <?php esc_html_e('These links can be used to give developers or clients access to your website, ensuring the link becomes invalid after the given period.', 'wp-staging') ?>
    </p>
    <div class="wpstg-temp-login-header-container">
        <?php if (WPStaging::isValidLicense()) :?>
        <button class="wpstg-button wpstg-blue-primary" id="wpstg-create-temp-login" >
            <?php esc_html_e('Create Temporary Login', 'wp-staging') ?>
        </button>
        <?php endif;?>
    </div>
    <div id="wpstg-temporary-logins-wrapper"></div>
</div>
