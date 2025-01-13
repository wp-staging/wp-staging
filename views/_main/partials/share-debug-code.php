                <div class="wpstg-contact-us-debug-info">
                    <button type="button" class="wpstg-blue-primary wpstg-button--blue wpstg-report-issue-btn">
                        <?php esc_html_e("Share Debug Logs with WP STAGING & Open Support Forum", "wp-staging") ?>
                    </button>
                    <div class="wpstg-loader wpstg-contact-us-report-issue-loader"></div>
                    <div class="wpstg-contact-us-support-forum wpstg-ml-30px wpstg--process-modal--msg--critical">
                        <?php esc_html_e("Can not send email. Please contact us in the ", "wp-staging") ?>
                        <a href="https://wp-staging.com/support-on-wordpress" target="_blank"><?php esc_html_e("Support Forum", "wp-staging") ?></a>
                    </div>
                </div>
                <p>
                    <?php esc_html_e("You'll share: Your email, URL,", "wp-staging") ?><?php esc_html_e(" system information, and ", "wp-staging") ?>
                    <a href="<?php echo esc_url(admin_url("admin-post.php?action=wpstg_download_sysinfo")) ?>" target="_blank"><?php esc_html_e("debug logs", "wp-staging") ?></a>
                    <br>
                    <?php esc_html_e("Your email address will only be used to contact you about your issue.", "wp-staging"); ?>
                    <br>
                    <br>
                    <a href="https://wp-staging.com/support-on-wordpress" target="_blank"><?php esc_html_e("Open forum", "wp-staging") ?></a><?php esc_html_e(' without sending the information.', 'wp-staging') ?>
                </p>
                <div class="wpstg-contact-us-modal-align wpstg-debug-response"></div>
