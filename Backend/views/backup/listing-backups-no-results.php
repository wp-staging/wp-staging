<?php
/**
 * @var WPStaging\Framework\TemplateEngine\TemplateEngine $this
 * @var string                                            $urlAssets
 * @var string                                            $isValidLicenseKey
 * @see \WPStaging\Pro\Backup\Ajax\FileList::render()
 */
?>
<li id="wpstg-backup-no-results" class="wpstg-clone">
    <img class="wpstg--dashicons" src="<?php echo $urlAssets; ?>svg/vendor/dashicons/cloud.svg" alt="cloud">
    <div class="no-backups-found-text">
        <?php if ($isValidLicenseKey) : ?>
            <?php _e('No Backups found. Create your first Backup above!', 'wp-staging'); ?>
        <?php else :?>
            <strong id="wpstg-invalid-license-message" class="wpstg--red"><?php echo sprintf(__('Please<a href="%s">use a valid license key</a> to create and access your backups.', 'wp-staging'), admin_url() . 'admin.php?page=wpstg-license'); ?></strong>
        <?php endif; ?>
    </div>
</li>
