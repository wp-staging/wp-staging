
<?php
/**
 * @see \WPStaging\Backend\Administrator::ajaxCloneExcludeSettings Context where this is included.
 *
 * @var stdClass $options
 *
 * @see \WPStaging\Backend\Modules\Jobs\Scan::start For details on $options.
 */
?>


<div>
    <h1 class="wpstg-m-0 wpstg-mt-10px"><?php _e('RESET CLONE'); ?></h1>
    <p><?php _e('Do you really want to reset this staging site with the current state of the production site? The original selection for tables and files have been preselected. You can adjust and verify them before starting the reset.', 'wp-staging'); ?></p>
    <p class="wpstg--modal--process--msg--critical"><?php _e('This will delete all your modifications!', 'wp-staging'); ?></p>
    <div class="wpstg-tabs-wrapper" style="text-align: left;">
        <a href="#" class="wpstg-tab-header wpstg-reset-exclude-tab" data-id="#wpstg-reset-excluded-tables" data-collapsed="true">
            <span class="wpstg-tab-triangle">&#9658;</span>
            <?php _e("Selected Tables", "wp-staging") ?>
        </a>

        <fieldset class="wpstg-tab-section" id="wpstg-reset-excluded-tables">
            <?php require(WPSTG_PLUGIN_DIR . 'Backend/views/selections/database-tables.php'); ?>
        </fieldset>

        <a href="#" class="wpstg-tab-header wpstg-reset-exclude-tab" data-id="#wpstg-reset-excluded-files" data-collapsed="true">
            <span class="wpstg-tab-triangle">&#9658;</span>
            <?php _e("Selected Files", "wp-staging") ?>
        </a>

        <fieldset class="wpstg-tab-section" id="wpstg-reset-excluded-files">
            <?php require(WPSTG_PLUGIN_DIR . 'Backend/views/selections/files.php'); ?>
        </fieldset>
    </div>    
</div>

