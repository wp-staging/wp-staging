<?php

/**
 * @var $this \WPStaging\Backend\Notices\Notices
 * @var $viewsNoticesPath
 * @see \WPStaging\Backend\Notices\Notices::messages
 */

use WPStaging\Backend\Notices\Notices;

?>
<div class="notice notice-warning wpstg-warning-notice">
    <p><strong><?php _e('WP STAGING:', 'wp-staging'); ?></strong></p>
    <p>
        <?php _e('Renaming the folder <code>wp-content</code> or the <code>uploads</code> path can lead to missing images after the push process. If not absolutely necessary don\'t rename the default WordPress folders.', 'wp-staging') ?>
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
