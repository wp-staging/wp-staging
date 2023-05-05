<?php
/**
 * @see \WPStaging\Backend\Administrator::ajaxUpdateProcess A place where this view is being called.
 * @see \WPStaging\Backend\Administrator::ajaxResetProcess A place where this view is being called.
 * @var \WPStaging\Backend\Modules\Jobs\Cloning $cloning
 */
?>
<div class=successfullying-section">
    <h2 id="wpstg-processing-header"><?php echo esc_html__("Processing, please wait...", "wp-staging")?></h2>
    <div class="wpstg-progress-bar">
        <div class="wpstg-progress" id="wpstg-progress-db"></div>
        <div class="wpstg-progress" id="wpstg-progress-sr"></div>
        <div class="wpstg-progress" id="wpstg-progress-dirs"></div>
        <div class="wpstg-progress" id="wpstg-progress-files"></div>
    </div>
    <div class="wpstg-clear-both">
        <div id="wpstg-processing-status"></div>
        <div id="wpstg-processing-timer"></div>
    </div>
    <div class="wpstg-clear-both"></div>
</div>

<button type="button" class="wpstg-prev-step-link wpstg-button--primary wpstg-mt-10px" style="display: none;">
    <?php esc_html_e("Back", "wp-staging") ?>
</button>

<button type="button" id="wpstg-update-cloning-site-button" class="wpstg-blue-primary wpstg-button wpstg-ml-12" style="display: none;">
    <?php esc_html_e("Open Staging Site", "wp-staging") ?>
</button>

<button type="button" id="wpstg-cancel-cloning-update" data-job="<?php echo esc_attr($cloning->getOptions()->mainJob); ?>" class="wpstg-link-btn wpstg-button--primary wpstg-button--red">
    <?php
    if ($cloning->getOptions()->mainJob === 'resetting') {
        esc_html_e("Cancel Reset", "wp-staging");
    } else {
        esc_html_e("Cancel Update", "wp-staging");
    }
    ?>
</button>

<button type="button" id="wpstg-show-log-button" class="button" data-clone="<?php echo esc_attr($cloning->getOptions()->clone) ?>" style="margin-top: 5px;display:none;">
    <?php esc_html_e('Display working log', 'wp-staging')?>
</button>

<div>
    <span id="wpstg-cloning-result"></span>
</div>


<div id="wpstg-error-wrapper">
    <div id="wpstg-error-details"></div>
</div>

<div class="wpstg-log-details"></div>