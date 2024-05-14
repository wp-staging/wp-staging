<?php $modalType = $modalType ?? 'backup';?>

<div id="wpstg-<?php echo esc_attr($modalType); ?>-process-modal" data-cancelButtonText="<?php esc_attr_e('Cancel', 'wp-staging') ?>" class="wpstg-<?php echo esc_attr($modalType); ?>-process-modal">
    <span class="wpstg-loader"></span>
    <h3 class="wpstg-<?php echo esc_attr($modalType); ?>-process-title">
        <?php esc_html_e('Processing...', 'wp-staging') ?>
    </h3>
    <div class="wpstg-<?php echo esc_attr($modalType); ?>-process-subtitle">
        <?php esc_html_e('Progress', 'wp-staging') ?>
        <span class="wpstg-<?php echo esc_attr($modalType); ?>-process-percent">0</span>% - Elapsed time
        <span class="wpstg-<?php echo esc_attr($modalType); ?>-process-elapsed-time">0:00</span>
    </div>
    <button class="wpstg-<?php echo esc_attr($modalType); ?>-process-logs-button" data-txt-bad="<?php echo sprintf(
        esc_attr__('(%s) Critical, (%s) Errors, (%s) Warnings. Show Logs', 'wp-staging'),
        '<span class=\'wpstg--modal--logs--critical-count\'>0</span>',
        '<span class=\'wpstg--modal--logs--error-count\'>0</span>',
        '<span class=\'wpstg--modal--logs--warning-count\'>0</span>'
    ) ?>">
        <span style="text-decoration: underline"><?php esc_html_e('Show Logs', 'wp-staging') ?></span>
    </button>
    <div class="wpstg--modal--process--generic-problem"></div>
    <div class="wpstg-<?php echo esc_attr($modalType); ?>-process-logs"></div>
</div>