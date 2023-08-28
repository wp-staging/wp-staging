<?php

use WPStaging\Backend\Modules\SystemInfo;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Filesystem\DebugLogReader;

?>
<div id="wpstg-contact-us-modal" class="wpstg-contact-us-modal">
    <div class="wpstg-modal-content">
        <div class="wpstg-modal-header">
            <span id="wpstg-modal-close" class="wpstg-contact-us-close-btn"></span>
            <span class="wpstg-contact-us-modal-header-title"><?php esc_html_e("Contact us", "wp-staging") ?></span>
        </div>
        <div class="wpstg-modal-body">
            <a href="https://wp-staging.com/?utm_source=wp-admin&utm_medium=modal&utm_campaign=contact-us-modal&utm_id=100" target="_blank"  class="wpstg-contact-us-inner-container wpstg-contact-us-margin-left-right">
                    <span class="wpstg-contact-us-inner-container-header"><?php esc_html_e("Buy the Premium Plugin", "wp-staging") ?></span><br>
                    <span class="wpstg-contact-us-inner-container-content"> <?php  esc_html_e("Contact us directly and get priority email support to help you faster.", "wp-staging") ?></span>
            </a>
            <a href="javascript:void(0)" id="wpstg-contact-us-report-issue"  class="wpstg-contact-us-inner-container">
                     <span class="wpstg-contact-us-inner-container-header"><?php esc_html_e("Ask in the forum", "wp-staging") ?> </span><br>
                     <span class="wpstg-contact-us-inner-container-content"> <?php esc_html_e("Open a support thread and send us the debug information", "wp-staging") ?></span>
            </a>
        </div>
        <div class="wpstg-contact-us-report-issue" id="wpstg-contact-us-report-issue-form">
            <span class="wpstg-contact-us-basic-h1"><?php esc_html_e("Ask in the forum", "wp-staging"); ?></span>
            <div class="wpstg-contact-us-troubleshot-container">
                <h2><?php esc_html_e("Send troubleshooting data to help debugging", "wp-staging"); ?></h2>
                <p><?php esc_html_e("Send us system information and debug logs of the recent cloning or backup before opening a ticket, so that we can help investigating.", "wp-staging"); ?></p>
                <div class="wpstg-contact-us-debug-info">
                    <button type="button" class="wpstg-blue-primary wpstg-button" id="wpstg-contact-us-report-issue-btn">
                        <?php esc_html_e("Share debug information with WP STAGING team & Open Forum", "wp-staging") ?>
                    </button>
                    <div class="wpstg-loader" id="wpstg-contact-us-report-issue-loader"></div>
                    <div id="wpstg-contact-us-support-forum" class="wpstg-ml-30px wpstg--modal--process--msg--critical">
                        <?php esc_html_e("Can not send email. Please contact us in the ", "wp-staging") ?>
                        <a href="https://wp-staging.com/support-on-wordpress" target="_blank"><?php esc_html_e("Support Forum", "wp-staging") ?></a>
                    </div>
                </div>
                <p><?php esc_html_e("You'll share: Your email, URL,", "wp-staging") ?><?php esc_html_e("system information", "wp-staging") ?><?php esc_html_e(", and debug logs.", "wp-staging") ?><br>
                <?php esc_html_e("Your email address will only be used to contact you about your issue.", "wp-staging"); ?>
                    <br>
                    <br>
                    <a href="https://wp-staging.com/support-on-wordpress" target="_blank">
                    <?php esc_html_e("Open forum", "wp-staging") ?></a><?php esc_html_e(' without sending the information.', 'wp-staging') ?>
                </p>
                <div id="wpstg-contact-us-debug-response" class="wpstg-contact-us-modal-align">
            </div>
            </div>
<!--            <div>
                <span class="wpstg-contact-us-basic-h2"><?php /*esc_html_e("Site Information", "wp-staging"); */?></span>
                <p><?php /*esc_html_e("Here are the site information and last 50Kbyte of the available log files. This may help to debug if there is an issue:", "wp-staging"); */?></p>
                <textarea class="wpstg-sysinfo" readonly="readonly" id="system-info-textarea" name="wpstg-sysinfo">
                    <?php
/*                    echo esc_html(WPStaging::make(SystemInfo::class)->get());
                    echo esc_html(PHP_EOL . PHP_EOL . '## Last Log entries ##' . PHP_EOL . PHP_EOL);
                    echo esc_html(WPStaging::make(DebugLogReader::class)->getLastLogEntries(1 * KB_IN_BYTES, true, true));
                    */?>
                </textarea>
            </div>-->
        </div>
        <div class="wpstg-modal-footer">
        </div>
        <div id="wpstg-contact-us-success-form">
            <?php require_once(WPSTG_PLUGIN_DIR . 'Backend/views/_main/contact-us-success.php'); ?>
        </div>
    </div>
</div>