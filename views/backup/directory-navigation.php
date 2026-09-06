<?php

/**
 * @see WPStaging\Backup\Service\DirectoryExplorer\Scan::listDirectoryForBackup
 *
 * @var string $idName
 * @var bool   $isDisabled
 * @var bool   $forceDisabled
 * @var string $data current directory data
 * @var string $dirBaseName
 */

?>
<div class="wpstg-dir wpstg-flex wpstg-items-center wpstg-gap-2">
    <input type="checkbox" class="!wpstg-m-0 wpstg-checkbox wpstg-check-dir" id="<?php echo esc_attr($idName); ?>" name="selectedDirectories[]" <?php echo $isDisabled || $forceDisabled ? "disabled" : "";?> value="<?php echo isset($data["path"]) ? esc_attr($data["path"]) : ''; ?>" data-id="#wpstg-scanning-files" >
    <label class="wpstg-backup-expand-dir-label <?php echo ($isDisabled || $forceDisabled) ? 'wpstg-storage-settings-disabled' : ''; ?>"
        for="<?php echo esc_attr($idName); ?>"
        title="<?php echo (($isDisabled || $forceDisabled) ? esc_attr__('Staging sites and wp core folders can not be selected.', 'wp-staging') : esc_attr($dirBaseName)); ?>"
        >
            <?php echo esc_attr($dirBaseName); ?>
    </label>
</div>
