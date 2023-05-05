<?php

/**
 * @var WPStaging\Framework\TemplateEngine\TemplateEngine $this
 * @var string                                            $urlAssets
 * @var bool                                              $isProVersion
 * @var bool                                              $isValidLicenseKey
 * @see \WPStaging\Backup\Ajax\FileList::render()
 */

use WPStaging\Framework\Facades\Escape;

?>
<li id="wpstg-backup-no-results" class="wpstg-clone">
    <img class="wpstg--dashicons" src="<?php echo esc_url($urlAssets); ?>svg/vendor/dashicons/cloud.svg" alt="cloud">
    <div class="no-backups-found-text">
        <?php if ($isValidLicenseKey || !$isProVersion) : ?>
            <?php esc_html_e('No Backups found. Create your first Backup above!', 'wp-staging'); ?>
        <?php else :?>
            <strong id="wpstg-invalid-license-message" class="wpstg--red">
                <?php echo sprintf(
                    Escape::escapeHtml(__('Please<a href="%s">enter your license key</a> to create and restore your backup files.', 'wp-staging')),
                    esc_url(admin_url() . 'admin.php?page=wpstg-license')
                ); ?>
            </strong>
        <?php endif; ?>
    </div>
</li>
