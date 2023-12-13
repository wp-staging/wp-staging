<?php

/**
 * @var string $urlAssets
 * @var bool   $isProVersion
 */

use WPStaging\Backup\Storage\Providers;
use WPStaging\Core\WPStaging;

/** @var Providers */
$storages = WPStaging::make(Providers::class);

$disabledProAttribute = $isProVersion ? '' : ' disabled';

?>
<div id="wpstg--modal--remote-upload" data-confirmButtonText="<?php esc_attr_e('Start Upload', 'wp-staging') ?>" style="display: none">
    <h3 class="wpstg--swal2-title wpstg-w-100" for="wpstg-backup-name-input"><?php esc_html_e('Upload Backup to Remote Storage(s)', 'wp-staging') ?></h3>

    <div class="wpstg-advanced-options" style="text-align: left;">

        <!-- BACKUP CHECKBOXES -->
        <div class="wpstg-advanced-options-site">

            <div class="wpstg-backup-options-section">
                <h4 class="swal2-title wpstg-w-100">
                    <?php esc_html_e('Storages', 'wp-staging') ?>
                </h4>

                <div class="wpstg-backup-scheduling-options wpstg-container">

                    <?php foreach ($storages->getStorages($enabled = true) as $storage) : ?>
                        <label class="wpstg-storage-option">
                            <?php
                            $isActivated   = $storages->isActivated($storage['authClass']);
                            $isProStorage  = empty($storage['authClass']);
                            $isDisabled    = !$isActivated || (!$isProVersion && $isProStorage);
                            $disabledClass = $isDisabled ? 'wpstg-storage-settings-disabled' : '';
                            ?>
                            <input type="checkbox" class="wpstg-checkbox" name="storages" id="storage-<?php echo esc_attr($storage['id']) ?>" value="<?php echo esc_attr($storage['id']) ?>" <?php echo $isDisabled ? 'disabled' : '' ?> />
                            <span class="<?php echo esc_attr($disabledClass) ?>"><?php echo esc_html($storage['name']); ?></span>
                            <?php if (!$isProVersion && $isProStorage) { ?>
                                <a href="https://wp-staging.com/get-<?php echo esc_attr($storage['id']) ?>" target="_blank" class="wpstg-pro-feature-link"><span class="wpstg-pro-feature wpstg-ml-8"><?php esc_html_e('Upgrade', 'wp-staging') ?></span></a>
                            <?php } else { ?>
                                <span class="wpstg-storage-settings"><a class="" href="<?php echo esc_url($storage['settingsPath']); ?>" target="_blank"><?php echo $isActivated ? esc_html('Settings', 'wp-staging') : esc_html('Activate', 'wp-staging'); ?></a></span>
                            <?php } ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>
</div>
