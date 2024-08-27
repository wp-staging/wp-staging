<?php

use WPStaging\Framework\Facades\Escape;

?>
<span class="wpstg-notice-alert">
    <?php echo sprintf(
        Escape::escapeHtml(__('WordPress Multisite is not supported in the WP Staging free version! Please upgrade to <a href="%s" target="_blank">WP Staging Pro</a>', 'wp-staging')),
        'https://wp-staging.com/'
    )?>
</span>
