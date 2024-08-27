<?php

/**
 * @see src/views/_main/main-navigation.php
 * @see src/views/clone/index.php
 */

use WPStaging\Core\WPStaging;

$urlAssets = trailingslashit(WPSTG_PLUGIN_URL) . 'assets/';
?>
<div id="wpstg-report-issue-wrapper">
    <button type="button" id="<?php echo defined('WPSTGPRO_VERSION') ? "wpstg-report-issue-button" : "wpstg-contact-us-button"; ?>" class="wpstg-report-issue-button">
        <i class="wpstg-contact-us-icon">
            <img class="wpstg--dashicons" src="<?php echo esc_url($urlAssets); ?>svg/contact-us.svg" alt="<?php esc_attr_e("Contact Us", "wp-staging"); ?>">
        </i>
        <span class="wpstg-contact-us-label"><?php echo esc_html__("Contact Us", "wp-staging"); ?></span>
    </button>
    <?php
    if (WPStaging::isPro()) {
        require_once(WPSTG_VIEWS_DIR . '_main/contact-us-pro.php');
    } else {
        require(WPSTG_VIEWS_DIR . '_main/contact-us-basic.php');
        require(WPSTG_VIEWS_DIR . '_main/general-error-modal.php');
    }
    ?>
</div>
