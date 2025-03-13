<?php

namespace WPStaging\Framework\Mails;

use WPStaging\Notifications\Notifications;
use WPStaging\Framework\Facades\Sanitize;

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
     * @var array
     */
    protected $attachments;

    /**
     * @var string
     */
    protected $recipient;

    /**
     * @var bool
     */
    protected $addFooter;

    /**
     * @var Sanitize
     */
    protected $sanitize;

    /**
     * @param Notifications $notifications
     * @param Sanitize $sanitize
     */
    public function __construct(Notifications $notifications, Sanitize $sanitize)
    {
        $this->notifications = $notifications;
        $this->attachments   = [];
        $this->recipient     = get_option(Notifications::OPTION_BACKUP_SCHEDULE_REPORT_EMAIL);
        $this->addFooter     = false;
        $this->sanitize      = $sanitize;
    }

    /**
     * @param array $attachments
     * @return void
     */
    public function setAttachments(array $attachments)
    {
        $this->attachments = $attachments;
    }

    /**
     * @param string $recipient
     * @return void
     */
    public function setRecipient(string $recipient)
    {
        $this->recipient = $recipient;
    }

    /**
     * @param bool $addFooter
     * @return void
     */
    public function setAddFooter(bool $addFooter)
    {
        $this->addFooter = $addFooter;
    }

    /**
     * This function sends a request to the server to send an email notification even if the optimizer is enabled by whitelisting all plugins for our internal use
     * The wpstg_action parameter is set to bypass_optimizer to bypass the optimizer and allow the email to be sent.
     * @param string $subject
     * @param string $body
     * @param array $details
     * @return bool
     */
    public function sendRequestForEmailNotification(string $subject, string $body, array $details = []): bool
    {
        if (empty($subject) || empty($body)) {
            debug_log('Email subject or body is empty', 'error');
            return false;
        }

        $accessToken = wp_generate_password(64, false);
        set_transient(self::TRANSIENT_EMAIL_NOTIFICATION_ACCESS_TOKEN, $accessToken, 10);

        $attachments = $this->prepareAttachments();

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
                    'recipient'    => $this->recipient,
                    'attachments'  => implode(',', $attachments),
                    'footer'       => $this->addFooter,
                    'details'      => $details,
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
        $footer  = isset($_POST['footer']) ? (bool)$_POST['footer'] : true;
        $details = isset($_POST['details']) ? $this->sanitize->sanitizeArray($_POST['details']) : [];
        if (empty($subject) || empty($body)) {
            debug_log('Email subject or body is empty', 'error');
            wp_send_json_error();
        }

        if (empty($_POST['recipient']) || !filter_var($_POST['recipient'], FILTER_VALIDATE_EMAIL)) {
            debug_log('Report email is not set or invalid', 'error');
            wp_send_json_error();
        }

        $attachments = $this->getPreparedAttachments();
        $result      = false;
        try {
            if (get_option(Notifications::OPTION_SEND_EMAIL_AS_HTML, false) === 'true') {
                $result = $this->notifications->sendEmailAsHTML(sanitize_email($_POST['recipient']), $subject, $body, '', $details, $attachments);
            } else {
                $result = $this->notifications->sendEmail(sanitize_email($_POST['recipient']), $subject, $body, '', $attachments, $footer);
            }

            $this->cleanupAttachments($attachments);

            if (!$result) {
                wp_send_json_error();
            }
        } catch (\Exception $error) {
            debug_log($error->getMessage(), 'error');
            wp_send_json_error();
        }

        wp_send_json_success();
    }

    private function prepareAttachments(): array
    {
        $attachments = [];
        foreach ($this->attachments as $attachment) {
            if (!file_exists($attachment)) {
                continue;
            }

            $attachments[] = $attachment;
        }

        return $attachments;
    }

    private function getPreparedAttachments(): array
    {
        $attachments = isset($_POST['attachments']) ? sanitize_text_field($_POST['attachments']) : '';
        if (empty($attachments)) {
            return [];
        }

        $this->attachments = explode(',', $attachments);
        $attachments = [];
        foreach ($this->attachments as $attachment) {
            if (!file_exists($attachment)) {
                continue;
            }

            $attachments[] = $attachment;
        }

        return $attachments;
    }

    /**
     * @param array $attachments
     * @return void
     */
    private function cleanupAttachments(array $attachments)
    {
        foreach ($attachments as $attachment) {
            if (file_exists($attachment)) {
                unlink($attachment);
            }
        }
    }
}
