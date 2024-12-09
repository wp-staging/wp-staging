<div id="wpstg--process-modal" data-cancelButtonText="<?php esc_attr_e('Cancel', 'wp-staging') ?>" class="wpstg--process-modal">
    <span class="wpstg-loader"></span>
    <h3 class="wpstg--process-modal--title">
        <?php esc_html_e('Processing...', 'wp-staging') ?>
    </h3>
    <div class="wpstg--process-modal--subtitle">
        <?php esc_html_e('Progress', 'wp-staging') ?>
        <span class="wpstg--process-modal--percent">0</span>% - <?php esc_html_e('Elapsed time', 'wp-staging');?>
        <span class="wpstg--process-modal--elapsed-time">0:00</span>
    </div>
    <button class="wpstg--process-modal--logs-button" data-txt-bad="<?php echo sprintf(
        esc_attr__('(%s) Critical, (%s) Errors, (%s) Warnings. Show Logs', 'wp-staging'),
        '<span class=\'wpstg--modal--logs--critical-count\'>0</span>',
        '<span class=\'wpstg--modal--logs--error-count\'>0</span>',
        '<span class=\'wpstg--modal--logs--warning-count\'>0</span>'
    ) ?>">
        <span style="text-decoration: underline"><?php esc_html_e('Show Logs', 'wp-staging') ?></span>
    </button>
    <div class="wpstg--process-modal--generic-problem"></div>
    <div class="wpstg--process-modal--logs wpstg--logs--container">
        <?php require(WPSTG_VIEWS_DIR . 'logs/logs-template.php'); ?>
    </div>
</div>
