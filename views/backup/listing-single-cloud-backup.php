<?php

use WPStaging\Framework\Facades\Escape;

/**
 * @var \WPStaging\Framework\TemplateEngine\TemplateEngine $this
 * @var \WPStaging\Backup\Entity\ListableBackup            $backup
 * @var string                                             $urlAssets
 * @see \WPStaging\Pro\Backup\Ajax\CloudFileList
 */
$name                = $backup->name;
$notes               = empty($backup->notes) ? '' : $backup->notes;
$createdAt           = empty($backup->dateCreatedTimestamp) ? 0 : $backup->dateCreatedTimestamp;
$uploadedAt          = empty($backup->dateUploadedTimestamp) ? 0 : $backup->dateUploadedTimestamp;
$size                = $backup->size;
$id                  = $backup->id;
$automatedBackup     = empty($backup->automatedBackup) ? false : $backup->automatedBackup;
$legacy              = empty($backup->isLegacy) ? false : $backup->isLegacy;
$corrupt             = empty($backup->isCorrupt) ? false : $backup->isCorrupt;
$storageProviderName = $backup->storageProviderName;
?>
<li id="<?php echo esc_attr($storageProviderName . "-" . $id) ?>" class="wpstg-clone wpstg-backup wpstg-cloud-backup-item" data-name="<?php echo esc_attr($backup->name); ?>">
    <div class="wpstg-clone-header">
        <span class="wpstg-clone-title wpstg-clone-cloud-title">
            <?php echo esc_html($name); ?>
        </span>
        <div>
            <span class="wpstg-cloud-backup-type"><?php echo esc_html($backup->type); ?></span>
        </div>
        <div class="wpstg-clone-actions">
            <div class="wpstg-dropdown wpstg-action-dropdown">
                <a href="#" class="wpstg-dropdown-toggler transparent">
                    <?php esc_html_e("Actions", "wp-staging"); ?>
                    <span class="wpstg-caret"></span>
                </a>
                <div class="wpstg-dropdown-menu">
                    <?php if (!$legacy && !$corrupt) : ?>
                        <a href="#" class="wpstg-clone-action wpstg--cloud--backup--restore" data-id="<?php echo esc_attr($backup->id) ?>" data-name="<?php echo esc_attr($name) ?>" data-storageProviderName="<?php echo esc_attr($storageProviderName) ?>" data-size="<?php echo esc_attr($size) ?>" data-title="<?php esc_attr_e('Restore and overwrite the current site with the contents of this backup.', 'wp-staging') ?>" title="<?php esc_attr_e('Restore and overwrite current website according to the contents of this backup.', 'wp-staging') ?>">
                            <?php esc_html_e('Restore', 'wp-staging') ?>
                        </a>
                    <?php endif ?>
                    <a href="#" class="wpstg--cloud--backup--download wpstg-merge-clone wpstg-clone-action" data-id="<?php echo esc_attr($backup->id) ?>" data-name="<?php echo esc_attr($name) ?>" data-storageProviderName="<?php echo esc_attr($storageProviderName) ?>" data-size="<?php echo esc_attr($size) ?>" title="<?php esc_attr_e('Download backup file to server or local computer', 'wp-staging') ?>">
                        <?php esc_html_e('Download', 'wp-staging') ?>
                    </a>
                    <a href="#" data-id="<?php echo esc_attr($storageProviderName . "-" . $id) ?>" data-file="<?php echo esc_attr($id); ?>" data-storageProviderName="<?php echo esc_attr($storageProviderName) ?>" data-name="<?php echo esc_attr($name) ?>" class="wpstg-remove-clone wpstg-clone-action wpstg-delete-cloud-backup" title="<?php esc_attr_e('Delete this backup. This action can not be undone!', 'wp-staging') ?>">
                        <?php esc_html_e('Delete', 'wp-staging') ?>
                    </a>
                    <?php
                    do_action('wpstg.views.backup.listing.single.after_actions', $backup);
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="wpstg-staging-info">
        <ul>
            <?php if ($createdAt) : ?>
                <li>
                    <strong><?php $corrupt ? esc_html_e('Last modified:', 'wp-staging') : esc_html_e('Created on:', 'wp-staging') ?></strong>
                    <?php
                    if (strpos($createdAt, "00:00:00") === false) {
                        $date = new DateTime($createdAt);
                        echo esc_html($this->transformToWpFormat($date));
                    } else {
                        esc_html_e("Unknown", "wp-staging");
                    }
                    ?>
                </li>
            <?php endif ?>
            <?php if ($notes) : ?>
                <li>
                    <strong><?php esc_html_e('Notes:', 'wp-staging') ?></strong><br />
                    <div class="backup-notes">
                    <?php echo Escape::escapeHtml(nl2br($notes, 'wp-staging'), 'wp-staging'); ?>
                    </div>
                </li>
            <?php endif ?>
            <li>
                <strong><?php esc_html_e('Size: ', 'wp-staging') ?></strong>
                <?php echo esc_html(size_format($size)); ?>
            </li>
        </ul>
    </div>
</li>
