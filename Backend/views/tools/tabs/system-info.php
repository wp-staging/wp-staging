<?php

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Filesystem\DebugLogReader;

?>

<form action="<?php echo esc_url(admin_url("admin-post.php?action=wpstg_download_sysinfo")) ?>" method="post" dir="ltr">
    <!-- Keep the class wpstg--tab--active or the report issue form js can not grab the form values because the same form is embedded multiple times into the UI. See sendIssueReport() in wpstg-admin.js -->
    <div id="wpstg--systeminfo-header" style="">
        <input type="submit" name="wpstg-download-sysinfo" id="wpstg-download-sysinfo" class="wpstg-button wpstg-blue-primary" value="Download System Info">
        <button type="button" id="wpstg-report-issue-button" class="wpstg-button">
            <i class="wpstg-icon-issue"></i><?php echo esc_html__("Contact Us", "wp-staging"); ?>
        </button>
        <div class="wpstg--tab--active" id="wpstg-report-issue-wrapper" style="padding-bottom:7px;">
            <?php require_once(WPSTG_PLUGIN_DIR . 'Backend/views/_main/report-issue.php'); ?>
        </div>
    </div>
    <div>
        <textarea class="wpstg-sysinfo" readonly="readonly" id="system-info-textarea" name="wpstg-sysinfo" title="To copy the system info, click below then press Ctrl + C (PC) or Cmd + C (Mac)."><?php echo esc_textarea(\WPStaging\Core\WPStaging::getInstance()->get("systemInfo")) ?></textarea>
    </div>
    <h3>WP STAGING Logs <a href="<?php echo esc_url(admin_url() . 'admin.php?page=wpstg-tools&tab=system-info&deleteLog=wpstaging&deleteLogNonce=' . wp_create_nonce('wpstgDeleteLogNonce')); ?>">(<?php esc_html_e('Delete', 'wp-staging'); ?>)</a></h3>
    <p><a href="#" id="btn-purge-queue-table" style="color: #E01E5A;"> <?php esc_html_e('Purge Backup Queue', 'wp-staging') ?></a></p>
    <textarea class="wpstg-sysinfo" readonly="readonly" id="debug-logs-textarea" name="wpstg-debug-logs"><?php echo esc_textarea(WPStaging::make(DebugLogReader::class)->getLastLogEntries(8 * KB_IN_BYTES, true, false)); ?></textarea>
    <h3>PHP debug.log <a href="<?php echo esc_url(admin_url() . 'admin.php?page=wpstg-tools&tab=system-info&deleteLog=php&deleteLogNonce=' . wp_create_nonce('wpstgDeleteLogNonce')); ?>">(<?php esc_html_e('Delete', 'wp-staging'); ?>)</a></h3>
    <textarea class="wpstg-sysinfo" readonly="readonly" id="debug-logs-textarea" name="wpstg-debug-logs"><?php echo esc_textarea(WPStaging::make(DebugLogReader::class)->getLastLogEntries(8 * KB_IN_BYTES, false, true)); ?></textarea>
</form>


<script>
    jQuery(document).ready(function ($) {

        if (typeof Notyf !== 'undefined') {
            var notyf = new Notyf({
                duration: 4000,
                position: {
                    x: 'center',
                    y: 'bottom',
                },
                dismissible: true,
                types: [
                    {
                        type: 'warning',
                        background: 'orange',
                        icon: false,
                    },
                ],
            });
        } else {
            var notyf = false;
            console.log('Notyf is not defined');
        }

        function wpstgShowAlert(message, type = 'warning') {
            if (notyf === false) {
                alert(message);
                return;
            }
            if (type === 'warning') {
                notyf.error(message);
            }
            if (type === 'error') {
                notyf.error(message);
            }
            if (type === 'success') {
                notyf.success(message);
            }
        }

        jQuery(document).on('click', '#btn-purge-queue-table', function (e) {
            e.preventDefault();
            let cancelProcess = confirm("Are you sure?\nThis will purge the database table _wpstg_queue. Use this only for debugging purposes, for example if the scheduled backups do not work. Click OK to purge the scheduled backup queue data.");
            if (cancelProcess === false) {
                return false;
            }
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wpstg_purge_queue_table',
                    nonce: wpstg.nonce
                },
                error: function error(xhr, textStatus, errorThrown) {
                    console.log(xhr.status + ' ' + xhr.statusText + '---' + textStatus);
                    console.log(textStatus);
                    let message = 'Can not delete the queue data. Please contact us at support@wp-staging.com. HTTP status code: ' + xhr.status + ' Error: ' + xhr.statusText + ' ' + textStatus;
                    wpstgShowAlert(message, 'error');
                },
                success: function success(data) {
                    wpstgShowAlert(data.message, 'success');
                    return true;
                },
                statusCode: {
                    404: function _() {
                        let message = 'Something went wrong; can\'t find ajax request URL! Please contact us at support@wp-staging.com';
                        wpstgShowAlert(message, 'error');
                    },
                    500: function _() {
                        let message = 'Something went wrong; internal server error while processing the request! Please contact us at support@wp-staging.com';
                        wpstgShowAlert(message, 'error');
                    }
                }
            });
        });
    })
    ;
</script>

