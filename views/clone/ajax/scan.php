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
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Facades\UI\Checkbox;

?>
<label id="wpstg-clone-label" for="wpstg-new-clone-id">
    <input type="text" id="wpstg-new-clone-id" class="wpstg-textbox"
        placeholder="<?php esc_html_e('Enter Site Name (Optional)', 'wp-staging') ?>"
        data-clone="<?php echo esc_attr($options->current); ?>"
        <?php if ($options->current !== null) {
            $siteName = isset($options->currentClone['cloneName']) ? Sanitize::sanitizeString(wpstg_urldecode($options->currentClone['cloneName'])) : $options->currentClone['directoryName'];
            echo ' value="' . esc_attr($siteName) . '"';
            echo " disabled='disabled'";
        } ?> />
</label>

<span class="wpstg-error-msg" id="wpstg-clone-id-error" style="display:none;">
    <?php
    echo Escape::escapeHtml(__(
        "<br>Probably not enough free disk space to create a staging site. " .
            "<br> You can continue but its likely that the copying process will fail.",
        "wp-staging"
    ))
    ?>
</span>

<div class="wpstg-tabs-wrapper">
    <p class="wpstg-tables-selection-note">
        <b class="wpstg--red"><?php esc_html_e("Note: ", "wp-staging") ?></b>
        <?php esc_html_e("The tables and folder selection will be saved and preselected for the next update or reset on this staging site.", "wp-staging") ?>
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
    <p class="wpstg--advanced-settings--checkbox">
        <label for="wpstg-clean-plugins-themes"><?php esc_html_e('Clean Plugins/Themes', 'wp-staging'); ?></label>
        <?php Checkbox::render('wpstg-clean-plugins-themes', 'wpstg-clean-plugins-themes', 'true'); ?>
        <span class="wpstg--tooltip">
            <img class="wpstg--dashicons" src="<?php echo esc_url($scan->getInfoIcon()); ?>" alt="info" />
            <span class="wpstg--tooltiptext">
                <?php esc_html_e('Delete all plugins & themes on staging site before starting update process.', 'wp-staging'); ?>
            </span>
        </span>
    </p>
    <p class="wpstg--advanced-settings--checkbox">
        <label for="wpstg-clean-uploads"><?php esc_html_e('Clean Uploads', 'wp-staging'); ?></label>
        <?php Checkbox::render('wpstg-clean-uploads', 'wpstg-clean-uploads', 'true'); ?>
        <span class="wpstg--tooltip">
            <img class="wpstg--dashicons" src="<?php echo esc_url($scan->getInfoIcon()); ?>" alt="info" />
            <span class="wpstg--tooltiptext">
                <?php esc_html_e('Delete entire folder wp-content/uploads on staging site including all images before starting update process.', 'wp-staging'); ?>
                <?php echo $uploadsSymlinked ? "<br/><br/><b>" . esc_html__("Note: This option is disabled as uploads directory is symlinked", "wp-staging") . "</b>" : '' ?>
            </span>
        </span>
    </p>
</fieldset>
<hr/>
    <?php
}
?>

<button type="button" class="wpstg-prev-step-link wpstg-button--primary wpstg-button-back-arrow">
    <i class="wpstg-back-arrow"></i>
    <?php esc_html_e("Back", "wp-staging") ?>
</button>

<?php
$label  = esc_html__("Start Cloning", "wp-staging");
$action = 'wpstg_cloning';
$btnId  = 'wpstg-start-cloning';
if ($options->current !== null && $options->mainJob === Job::UPDATE) {
    $label  = esc_html__("Update Staging Site", "wp-staging");
    $action = 'wpstg_update';
    $btnId  = 'wpstg-start-updating';
}
?>

<button type="button" id="<?php echo esc_attr($btnId); ?>" class="wpstg-next-step-link wpstg-button--primary wpstg-button--blue" data-action="<?php echo esc_attr($action); ?>" data-url="<?php echo esc_attr(isset($options->currentClone['url']) ? $options->currentClone['url'] : ''); ?>"><?php echo esc_html($label); ?></button>

<a href="#" id="wpstg-check-space"><?php esc_html_e('Check required disk space', 'wp-staging'); ?></a>