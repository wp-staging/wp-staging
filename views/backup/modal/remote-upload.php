<?php

/**
 * @var string $urlAssets
 * @var bool   $isProVersion
 * @var bool   $isPersonalLicense
 */

use WPStaging\Backup\Storage\Providers;
use WPStaging\Core\WPStaging;

/** @var Providers */
$storages = WPStaging::make(Providers::class);

$disabledProAttribute = $isProVersion ? '' : ' disabled';
$storagesPrefix = 'storage-upload-';
?>
<div id="wpstg--modal--remote-upload" data-confirmButtonText="<?php esc_attr_e('Start Upload', 'wp-staging') ?>" style="display: none">
    <h3 class="wpstg--swal2-title wpstg-w-100" for="wpstg-backup-name-input"><?php esc_html_e('Upload Backup to Remote Storage(s)', 'wp-staging') ?></h3>
    <div class="wpstg-advanced-options wpstg-text-left">
        <div class="wpstg-advanced-options-site">
            <?php require WPSTG_VIEWS_DIR . 'backup/modal/backup-storages.php'; ?>
        </div>
    </div>
</div>
