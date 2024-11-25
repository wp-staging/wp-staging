<?php

use WPStaging\Framework\Utils\Strings;

?>
<div class="wpstg--full-screen-overlay" id="wpstg--otp--overlay-screen">
    <div class="wpstg--full-screen-overlay--container">
        <div class="wpstg-logo">
            <img class="wpstg-logo-light" src="<?php echo esc_url(WPSTG_PLUGIN_URL . "assets/img/logo.svg"); ?>">
            <img class="wpstg-logo-dark" src="<?php echo esc_url(WPSTG_PLUGIN_URL . "assets/img/dark-logo.svg"); ?>">
        </div>
        <h2 class="wpstg-title"><?php esc_html_e('Verify Identity', 'wp-staging') ?></h2>
        <p class="wpstg-text"><?php echo sprintf(esc_html__('For your security, we\'ve sent a verification code to your email address: %s. Please enter the code below to continue.', 'wp-staging'), '<strong>' . esc_html((new Strings())->maskEmail(wp_get_current_user()->user_email)) . '</strong>'); ?></p>
        <div class="wpstg-otp-container">
            <input type="text" name="otp[]" maxlength="1" class="wpstg-otp-input-item" />
            <input type="text" name="otp[]" maxlength="1" class="wpstg-otp-input-item" />
            <input type="text" name="otp[]" maxlength="1" class="wpstg-otp-input-item" />
            <input type="text" name="otp[]" maxlength="1" class="wpstg-otp-input-item" />
            <input type="text" name="otp[]" maxlength="1" class="wpstg-otp-input-item" />
            <input type="text" name="otp[]" maxlength="1" class="wpstg-otp-input-item" />
        </div>
        <div class="wpstg--full-screen-overlay--footer">
            <button type="button" class="wpstg-button wpstg-otp-overlay-close-btn wpstg-border-thin-button"><?php esc_html_e('Cancel', 'wp-staging') ?></button>
            <button type="button" class="wpstg-button wpstg-otp-verify-btn wpstg-blue-primary"><?php esc_html_e('Verify', 'wp-staging') ?></button>
            <p class="wpstg-otp-message"></p>
            <p><a class="wpstg-otp-resend-btn"><?php esc_html_e('Resend OTP', 'wp-staging') ?></a></p>
            <p><a class="wpstg-overlay-link" href="https://wp-staging.com/docs/disable-verification-code/" target="_blank"><?php esc_html_e('Don\'t receive the email?', 'wp-staging') ?></a></p>
        </div>
    </div>    
</div>
