
<?php
/**
 * @see \WPStaging\Backend\Administrator::ajaxCloneScan Context where this is included.
 *
 * @var \WPStaging\Backend\Modules\Jobs\Scan $scan
 * @var stdClass $options
 * @var \WPStaging\Framework\Filesystem\Filters\ExcludeFilter $excludeUtils
 *
 * @see \WPStaging\Backend\Modules\Jobs\Scan::start For details on $options.
 */

$isPro = defined('WPSTGPRO_VERSION');
?>
<label id="wpstg-clone-label" for="wpstg-new-clone">
    <input type="text" id="wpstg-new-clone-id" class="wpstg-textbox"
        placeholder="<?php _e('Enter Site Name (Optional)', 'wp-staging') ?>"
        data-clone="<?php echo $options->current; ?>"
        <?php if ($options->current !== null) {
            $siteName = isset($options->currentClone['cloneName']) ? $options->currentClone['cloneName'] : $options->currentClone['directoryName'];
            echo ' value="' . $siteName . '"';
            echo " disabled='disabled'";
        } ?> />
</label>

<span class="wpstg-error-msg" id="wpstg-clone-id-error" style="display:none;">
    <?php
    echo __(
        "<br>Probably not enough free disk space to create a staging site. " .
            "<br> You can continue but its likely that the copying process will fail.",
        "wp-staging"
    )
    ?>
</span>

<div class="wpstg-tabs-wrapper">
    <a href="#" class="wpstg-tab-header active" data-id="#wpstg-scanning-db">
        <span class="wpstg-tab-triangle"></span>
        <?php echo __("Database Tables", "wp-staging") ?>
    </a>

    <fieldset class="wpstg-tab-section" id="wpstg-scanning-db">
        <?php require(WPSTG_PLUGIN_DIR . 'Backend/views/selections/database-tables.php'); ?>
    </fieldset>

    <a href="#" class="wpstg-tab-header" data-id="#wpstg-scanning-files">
        <span class="wpstg-tab-triangle"></span>
        <?php echo __("Files", "wp-staging") ?>
    </a>

    <fieldset class="wpstg-tab-section" id="wpstg-scanning-files">
        <?php require(WPSTG_PLUGIN_DIR . 'Backend/views/selections/files.php'); ?>
    </fieldset>

    <a href="#" class="wpstg-tab-header" data-id="#wpstg-advanced-settings">
        <span class="wpstg-tab-triangle wpstg-no-icon"><input type="checkbox" name="wpstg-advanced" value="true"></span>
        <?php
            $pro = $isPro ? ' ' : ' (Requires Pro Version)';
            echo __("Advanced Settings " . $pro, "wp-staging"); ?>
    </a>

    <div class="wpstg-tab-section" id="wpstg-advanced-settings">
        <?php
        require_once(__DIR__ . DIRECTORY_SEPARATOR . 'external-database.php');
        require_once(__DIR__ . DIRECTORY_SEPARATOR . 'custom-directory.php');
        require_once(__DIR__ . DIRECTORY_SEPARATOR . 'mail-setting.php');
        ?>
    </div>
</div>

<?php

if ($options->current !== null && $options->mainJob === 'updating') {
    $uploadsSymlinked = isset($options->existingClones[$options->current]['uploadsSymlinked']) ? (bool)$options->existingClones[$options->current]['uploadsSymlinked'] : false;

    ?>
<fieldset class="wpstg-fieldset" style="margin-left: 16px;">
    <p class="wpstg--advance-settings--checkbox">
        <label for="wpstg-clean-plugins-themes"><?php _e('Clean Plugins/Themes'); ?></label>
        <input type="checkbox" id="wpstg-clean-plugins-themes" name="wpstg-clean-plugins-themes" value="true">
        <span class="wpstg--tooltip">
            <img class="wpstg--dashicons" src="<?php echo $scan->getInfoIcon(); ?>" alt="info" />
            <span class="wpstg--tooltiptext">
                <?php _e('Delete all plugins & themes on staging site before starting copy process.', 'wp-staging'); ?>
            </span>
        </span>
    </p>
    <p class="wpstg--advance-settings--checkbox">
        <label for="wpstg-clean-uploads"><?php _e('Clean Uploads'); ?></label>
        <input type="checkbox" id="wpstg-clean-uploads" name="wpstg-clean-uploads" value="true">
        <span class="wpstg--tooltip">
            <img class="wpstg--dashicons" src="<?php echo $scan->getInfoIcon(); ?>" alt="info" />
            <span class="wpstg--tooltiptext">
                <?php _e('Delete entire folder wp-content/uploads on staging site including all images before starting copy process.', 'wp-staging'); ?>
                <?php echo ($uploadsSymlinked ? "<br/><br/><b>" . __("Note: This option is disabled as uploads directory is symlinked", "wp-staging") . "</b>" : '') ?>
            </span>
        </span>
    </p>
</fieldset>
<hr/>
    <?php
}
?>

<button type="button" class="wpstg-prev-step-link wpstg-button--primary">
    <?php _e("Back", "wp-staging") ?>
</button>

<?php
 $label  = __("Start Cloning", "wp-staging");
 $action = 'wpstg_cloning';
 $btnId  = 'wpstg-start-cloning';
if ($options->current !== null && $options->mainJob === 'updating') {
    $label  = __("Update Clone", "wp-staging");
    $action = 'wpstg_update';
    $btnId  = 'wpstg-start-updating';
}
?>

<button type="button" id="<?php echo $btnId; ?>" class="wpstg-next-step-link wpstg-button--primary wpstg-button--blue" data-action="<?php echo $action; ?>"><?php echo $label; ?></button>

<a href="#" id="wpstg-check-space"><?php _e('Check required disk space', 'wp-staging'); ?></a>
