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
            <input type="checkbox" class="wpstg-report-syslog" id="wpstg-report-syslog">
            <?php echo wp_kses_post(sprintf(
                __('Enable this option to automatically submit the %s. This speeds up the resolution of problems.', 'wp-staging'),
                '<a href="' . esc_url(admin_url()) . 'admin.php?page=wpstg-tools&tab=system-info' . '" target="_blank">' . esc_html__('log files', 'wp-staging') . '</a>'
            )); ?>
        </label>
    </div>
    <div class="wpstg-field wpstg-report-privacy-policy">
        <label for="wpstg-report-terms">
            <input type="checkbox" class="wpstg-report-terms" id="wpstg-report-terms">
            <?php echo sprintf(esc_html__('I allow WP STAGING to use my email to reply to my request. %s', 'wp-staging'), '<a href="https://wp-staging.com/privacy-policy/" target="_blank">' . esc_html__('Privacy Policy', 'wp-staging') . '</a>'); ?>
        </label>
    </div>
    <div class="wpstg-field">
        <div class="wpstg-buttons">
            <button type="submit" id="wpstg-report-submit" class="wpstg-form-submit wpstg-button--blue">
                <?php esc_html_e('Submit', 'wp-staging'); ?>
            </button>
            <span class="spinner"></span>
             <a href="#" id="wpstg-report-cancel" class="wpstg-report-cancel wpstg--red">Close</a>
            <div class="wpstg-clear"></div>
        </div>
    </div>
</div>
