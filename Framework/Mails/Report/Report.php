<?php

namespace WPStaging\Framework\Mails\Report;

use WPStaging\Backend\Modules\SystemInfo;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\DebugLogReader;
use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Utils\Sanitize;

class Report
{

    /** @var string  */
    const WPSTG_SUPPORT_EMAIL = "support@wp-staging.com";

    /** @var string  */
    const EMAIL_SUBJECT = "Report Issue!";

    /** @var SystemInfo */
    private $systemInfo;


    /** @var Directory */
    private $directory;

    /** @var DebugLogReader */
    private $debugLogReader;

    /** @var ReportSubmitTransient */
    private $transient;

    const TEMP_DIRECTORY = 'tmp';

    /**
     * @var Auth
     */
    private $auth;

    /** @var Sanitize */
    private $sanitize;

    /**
     * @param SystemInfo $systemInfo
     * @param Directory $directory
     * @param DebugLogReader $debugLogReader
     * @param ReportSubmitTransient $reportSubmitTransient
     * @param Auth $auth
     * @param Sanitize $sanitize
     */
    public function __construct(SystemInfo $systemInfo, Directory $directory, DebugLogReader $debugLogReader, ReportSubmitTransient $reportSubmitTransient, Auth $auth, Sanitize $sanitize)
    {
        $this->systemInfo     = $systemInfo;
        $this->directory      = $directory;
        $this->debugLogReader = $debugLogReader;
        $this->transient      = $reportSubmitTransient;
        $this->auth           = $auth;
        $this->sanitize       = $sanitize;
    }

    /**
     * Send customer issue report
     *
     * @param string $email User e-mail
     * @param string $message User message
     * @param bool $terms User accept terms
     * @param bool $sendLogFiles User selected syslog
     * @param string|null $provider User site provider
     * @param bool $forceSend force send mail even if already sent
     *
     * @return array
     */
    public function send(string $email, string $message, bool $terms, bool $sendLogFiles, string $provider = '', bool $forceSend = false): array
    {
        $errors = [];

        if (empty($email)) {
            $errors[] = __('Please enter your email.', 'wp-staging');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = __('Email address is not valid.', 'wp-staging');
        }

        if (empty($message)) {
            $errors[] = __('Please enter your issue.', 'wp-staging');
        }

        if (empty($terms)) {
            $errors[] = __('Please accept our privacy policy.', 'wp-staging');
        }

        if (count($errors) !== 0) {
            return $errors;
        }

        if (!$forceSend && $this->transient->getTransient()) {
            // to show alert using js
            $errors[] = [
                "status" => 'already_submitted',
                "message" => __("You've already submitted a ticket!<br/>" .
                    "Do you want to send another one?", 'wp-staging')
            ];
            return $errors;
        }

        $attachments = [];
        $message .= "\n\nLicense Key: " . get_option('wpstg_license_key');
        if ($provider) {
            $message .= "\n\n'Hosting provider: " . $provider;
        }

        if (!empty($sendLogFiles)) {
            $attachments = $this->getAttachments($attachments);
        }

        if ($this->sendMail($email, $message, $attachments) === false) {
            $errors[] = __('Can not send mail. <br>Please write us a mail to<br>support@wp-staging.com', 'wp-staging');
            return $errors;
        }

        foreach ($attachments as $filePath) {
            unlink($filePath);
        }

        $this->transient->setTransient();
        return $errors;
    }

    /**
     * @param array $attachments
     * @return array
     */
    protected function getAttachments(array $attachments): array
    {
        $systemInformationFile = trailingslashit($this->getTempDirectoryForLogsAttachments()) . 'wpstg-bundled-logs.txt';
        $this->copyDataToFile($systemInformationFile, $this->systemInfo->get());
        $attachments[] = $systemInformationFile;

        if (file_exists(WPSTG_DEBUG_LOG_FILE)) {
            $destinationWpstgDebugFilePath = trailingslashit($this->getTempDirectoryForLogsAttachments()) . 'wpstg_debug.log';
            $this->copyDataToFile($destinationWpstgDebugFilePath, $this->debugLogReader->getLastLogEntries(512 * KB_IN_BYTES, true, false));
            $attachments[] = $destinationWpstgDebugFilePath;
        }

        $debugLogFile = WP_CONTENT_DIR . '/debug.log';
        if (file_exists($debugLogFile)) {
            $destinationDebugLogFilePath = trailingslashit($this->getTempDirectoryForLogsAttachments()) . 'debug.log';
            $this->copyDataToFile($destinationDebugLogFilePath, $this->debugLogReader->getLastLogEntries(512 * KB_IN_BYTES, false));
            $attachments[] = $destinationDebugLogFilePath;
        }

        $latestLogFiles = $this->debugLogReader->getLatestLogFiles();
        if (count($latestLogFiles) === 0) {
            return $attachments;
        }

        foreach ($latestLogFiles as $logFilePrefix => $logFile) {
            if (file_exists($logFile)) {
                $destinationLogFilePath = trailingslashit($this->getTempDirectoryForLogsAttachments()) . $logFilePrefix . '.log';
                $this->copyDataToFile($destinationLogFilePath, @file_get_contents($logFile));
                $attachments[] = $destinationLogFilePath;
            }
        }

        return $attachments;
    }

