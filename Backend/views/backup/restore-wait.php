<?php

/**
 * @see \WPStaging\Backup\Ajax\Listing::render
 */

?>

<div id="wpstg-restore-wait">
    <div class="wpstg-logo"><img width="220" src="<?php echo esc_url(WPSTG_PLUGIN_URL . "assets/img/logo.svg"); ?>"></div>
    <div class="wpstg-title"><?php esc_html_e('Backup Restore Successful!', 'wp-staging') ?></div>
    <div class="wpstg-text"><?php esc_html_e('You are being redirected to the login page...', 'wp-staging') ?></div>
</div>
