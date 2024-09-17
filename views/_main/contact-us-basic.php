<?php

use WPStaging\Framework\Facades\UI\Checkbox;

$modalClassID     = 'contact-us';
$isContactUsModal = true;

?>
<div id="wpstg-<?php echo esc_attr($modalClassID); ?>-modal" class="wpstg-contact-us-modal">
    <div class="wpstg-modal-content">
        <div class="wpstg-modal-header">
            <span id="wpstg-modal-close" class="wpstg-contact-us-close-btn"></span>
            <span class="wpstg-contact-us-modal-header-title"><?php esc_html_e("Contact us", "wp-staging") ?></span>
        </div>
        <?php if ($isContactUsModal) { ?>
        <div class="wpstg-modal-body">
            <a href="https://wp-staging.com/?utm_source=wp-admin&utm_medium=modal&utm_campaign=contact-us-modal&utm_id=100" target="_blank"  class="wpstg-contact-us-inner-container wpstg-contact-us-margin-left-right">
                    <span class="wpstg-contact-us-inner-container-header"><?php esc_html_e("Buy the Premium Plugin", "wp-staging") ?></span><br>
                    <span class="wpstg-contact-us-inner-container-content"> <?php  esc_html_e("Contact us directly and get priority email support to help you faster.", "wp-staging") ?></span>
            </a>
            <a href="javascript:void(0)" id="wpstg-<?php echo esc_attr($modalClassID); ?>-report-issue"  class="wpstg-contact-us-inner-container">
                     <span class="wpstg-contact-us-inner-container-header"><?php esc_html_e("Ask in the forum", "wp-staging") ?> </span><br>
                     <span class="wpstg-contact-us-inner-container-content"> <?php esc_html_e("Open a support thread and send us the debug information", "wp-staging") ?></span>
            </a>
        </div>
        <?php }?>
        <div class="wpstg-contact-us-report-issue" id="wpstg-<?php echo esc_attr($modalClassID); ?>-report-issue-form">
            <?php if ($isContactUsModal) { ?>
            <span class="wpstg-contact-us-basic-h1"><?php esc_html_e("Ask in the forum", "wp-staging"); ?></span>
            <?php } ?>
            <div class="wpstg-contact-us-troubleshot-container">
                <h2><?php esc_html_e('Send troubleshooting data', "wp-staging"); ?></h2>
                <p><?php esc_html_e('Send us system information and debug logs before opening a ticket so we can help investigate.', "wp-staging"); ?></p>
                <div class="wpstg-contact-us-force-send-email-container">
                    <label for="wpstg-force-send-debug-log">
                        <?php Checkbox::render('wpstg-force-send-debug-log', 'wpstg-force-send-debug-log'); ?>
                        <?php esc_html_e('Force Send Debug Log', 'wp-staging') ?>
                        <div class="wpstg--tooltip">
                            <img class="wpstg--dashicons" src="<?php echo esc_url($urlAssets); ?>svg/info-outline.svg" alt="info"/>
                            <span class="wpstg--tooltiptext">
                                <?php esc_html_e('Use this option if you have already sent a debug log email and want to send it again within five minutes.', 'wp-staging');?>
                            </span>
                        </div>
                    </label>
                </div>
                <div class="wpstg-contact-us-debug-info">
                    <button type="button" class="wpstg-blue-primary wpstg-button--blue" id="wpstg-<?php echo esc_attr($modalClassID); ?>-report-issue-btn">
                        <?php esc_html_e("Share Debug Logs with WP STAGING & Open Support Forum", "wp-staging") ?>
                    </button>
                    <div class="wpstg-loader" id="wpstg-<?php echo esc_attr($modalClassID); ?>-report-issue-loader"></div>
                    <div id="wpstg-<?php echo esc_attr($modalClassID); ?>-support-forum" class="wpstg-ml-30px wpstg--modal--process--msg--critical">
                        <?php esc_html_e("Can not send email. Please contact us in the ", "wp-staging") ?>
                        <a href="https://wp-staging.com/support-on-wordpress" target="_blank"><?php esc_html_e("Support Forum", "wp-staging") ?></a>
                    </div>
                </div>
                <p><?php esc_html_e("You'll share: Your email, URL,", "wp-staging") ?><?php esc_html_e(" system information, and ", "wp-staging") ?>
                    <a href="<?php echo esc_url(admin_url("admin-post.php?action=wpstg_download_sysinfo")) ?>" target="_blank"><?php esc_html_e("debug logs", "wp-staging") ?></a>
                    <br>
                <?php esc_html_e("Your email address will only be used to contact you about your issue.", "wp-staging"); ?>
                    <br>
                    <br>
                    <a href="https://wp-staging.com/support-on-wordpress" target="_blank">
                    <?php esc_html_e("Open forum", "wp-staging") ?></a><?php esc_html_e(' without sending the information.', 'wp-staging') ?>
                </p>
                <div id="wpstg-<?php echo esc_attr($modalClassID); ?>-debug-response" class="wpstg-contact-us-modal-align">
                </div>
            </div>
        </div>
        <div class="wpstg-modal-footer"></div>
        <div id="wpstg-<?php echo esc_attr($modalClassID); ?>-success-form">
            <?php require(WPSTG_VIEWS_DIR . '_main/contact-us-success.php'); ?>
        </div>
    </div>
</div>
