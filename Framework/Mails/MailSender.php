<?php

namespace WPStaging\Framework\Mails;

use WPStaging\Backup\BackupScheduler;
use WPStaging\Notifications\Notifications;

use function WPStaging\functions\debug_log;

/**
 * Class MailSender
 * This class is responsible for sending email notifications during WPStaging jobs by white-listing all plugins for our internal use
 * The wpstg_action parameter is set to bypass_optimizer to bypass the optimizer and allow the email to be sent.
 * @package WPStaging\Framework\Mails
 */
class MailSender
{
    /**
     * @var string
     */
    const TRANSIENT_EMAIL_NOTIFICATION_ACCESS_TOKEN = 'wpstg_email_notification_access_token';

    /**
     * @var Notifications
     */
    protected $notifications;

    /**
     * @param Notifications $notifications
     */
    public function __construct(Notifications $notifications)
    {
        $this->notifications = $notifications;
    }

    /**
     * This function sends a request to the server to send an email notification even if the optimizer is enabled by whitelisting all plugins for our internal use
     * The wpstg_action parameter is set to bypass_optimizer to bypass the optimizer and allow the email to be sent.
     * @param string $subject
     * @param string $body
     * @return bool
     */
    public function sendRequestForEmailNotification(string $subject, string $body): bool
    {
        if (empty($subject) || empty($body)) {
            debug_log('Email subject or body is empty', 'error');
            return false;
        }

        $accessToken = wp_generate_password(64, false);
        set_transient(self::TRANSIENT_EMAIL_NOTIFICATION_ACCESS_TOKEN, $accessToken, 10);

        $response = wp_remote_post(
            admin_url('admin-ajax.php'),
            [
                'timeout'   => 15,
                'sslverify' => false,
                'body'      => [
                    'action'       => 'wpstg_send_mail_notification',
                    'wpstg_action' => 'bypass_optimizer',
                    'access_token' => $accessToken,
                    'subject'      => $subject,
                    'body'         => $body,
                ],
            ]
        );

        if (is_wp_error($response)) {
            debug_log($response->get_error_message(), 'error');
            return false;
        }

        if (wp_remote_retrieve_response_code($response) !== 200) {
            debug_log('Failed to send email notification', 'error');
            return false;
        }

        $responseBody = wp_remote_retrieve_body($response);
        if (empty($responseBody)) {
            debug_log('Empty response body', 'error');
            return false;
        }

        $responseBody = json_decode($responseBody, true);
        if (empty($responseBody['success'])) {
            return false;
        }

        return $responseBody['success'];
    }

    /**
     * This method is called when the wpstg_send_mail_notification action is triggered
     * It sends an email notification to the report email address
     * @return void
     */
    public function ajaxSendEmailNotification()
    {
        $accessToken = isset($_POST['access_token']) ? sanitize_text_field($_POST['access_token']) : '';
        if (empty($accessToken) || get_transient(self::TRANSIENT_EMAIL_NOTIFICATION_ACCESS_TOKEN) !== $accessToken) {
            debug_log('Invalid/Missing access token', 'error');
            wp_send_json_error();
        }

        delete_transient(self::TRANSIENT_EMAIL_NOTIFICATION_ACCESS_TOKEN);

        $subject = isset($_POST['subject']) ? sanitize_text_field($_POST['subject']) : '';
        $body    = isset($_POST['body']) ? wp_kses_post($_POST['body']) : '';
        if (empty($subject) || empty($body)) {
            debug_log('Email subject or body is empty', 'error');
            wp_send_json_error();
        }

        $reportEmail = get_option(BackupScheduler::OPTION_BACKUP_SCHEDULE_REPORT_EMAIL);
        if (empty($reportEmail) || !filter_var($reportEmail, FILTER_VALIDATE_EMAIL)) {
            debug_log('Report email is not set or invalid', 'error');
            wp_send_json_error();
        }

        try {
            $result = $this->notifications->sendEmail($reportEmail, $subject, $body);
            if (!$result) {
                wp_send_json_error();
            }
        } catch (\Exception $error) {
            debug_log($error->getMessage(), 'error');
            wp_send_json_error();
        }

        wp_send_json_success();
    }
}
