<div class=successfullying-section">
    <h2 id="wpstg-processing-header"><?php echo __("Processing, please wait...", "wp-staging")?></h2>
    <div class="wpstg-progress-bar">
        <div class="wpstg-progress" id="wpstg-progress-db" style="width:0;overflow: hidden;"></div>
        <div class="wpstg-progress" id="wpstg-progress-sr" style="width:0;background-color:#3c9ee4;overflow: hidden;"></div>
        <div class="wpstg-progress" id="wpstg-progress-dirs" style="width:0;background-color:#3a96d7;overflow: hidden;"></div>
        <div class="wpstg-progress" id="wpstg-progress-files" style="width:0;background-color:#378cc9;overflow: hidden;"></div>
    </div>
    <div style="clear:both;">
        <div id="wpstg-processing-status"></div>
        <div id="wpstg-processing-timer"></div>
    </div>
    <div style="clear: both;"></div>
</div>

<button type="button" id="wpstg-cancel-cloning-update" class="wpstg-link-btn button-primary">
    <?php echo __("Cancel Update", "wp-staging")?>
</button>

<button type="button" id="wpstg-show-log-button" class="button" data-clone="<?php echo $cloning->getOptions()->clone?>" style="margin-top: 5px;display:none;">
    <?php _e('Display working log', 'wp-staging')?>
</button>

<div>
    <span id="wpstg-cloning-result"></span>
</div>


<div id="wpstg-error-wrapper">
    <div id="wpstg-error-details"></div>
</div>

<div class="wpstg-log-details"></div>