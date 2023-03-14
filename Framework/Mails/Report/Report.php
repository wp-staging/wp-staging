<?php

namespace WPStaging\Framework\Mails\Report;

use WPStaging\Backend\Modules\SystemInfo;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\DebugLogReader;
use WPStaging\Framework\Mails\Report\ReportSubmitTransient;

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

    public function __construct(SystemInfo $systemInfo, Directory $directory, DebugLogReader $debugLogReader, ReportSubmitTransient $reportSubmitTransient)
    {
        $this->systemInfo = $systemInfo;
        $this->directory = $directory;
        $this->debugLogReader = $debugLogReader;
        $this->transient = $reportSubmitTransient;
    }



    /**
     * Send customer issue report
     *
     * @param string $email User e-mail
     * @param string $message User message
     * @param int $terms User accept terms
     * @param bool $sendLogFiles User selected syslog
     * @param string $provider User site provider
     * @param bool $forceSend force send mail even if already sent
     *
     * @return array
     */
    public function send($email, $message, $terms, $sendLogFiles, $provider = null, $forceSend = false)
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
                "message" => __("You've already submitted a ticket.<br/>" .
                    "Do you want to send another one?", 'wp-staging')
            ];
            return  $errors;
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
    protected function getAttachments(array $attachments)
    {
        $systemInformationFile = trailingslashit($this->directory->getTmpDirectory()) . 'system_information.txt';
        $this->copyDataToFile($systemInformationFile, $this->systemInfo->get());
        $attachments[] = $systemInformationFile;

        if (file_exists(WPSTG_DEBUG_LOG_FILE)) {
            $destinationWpstgDebugFilePath = trailingslashit($this->directory->getTmpDirectory()) . 'wpstg_debug.log';
            $this->copyDataToFile($destinationWpstgDebugFilePath, $this->debugLogReader->getLastLogEntries(512 * KB_IN_BYTES, true, false));
            $attachments[] = $destinationWpstgDebugFilePath;
        }

        $debugLogFile = WP_CONTENT_DIR . '/debug.log';
        if (file_exists($debugLogFile)) {
            $destinationDebugLogFilePath = trailingslashit($this->directory->getTmpDirectory()) . 'debug.log';
            $this->copyDataToFile($destinationDebugLogFilePath, $this->debugLogReader->getLastLogEntries(512 * KB_IN_BYTES, false));
            $attachments[] = $destinationDebugLogFilePath;
        }
        return $attachments;
    }

    /**
     * Copy data into file
     *
     * @param  string $destinationFile  where to copy to.
     * @param  string $data data to put into file.
     * @return void
     */
    protected function copyDataToFile($destinationFile, $data)
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
    private function sendMail($from, $text, $attachments)
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
}
