<?php

use WPStaging\Framework\Database\TableDto;
use WPStaging\Framework\Collection\Collection;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Pro\Database\Legacy\Entity\Backup;

/** @var TemplateEngine $this */
/** @var Backup $backup */
/** @var TableDto[]|Collection $backupTables */
/** @var TableDto[]|Collection $prodTables */
/** @var Closure $isTableChanged */
?>
<div class="wpstg-beta-notice" style="display:none;">
    <?php _e('WP STAGING backup restoring works well but is a new feature in beta status. Please use it on your own risk and have a backup of your site before using it. <br>If you experience an issue or you have a feature request <a href="#" id="wpstg-backups-report-issue-button">please give us some feedback.</a>', 'wp-staging') ?>
</div>
<div class="wpstg-notice-alert wpstg-failed">
    <h4 style="margin:0;">
        <?php _e('Take care: This will overwrite your database!', 'wp-staging') ?>
    </h4>

    <p>
        <?php _e('Are you sure that you want to restore the WordPress database with this backup created at ' . $this->transformToWpFormat($backup->getCreatedAt()) . '?', 'wp-staging') ?>
    </p>
    <?php _e('The production tables will be overwritten with the backup and the WordPress database will be restored to another point in time.', 'wp-staging') ?>
    <br><br>
    <?php _e('This will not delete any tables that are existing on the production site but not in the backup. If you want to remove them you will need to delete them manually.', 'wp-staging') ?>
    <br><br>
    <?php _e('Do not interrupt the process. Restoring can not be undone!', 'wp-staging') ?>
</div>

<div class="wpstg-box">
    <div class="wpstg-float-left">

        <h3><?php _e('Tables below will be overwritten:', 'wp-staging') ?></h3>
        <table class="wpstg--snaphot-restore-table">
            <?php foreach ($prodTables as $prodTable) : ?>
                <tr>
                    <?php
                    $lastUpdate     = $this->transformToWpFormat($prodTable->getUpdatedAt() ?: $prodTable->getCreatedAt());
                    $textBoldDanger = $isTableChanged($prodTable, $backupTables) ? ' wpstg--text--strong wpstg--text--danger' : '';
                    $title          = empty($textBoldDanger) ? '' : __('This table is different compared to its backup equivalent.', 'wp-staging');
                    ?>
                    <td>
                        <span class="wpstg-db-table<?php echo $textBoldDanger ?>" title="<?php echo $title ?>"><?php echo $prodTable->getName() ?></span>
                    </td>
                    <td>
                        <span class="wpstg-size-info <?php echo $textBoldDanger ?>"><?php echo $prodTable->getHumanReadableSize() ?></span>
                        <span class="wpstg-size-info <?php echo $textBoldDanger ?>" title="Last updated: <?php echo $lastUpdate ?>"> - <?php echo $lastUpdate; ?></span>
                    </td>
                </tr>
            <?php endforeach ?>
        </table>
    </div>
    <div class="wpstg-float-left" style="margin-left:10px;">
        <h3><?php _e('Tables below will be restored:', 'wp-staging') ?></h3>
        <table class="wpstg--snaphot-restore-table">
            <?php foreach ($backupTables as $backupTable) : ?>
                <tr>
                    <?php
                    $lastUpdate = $this->transformToWpFormat($backupTable->getUpdatedAt() ?: $backupTable->getCreatedAt());
                    $textBold   = $isTableChanged($backupTable, $prodTables) ? ' wpstg--text--strong' : '';
                    $title      = empty($textBold) ? '' : __('This table is different compared to its production equivalent.', 'wp-staging');
                    ?>
                    <td>
                        <span class="wpstg-db-table<?php echo $textBold ?>" title="<?php echo $title ?>"><?php echo $backupTable->getName() ?></span>
                    </td>
                    <td>
                        <span class="wpstg-size-info <?php echo $textBold ?>"><?php echo $backupTable->getHumanReadableSize() ?></span>
                        <span class="wpstg-size-info <?php echo $textBold ?>" title="Last updated: <?php echo $lastUpdate ?>"> - <?php echo $lastUpdate ?></span>
                    </td>
                </tr>
            <?php endforeach ?>
        </table>
    </div>
</div>

<a href="#" class="wpstg-link-btn wpstg-blue-primary" id="wpstg--backup--restore--cancel">
    <?php _e('Cancel', 'wp-staging') ?>
</a>

<a href="#" class="wpstg-link-btn wpstg-blue-primary" id="wpstg--backup--restore"
   data-id="<?php echo $backup->getId() ?>">
    <?php _e('Restore Backup', 'wp-staging') ?>
</a>

<!-- TODO RPoC -->
<div id="wpstg--modal--backup--process" data-cancelButtonText="<?php _e('CANCEL', 'wp-staging') ?>" style="display: none">
    <span class="wpstg-loader"></span>
    <h3 class="wpstg--modal--process--title" style="color: #a8a8a8;margin: .25em 0;">
        <?php _e('Processing...', 'wp-staging') ?>
    </h3>
    <div style="margin: .5em 0; color: #a8a8a8;">
        <?php
        echo sprintf(
            __('Progress %s - Elapsed time %s', 'wp-staging'),
            '<span class="wpstg--modal--process--percent">0</span>%',
            '<span class="wpstg--modal--process--elapsed-time">0:00</span>'
        )
        ?>
    </div>
    <button
            class="wpstg--modal--process--logs--tail"
            data-txt-bad="<?php echo sprintf(
                __('(%s) Critical, (%s) Errors, (%s) Warnings. Show Logs', 'wp-staging'),
                '<span class=\'wpstg--modal--logs--critical-count\'>0</span>',
                '<span class=\'wpstg--modal--logs--error-count\'>0</span>',
                '<span class=\'wpstg--modal--logs--warning-count\'>0</span>'
            ) ?>"
    >
        <?php _e('Show Logs', 'wp-staging') ?>
    </button>
    <div class="wpstg--modal--process--logs"></div>
</div>
