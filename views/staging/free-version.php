<?php

/**
 * This file is called on the staging site in
 * @see src/views/clone/index.php
 */

?>

<button id="wpstg-new-clone" class="wpstg-button wpstg-mb-20px" disabled>
    <?php echo esc_html__("Create Staging Site", "wp-staging") ?>
</button>

<span class="wpstg-notice-alert">
    <?php echo sprintf(
        esc_html__('WordPress Multisite is not supported in the WP Staging free version! Please upgrade to %s', 'wp-staging'),
        '<a href="https://wp-staging.com/" target="_blank">WP Staging Pro</a>'
    )?>
</span>
