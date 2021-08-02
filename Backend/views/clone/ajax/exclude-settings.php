
<?php
/**
 * @see \WPStaging\Backend\Administrator::ajaxCloneExcludesSettings Context where this is included.
 *
 * @var stdClass $options
 *
 * @see \WPStaging\Backend\Modules\Jobs\Scan::start For details on $options.
 */
?>


<div>
    <h1 class="wpstg-m-0 wpstg-mt-10px wpstg--swal2-title"><?php _e('Reset Staging Site'); ?></h1>
    <p style="text-align: justify;"><?php _e('Do you really want to reset this staging site with the current state of the production site?', 'wp-staging'); ?></p>
    <p style="color: #e01e5a; font-size: 18px;"><?php _e('This will delete your modifications!', 'wp-staging'); ?></p>
    <p style="text-align: justify;"><?php _e('The original selection for tables and files have been preselected. You can adjust and verify them before starting the reset.', 'wp-staging'); ?></p>
    <div class="wpstg-tabs-wrapper" style="text-align: left;">
        <a href="#" class="wpstg-tab-header wpstg-reset-exclude-tab" data-id="#wpstg-reset-excluded-tables" data-collapsed="true">
            <span class="wpstg-tab-triangle"></span>
            <?php _e("Selected Tables", "wp-staging") ?>
        </a>

        <fieldset class="wpstg-tab-section" id="wpstg-reset-excluded-tables">
            <?php require(WPSTG_PLUGIN_DIR . 'Backend/views/selections/database-tables.php'); ?>
        </fieldset>

        <a href="#" class="wpstg-tab-header wpstg-reset-exclude-tab" data-id="#wpstg-reset-excluded-files" data-collapsed="true">
            <span class="wpstg-tab-triangle"></span>
            <?php _e("Selected Files", "wp-staging") ?>
        </a>

        <fieldset class="wpstg-tab-section" id="wpstg-reset-excluded-files">
            <?php require(WPSTG_PLUGIN_DIR . 'Backend/views/selections/files.php'); ?>
        </fieldset>
    </div>    
</div>

