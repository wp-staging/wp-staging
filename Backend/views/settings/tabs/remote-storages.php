<?php
$storages = WPStaging\Core\WPStaging::make(\WPStaging\Pro\Backup\Storage\Providers::class);
$provider = 'googledrive';
$providerId = '';
if (isset($_REQUEST['sub'])) {
    $provider = strtolower($_REQUEST['sub']);
}

?>
<div class="wpstg-storages-postbox">
    <?php foreach ($storages->getStorages(true) as $storage) : ?>
        <?php
            $isActive = $provider === strtolower($storage['id']);
        if ($isActive) {
            $providerId = $storage['id'];
        }
        ?>
        <a class="wpstg-storage-provider <?php echo $isActive ? 'wpstg-storage-provider-active' : '' ?>" href="<?php echo $isActive ? 'javascript:void(0);' : $storage['settingsPath']; ?>">
            <?php echo $storage['name']; ?>
        </a>
    <?php endforeach; ?>
</div>

<?php
$providerPath = $this->path . "views/settings/tabs/storages/" . $provider . "-settings.php";
if (file_exists($providerPath)) {
    ?> 
    <div class="wpstg-storage-postbox">
    <?php require_once($providerPath); ?>
    </div>
    <?php
}
?>
