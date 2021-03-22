<?php

use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Pro\Database\Legacy\Entity\Backup;

/** @var TemplateEngine $this */
/** @var Backup $backup */
?>
<div class="wpstg-notice-alert wpstg-failed">
    <h4 style="margin:0;">
        <?php _e('This backup will be deleted.', 'wp-staging') ?>
    </h4>
    <?php _e('Are you sure that you want to delete the site backup? This action can not be undone!', 'wp-staging') ?>
</div>

<div class="wpstg-box">
  <div class="wpstg-db-table">
    <?php if (!empty($size = $backup->getFileSize())) : ?>
        <label><?php _e('File Size', 'wp-staging')?></label>
        <span class="wpstg-size-info"><?php echo esc_html($size); ?></span>
    <?php endif; ?>
  </div>
</div>

<a href="#" class="wpstg-link-btn button-primary" id="wpstg-cancel-backup-delete">
    <?php _e('Cancel', 'wp-staging')?>
</a>

<a href="#" class="wpstg-link-btn button-primary" id="wpstg-delete-backup" data-id="<?php echo $backup->getId()?>">
    <?php _e('Delete', 'wp-staging')?>
</a>