    /**
     * Copy data into file
     *
     * @param string $destinationFile  where to copy to.
     * @param string $data data to put into file.
     * @return void
     */
    protected function copyDataToFile(string $destinationFile, string $data)
    {
        $ft = fopen($destinationFile, "w");
        fputs($ft, $data);
        fclose($ft);
    }

    /**
     * send feedback via email
     *
     * @param $from
     * @param $text
     * @param $attachments
     * @return bool
     */
    private function sendMail($from, $text, $attachments): bool
    {
        $headers = [];

        $headers[] = "From: $from";
        $headers[] = "Reply-To: $from";

        $success = wp_mail(self::WPSTG_SUPPORT_EMAIL, self::EMAIL_SUBJECT, $text, $headers, $attachments);

        if ($success) {
            return true;
        }

        return false;
    }

    /**
     * Send customers debug log
     * @param $fromEmail
     * @param $debugCode
     * @return array
     */
    public function sendDebugLog($fromEmail, $debugCode): array
    {

        $message = esc_html__("Create a new ticket in the ", 'wp-staging') .
            '<a href="https://wp-staging.com/support-on-wordpress" target="_blank">' . esc_html__('Support Forum.', 'wp-staging') . '</a>' .
            sprintf(esc_html__(' Post there the debug code: %s with additional information about your issue.', 'wp-staging'), esc_html__($debugCode, 'wp-staging')) .
            '<a href="https://wp-staging.com/support-on-wordpress " target="_blank" class="wpstg-button wpstg-button--blue">
<?php esc_html_e("Open Support Forum", "wp-staging") ?></a>';


        if ($this->transient->getTransient()) {
            return [
                "sent"    => false,
                "status"  => 'already_submitted',
                "message" => $message
            ];
        }

        $attachments  = [];
        $headers      = [];
        $mailSubject  =  sprintf(__('WP Staging - Debug Code: %s', 'wp-staging'), $debugCode);
        $response     = __("Sending debug info failed!", 'wp-staging');
        $message      = $mailSubject . PHP_EOL . sprintf(__("License Key: %s", 'wp-staging'), get_option('wpstg_license_key'));
        $headers[] = "From: $fromEmail";
        $headers[] = "Reply-To: $fromEmail";
        $attachments = $this->getAttachments($attachments);
        $isSent = wp_mail(self::WPSTG_SUPPORT_EMAIL, $mailSubject, $message, $headers, $attachments);

        if ($isSent) {
            $this->transient->setTransient();
            $response =  __("Successfully submitted debug info!", 'wp-staging');
        }

        $errors = [
            "sent"    => $isSent,
            "status"  => $isSent ? 'submitted' : 'failed',
            "message" => $response
        ];

        foreach ($attachments as $filePath) {
            unlink($filePath);
        }

        return  $errors;
    }

    /**
     * @return void
     */
    public function ajaxSendDebugLog()
    {
        if (!$this->auth->isAuthenticatedRequest()) {
            return;
        }

        $postData = stripslashes_deep($_POST);

        $debugCode = '';
        if (!empty($postData['debugCode'])) {
            $debugCode = trim($this->sanitize->sanitizeString($postData['debugCode']));
        }

        $loggedInUser = wp_get_current_user();
        $loggedInUserEmail = '';
        if (!empty($loggedInUser->user_email)) {
            $loggedInUserEmail = trim($this->sanitize->sanitizeString($loggedInUser->user_email));
        }

        $response = $this->sendDebugLog($loggedInUserEmail, $debugCode);
        wp_send_json(['response' => $response]);
    }

     /**
     * create temp location for storing logs
     * @return string temporary path to hold logs attachments
     */
    private function getTempDirectoryForLogsAttachments(): string
    {
        $tempDirectory = trailingslashit(wp_normalize_path($this->directory->getPluginUploadsDirectory() . self::TEMP_DIRECTORY));
        wp_mkdir_p($tempDirectory);

        return $tempDirectory;
    }
}
