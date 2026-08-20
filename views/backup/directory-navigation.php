<?php

use WPStaging\Framework\Facades\UI\Checkbox;

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
<div class='wpstg-dir'>
    <?php
        Checkbox::render(
            $idName,
            'selectedDirectories[]',
            isset($data["path"]) ? $data["path"] : '',
            false,
            [
                'classes'    => 'wpstg-check-dir',
                'isDisabled' => $isDisabled || $forceDisabled,
            ],
            [
                'id' => '#wpstg-scanning-files',
            ]
        );
        ?>
    <label
        class="wpstg-backup-expand-dir-label <?php echo ($isDisabled || $forceDisabled) ? 'wpstg-storage-settings-disabled' : ''; ?>"
        for="<?php echo esc_attr($idName); ?>"
        title="<?php echo (($isDisabled || $forceDisabled) ? esc_attr__('Staging sites and wp core folders can not be selected.', 'wp-staging') : esc_attr($dirBaseName)); ?>"
        >
            <?php echo esc_attr($dirBaseName); ?>
    </label>
</div>
