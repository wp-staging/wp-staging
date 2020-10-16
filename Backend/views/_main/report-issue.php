<div class="wpstg-report-issue-form">
    <div class="wpstg-field">
        <input placeholder="Your email address..." type="email" id="wpstg-report-email" class="wpstg-report-email">
    </div>
    <div class="wpstg-field">
        <input placeholder="Your hosting provider...(optional)" type="text" id="wpstg-report-hosting-provider" class="wpstg-report-hosting-provider">
    </div>
    <div class="wpstg-field">
        <textarea rows="3" id="wpstg-report-description" class="wpstg-report-description" placeholder="Describe your issue here! Optionally, include the login credentials to your WordPress admin so we can help you faster."></textarea>
    </div>
    <div class="wpstg-field wpstg-report-privacy-policy">
        <label for="wpstg-report-syslog">
            <input type="checkbox" class="wpstg-report-syslog" id="wpstg-report-syslog">
            <?php echo sprintf(
                    __('Optional: Submit the <a href="%s" target="_blank">System Log</a> and your WordPress debug log. This helps us to resolve your technical issues.','wp-staging'),
                    admin_url().'admin.php?page=wpstg-tools&tab=system_info'
                    ); ?>
        </label>
    </div>
    <div class="wpstg-field wpstg-report-privacy-policy">
        <label for="wpstg-report-terms">
            <input type="checkbox" class="wpstg-report-terms" id="wpstg-report-terms">
            <?php _e('By submitting, I accept the <a href="https://wp-staging.com/privacy-policy/" target="_blank">Privacy Policy</a> and consent that my email will be stored and processed for the purposes of proving support.', 'wp-staging'); ?>
        </label>
    </div>
    <div class="wpstg-field">
        <div class="wpstg-buttons">
            <button type="submit" id="wpstg-report-submit" class="wpstg-form-submit button-primary wpstg-button">
                <?php _e( 'Submit', 'wp-staging' ); ?>
            </button>
            <span class="spinner"></span>
             <a href="#" id="wpstg-report-cancel" class="wpstg-report-cancel">Close</a>
            <div class="wpstg-clear"></div>
        </div>
    </div>
</div>
