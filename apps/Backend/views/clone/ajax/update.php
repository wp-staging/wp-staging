<div class=successfullying-section">
    <?php echo __("Copy Database Tables", "wpstg")?>
    <div class="wpstg-progress-bar">
        <div class="wpstg-progress" id="wpstg-db-progress" style="width:0"></div>
    </div>
</div>

<div class="wpstg-cloning-section">
    <?php echo __("Prepare Directories", "wpstg")?>
    <div class="wpstg-progress-bar">
        <div class="wpstg-progress" id="wpstg-directories-progress" style="width:0"></div>
    </div>
</div>

<div class="wpstg-cloning-section">
    <?php echo __("Copy Files", "wpstg")?>
    <div class="wpstg-progress-bar">
        <div class="wpstg-progress" id="wpstg-files-progress" style="width:0"></div>
    </div>
</div>

<div class="wpstg-cloning-section">
    <?php echo __("Replace Data", "wpstg")?>
    <div class="wpstg-progress-bar">
        <div class="wpstg-progress" id="wpstg-links-progress" style="width:0"></div>
    </div>
</div>

<button type="button" id="wpstg-cancel-cloning-update" class="wpstg-link-btn button-primary">
    <?php echo __("Cancel Update", "wpstg")?>
</button>

<button type="button" id="wpstg-show-log-button" class="button" data-clone="<?php echo $cloning->getOptions()->clone?>" style="margin-top: 5px;display:none;">
    <?php _e('Display working log', 'wpstg')?>
</button>

<div>
    <span id="wpstg-cloning-result"></span>
</div>


<div id="wpstg-error-wrapper">
    <div id="wpstg-error-details"></div>
</div>

<div id="wpstg-log-details"></div>