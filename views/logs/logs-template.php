<?php

/**
 * @see src/views/job/modal/success.php
 * @see src/views/job/modal/process.php
 */

$selectorUniqueId = $logType ?? uniqid();

?>
<div class="wpstg--logs--header">
    <div>
        <label for="wpstg--logs--selector--<?php echo esc_attr($selectorUniqueId); ?>"></label>
        <select class="wpstg--logs--selector" id="wpstg--logs--selector--<?php echo esc_attr($selectorUniqueId); ?>">
            <option value="all"><?php esc_html_e('All', 'wp-staging') ?></option>
            <option value="info"><?php esc_html_e('Info', 'wp-staging') ?></option>
            <option value="debug"><?php esc_html_e('Debug', 'wp-staging') ?></option>
            <option value="warning"><?php esc_html_e('Warning', 'wp-staging') ?></option>
            <option value="error"><?php esc_html_e('Error', 'wp-staging') ?></option>
            <option value="critical"><?php esc_html_e('Critical', 'wp-staging') ?></option>
        </select>
    </div>
    <div class="wpstg--logs--counter">
        <button class="wpstg--logs--button-critical hidden">
            <?php esc_html_e('Critical:', 'wp-staging') ?>
            <span class="wpstg--logs--counter-critical">0</span>
        </button>
        <button class="wpstg--logs--button-error hidden">
            <?php esc_html_e('Error:', 'wp-staging') ?>
            <span class="wpstg--logs--counter-error">0</span>
        </button>
        <button class="wpstg--logs--button-warning hidden">
            <?php esc_html_e('Warning:', 'wp-staging') ?>
            <span class="wpstg--logs--counter-warning">0</span>
        </button>
        <button class="wpstg--logs--button-debug hidden">
            <?php esc_html_e('Debug:', 'wp-staging') ?>
            <span class="wpstg--logs--counter-debug">0</span>
        </button>
        <button class="wpstg--logs--button-info hidden">
            <?php esc_html_e('Info:', 'wp-staging') ?>
            <span class="wpstg--logs--counter-info">0</span>
        </button>
    </div>
</div>
<div class="wpstg--logs--body">
    <table class="wpstg--logs-table">
        <thead>
            <tr>
                <th></th>
                <th><?php esc_html_e('Level', 'wp-staging') ?></th>
                <th><?php esc_html_e('Time', 'wp-staging') ?></th>
                <th><?php esc_html_e('Description', 'wp-staging') ?></th>
            </tr>
        </thead>
        <tbody class="wpstg--logs-table--body">
            <tr class="wpstg--logs-table--template-row hidden">
                <td></td>
                <td></td>
                <td class="wpstg--logs-table--date"></td>
                <td></td>
            </tr>
        </tbody>
    </table>
</div>
