<div id="wpstg--modal--backup--process" data-cancelButtonText="<?php esc_attr_e('Cancel', 'wp-staging') ?>" style="display: none">
    <span class="wpstg-loader"></span>
    <h3 class="wpstg--modal--process--title">
        <?php esc_html_e('Processing...', 'wp-staging') ?>
    </h3>
    <div class="wpstg--modal--process--subtitle">
        <?php
        echo sprintf(
            esc_html__('Progress %s - Elapsed time %s', 'wp-staging'),
            '<span class="wpstg--modal--process--percent">0</span>%',
            '<span class="wpstg--modal--process--elapsed-time">0:00</span>'
        )
        ?>
    </div>
    <div class="wpstg--modal--process--generic-problem"></div>
    <button
            class="wpstg--modal--process--logs--tail"
            data-txt-bad="<?php echo sprintf(
                esc_attr__('(%s) Critical, (%s) Errors, (%s) Warnings. Show Logs', 'wp-staging'),
                '<span class=\'wpstg--modal--logs--critical-count\'>0</span>',
                '<span class=\'wpstg--modal--logs--error-count\'>0</span>',
                '<span class=\'wpstg--modal--logs--warning-count\'>0</span>'
            ) ?>"
    >
        <span style="text-decoration: underline"><?php esc_html_e('Show Logs', 'wp-staging') ?></span>
    </button>
    <div class="wpstg--modal--process--logs"></div>
</div>
