<?php

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Filesystem\DebugLogReader;

$isPro = WPStaging::isPro();
?>

<form action="<?php echo esc_url(admin_url("admin-post.php?action=wpstg_download_sysinfo")) ?>" method="post" dir="ltr">
    <!-- Keep the class wpstg--tab--active or the report issue form js can not grab the form values because the same form is embedded multiple times into the UI. See sendIssueReport() in wpstg-admin.js -->
    <div id="wpstg--systeminfo-header">
        <input type="submit" name="wpstg-download-sysinfo" id="wpstg-download-sysinfo" class="wpstg-button wpstg-blue-primary" value="<?php esc_html_e('Download All Log Files', 'wp-staging'); ?>">
    </div>

    <div>
        <textarea class="wpstg-sysinfo" readonly="readonly" id="system-info-textarea" name="wpstg-sysinfo" title="<?php esc_html_e('To copy the system info, click below then press Ctrl + C (PC) or Cmd + C (Mac).', 'wp-staging'); ?>"><?php echo esc_textarea(\WPStaging\Core\WPStaging::getInstance()->get("systemInfo")); ?></textarea>
    </div>

    <h3>WP STAGING Logs <a href="<?php echo esc_url(admin_url() . 'admin.php?page=wpstg-tools&tab=system-info&deleteLog=wpstaging&deleteLogNonce=' . wp_create_nonce('wpstgDeleteLogNonce')); ?>">(<?php esc_html_e('Delete', 'wp-staging'); ?>)</a></h3>
    <p><a href="javascript:void(0)" id="wpstg-purge-backup-queue-btn"> <?php esc_html_e('Purge Backup Queue', 'wp-staging') ?></a></p>
    <textarea class="wpstg-sysinfo" readonly="readonly" id="wpstg-debug-logs-textarea" name="wpstg-debug-logs"><?php echo esc_textarea(WPStaging::make(DebugLogReader::class)->getLastLogEntries(256 * KB_IN_BYTES, true, false)); ?></textarea>
    <h3>PHP debug.log <a href="<?php echo esc_url(admin_url() . 'admin.php?page=wpstg-tools&tab=system-info&deleteLog=php&deleteLogNonce=' . wp_create_nonce('wpstgDeleteLogNonce')); ?>">(<?php esc_html_e('Delete', 'wp-staging'); ?>)</a></h3>
    <textarea class="wpstg-sysinfo" readonly="readonly" id="wpstg-php-debug-logs-textarea" name="wpstg-php-debug-logs"><?php echo esc_textarea(WPStaging::make(DebugLogReader::class)->getLastLogEntries(128 * KB_IN_BYTES, false, true)); ?></textarea>
</form>
