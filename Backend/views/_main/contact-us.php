<div id="wpstg-report-issue-wrapper">
    <button type="button" id="<?php echo defined('WPSTGPRO_VERSION') ? "wpstg-report-issue-button" : "wpstg-contact-us-button"; ?>" class="wpstg-report-issue-button">
        <i class="wpstg-icon-issue"></i><?php echo esc_html__("Contact Us", "wp-staging"); ?>
    </button>
    <?php if (defined('WPSTGPRO_VERSION')) {
        require_once(WPSTG_PLUGIN_DIR . 'Backend/views/_main/contact-us-pro.php');
    }
    ?>
</div>