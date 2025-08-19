<?php

/**
 * @var \WPStaging\Framework\TemplateEngine\TemplateEngine $this
 * @var array $backupParts
 */

?>
<?php
foreach ($backupParts as $backupPart) :?>
<div class="wpstg--backups--part">
    <div class="wpstg-backup-part-header">
        <div class="wpstg-backup-part-title">
            <?php $this->getAssets()->renderSvg($backupPart['icon']); ?>
            <div class="wpstg--backup-category <?php esc_attr_e($backupPart['partType'], 'wp-staging');?>">
                <?php esc_html_e($backupPart['name'], 'wp-staging'); ?>
            </div>
            <?php if (!empty($backupPart['partIndex'])) : ?>
                <span class="wpstg--backup-category wpstg--backup-category-parts">
                    <?php esc_html_e($backupPart['partIndex'], 'wp-staging'); ?>
                </span>
            <?php endif; ?>
        </div>
        <a href="<?php esc_attr_e($backupPart['downloadLink'], 'wp-staging'); ?>" class="wpstg--download-btn">
            <?php $this->getAssets()->renderSvg('download'); ?>
        </a>
    </div>
    <div class="wpstg-backup-part-desc">
        <?php esc_html_e($backupPart['description'], 'wp-staging'); ?>
    </div>
    <div class="wpstg--backup-parts-info">
        <span class="wpstg--backup-icon">
            <?php $this->getAssets()->renderSvg('file'); ?>
            <?php esc_html_e('File Size:', 'wp-staging');?>
            <?php esc_html_e($backupPart['fileSize'], 'wp-staging');?>
        </span>
    </div>
</div>
<?php endforeach;?>
