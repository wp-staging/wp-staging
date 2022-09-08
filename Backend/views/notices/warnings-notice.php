<?php

/**
 * @var $this \WPStaging\Backend\Notices\Notices
 * @var $viewsNoticesPath
 * @see \WPStaging\Backend\Notices\Notices::messages
 */

use WPStaging\Backend\Notices\Notices;

?>
<div class="notice notice-warning wpstg-warning-notice">
    <p><strong><?php esc_html_e('WP STAGING:', 'wp-staging'); ?></strong></p>
    <p>
        <?php echo sprintf(esc_html__('Renaming the folder %s or the %s path can lead to missing images after the push process. If not absolutely necessary don\'t rename the default WordPress folders.', 'wp-staging'), "<code>wp-content</code>", "<code>uploads</code>") ?>
    </p>   
    <p>
      <?php Notices::renderNoticeDismissAction(
          $viewsNoticesPath,
          'warnings_notice',
          '.wpstg_dismiss_warning_notice',
          '.wpstg-warning-notice'
      ) ?>
    </p>
</div>
