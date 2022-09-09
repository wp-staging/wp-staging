<?php

use WPStaging\Framework\Facades\Escape;

?>
<span class="wpstg-notice-alert wpstg-mt-20px">
    <?php echo sprintf(
        Escape::escapeHtml(__('WordPress Multisite is not supported! Upgrade to <a href="%s" target="_blank">WP STAGING | PRO</a>', 'wp-staging')),
        'https://wp-staging.com/'
    )?>
</span>
