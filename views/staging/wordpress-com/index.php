<?php

/**
 * This view shows why staging sites can not be created on WordPress.com hosted websites.
 * @see src/views/clone/index.php
 */

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
        <?php echo sprintf(esc_html__("WP Staging cannot create a staging site on environments based on the WordPress.com (Automattic) infrastructure due to technical limitations. Instead you can use %s to create a backup of your website and then use %s to upload and restore that backup on any hosting environment—even WordPress.com.", 'wp-staging'), '<a href="' . esc_url(get_admin_url() . 'admin.php?page=wpstg_backup') . '" data-target="#wpstg--tab--backup" class="wpstg-navigate-button" rel="noopener">' . esc_html__('Backup &amp; Migration', 'wp-staging') . '</a>', '<a href="https://wp-staging.com" target="_blank">' . esc_html__('WP Staging Pro', 'wp-staging') . '</a>'); ?>
        <br>
        <br>
        <a href="<?php echo esc_url($urlToMigrationArticle); ?>" target="_blank"><?php esc_html_e('Read More', 'wp-staging'); ?></a> <br/>
        <br>
        <?php echo sprintf(esc_html__('Optionally, you can use the built-in WordPress.com %s if you have a Creator or Entrepreneur plan.', 'wp-staging'), '<a href="' . esc_url($urlToWpComStagingArticle) . '" target="_blank">' . esc_html__('staging function', 'wp-staging') . '</a>') ?>
    </p>
</div>
