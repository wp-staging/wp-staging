<?php

/**
 * @see \WPStaging\Framework\Notices\Notices::renderNotices
 */

?>

<div class='notice-warning notice is-dismissible'>
    <p><strong><?php esc_html_e('WP STAGING - Optimizer option disabled!', 'wp-staging'); ?></strong>
        <br>
    <?php esc_html_e('The optimizer option is an important feature that can significantly improve 
the speed and efficiency during the clone and backup process. By disabling this option, 
the clone process may take longer and consume more resources than necessary.
To optimize the clone process, please enable this option in the plugin settings!', 'wp-staging'); ?></p>
</div>
