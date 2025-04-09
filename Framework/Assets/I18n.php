<?php

namespace WPStaging\Framework\Assets;

use WPStaging\Core\WPStaging;

/**
 * Provide translated strings which can be used in JS part of the plugin
 */
class I18n
{
    public function getTranslations(): array
    {
        $backupCompleteMessage = __('You can restore this backup anytime or upload it to another website and restore it there.', 'wp-staging');
        if (WPStaging::isPro() === false) {
            $backupCompleteMessage = __('You can restore this backup anytime on this website.', 'wp-staging');
        }

        return [
            'show_logs'             => esc_html__('Show Logs', 'wp-staging'),
            'hide_logs'             => esc_html__('Hide Logs', 'wp-staging'),
            'tables_not_selected'   => esc_html__('No table selected', 'wp-staging'),
            'tables_selected'       => esc_html__('{d} of {t} tables(s) selected', 'wp-staging'),
            'files_selected'        => esc_html__('{t} theme{ts}, {p} plugin{ps}, {o} other folder{os} selected', 'wp-staging'),
            'read_less'             => esc_html__('Read Less', 'wp-staging'),
            'read_more'             => esc_html__('Read More', 'wp-staging'),
            'ok'                    => esc_html__('OK', 'wp-staging'),
            'cancel'                => esc_html__('Cancel', 'wp-staging'),
            'next'                  => esc_html__('Next', 'wp-staging'),
            'yes'                   => esc_html__('Yes', 'wp-staging'),
            'no'                    => esc_html__('No', 'wp-staging'),
            'success'               => esc_html__('Success', 'wp-staging'),
            'failed'                => esc_html__('Failed', 'wp-staging'),
            'error'                 => esc_html__('Error', 'wp-staging'),
            'save'                  => esc_html__('Save', 'wp-staging'),
            'close'                 => esc_html__('Close', 'wp-staging'),
            'delete'                => esc_html__('Delete', 'wp-staging'),
            'restore'               => esc_html__('Restore', 'wp-staging'),
            'upload'                => esc_html__('Upload', 'wp-staging'),
            'unselect_all'          => esc_html__('Unselect All', 'wp-staging'),
            'select_all'            => esc_html__('Select All', 'wp-staging'),
            'something_went_wrong'  => esc_html__('Something went wrong', 'wp-staging'),
            'submit_error_report'   => esc_html__('Please submit an error report by using the CONTACT US button.', 'wp-staging'),
            'cancel_modal_title'    => esc_html__('Cancelling & Cleaning up', 'wp-staging'),
            'cancel_modal_text'     => esc_html__('This modal will close automatically when done...', 'wp-staging'),
            'cancel_modal_error'    => esc_html__('Cancel process did not finish gracefully. Some temporary files might not have been cleaned up.', 'wp-staging'),
            'cancel_modal_confirm_text' => esc_html__('Do you want to cancel the process?', 'wp-staging'),
            'progress'              => esc_html__('Progress', 'wp-staging'),
            'elapsed_time'          => esc_html__('Elapsed time', 'wp-staging'),
            'failed_response'       => esc_html__('Failed response', 'wp-staging'),
            'unknown_error'         => esc_html__('Unknown error', 'wp-staging'),
            'something_went_wrong_use_low_setting_error_text' => sprintf(esc_html__('Something went wrong! No response. Go to WP Staging > Settings and lower \'File Copy Limit\' and \'DB Query Limit\'. Also set \'CPU Load Priority to low \' and try again. If that does not help, %s', 'wp-staging'), '<a href=\'https://wp-staging.com/support/\' target=\'_blank\'>open a support ticket</a>'),
            'something_went_wrong_open_ticket_error_text' => sprintf(esc_html__('Something went wrong! No response. Please try again. If that does not help, %s', 'wp-staging'), '<a href=\'https://wp-staging.com/support/\' target=\'_blank\'>open a support ticket</a>'),
            'contact_us_to_solve' => sprintf(esc_html__('Please get in contact with us to solve it %s', 'wp-staging'), 'support@wp-staging.com'),
            'clone_data_save_success' => esc_html__('Clone data saved successfully.', 'wp-staging'),
            'clone_data_save_error' => sprintf(esc_html__('Could not save clone data %s Error: ', 'wp-staging'), '<br/>'),
            'ajax_status_code_404' => sprintf(esc_html__('Something went wrong; can\'t find ajax request URL! Please try the %s. If that does not help, %s', 'wp-staging'), '<a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a>', '<a href=\'https://wp-staging.com/support/\' target=\'_blank\'>open a support ticket</a>'),
            'status_code_500' => sprintf(esc_html__('Something went wrong; internal server error while processing the request! Please try the %s. If that does not help, %s', 'wp-staging'), '<a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a>', '<a href=\'https://wp-staging.com/support/\' target=\'_blank\'>open a support ticket</a>'),
            'small_server_settings_text' => sprintf(esc_html__('Please try the %s or submit an error report and contact us.', 'wp-staging'), '<a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a>'),
            'report_this_warning' => sprintf(esc_html__('Please report this warning %s and keep using WP Staging!', 'wp-staging'), '<a href="https://wp-staging.com/support/" target="_blank">to our support</a>'),
            'please_wait' => esc_html__('Please wait...this can take up a while.', 'wp-staging'),
            'contact_us' => esc_html__('CONTACT US', 'wp-staging'),
            'database_connection'   => [
                'success' => esc_html__('Database connection successful', 'wp-staging'),
                'failed' => esc_html__('Database connection failed', 'wp-staging'),
                'nothing_to_test' => esc_html__('Nothing to test!', 'wp-staging'),
            ],
            'staging_site' => [
                'delete' => [
                    'confirmation' => esc_html__('Delete Staging Site "%s"', 'wp-staging'),
                    'title' => esc_html__('Staging Site Deleted Successfully!', 'wp-staging'),
                ],
            ],
            'cloning'         => [
                'title' => esc_html__('Staging Site Created Successfully!', 'wp-staging'),
                'body'  => esc_html__('You can access it from here:', 'wp-staging'),
            ],
            'update'          => [
                'title' => esc_html__('Staging Site Updated Successfully!', 'wp-staging'),
                'body'  => esc_html__('You can access it from here:', 'wp-staging'),
            ],
            'push_processing' => [
                'title' => esc_html__('Staging Site Pushed Successfully!', 'wp-staging'),
                'body'  => esc_html__('Now delete the theme and the website cache if the website does not look as expected! ', 'wp-staging'),
            ],
            'reset'           => [
                'title' => esc_html__('Staging Site Reset Successfully!', 'wp-staging'),
                'body'  => esc_html__('You can access it from here:', 'wp-staging'),
                'confirm_button' => esc_html__('Reset Staging Site', 'wp-staging'),
            ],
            'delete_clone'    => [
                'title' => esc_html__('Staging Site Deleted Successfully!', 'wp-staging'),
            ],
            'backup_success' => [
                'scheduled' => [
                    'title' => esc_html__('Backup Schedule Created', 'wp-staging'),
                    'body'  => esc_html__('Backup is scheduled according to the provided settings.', 'wp-staging'),
                ],
                'run_in_bg' => [
                    'title' => esc_html__('Backup Creation Triggered', 'wp-staging'),
                    'body'  => esc_html__('Backup will run in background. You can close the window.', 'wp-staging'),
                ],
                'created' => [
                    'title' => esc_html__('Backup Complete', 'wp-staging'),
                    'body'  => esc_html($backupCompleteMessage),
                ]
            ],
            'admin_warn_if_closing_during_process' => esc_html__('You MUST leave this window open while cloning/pushing. Please wait...', 'wp-staging'),
            'admin_handle_fetch_errors' => esc_html__('Please try again or contact support.', 'wp-staging'),
            'admin_elements' => [
                'check_clone_error' => esc_html__('Error: Please choose correct name for the staging site.', 'wp-staging'),
                'clone_hostname_error' => esc_html__('Invalid host name. Please provide it in a format like http://example.com', 'wp-staging'),
            ],
            'admin_clone_actions_update_modal' => [
                'title' => esc_html__('Do you want to update the staging site?', 'wp-staging'),
                'body' => sprintf(esc_html__('This function overwrites the staging site at "%s" with data from the production site, making it identical to the live site and removing any changes made on the staging site. Use this only if you want to re-clone your live site. You can select specific tables and files in the next step.', 'wp-staging'), '<b>{URL}</b>'),
                'suggestion' => sprintf(esc_html__('%sPlease back up your staging site before proceeding!%s', 'wp-staging'), '<b class="wpstg-flex-text-center">', '</b>'),
                'confirm_button_text' => esc_html__('Update', 'wp-staging'),
            ],
            'admin_clone_actions' => [
                'cancel_cloning_confirm' => esc_html__('Are you sure you want to cancel cloning process?', 'wp-staging'),
                'resume_cloning_status' => esc_html__('Try to resume cloning process...', 'wp-staging'),
                'confirm_delete_clone_title' => esc_html__('Delete staging site', 'wp-staging'),
                'scanning_show_error' => sprintf(esc_html__('Something went wrong! Error: No response. Please try the %s or submit an error report and contact us.', 'wp-staging'), '<a href="https://wp-staging.com/docs/wp-staging-settings-for-small-servers/" target="_blank">Small Server Settings</a>'),
            ],
            'admin_ajax' => [
                'error_msg_footer' => sprintf(esc_html__('Please use the %s and try again.', 'wp-staging'), '<a href="https://wp-staging.com/docs/wp-staging-settings-for-small-servers/" target="_blank">Small Server Settings</a>'),
                'pushing_error_msg' => sprintf(esc_html__('If this issue persists, you can use the %s feature to move your staging site to live. %s', 'wp-staging'), '<strong>Backup & Migration</strong>', '<a href="https://wp-staging.com/docs/how-to-migrate-your-wordpress-site-to-a-new-host/" target="_blank">' . esc_html__("Read more", "wp-staging") . '.</a>'),
                'contact_us_text' => esc_html__('to analyze this issue further.', 'wp-staging'),
                'help_content_pro' => esc_html__('Please contact WP Staging support if you need further assistance.', 'wp-staging'),
            ],
            'admin_step_buttons' => [
                'popup_title' => esc_html__('Do You Want to Proceed?', 'wp-staging'),
                'popup_html' => sprintf(esc_html__('This will overwrite the staging site "%s" and will lead to loose of your staging sites modifications.%sThis is a final warning. Do not stop the update process once it starts, as that may break your staging site.%sClick on %scancel%s if you don\'t want to update the staging site.', 'wp-staging'), '<b>{URL}</b>', '<br><br><b>', '</b><br><br>', '<b>', '</b>'),
                'confirm_button_text' => esc_html__('Update', 'wp-staging'),
            ],
            'admin_verify_external_database' => [
                'error_no_response' => esc_html__('Something went wrong! Error: No response. Please try again. If that does not help,', 'wp-staging'),
                'error_invalid_response' => esc_html__('Something went wrong! Error: Invalid response. Please try again. If that does not help,', 'wp-staging'),
                'comparison_modal_html_note' => esc_html__('Note: Some MySQL/MariaDB properties do not match. You may proceed but the staging site may not work as expected.', 'wp-staging'),
                'comparison_modal_title' => esc_html__('Different Database Properties', 'wp-staging'),
                'comparison_modal_confirm_button_text' => esc_html__('Proceed', 'wp-staging'),
                'error_modal_title' => esc_html__('Different Database Properties', 'wp-staging'),
                'error_modal_confirm_button_text' => esc_html__('Proceed', 'wp-staging'),
                'insufficient_db_privilege_title' => esc_html__('Insufficient Database Privileges', 'wp-staging'),
                'show_full_message' => esc_html__('Show Full Message', 'wp-staging'),
                'hide_full_message' => esc_html__('Hide Full Message', 'wp-staging'),
            ],
            'admin_send_cloning_ajax' => [
                'error_general' => sprintf(esc_html__('Something went wrong!%sGo to WP Staging > Settings and lower \'File Copy Limit\' and \'DB Query Limit\'. Also set \'CPU Load Priority to low \' and try again. If that does not help, %s', 'wp-staging'), '<br/><br/>', '<a href=\'https://wp-staging.com/support/\' target=\'_blank\'>open a support ticket</a>'),
                'error_no_response' => sprintf(esc_html__('Something went wrong! No response.%sGo to WP Staging > Settings and lower \'File Copy Limit\' and \'DB Query Limit\'. Also set \'CPU Load Priority to low \' and try again. If that does not help, %s', 'wp-staging'), '<br/><br/>', '<a href=\'https://wp-staging.com/support/\' target=\'_blank\'>open a support ticket</a>'),
            ],
            'admin_load_overview' => [
                'error_no_response' => sprintf(esc_html__('Something went wrong! No response. Please try the %s or submit an error report.', 'wp-staging'), '<a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>Small Server Settings</a>'),
            ],
            'admin_check_disk_space' => [
                'error_disk_space' => esc_html__('Can not detect required disk space', 'wp-staging'),
                'error_disk_space_html_1' => esc_html__('Estimated necessary disk space: ', 'wp-staging'),
                'error_disk_space_html_2' => esc_html__('Before proceeding, make sure that your server has enough free disk space to clone the website. You can check the available disk space in your hosting customer account (e.g. cPanel).', 'wp-staging'),
            ],
            'admin_that_timer' => [
                'elapsed_time' => esc_html__('Elapsed Time: ', 'wp-staging'),
            ],
            'admin_is_clone_destination_path_same_as_root_error' => esc_html__('The target path must be different from the root path of the production website.', 'wp-staging'),
            'admin_check_user_db_permissions_confirm_button_text' => esc_html__('Proceed', 'wp-staging'),
            'admin_send_issue_report_success_message' => sprintf(esc_html__('Thanks for submitting your request! You should receive an auto reply mail with your ticket ID immediately for confirmation!%sIf you do not get that mail please contact us directly at %s', 'wp-staging'), '<br/><br/>', '<strong>support@wp-staging.com</strong>'),
            'common' => [
                'firewall_error_text' => sprintf(esc_html__('WP Staging is blocked by a security plugin or firewall setting. Please whitelist all ajax requests coming from %s. If possible put your firewall into learning mode and try again.%sPlease ask your hosting provider or security plugin to permanently whitelist WP Staging requests. If it still fails, please contact us.', 'wp-staging'), '<code>action=wpstg_</code>', '<br><br>'),
                'fetch_errors_error_text' => esc_html__('Please try again or contact support.', 'wp-staging'),
                'ajax_error_fatal_error' => esc_html__('Fatal Error: ', 'wp-staging'),
                'ajax_error_error_text' => sprintf(esc_html__(' Please try the %s or submit an error report and contact us.', 'wp-staging'), '<a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a>'),
                'ajax_status_code_504' => sprintf(esc_html__('Error 504 - It looks like your server is rate limiting ajax requests. Please try to resume after a minute. If this still not works try the %s or submit an error report and contact us.', 'wp-staging'), '<a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a>'),
                'ajax_status_code_502' => sprintf(esc_html__('Error 502 - It looks like your server is rate limiting ajax requests. Please try to resume after a minute. If this still not works try the %s or submit an error report and contact us.', 'wp-staging'), '<a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a>'),
                'ajax_status_code_503' => sprintf(esc_html__('Error 503 - It looks like your server is rate limiting ajax requests. Please try to resume after a minute. If this still not works try the %s or submit an error report and contact us.', 'wp-staging'), '<a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a>'),
                'ajax_status_code_429' => sprintf(esc_html__('Error 429 - It looks like your server is rate limiting ajax requests. Please try to resume after a minute. If this still not works try the %s or submit an error report and contact us.', 'wp-staging'), '<a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a>'),
            ],
            'pro_admin_pro' => [
                'start_process_modal_html' => sprintf(esc_html__('This will overwrite the production/live site and its plugins, themes and media assets with data from the staging site: %sDatabase data will be overwritten for each selected table. Take care if you use a shop system like WooCommerce and read the %s. %s %sImportant:%s Before you proceed make sure that you have a full site backup. If the pushing process is not successful contact us at %s or use the Contact Us button.', 'wp-staging'), '%cloneName', '<a href="https://wp-staging.com/docs/skip-woocommerce-orders-and-products/" target="_blank">FAQ</a>', '<br/><br/>', '<b>', '</b>', '<a href=\'mailto:support@wp-staging.com\'>support@wp-staging.com</a>'),
                'connect_database_ajax_success_notify' => esc_html__('Database connection successful', 'wp-staging'),
            ],
            'module_process_modal' => [
                'cancel_process_confirm' => esc_html__('Are you sure you want to cancel cloning process?', 'wp-staging'),
                'cancel_process_text' => esc_html__('Canceling Please wait...', 'wp-staging'),
                'set_title_copying_database' => esc_html__('Copying Database', 'wp-staging'),
                'set_title_processing_data' => esc_html__('Processing Data', 'wp-staging'),
                'set_title_preserve_data' => esc_html__('Preserve Data', 'wp-staging'),
                'set_title_scanning_files' => esc_html__('Scanning Files', 'wp-staging'),
                'set_title_copying_files' => esc_html__('Copying Files', 'wp-staging'),
                'set_title_backup_files_scanning' => esc_html__('Backup Files Scanning', 'wp-staging'),
                'set_title_process_finished' => esc_html__('Process Finished', 'wp-staging'),
                'set_title_renaming_database' => esc_html__('Renaming Database', 'wp-staging'),
                'set_title_updating_database_data' => esc_html__('Updating Database Data', 'wp-staging'),
                'set_title_search_replace' => esc_html__('Search Replace', 'wp-staging'),
            ],
            'system_info' => [
                'confirm_and_proceed_purge_html' => esc_html__('This cleans up the database table %s. Only use this function for debugging purposes, or if the scheduled backups do not work as expected.', 'wp-staging'),
                'confirm_button_text' => esc_html__('Clean Backup Queue', 'wp-staging'),
                'confirm_and_proceed_purge_title' => esc_html__('Are You Sure?', 'wp-staging'),
            ],
            'clone_edit' => [
                'check_database_error' => esc_html__('Warning: Database table prefix can not be empty!', 'wp-staging'),
            ],
            'backup_remote_upload' => [
                'backup_successfully_uploaded' => esc_html__('Backup successfully uploaded!', 'wp-staging'),
                'backup_upload_complete' => esc_html__('Backup Upload Complete', 'wp-staging'),
            ],
            'backup_storages' => [
                'delete_cloud_file_modal_html' => esc_html__('Do you want to delete this backup file from the remote storage server?', 'wp-staging'),
                'delete_cloud_file_modal_title' => esc_html__('Delete Remote Backup?', 'wp-staging'),
                'delete_cloud_file_ajax_response_success_modal_title' => esc_html__('Backup Deleted!', 'wp-staging'),
                'delete_cloud_file_ajax_response_success_modal_text' => esc_html__('Backup has been deleted from remote storage.', 'wp-staging'),
                'delete_cloud_file_ajax_response_error_modal_text' => esc_html__('Failed to delete the backup', 'wp-staging'),
                'clicked_cloud_restore_download_body_text' => sprintf(esc_html__('You can optionally download the backup to your local device:%s', 'wp-staging'), '<div class="download-action-buttons"><a href="{downloadUrl}" class="wpstg-button wpstg-blue-primary wpstg-download-to-computer">' . esc_html__('Download to my computer', 'wp-staging') . '</a></div>'),
                'clicked_cloud_restore_download_modal_title' => esc_html__('Backup Successfully Downloaded to this Website', 'wp-staging'),
                'setup_download_modal_title' => esc_html__('Downloading backup from remote', 'wp-staging'),
            ],
            'backup_create' => [
                'create_backup_fetch_listing_error_md5' => sprintf(esc_html__('Failed to get backup md5 from response!%sResponse content:', 'wp-staging'), '<br/>'),
            ],
            'backup_delete' => [
                'delete_html' => esc_html__('Do you want to delete the backup', 'wp-staging'),
                'delete_title' => esc_html__('Delete Backup', 'wp-staging'),
            ],
            'backup_download' => [
                'render_part_html_file_size' => esc_html__('File Size', 'wp-staging'),
                'render_part_html_download' => esc_html__('Download', 'wp-staging'),
                'download_modal_title' => esc_html__('Download Backup', 'wp-staging'),
            ],
            'backup_edit' => [
                'edit_html_name' => esc_html__('Backup Name', 'wp-staging'),
                'edit_html_notes' => esc_html__('Additional Notes', 'wp-staging'),
            ],
            'backup_manage_schedules' => [
                'show_alert_for_basic_alert' => esc_html__('Please upgrade to WP Staging Pro to edit existing backup plans. You can delete this plan and create a new one if you want to change it.', 'wp-staging'),
            ],
            'backup_restore' => [
                'import_modal_progress_status' => esc_html__('Backup successfully imported!', 'wp-staging'),
                'import_modal_login_text' => esc_html__('You will be redirected to the login page after closing this modal.', 'wp-staging'),
                'import_modal_modal_title' => esc_html__('Finished Successfully', 'wp-staging'),
                'import_modal_modal_html' => esc_html__('Site has been restored from backup. ', 'wp-staging'),
            ],
            'backup_upload_url' => [
                'handle_success_response_success' => esc_html__('Upload finished', 'wp-staging'),
                'handle_success_response_error' => esc_html__('Invalid request data', 'wp-staging'),
                'handle_error_response_confirm' => esc_html__('An error occurred during the download. Do you want to resume the download?', 'wp-staging'),
                'handle_cancel_process_confirm' => esc_html__('Do you want to abort the upload?', 'wp-staging'),
                'upload_backup_from_url_empty_error' => esc_html__('Backup file URL is empty', 'wp-staging'),
                'upload_backup_from_url_valid_url_error' => esc_html__('Please enter a valid backup file url.', 'wp-staging'),
                'upload_backup_from_url_correct_url_error' => esc_html__('Please enter correct backup file url', 'wp-staging'),
            ],
            'backup_upload' => [
                'upload_not_supported_error' => sprintf(esc_html__('Your browser do not support the File API, needed for the uploads. Please try a different/updated browser, or upload the Backup using FTP to the folder %s', 'wp-staging'), '<strong>wp-content/uploads/wp-staging/backups</strong>'),
                'event_listener_confirm_cancel_upload_confirm' => esc_html__('Do you want to abort the upload?', 'wp-staging'),
                'handle_error_modal' => sprintf(esc_html__('We could not upload the backup file, please try uploading it directly using FTP to the folder %s. Please also make sure you have enough free disk space on the server.', 'wp-staging'), '<strong>wp-content/uploads/wp-staging/backups</strong>'),
            ],
            'create_text'                => esc_html__('Create', 'wp-staging'),
            'update_text'                => esc_html__('Update', 'wp-staging'),
            'temporary_logins'      => [
                'not_allowed'       => esc_html__('You are not allowed to create a temporary login link.', 'wp-staging'),
                'invalid_email'     => esc_html__('Please enter the email address of the person you wish to grant access to.', 'wp-staging'),
                'invalid_expiry'    => esc_html__('Please select an expiry date to create a temporary login.', 'wp-staging'),
                'delete_success'    => esc_html__('Login link removed successfully!', 'wp-staging'),
                'confirm_delete'    => esc_html__('Do you really want to delete this temporary login link?', 'wp-staging'),
                'confirm_delete_title' => esc_html__('Are you sure?', 'wp-staging'),
            ],
            'cannot_generate_otp_error' => wp_kses(__('We couldn\'t create the verification code. This might be caused by a firewall blocking the request. The verification code is necessary for security before you can upload a backup to this site. To proceed, you can temporarily <a href="%s" target="_blank">disable the verification code generation</a> or contact WP Staging support for assistance.', 'wp-staging'), ["a" => ["href" => [], "target" => []]]),
        ];
    }
}
