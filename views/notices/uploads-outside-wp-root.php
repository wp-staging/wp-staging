<?php
/**
 * @var $this \WPStaging\Framework\Notices\Notices
 * @see \WPStaging\Framework\Notices\Notices::renderNotices
 */
?>
<div class="notice notice-error">
    <p>
        <strong><?php esc_html_e('WP STAGING - Customized Uploads Folder.', 'wp-staging'); ?></strong> <br/>
        <?php echo sprintf(__('The uploads folder is outside the WordPress root ABSPATH folder. This may lead to missing images when creating a staging site. <a href="%s" target="_blank">How to fix this</a>', 'wp-staging'), 'https://wp-staging.com/docs/no-images-are-visible-on-staging-site/'); ?>
    </p>
</div>
