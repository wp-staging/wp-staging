<?php

/**
 * @var bool $isProVersion
 * @var string $storagesPrefix
 * @var bool $isPersonalLicense
 * @var string $licenseType
 */

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Assets\Assets;
use WPStaging\Backup\Storage\Providers;
use WPStaging\Framework\Language\Language;
use WPStaging\Backup\Service\BackupsDirectoryResolver;

 
$storages = WPStaging::make(Providers::class);
$assets   = WPStaging::make(Assets::class);
$restrictedStorages = [
    'personal'           => ['all'], 
    'personal_legacy'    => ['pcloud', 'one-drive'], 
    'basic'              => ['all'], 
    'business'           => ['none'], 
    'developer'          => ['none'], 
    'developer_legacy'   => ['none'], 
    'developer_30_sites' => ['none'], 
    'agency'             => ['none'], 
];
$allStorages         = $storages->getStorages($enabled = true);
$currentRestrictions = $restrictedStorages[$licenseType] ?? ['none'];
$isCloudUpsell       = !$isProVersion || $isPersonalLicense || in_array('all', $currentRestrictions, true);
$isCreateBackupModal = $storagesPrefix === 'storage-';
$uploadDir           = wp_upload_dir(null, false);
$backupsDir          = 'wp-content/uploads/wp-staging/backups/';
if (!empty($uploadDir['basedir'])) {
    $backupsDir = WPStaging::make(BackupsDirectoryResolver::class)->resolveFromUploadsDirectory($uploadDir['basedir']);
}

