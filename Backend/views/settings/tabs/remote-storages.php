<?php

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Security\Capabilities;

// This is already covered, but just to make sure, since this data is sensitive.
if (!current_user_can(WPStaging::make(Capabilities::class)->manageWPSTG())) {
    return;
}

$storages = WPStaging::make(\WPStaging\Backup\Storage\Providers::class);
$provider = 'googledrive';
$providerId = '';
if (isset($_REQUEST['sub'])) {
    $provider = strtolower(sanitize_file_name($_REQUEST['sub']));
}

$loadingBarsOption = ['googledrive' => 12, 'amazons3' => 17, 'dropbox' => 12, 'sftp' => 22, 'digitalocean-spaces' => 17, 'wasabi-s3' => 17, 'generic-s3' => 25];
?>
<div class="wpstg-storages-postbox">
    <?php foreach ($storages->getStorages(true) as $storage) : ?>
        <?php
            $isActive = $provider === strtolower($storage['id']);
        if ($isActive) {
            $providerId = $storage['id'];
        }
        ?>
        <a class="wpstg-storage-provider <?php echo $isActive ? 'wpstg-storage-provider-active' : '' ?>" href="<?php echo $isActive ? 'javascript:void(0);' : esc_url($storage['settingsPath']); ?>">
            <?php echo esc_html($storage['name']); ?>
        </a>
    <?php endforeach; ?>
</div>

<?php
$providerPath = $this->path . "views/settings/tabs/storages/" . $provider . "-settings.php";
$providerPath = wp_normalize_path($providerPath);
// Additional check to make sure no file is accessed outside the plugin storage setting directory
if (strpos($providerPath, wp_normalize_path($this->path) . "views/settings/tabs/storages/") !== 0) {
    ?>
    <div class="notice notice-error"><p><?php esc_html_e('Error: Wrong URL for remote settings provided!', 'wp-staging'); ?></p></div>
    <?php
    return;
}

if (file_exists($providerPath)) {
    ?> 
    <div class="wpstg-storage-postbox">
    <?php
    $numberOfLoadingBars = $loadingBarsOption[$provider] ?? 15;
    include(WPSTG_PLUGIN_DIR . 'Backend/views/_main/loading-placeholder.php');
    require_once($providerPath);
    ?>
    </div>
    <?php
    return;
}
?>

<div class="notice notice-error"><p><?php esc_html_e('Error: Wrong URL for remote settings provided!', 'wp-staging'); ?></p></div>
