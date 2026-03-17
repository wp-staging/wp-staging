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
        esc_url(Language::localizePricingUrl('https://wp-staging.com/#pricing'))
    ); ?>
</span>

<button id="wpstg-new-clone" class="wpstg-btn wpstg-btn-lg wpstg-btn-primary" disabled>
    <?php esc_html_e('Create Staging Site', 'wp-staging'); ?>
</button>
