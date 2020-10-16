<?php

use WPStaging\Framework\Collection\OptionCollection;
use WPStaging\Framework\TemplateEngine\TemplateEngine;

/** @var TemplateEngine $this */
/** @var OptionCollection $snapshots */

/**
 * @todo See if we can unify this file with src/Pro/Snapshot/template/listing.php:12
 */
?>
<div class="wpstg-beta-notice">
    <?php _e('WP Staging snapshots work well but is a new feature in beta status. Please use it on your own risk and have a backup of your site before using it. <br>If you experience an issue or you have a feature request <a href="#" id="wpstg-snapshots-report-issue-button">please give us some feedback.</a>','wp-staging')?>
</div>
<div id="wpstg-step-1">
    <button id="wpstg-new-snapshot" class="wpstg-next-step-link wpstg-link-btn wpstg-blue-primary wpstg-button"
            data-action="wpstg--snapshots--create">
        <?php _e('Take New Snapshot', 'wp-staging') ?>
    </button>
    <div class="wpstg--tooltip"> <?php _e('What is this?', 'wp-staging'); ?>

        <span class="wpstg--tooltiptext wpstg--tooltiptext-snapshots">
    <?php _e('Snapshots are copies of the WordPress database tables at a particular point in time. 
    They can restore WordPress and roll back the database to another state.<br><br>
    This is useful if you need to reset WordPress to the state before you\'ve pushed a staging site to live or if you want to revert other database changes 
    like activating a new theme or updating its settings.<br><br>
    WP Staging Snapshots include all WordPress core tables and custom ones created by other plugins.
    Restoring a snapshot will not affect other staging sites or snapshots. <br><br>
    No files are included in snapshots! WP Staging snapshots are a quick way to roll back your site in time but for a full site backup it is recommended to use a dedicated backup plugin!
', 'wp-staging') ?>
    <p></p>
    <?php if (is_multisite()) {
        echo '<strong>' . __('Multisite Users Only: ', 'wp-staging') . '</strong>';
        echo '<p></p>';
        echo __("- If you run the snapshot function on a multisite network site the snapshot will contain only the tables belonging to the particular network site. <p></p>It will not store all database tables of all network sites. So you are able to restore all network sites independently. <p></p>- If you create a snapshot on a multisite main site it will create a snapshot of <strong>all database tables</strong>.</p></p><strong>Take care:</strong> Restoring a multisite main snapshot will <strong>restore all children sites including the mainsite.</strong>", 'wp-staging');
    } ?>
        </span>
    </div>
</div>

<div id="wpstg-existing-snapshots">
    <h3>
        <?php echo $snapshots ? __('Your Snapshots:', 'wp-staging') : '' ?>
    </h3>
    <?php foreach ($snapshots as $snapshot): ?>
        <div id="<?php echo $snapshot->getId() ?>" class="wpstg-clone">
            <span class="wpstg-clone-title"><?php echo $snapshot->getName() ?></span>

            <a href="#" class="wpstg--snapshot--export wpstg-merge-clone wpstg-clone-action"
               data-id="<?php echo $snapshot->getId() ?>"
               title="<?php _e('Download backup as sql file to local system', 'wp-staging') ?>">
                <?php _e('Export', 'wp-staging') ?>
            </a>

            <a href="#" class="wpstg--snapshot--restore wpstg-merge-clone wpstg-clone-action"
               data-id="<?php echo $snapshot->getId() ?>"
               title="<?php _e('Restore this snapshot to your live website!', 'wp-staging') ?>">
                <?php _e('Restore', 'wp-staging') ?>
            </a>

            <a href="#" class="wpstg-remove-clone wpstg-clone-action wpstg-delete-snapshot"
               data-id="<?php echo $snapshot->getId() ?>"
               title="<?php _e('Delete this snapshot. This action can not be undone!', 'wp-staging') ?>">
                <?php _e('Delete', 'wp-staging') ?>
            </a>

            <a href="#" class="wpstg--snapshot--edit wpstg-clone-action"
               data-id="<?php echo $snapshot->getId() ?>"
               data-name="<?php echo $snapshot->getName() ?>"
               data-notes="<?php echo $snapshot->getNotes() ?>"
               title="<?php _e('Edit backup name and / or notes', 'wp-staging') ?>">
                <?php _e('Edit', 'wp-staging') ?>
            </a>

            <div class="wpstg-staging-info">
                <ul>
                    <li>
                        <strong><?php _e('Table Prefix:', 'wp-staging') ?></strong>
                        <?php echo $snapshot->getId() ?>
                    </li>
                    <li>
                        <strong><?php _e('Created on:', 'wp-staging') ?></strong>
                        <?php echo $this->transformToWpFormat($snapshot->getCreatedAt()) ?>
                        <?php if ($snapshot->getUpdatedAt()): ?>
                            &nbsp; | &nbsp;<strong><?php _e('Updated on:', 'wp-staging') ?></strong>
                            <?php echo $this->transformToWpFormat($snapshot->getUpdatedAt()) ?>
                        <?php endif ?>
                    </li>
                    <?php if ($snapshot->getNotes()):?>
                        <li>
                            <strong><?php _e('Notes:', 'wp-staging') ?></strong><br/>
                            <?php echo nl2br($snapshot->getNotes()) ?>
                        </li>
                    <?php endif ?>
                </ul>
            </div>
        </div>
    <?php endforeach ?>
</div>

<div id="wpstg-delete-confirmation"></div>
