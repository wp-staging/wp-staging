
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
?>
<label id="wpstg-clone-label" for="wpstg-new-clone">
    <?php echo __('Staging Site Name:', 'wp-staging') ?>
    <input type="text" id="wpstg-new-clone-id" value="<?php echo $options->current; ?>"<?php if ($options->current !== null) {
        echo " disabled='disabled'";
                                                      } ?>>
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
        <span class="wpstg-tab-triangle">&#9658;</span>
        <?php echo __("Database Tables", "wp-staging") ?>
    </a>

    <fieldset class="wpstg-tab-section" id="wpstg-scanning-db">
        <?php require(WPSTG_PLUGIN_DIR . 'Backend/views/selections/database-tables.php'); ?>
    </fieldset>

    <a href="#" class="wpstg-tab-header" data-id="#wpstg-scanning-files">
        <span class="wpstg-tab-triangle">&#9658;</span>
        <?php echo __("Files", "wp-staging") ?>
    </a>

    <fieldset class="wpstg-tab-section" id="wpstg-scanning-files">
        <?php require(WPSTG_PLUGIN_DIR . 'Backend/views/selections/files.php'); ?>
    </fieldset>

    <a href="#" class="wpstg-tab-header" data-id="#wpstg-advanced-settings">
        <span class="wpstg-tab-triangle"><input type="checkbox" name="wpstg-advanced" value="true"></span>
        <?php
            $pro = defined('WPSTGPRO_VERSION') ? ' ' : ' / Pro';
            echo __("Advanced Settings " . $pro, "wp-staging"); ?>
    </a>

    <div class="wpstg-tab-section" id="wpstg-advanced-settings">
        <?php
        if (defined('WPSTGPRO_VERSION')) {
            require_once(WPSTG_PLUGIN_DIR . 'Backend/Pro/views/clone/ajax/external-database.php');
            require_once(WPSTG_PLUGIN_DIR . 'Backend/Pro/views/clone/ajax/custom-directory.php');
        } else {
            require_once(__DIR__ . DIRECTORY_SEPARATOR . 'external-database.php');
            require_once(__DIR__ . DIRECTORY_SEPARATOR . 'custom-directory.php');
            require_once(__DIR__ . DIRECTORY_SEPARATOR . 'mail-setting.php');
        }
        ?>
    </div>
</div>

<?php

if (defined('WPSTGPRO_VERSION')) {
    require_once(WPSTG_PLUGIN_DIR . 'Backend/Pro/views/clone/ajax/mail-setting.php');
}

if ($options->current !== null && $options->mainJob === 'updating') {
    $uploadsSymlinked = isset($options->existingClones[$options->current]['uploadsSymlinked']) ? (bool)$options->existingClones[$options->current]['uploadsSymlinked'] : false;

    ?>
<p><label>
    <input type="checkbox" id="wpstg-clean-plugins-themes" name="wpstg-clean-plugins-themes">
    <?php echo __("Delete all plugins & themes on staging site before starting copy process.", "wp-staging"); ?>
</label></p>
<p><label> <?php echo ($uploadsSymlinked ? "<b>" . __("Note: This option is disabled as uploads directory is symlinked", "wp-staging") . "</b><br/>" : '') ?>
    <input type="checkbox" id="wpstg-clean-uploads" name="wpstg-clean-uploads" <?php echo ($uploadsSymlinked ? 'disabled' : '') ?>>
    <?php echo __("Delete entire folder wp-content/uploads on staging site including all images before starting copy process.", "wp-staging"); ?>
</label></p>
    <?php
}
?>
<strong>Important:</strong><a href="#" id="wpstg-check-space"><?php _e('Check required disk space', 'wp-staging'); ?></a>
<p></p>

<button type="button" class="wpstg-prev-step-link wpstg-link-btn wpstg-blue-primary wpstg-button">
    <?php _e("Back", "wp-staging") ?>
</button>

<?php
if ($options->current !== null && $options->mainJob === 'updating') {
    $label  = __("Update Clone", "wp-staging");
    $action = 'wpstg_update';

    echo '<button type="button" id="wpstg-start-updating" class="wpstg-next-step-link  wpstg-link-btn wpstg-blue-primary wpstg-button" data-action="' . $action . '">' . $label . '</button>';
} else {
    $label  = __("Start Cloning", "wp-staging");
    $action = 'wpstg_cloning';

    echo '<button type="button" id="wpstg-start-cloning" class="wpstg-next-step-link wpstg-link-btn wpstg-blue-primary wpstg-button" data-action="' . $action . '">' . $label . '</button>';
}
?>