?>
<div class="wpstg-storages-section">
    <?php if (!$isCreateBackupModal) :?>
    <h4 class="swal2-title wpstg-w-100">
        <?php esc_html_e('Storage Providers', 'wp-staging') ?>
    </h4>
    <?php endif; ?>
    <?php if ($isCreateBackupModal) : ?>
        <div class="wpstg-grid wpstg-gap-2">
            <div class="wpstg-storages-option wpstg-option-row" data-row="0" data-position="0">
                <label class="wpstg-storages-label !wpstg-m-0 !wpstg-flex wpstg-min-w-0 wpstg-flex-1 wpstg-items-center wpstg-gap-3 !wpstg-leading-[1.3]" for="storage-localStorage">
                    <span class="wpstg-option-icon" aria-hidden="true">
                        <?php $assets->renderSvg('folder', 'wpstg-h-[17px] wpstg-w-[17px]'); ?>
                    </span>
                    <span class="wpstg-storages-content wpstg-option-content">
                        <span class="wpstg-storages-name wpstg-option-title"><?php esc_html_e('Local Storage', 'wp-staging'); ?></span>
                        <span class="wpstg-option-description"><?php echo esc_html($backupsDir); ?></span>
                    </span>
                </label>
                <input type="checkbox" class="wpstg-checkbox" id="storage-localStorage" name="storages" value="localStorage" data-summary-kind="storage" data-summary-label="<?php esc_attr_e('Local Storage', 'wp-staging'); ?>" aria-label="<?php esc_attr_e('Local Storage', 'wp-staging'); ?>" checked>
            </div>

            <details class="wpstg-m-0" <?php echo $isCloudUpsell ? 'open' : ''; ?>>
                <summary id="wpstg-cloud-storages-container" class="wpstg-option-row wpstg-option-row--dashed">
                    <div class="wpstg-flex wpstg-gap-3">
                        <span class="wpstg-option-icon" aria-hidden="true">
                            <?php if ($isCloudUpsell) : ?>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="3" y="11" width="18" height="10" rx="2" ry="2"/>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                            <?php else : ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 13v8"/><path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="m8 17 4-4 4 4"/></svg>
                            <?php endif; ?>
                        </span>
                        <span class="wpstg-storages-content wpstg-option-content">
                            <span class="wpstg-storages-name wpstg-option-title"><?php esc_html_e('Cloud storage', 'wp-staging'); ?></span>
                            <span class="wpstg-option-description"><?php esc_html_e('Google Drive, Amazon S3, Dropbox, One Drive, sFTP/FTP and more', 'wp-staging'); ?></span>
                        </span>
                    </div>
                    <div class="wpstg-flex wpstg-gap-3">
                        <?php if ($isCloudUpsell) :?>
                        <span class="wpstg-badge-pro"><?php esc_html_e('Pro', 'wp-staging'); ?></span>
                        <?php else : ?>
                        <span class="wpstg-inline-flex wpstg-shrink-0 wpstg-cursor-pointer wpstg-items-center wpstg-gap-1 wpstg-border-0 wpstg-bg-transparent wpstg-p-0 wpstg-text-xs wpstg-font-bold wpstg-leading-none wpstg-text-royal-600 dark:wpstg-text-blue-400">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                            </svg>
                            <?php esc_html_e('Configure', 'wp-staging'); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </summary>
                <div class="<?php echo $isCloudUpsell ? 'wpstg-upgrade-callout wpstg-mt-4' : 'wpstg-content-panel'; ?>">
                    <?php if ($isCloudUpsell) : ?>
                    <div class="wpstg-upgrade-callout-header">
                        <div class="wpstg-upgrade-callout-icon" aria-hidden="true">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 13v8"/>
                                <path d="M4 14.9A7 7 0 1 1 15.7 8h1.8a4.5 4.5 0 0 1 2.5 8.2"/>
                                <path d="m8 17 4-4 4 4"/>
                            </svg>
                        </div>
                        <div class="wpstg-upgrade-callout-content">
                            <div class="wpstg-upgrade-callout-title">
                                <?php echo esc_html($isPersonalLicense ? __('Cloud storage is available in Business and higher', 'wp-staging') : __('Cloud storage is available in Pro', 'wp-staging')); ?>
                                <span class="wpstg-badge-pro"><?php esc_html_e('Pro', 'wp-staging'); ?></span>
                            </div>
                            <p class="wpstg-upgrade-callout-description"><?php esc_html_e('Store backups off-site with Google Drive, Amazon S3, Dropbox, One Drive, sFTP/FTP and more.', 'wp-staging'); ?></p>
                            <?php if ($isPersonalLicense) : ?>
                            <p class="wpstg-upgrade-callout-plan-info">
                                <?php esc_html_e('Available in Business and Higher plans.', 'wp-staging'); ?>
                                <?php esc_html_e('Your current plan: Personal License.', 'wp-staging'); ?>
                            </p>
                            <?php endif; ?>
                            <?php $source = $isPersonalLicense ? 'wp-staging-pro' : 'wp-staging-free' ?>
                            <div class="wpstg-upgrade-callout-actions">
                                <a href="<?php echo esc_url(Language::getUpgradeUrl('storage_upgrade_to_higher', $source)); ?>" target="_blank" rel="noopener noreferrer" class="wpstg-btn wpstg-btn-md wpstg-btn-primary"><?php esc_html_e('Upgrade to Pro', 'wp-staging'); ?></a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
    <?php endif; ?>

    <?php if (!$isCloudUpsell || !$isCreateBackupModal) : ?>
    <div class="wpstg-storages-grid <?php echo $isCreateBackupModal ? 'wpstg-gap-2' : ''; ?>">
        <?php
        $itemCount = 0;
        $rowCount = 0;

        foreach ($allStorages as $storageKey => $storage) :
            $storageDisplayName = $isCloudUpsell && $storage['id'] === Providers::IDENTIFIER_ONE_DRIVE ? __('One Drive', 'wp-staging') : $storage['name'];
            $isRestrictedStorage = false;
            if (in_array('all', $currentRestrictions, true) || in_array($storage['id'], $currentRestrictions, true)) {
                $isRestrictedStorage = true;
            }

            $positionInRow = $itemCount % 2;
            if ($positionInRow === 0) {
                $rowCount++;
            }

            $itemCount++;
            ?>
            <div class="wpstg-storages-option wpstg-box-border wpstg-flex wpstg-items-center wpstg-justify-between wpstg-gap-3 wpstg-rounded-[7px] wpstg-border wpstg-border-solid wpstg-border-slate-250 wpstg-bg-white wpstg-p-3 dark:wpstg-border-slate-700 dark:wpstg-bg-slate-800 <?php echo $isCreateBackupModal ? 'wpstg-cloud-provider-option' : ''; ?>" data-row="<?php echo esc_attr((string)$rowCount); ?>" data-position="<?php echo esc_attr((string)$positionInRow); ?>">
                <?php
                $isActivated   = $storages->isActivated($storage['authClass']);
                $isProStorage  = empty($storage['authClass']);
                $isDisabled    = !$isActivated || (!$isProVersion && $isProStorage) || $isPersonalLicense || $isRestrictedStorage;
                $disabledClass = $isDisabled ? 'wpstg-storages-settings-disabled' : '';
                $tooltipClass  = $isDisabled && $isProVersion ? 'wpstg--tooltip' : '';
                $tooltipText   = __('Click on "Configure" to set up and activate the storage provider first.', 'wp-staging');
                $upgradeLink   = Language::getUpgradeUrl('storage_' . $storage['id']);
                if ($isPersonalLicense || ($isRestrictedStorage && $licenseType !== 'basic')) {
                    $upgradeLink = admin_url('admin.php?page=wpstg-license');
                    $tooltipText = __('Upgrade to the Business plan (or higher) to start using this feature.', 'wp-staging');
                }

                if (empty($storagesPrefix)) {
                    $storagesPrefix = 'storage-';
                }
                ?>
                <label class="wpstg-storages-label <?php echo esc_attr($disabledClass); ?> <?php echo esc_attr($tooltipClass); ?> !wpstg-flex wpstg-items-center wpstg-gap-1 wpstg-text-slate-700 dark:wpstg-text-slate-200">
                    <input type="checkbox" class="wpstg-mt-1 wpstg-checkbox" id="<?php echo esc_attr($storagesPrefix . $storage['id']); ?>" name="storages" value="<?php echo esc_attr($storage['id']); ?>" data-summary-kind="storage" data-summary-label="<?php echo esc_attr($storageDisplayName); ?>" <?php echo $isDisabled ? "disabled" : "";?>>
                    <div class="wpstg-storages-content wpstg-flex-row">
                        <?php $assets->renderSvg($storage['id'], 'wpstg-storages-icon'); ?>
                        <span class="wpstg-storages-name <?php echo esc_attr($disabledClass) ?> dark:wpstg-text-slate-100"><?php echo esc_html($storageDisplayName); ?></span>
                    </div>
                    <?php if ($isDisabled && $isProVersion) : ?>
                        <span class="wpstg--tooltiptext">
                            <?php echo esc_html($tooltipText); ?>
                        </span>
                    <?php endif; ?>
                </label>
                <?php if (!$isProVersion && $isProStorage || $isPersonalLicense || $isRestrictedStorage) { ?>
                    <a href="<?php echo esc_url($upgradeLink); ?>" target="_blank" class="wpstg-upgrade-btn !wpstg-p-0"><?php esc_html_e('Upgrade', 'wp-staging'); ?></a>
                <?php } else { ?>
                    <a href="javascript:void(0)" class="wpstg-configure-btn dark:wpstg-text-blue-400 dark:hover:wpstg-text-blue-300 wpstg-text-sm" data-id="<?php echo esc_attr($storage['id']); ?>">
                        <?php echo esc_html__('Configure', 'wp-staging'); ?>
                    </a>
                <?php } ?>
            </div>

            <?php if (!$isCloudUpsell && ($positionInRow === 1 || $storageKey === count($allStorages) - 1)) :?>
                <div id="wpstg-<?php echo esc_html($storagesPrefix);?>settings-<?php echo esc_attr((string)$rowCount); ?>" class="wpstg-storages-clear"></div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($isCreateBackupModal) : ?>
                </div>
            </details>
        </div>
    <?php endif; ?>
</div>
