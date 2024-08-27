<?php

/**
 * This view is only called on WordPress.com platform, as it is impossible to host a cloned staging site on WordPress.com on ABSPATH and wp-content/wp-staging-sites
 * due to nginx restriction by WordPress.com
 * We will support this feature in the future but for now we will show a notice to the user that he can use the backup & migration feature instead.
 */

$urlToMigrationArticle    = 'https://wp-staging.com/docs/migrate-a-self-hosted-wordpress-website-to-wordpress-com/';
$urlToWpComStagingArticle = 'https://wp-staging.com/wordpress-com-how-to-create-staging-site/';
?>
<div class="wpstg-notice-alert">
    <p class="wpstg-m-0">
        <?php echo sprintf(esc_html__("WP Staging can not create staging sites on WordPress.com hosted websites! Instead you can use %s to create a backup of this website. Then use %s to upload and restore the backup on the other website. The other site can be located on all hosts including WordPress.com.", 'wp-staging'), '<a href="#" data-target="#wpstg--tab--backup" class="wpstg-navigate-button">' . esc_html__('Backup &amp; Migration', 'wp-staging') . '</a>', '<a href="https://wp-staging.com" target="_blank">' . esc_html__('WP Staging Pro', 'wp-staging') . '</a>'); ?>
        <br>
        <br>
        <a href="<?php echo esc_attr($urlToMigrationArticle); ?>" target="_blank"><?php esc_html_e('Read More', 'wp-staging'); ?></a> <br/>
        <br>
        <?php echo sprintf(esc_html__('Optionally, you can use the built-in WordPress.com %s if you have a Creator or Entrepreneur plan.', 'wp-staging'), '<a href="' . esc_url($urlToWpComStagingArticle) . '" target="_blank">' . esc_html__('staging function', 'wp-staging') . '</a>') ?>
    </p>
</div>
