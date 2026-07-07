<?php

/**
 * This file is called on the staging site in
 * @see src/views/clone/index.php
 */

use WPStaging\Framework\Facades\Escape;
use WPStaging\Framework\Language\Language;

?>

<span class="wpstg-notice-alert">
    <?php echo sprintf(
        Escape::escapeHtml(__('The free version of WP Staging does not support WordPress Multisite. You can consider upgrading to the <a href="%s" target="_blank">pro version</a> as needed.', 'wp-staging')),
        esc_url(Language::getUpgradeUrl('staging_multisite'))
    ); ?>
</span>

<button class="wpstg-btn wpstg-btn-lg wpstg-btn-primary wpstg-new-staging-btn" disabled>
    <?php esc_html_e('Create Staging Site', 'wp-staging'); ?>
</button>
