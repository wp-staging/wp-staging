<?php

use WPStaging\Framework\Facades\UI\Checkbox;

?>
<div class="wpstg-report-issue-form">
    <div class="arrow-up"></div>
    <div class="wpstg-field">
        <input placeholder="Your email address..." type="email" id="wpstg-report-email" class="wpstg-report-email">
    </div>
    <div class="wpstg-field">
        <input placeholder="Your hosting provider...(optional)" type="text" id="wpstg-report-hosting-provider" class="wpstg-report-hosting-provider">
    </div>
    <div class="wpstg-field">
        <textarea rows="3" id="wpstg-report-description" class="wpstg-report-description" placeholder="How may we help you? In case of issues please provide us with access data so we can help you faster."></textarea>
    </div>
    <div class="wpstg-field wpstg-report-privacy-policy">
        <label for="wpstg-report-syslog">
            <?php Checkbox::render('wpstg-report-syslog', '', '', true, ['classes' => 'wpstg-report-syslog']); ?>
            <?php echo wp_kses_post(sprintf(
                __('Allow submission of %s.', 'wp-staging'),
                '<a href="' . esc_url(admin_url()) . 'admin.php?page=wpstg-tools&tab=system-info' . '" target="_blank">' . esc_html__('log files', 'wp-staging') . '</a>'
            )); ?>
        </label>
    </div>
    <div class="wpstg-field wpstg-report-privacy-policy">
        <label for="wpstg-report-terms">
            <?php Checkbox::render('wpstg-report-terms', '', '', true, ['classes' => 'wpstg-report-terms']); ?>
            <?php echo sprintf(esc_html__('I allow WP STAGING to use my email to reply to my request. %s', 'wp-staging'), '<a href="https://wp-staging.com/privacy-policy/" target="_blank">' . esc_html__('Privacy Policy', 'wp-staging') . '</a>'); ?>
        </label>
    </div>
    <div class="wpstg-field">
        <div class="wpstg-buttons">
            <div class="wpstg-contact-us-actions">
                <button type="submit" id="wpstg-report-submit" class="wpstg-form-submit wpstg-button--blue">
                    <?php esc_html_e('Submit', 'wp-staging'); ?>
                </button>
                <span id="wpstg-contact-us-loader" class="wpstg-loader"></span>
            </div>
            <button id="wpstg-report-cancel" class="wpstg-close-button">
                <a href="javascript:void(0)" class="wpstg-report-cancel wpstg--red"><?php esc_html_e('Close', 'wp-staging'); ?></a>
            </button>
            <div class="wpstg-clear"></div>
        </div>
    </div>
</div>
