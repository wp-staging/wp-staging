<?php

/**
 * @var string $id
 * @var array $missingParts
 * @var array $sizeIssues
 * @var array $existingBackupParts
 *
 * Used /Backend/views/backup/listing-single-backup.php
 */

if (!isset($urlAssets)) {
    $urlAssets = trailingslashit(WPSTG_PLUGIN_URL) . 'assets/';
}

?>

<a href="javascript:void(0)" class="wpstg-tab-header" data-id="#wpstg-invalid-backup-<?php echo esc_attr($id); ?>">
    <?php if (empty($sizeIssues) && empty($missingParts)) : ?>
        <span class="wpstg-tab-triangle" style="color: #3e3e3e"></span>
        <span style="color:#3e3e3e"><?php esc_html_e("Show available backup parts", "wp-staging") ?></span>
    <?php else : ?>
        <span class="wpstg-tab-triangle"></span>
        <span class="wpstg--text--danger"><?php esc_html_e("This is a multipart backup with issues!", "wp-staging") ?> </span>
        <div class="wpstg--tooltip" style="position: absolute;">
            <img class="wpstg--dashicons wpstg-dashicons-19 wpstg--grey" src="<?php echo esc_url($urlAssets); ?>svg/vendor/dashicons/info-outline.svg" alt="info"/>
            <span class="wpstg--tooltiptext wpstg--tooltiptext-backups">
            <?php esc_html_e("This backup contains missing or invalid parts.", 'wp-staging') ?>
        </span>
        </div>
    <?php endif; ?>
</a>

<fieldset class="wpstg-tab-section" id="wpstg-invalid-backup-<?php echo esc_attr($id); ?>">

    <h5>
        <?php esc_html_e('Multipart backup contains these parts', 'wp-staging') ?>
    </h5>
    <ol>
        <?php foreach ($existingBackupParts as $existingPart) : ?>
            <li><?php echo esc_html($existingPart); ?></li>
        <?php endforeach; ?>
    </ol>


    <?php if (!empty($missingParts)) : ?>
        <h5>
            <?php if (empty($sizeIssues)) : ?>
                <?php
                if (count($missingParts) === 1) {
                    esc_html_e('Part below is missing but you can still restore the other backup files!', 'wp-staging');
                } else {
                    esc_html_e('Parts below are missing but you can still restore the other backup files!', 'wp-staging');
                }
                ?>
            <?php else : ?>
                <?php esc_html_e('Missing Parts', 'wp-staging') ?>
            <?php endif; ?>
        </h5>
        <ol>
            <?php foreach ($missingParts as $part) : ?>
                <li><?php echo esc_html($part['name']); ?></li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
    <?php if (!empty($sizeIssues)) : ?>
        <h5><?php esc_html_e('Parts with invalid size that should be uploaded again:', 'wp-staging') ?></h5>
        <ol>
            <?php foreach ($sizeIssues as $part) : ?>
                <li><?php echo esc_html($part); ?></li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</fieldset>
