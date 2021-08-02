<?php
/**
 * @var $this \WPStaging\Backend\Notices\Notices
 * @see \WPStaging\Backend\Notices\Notices::messages
 */
?>
<div class="notice notice-error">
    <p>
        <strong><?php _e('WP STAGING - Customized Uploads Folder.', 'wp-staging'); ?></strong> <br/>
        <?php echo sprintf(__('You have a customized uploads folder which is located outside the WordPress root folder. This will lead to missing images when creating a staging site. <a href="%s" target="_blank">How to fix this</a>', 'wp-staging'), 'https://wp-staging.com/docs/no-images-are-visible-on-staging-site/'); ?>
    </p>
</div>
