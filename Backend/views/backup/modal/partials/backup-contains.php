<?php

/**
 * This function is to re-use the UI for "Backup Contains" without duplicating the code.
 */

$isExportingDatabase            = isset($isExportingDatabase) && $isExportingDatabase;
$isExportingPlugins             = isset($isExportingPlugins) && $isExportingPlugins;
$isExportingMuPlugins           = isset($isExportingMuPlugins) && $isExportingMuPlugins;
$isExportingThemes              = isset($isExportingThemes) && $isExportingThemes;
$isExportingUploads             = isset($isExportingUploads) && $isExportingUploads;
$isExportingOtherWpContentFiles = isset($isExportingOtherWpContentFiles) && $isExportingOtherWpContentFiles;

if (!isset($urlAssets)) {
    $urlAssets = trailingslashit(WPSTG_PLUGIN_URL) . 'assets/';
}

$partSize = [
    'sqlSize'       => null,
    'wpcontentSize' => null,
    'pluginsSize'   => null,
    'mupluginsSize' => null,
    'themesSize'    => null,
    'uploadsSize'   => null,
];

if (!empty($indexPartSize) && is_array($indexPartSize)) {
    foreach ($partSize as $part => $val) {
        $bytes = !empty($indexPartSize[$part]) ? $indexPartSize[$part] : 0;
        $partSize[$part] = size_format($bytes, 2);
    }
}

$partSize = (object)$partSize;
?>

<ul class="wpstg-restore-backup-contains wpstg-listing-single-backup">
    <?php if ($isExportingDatabase) : ?>
        <li>
            <span class="wpstg--tooltip wpstg-backups-contains">
                <img class="wpstg--dashicons" src="<?php echo esc_url($urlAssets); ?>svg/vendor/dashicons/database.svg"/>
                <div class='wpstg--tooltiptext'><?php esc_html_e('Database', 'wp-staging');?><br><?php echo esc_html($partSize->sqlSize);?></div>
            </span>
        </li>
    <?php endif; ?>
    <?php if ($isExportingPlugins) : ?>
        <li>
            <span class="wpstg--tooltip wpstg-backups-contains">
                <img class="wpstg--dashicons" src="<?php echo esc_url($urlAssets); ?>svg/vendor/dashicons/admin-plugins.svg"/>
                <div class='wpstg--tooltiptext'><?php esc_html_e('Plugins', 'wp-staging');?><br><?php echo esc_html($partSize->pluginsSize);?></div>
            </span>
        </li>
    <?php endif; ?>
    <?php if ($isExportingMuPlugins) : ?>
        <li>
            <span class="wpstg--tooltip wpstg-backups-contains">
                <img class="wpstg--dashicons" src="<?php echo esc_url($urlAssets); ?>svg/vendor/dashicons/plugins-checked.svg"/>
                <div class='wpstg--tooltiptext'><?php esc_html_e('Must-Use Plugins', 'wp-staging');?><br><?php echo esc_html($partSize->mupluginsSize);?></div>
            </span>
        </li>
    <?php endif; ?>
    <?php if ($isExportingThemes) : ?>
        <li>
            <span class="wpstg--tooltip wpstg-backups-contains">
                <img class="wpstg--dashicons" src="<?php echo esc_url($urlAssets); ?>svg/vendor/dashicons/layout.svg"/>
                <div class='wpstg--tooltiptext'><?php esc_html_e('Themes', 'wp-staging');?><br><?php echo esc_html($partSize->themesSize);?></div>
            </span>
        </li>
    <?php endif; ?>
    <?php if ($isExportingUploads) : ?>
        <li>
            <span class="wpstg--tooltip wpstg-backups-contains">
                <img class="wpstg--dashicons" src="<?php echo esc_url($urlAssets); ?>svg/vendor/dashicons/images-alt.svg"/>
                <div class='wpstg--tooltiptext'><?php esc_html_e('Uploads', 'wp-staging');?><br><?php echo esc_html($partSize->uploadsSize);?></div>
            </span>
        </li>
    <?php endif; ?>
    <?php if ($isExportingOtherWpContentFiles) : ?>
        <li>
            <span class="wpstg--tooltip wpstg-backups-contains">
                <img class="wpstg--dashicons" src="<?php echo esc_url($urlAssets); ?>svg/vendor/dashicons/admin-generic.svg"/>
                <div class='wpstg--tooltiptext'><?php esc_html_e('Other files in wp-content', 'wp-staging');?><br><?php echo esc_html($partSize->wpcontentSize);?></div>
            </span>
        </li>
    <?php endif; ?>
</ul>
