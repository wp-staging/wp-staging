<?php

$numberOfLoadingBars = 15;
include(WPSTG_PLUGIN_DIR . 'views/_main/loading-placeholder.php');
?>

<div class="wpstg-tab-temporary-logins">
    <div class="wpstg-temporary-logins-heading">
        <strong class="wpstg-fs-18"><?php esc_html_e('Temporary Logins Without Password', 'wp-staging');?></strong>
        <a href="https://wp-staging.com/docs/create-magic-login-links/" target="_blank" class="wpstg-button wpstg-button--primary">
            <?php echo esc_html__('Open Preview - ', 'wp-staging'); ?>
            <span class="wpstg--red-warning"><?php echo esc_html('Get WP Staging Pro'); ?> </span>
        </a>
    </div>
    <p>
        <?php esc_html_e('Create and share temporary login links for this website that do not require a password and automatically expire after a specific time.', 'wp-staging') ?>
        <br>
        <?php esc_html_e('These links can be used to give developers or clients access to your website, ensuring the link becomes invalid after the given period.', 'wp-staging') ?>
        <br>
        <?php echo sprintf(esc_html__('That is a %s feature.', 'wp-staging'), '<a href="https://wp-staging.com/#pricing" target="_blank" rel="noopener">WP Staging Pro</a>') ?>
    </p>
    <div id="wpstg-temporary-logins-wrapper"></div>
</div>
