<?php

use WPStaging\Manager\Database\TableDto;
use WPStaging\Framework\Collection\Collection;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Entity\Snapshot;

/** @var TemplateEngine $this */
/** @var Snapshot $snapshot */
/** @var TableDto[]|Collection $snapshotTables */
/** @var TableDto[]|Collection $prodTables */
/** @var Closure $isTableChanged */
?>
<div class="wpstg-beta-notice">
    <?php _e('WP Staging snapshot restoring works well but is a new feature in beta status. Please use it on your own risk and have a backup of your site before using it. <br>If you experience an issue or you have a feature request <a href="#" id="wpstg-snapshots-report-issue-button">please give us some feedback.</a>','wp-staging')?>
</div>
<div class="wpstg-notice-alert wpstg-failed">
    <h4 style="margin:0;">
        <?php _e('Take care: This will overwrite your database!', 'wp-staging') ?>
    </h4>

    <p>
        <?php _e('Are you sure that you want to restore the WordPress database with this snapshot created at ' . $this->transformToWpFormat($snapshot->getCreatedAt()) . '?', 'wp-staging') ?>
    </p>
    <?php _e('The production tables will be overwritten with the snapshot ones and the WordPress database will be restored to another point in time.', 'wp-staging') ?>
    <br><br>
    <?php _e('This will not delete any tables that are existing on the production site but not in the snapshot. If you want to remove them you will need to delete them manually.', 'wp-staging') ?>
    <br><br>
    <?php _e('Do not interrupt the process. Restoring can not be undone!', 'wp-staging') ?>
</div>

<div class="wpstg-box">
    <div class="wpstg-float-left">

    <h3><?php _e('These production tables will be overwritten:', 'wp-staging') ?></h3>
    <table class="wpstg--snaphot-restore-table">
    <?php foreach ($prodTables as $prodTable): ?>
    <tr>
        <?php
        $lastUpdate = $this->transformToWpFormat($prodTable->getUpdatedAt() ?: $prodTable->getCreatedAt());
        $textBoldDanger = $isTableChanged($prodTable, $snapshotTables) ? ' wpstg--text--strong wpstg--text--danger' : '';
        $title = empty($textBoldDanger) ? '' : __('This table is different compared to its snapshot equivalent.','wp-staging');
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
    <div class="wpstg-float-left">
    <h3><?php _e('These snapshot tables will be restored:', 'wp-staging') ?></h3>
    <table class="wpstg--snaphot-restore-table">
        <?php foreach ($snapshotTables as $snapshotTable): ?>
            <tr>
                <?php
                $lastUpdate = $this->transformToWpFormat($snapshotTable->getUpdatedAt() ?: $snapshotTable->getCreatedAt());
                $textBold = $isTableChanged($snapshotTable, $prodTables) ? ' wpstg--text--strong' : '';
                $title = empty($textBold) ? '' : __('This table is different compared to its production equivalent.','wp-staging');
                ?>
                <td>
                    <span class="wpstg-db-table<?php echo $textBold ?>" title="<?php echo $title ?>"><?php echo $snapshotTable->getName() ?></span>
                </td>
                <td>
                    <span class="wpstg-size-info <?php echo $textBold ?>"><?php echo $snapshotTable->getHumanReadableSize() ?></span>
                    <span class="wpstg-size-info <?php echo $textBold ?>" title="Last updated: <?php echo $lastUpdate ?>"> - <?php echo $lastUpdate ?></span>
                </td>
            </tr>
        <?php endforeach ?>
    </table>
    </div>
</div>

<a href="#" class="wpstg-link-btn wpstg-blue-primary" id="wpstg--snapshot--restore--cancel">
    <?php _e('Cancel', 'wp-staging') ?>
</a>

<a href="#" class="wpstg-link-btn wpstg-blue-primary" id="wpstg--snapshot--restore"
   data-id="<?php echo $snapshot->getId() ?>">
    <?php _e('Restore Snapshot', 'wp-staging') ?>
</a>
