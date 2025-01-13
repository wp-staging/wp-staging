<?php

use WPStaging\Framework\Facades\UI\Checkbox;

?>
<div id="wpstg-contact-us-modal" class="wpstg-contact-us-modal">
    <div class="wpstg-modal-content">
        <?php require(WPSTG_VIEWS_DIR . '_main/partials/contact-us-header.php'); ?>
        <?php require(WPSTG_VIEWS_DIR . '_main/partials/buy-or-open-forum.php'); ?>
        <div class="wpstg-contact-us-report-issue">
            <span class="wpstg-contact-us-basic-h1"><?php esc_html_e("Ask in the forum", "wp-staging"); ?></span>
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
                <?php require(WPSTG_VIEWS_DIR . '_main/partials/share-debug-code.php'); ?>
            </div>
        </div>
        <div class="wpstg-modal-footer"></div>
        <div class="wpstg-contact-us-success-form">
            <?php require(WPSTG_VIEWS_DIR . '_main/partials/contact-us-success.php'); ?>
        </div>
    </div>
</div>
