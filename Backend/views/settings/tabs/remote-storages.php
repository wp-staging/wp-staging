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
    $provider = strtolower(sanitize_text_field($_REQUEST['sub']));
}

$loadingBarsOption = ['googledrive' => 9, 'amazons3' => 15, 'dropbox' => 9, 'sftp' => 20, 'digitalocean-spaces' => 15, 'wasabi-s3' => 15, 'generic-s3' => 23];
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
}
?>
