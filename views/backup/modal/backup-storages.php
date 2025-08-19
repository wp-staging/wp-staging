<?php

/**
 * @var bool $isProVersion
 * @var string $storagesPrefix
 * @var bool $isPersonalLicense
 */

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Assets\Assets;
use WPStaging\Framework\Facades\UI\Checkbox;
use WPStaging\Backup\Storage\Providers;

/** @var Providers */
$storages = WPStaging::make(Providers::class);
$assets   = WPStaging::make(Assets::class);
?>
<div class="wpstg-storages-section">
    <?php if ($storagesPrefix !== 'storage-') :?>
    <h4 class="swal2-title wpstg-w-100">
        <?php esc_html_e('Storage Providers', 'wp-staging') ?>
    </h4>
    <?php endif; ?>
    <div class="wpstg-storages-grid">
        <?php
        $itemCount = 0;
        $rowCount = 0;

        // Add Local Storage if needed
        if ($storagesPrefix === 'storage-') :
            $itemCount++;
            ?>
            <div class="wpstg-storages-option" data-row="<?php echo esc_attr($rowCount); ?>" data-position="0">
                <label class="wpstg-storages-label">
                    <?php Checkbox::render("storage-localStorage", 'storages', 'localStorage', true); ?>
                    <div class="wpstg-storages-content">
                        <?php $assets->renderSvg('folder', 'wpstg-storages-icon'); ?>
                        <span class="wpstg-storages-name"><?php esc_html_e('Local Storage', 'wp-staging'); ?></span>
                    </div>
                </label>
            </div>
        <?php endif; ?>

        <?php
        $allStorages = $storages->getStorages($enabled = true);
        foreach ($allStorages as $storageKey => $storage) :
            $positionInRow = $itemCount % 2;
            if ($positionInRow === 0) {
                $rowCount++;
            }

            $itemCount++;
            ?>
            <div class="wpstg-storages-option" data-row="<?php echo esc_attr($rowCount); ?>" data-position="<?php echo esc_attr($positionInRow); ?>">
                <?php
                $isActivated   = $storages->isActivated($storage['authClass']);
                $isProStorage  = empty($storage['authClass']);
                $isDisabled    = !$isActivated || (!$isProVersion && $isProStorage) || $isPersonalLicense;
                $disabledClass = $isDisabled ? 'wpstg-storages-settings-disabled' : '';
                $tooltipClass  = $isDisabled && $isProVersion ? 'wpstg--tooltip' : '';
                $tooltipText   = __('Click on "Configure" to set up and activate the storage provider first.', 'wp-staging');
                $upgradeLink   = sprintf('https://wp-staging.com/get-%s', $storage['id']);
                if ($isPersonalLicense) {
                    $upgradeLink = admin_url('admin.php?page=wpstg-license');
                    $tooltipText = __('Upgrade to the Business plan (or higher) to start using this feature.', 'wp-staging');
                }

                if (empty($storagesPrefix)) {
                    $storagesPrefix = 'storage-';
                }
                ?>
                <label class="wpstg-storages-label <?php echo esc_attr($disabledClass); ?> <?php echo esc_attr($tooltipClass); ?>">
                    <?php Checkbox::render($storagesPrefix . $storage['id'], 'storages', $storage['id'], false, ['isDisabled' => $isDisabled]); ?>
                    <div class="wpstg-storages-content">
                        <?php $assets->renderSvg($storage['id'], 'wpstg-storages-icon'); ?>
                        <span class="wpstg-storages-name <?php echo esc_attr($disabledClass) ?>"><?php echo esc_html($storage['name']); ?></span>
                    </div>
                    <?php if ($isDisabled && $isProVersion) : ?>
                        <span class="wpstg--tooltiptext">
                            <?php echo esc_html($tooltipText); ?>
                        </span>
                    <?php endif; ?>
                </label>
                <?php if (!$isProVersion && $isProStorage || $isPersonalLicense) { ?>
                    <a href="<?php echo esc_url($upgradeLink); ?>" target="_blank" class="wpstg-upgrade-btn"><?php esc_html_e('Upgrade', 'wp-staging'); ?></a>
                <?php } else { ?>
                    <a href="javascript:void(0)" class="wpstg-configure-btn" data-id="<?php echo esc_attr($storage['id']); ?>">
                        <?php echo esc_html__('Configure', 'wp-staging'); ?>
                    </a>
                <?php } ?>
            </div>

            <?php if ($positionInRow === 1 || $storageKey === count($allStorages) - 1) :?>
                <div id="wpstg-<?php echo esc_html($storagesPrefix);?>settings-<?php echo esc_attr($rowCount); ?>" class="wpstg-storages-clear"></div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
