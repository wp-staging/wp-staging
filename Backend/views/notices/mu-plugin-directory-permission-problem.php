<?php

/**
 * @var string $varsDirectory
 * @see \WPStaging\Framework\Notices\Notices::renderNotices
 */

use WPStaging\Framework\Notices\Notices;

?>
<div class="notice notice-error wpstg-mu-dir-permission-notice">
    <p><strong><?php esc_html_e('WP STAGING - Folder Permission error.', 'wp-staging'); ?></strong>
        <br>
        <?php echo sprintf(esc_html__('The folder %s is not writable and/or readable.', 'wp-staging'), '<code>' . esc_html($varsDirectory) . '</code>'); ?>
        <br>
        <?php esc_html_e('Check if this folder exists! Folder permissions should be chmod 755 or 777.', 'wp-staging'); ?>
    </p>

    <p>
      <?php Notices::renderNoticeDismissAction(
          $viewsNoticesPath,
          'mu_dir_permission',
          '.wpstg_dismiss_mu_dir_permission_notice',
          '.wpstg-mu-dir-permission-notice'
      ) ?>
    </p>
</div>
