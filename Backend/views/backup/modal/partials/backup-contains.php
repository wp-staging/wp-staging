<?php

/**
 * This function is to re-use the UI for "Backup Contains" without duplicating the code.
 */

$isExportingDatabase = isset($isExportingDatabase) && $isExportingDatabase;
$isExportingPlugins = isset($isExportingPlugins) && $isExportingPlugins;
$isExportingMuPlugins = isset($isExportingMuPlugins) && $isExportingMuPlugins;
$isExportingThemes = isset($isExportingThemes) && $isExportingThemes;
$isExportingUploads = isset($isExportingUploads) && $isExportingUploads;
$isExportingOtherWpContentFiles = isset($isExportingOtherWpContentFiles) && $isExportingOtherWpContentFiles;

if (!isset($urlAssets)) {
    $urlAssets = trailingslashit(WPSTG_PLUGIN_URL) . 'assets/';
}
?>

<ul class="wpstg-restore-backup-contains wpstg-listing-single-backup">
    <?php if ($isExportingDatabase) : ?>
        <li>
            <span class="wpstg--tooltip wpstg-backups-contains">
                <img class="wpstg--dashicons" src="<?php echo esc_url($urlAssets); ?>svg/vendor/dashicons/database.svg"/>
                <div class='wpstg--tooltiptext'>Database</div>
            </span>
        </li>
    <?php endif; ?>
    <?php if ($isExportingPlugins) : ?>
        <li>
            <span class="wpstg--tooltip wpstg-backups-contains">
                <img class="wpstg--dashicons" src="<?php echo esc_url($urlAssets); ?>svg/vendor/dashicons/admin-plugins.svg"/>
                <div class='wpstg--tooltiptext'>Plugins</div>
            </span>
        </li>
    <?php endif; ?>
    <?php if ($isExportingMuPlugins) : ?>
        <li>
            <span class="wpstg--tooltip wpstg-backups-contains">
                <img class="wpstg--dashicons" src="<?php echo esc_url($urlAssets); ?>svg/vendor/dashicons/plugins-checked.svg"/>
                <div class='wpstg--tooltiptext'>Must-Use Plugins</div>
            </span>
        </li>
    <?php endif; ?>
    <?php if ($isExportingThemes) : ?>
        <li>
            <span class="wpstg--tooltip wpstg-backups-contains">
                <img class="wpstg--dashicons" src="<?php echo esc_url($urlAssets); ?>svg/vendor/dashicons/layout.svg"/>
                <div class='wpstg--tooltiptext'>Themes</div>
            </span>
        </li>
    <?php endif; ?>
    <?php if ($isExportingUploads) : ?>
        <li>
            <span class="wpstg--tooltip wpstg-backups-contains">
                <img class="wpstg--dashicons" src="<?php echo esc_url($urlAssets); ?>svg/vendor/dashicons/images-alt.svg"/>
                <div class='wpstg--tooltiptext'>Uploads</div>
            </span>
        </li>
    <?php endif; ?>
    <?php if ($isExportingOtherWpContentFiles) : ?>
        <li>
            <span class="wpstg--tooltip wpstg-backups-contains">
                <img class="wpstg--dashicons" src="<?php echo esc_url($urlAssets); ?>svg/vendor/dashicons/admin-generic.svg"/>
                <div class='wpstg--tooltiptext'>Other files in wp-content</div>
            </span>
        </li>
    <?php endif; ?>
</ul>
