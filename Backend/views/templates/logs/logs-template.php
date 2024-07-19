<div class="wpstg-logs-header-container">
    <div>
        <label for="wpstg-logs-selector"></label>
        <select id="wpstg-logs-selector">
            <option value="all"><?php esc_html_e('All', 'wp-staging') ?></option>
            <option value="info"><?php esc_html_e('Info', 'wp-staging') ?></option>
            <option value="debug"><?php esc_html_e('Debug', 'wp-staging') ?></option>
            <option value="warning"><?php esc_html_e('Warning', 'wp-staging') ?></option>
            <option value="error"><?php esc_html_e('Error', 'wp-staging') ?></option>
            <option value="critical"><?php esc_html_e('Critical', 'wp-staging') ?></option>
        </select>
    </div>
    <div class="wpstg-logs-counter">
        <button class="wpstg-critical-log-button hidden">
            <?php esc_html_e('Critical:', 'wp-staging') ?>
            <span id="wpstg-critical-counter">0</span>
        </button>
        <button class="wpstg-error-log-button hidden">
            <?php esc_html_e('Error:', 'wp-staging') ?>
            <span id="wpstg-error-counter">0</span>
        </button>
        <button class="wpstg-warning-log-button hidden">
            <?php esc_html_e('Warning:', 'wp-staging') ?>
            <span id="wpstg-warning-counter">0</span>
        </button>
        <button class="wpstg-debug-log-button hidden">
            <?php esc_html_e('Debug:', 'wp-staging') ?>
            <span id="wpstg-debug-counter">0</span>
        </button>
        <button class="wpstg-info-log-button hidden">
            <?php esc_html_e('Info:', 'wp-staging') ?>
            <span id="wpstg-info-counter">0</span>
        </button>
    </div>
</div>
<div id="wpstg-process-logs-container">
    <table id="wpstg-process-logs">
        <thead>
            <tr>
                <th></th>
                <th><?php esc_html_e('Level', 'wp-staging') ?></th>
                <th><?php esc_html_e('Time', 'wp-staging') ?></th>
                <th><?php esc_html_e('Description', 'wp-staging') ?></th>
            </tr>
        </thead>
        <tbody id="wpstg-process-logs-body">
            <tr id="wpstg-process-logs-template-row" class="hidden">
                <td></td>
                <td></td>
                <td class="wpstg-process-log-date"></td>
                <td></td>
            </tr>
        </tbody>
    </table>
</div>
