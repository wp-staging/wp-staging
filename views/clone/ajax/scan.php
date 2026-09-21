<?php

/**
 * @see \WPStaging\Backend\Administrator::ajaxCloneScan Context where this is included.
 *
 * @var \WPStaging\Backend\Modules\Jobs\Scan                  $scan
 * @var stdClass                                              $options
 * @var \WPStaging\Framework\Filesystem\Filters\ExcludeFilter $excludeUtils
 * @var bool                                                  $isPro
 *
 * @see \WPStaging\Backend\Modules\Jobs\Scan::start For details on $options.
 */

use WPStaging\Backend\Modules\Jobs\Job;
use WPStaging\Framework\Facades\Escape;

require WPSTG_VIEWS_DIR . 'clone/ajax/_partials/clone-name-field.php';
?>
<?php require_once WPSTG_VIEWS_DIR . 'staging/_partials/file-size-notice.php';?>
<section class="wpstg-card wpstg-card-body wpstg-mt-4">
    <div class="wpstg-tabs-wrapper">
        <p class="wpstg-tables-selection-note">
            <b class="wpstg--red"><?php esc_html_e("Note: ", "wp-staging") ?></b>
            <?php esc_html_e("These table and folder selections will be remembered for future updates and resets of this staging site.", "wp-staging") ?>
        </p>
        <a href="#" class="wpstg-tab-header active" data-id="#wpstg-scanning-db">
            <span class="wpstg-tab-triangle"></span>
            <?php echo esc_html__("Database Tables", "wp-staging") ?>
            <span id="wpstg-tables-count" class="wpstg-selection-preview"></span>
        </a>

        <fieldset class="wpstg-tab-section" id="wpstg-scanning-db">
            <?php require(WPSTG_VIEWS_DIR . 'selections/database-tables.php'); ?>
        </fieldset>

        <a href="#" class="wpstg-tab-header" data-id="#wpstg-scanning-files">
            <span class="wpstg-tab-triangle"></span>
            <?php echo esc_html__("Files", "wp-staging") ?>
            <span id="wpstg-files-count" class="wpstg-selection-preview"></span>
        </a>

        <fieldset class="wpstg-tab-section" id="wpstg-scanning-files">
            <?php require(WPSTG_VIEWS_DIR . 'selections/files.php'); ?>
        </fieldset>

        <a href="#" class="wpstg-tab-header" data-id="#wpstg-advanced-settings">
            <span class="wpstg-tab-triangle"></span>
            <?php
                $advanceSettingsTitle = esc_html__("Advanced Settings (Requires Pro Version)", "wp-staging");
                echo esc_html($advanceSettingsTitle);
            ?>
        </a>

        <div class="wpstg-tab-section" id="wpstg-advanced-settings">
            <?php
            if ($options->mainJob !== Job::UPDATE) {
                require_once(__DIR__  . '/login-data.php');
                require_once(__DIR__  . '/external-database.php');
                require_once(__DIR__  . '/custom-directory.php');
                require_once(__DIR__  . '/symlink-uploads.php');
            }

            if ($options->mainJob === Job::STAGING) {
                require_once(__DIR__ . '/cron-setting.php');
            }

            require_once(__DIR__ . '/mail-setting.php');
            require_once(__DIR__ . '/plugins-update.php');
            ?>
        </div>
    </div>

    <?php

    if ($options->current !== null && $options->mainJob === Job::UPDATE) {
        $uploadsSymlinked = isset($options->existingClones[$options->current]['uploadsSymlinked']) ? (bool)$options->existingClones[$options->current]['uploadsSymlinked'] : false;

        ?>
    <fieldset class="wpstg-fieldset" style="margin-left: 16px;">
        <?php require WPSTG_VIEWS_DIR . 'clone/ajax/_partials/update-cleanup-options.php'; ?>
    </fieldset>
        <?php
    }
    ?>
</section>
<div class="wpstg-mb-6"></div>
<?php require WPSTG_VIEWS_DIR . 'clone/ajax/_partials/scan-actions.php'; ?>
