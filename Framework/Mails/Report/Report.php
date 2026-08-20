<?php

namespace WPStaging\Framework\Mails\Report;

use WPStaging\Core\WPStaging;
use WPStaging\Backend\Modules\SystemInfo;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\DebugLogReader;
use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Utils\Sanitize;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Notifications\Notifications;

use function WPStaging\functions\debug_log;

class Report
{



    const FILTER_MAILS_REPORT_BUNDLED_LOGS_USE_ZIPARCHIVE = 'wpstg.mails.report.bundled_logs_use_ziparchive';




    const WPSTG_SUPPORT_EMAIL = "support@wp-staging.com";




    const EMAIL_SUBJECT = "Report Issue!";




    const FORCE_SEND_DEBUG_LOG = true;




    const TEMP_DIRECTORY = 'tmp';




    const MAX_SIZE_DEBUG_LOG = 512;




    const RETENTATION_LOG_DAYS = 14;




    private $systemInfo;




    private $directory;




    private $debugLogReader;




    private $transient;




    private $auth;




    private $sanitize;




    private $notifications;










    public function __construct(SystemInfo $systemInfo, Directory $directory, DebugLogReader $debugLogReader, ReportSubmitTransient $reportSubmitTransient, Auth $auth, Sanitize $sanitize, Notifications $notifications)
    {
        $this->systemInfo     = $systemInfo;
        $this->directory      = $directory;
        $this->debugLogReader = $debugLogReader;
        $this->transient      = $reportSubmitTransient;
        $this->auth           = $auth;
        $this->sanitize       = $sanitize;
        $this->notifications  = $notifications;
    }













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

        if ($forceSend !== self::FORCE_SEND_DEBUG_LOG && $this->transient->getTransient()) {
 
            $errors[] = [
                "status"  => 'already_submitted',
                "message" => __("You've already submitted a ticket!<br/>" .
                    "Do you want to send another one?", 'wp-staging'),
            ];
            return $errors;
        }

        $attachments = [];

        $message .= "\n\nLicense Key: " . $this->getLicenseKey();
        if ($provider) {
            $message .= "\n\nHosting provider: " . $provider;
        }

        if (!empty($sendLogFiles)) {
            $attachments = $this->getBundledLogs();
        }

        if ($this->sendFeedback($email, $message, $attachments) === false) {
            $errors[] = __('Can not send mail. <br>Please write us a mail to<br>support@wp-staging.com', 'wp-staging');
            return $errors;
        }

        $this->transient->setTransient();
        return $errors;
    }








    public function sendDebugLog(string $fromEmail, string $debugCode, bool $forceSend = false): array
    {
        if ($forceSend !== self::FORCE_SEND_DEBUG_LOG && $this->transient->getTransient()) {
            return [
                "sent"    => false,
                "status"  => 'already_submitted',
                "message" => __("Email already submitted!  Please select force send option to send it again.", "wp-staging"),
            ];
        }

        $attachments = [];
        $headers     = [];
        $mailSubject = sprintf(__('WP Staging - Debug Code: %s', 'wp-staging'), $debugCode);
        $response    = __("Sending debug info failed!", 'wp-staging');
        $message     = $mailSubject . "\n" . sprintf(__("License Key: %s", 'wp-staging'), $this->getLicenseKey());
        $attachments = $this->getBundledLogs();

        $isSent = $this->notifications->sendEmail(self::WPSTG_SUPPORT_EMAIL, $mailSubject, $message, $fromEmail, $attachments, Notifications::DISABLE_FOOTER_MESSAGE);

        if ($isSent) {
            $this->transient->setTransient();
            $response = __("Successfully submitted debug info!", 'wp-staging');
        };

        $errors = [
            "sent"    => $isSent,
            "status"  => $isSent ? 'submitted' : 'failed',
            "message" => $response,
        ];

        $this->deleteBundledLogs();

        return  $errors;
    }




    public function ajaxSendDebugLog()
    {
        if (!$this->auth->isAuthenticatedRequest()) {
            return;
        }

        $postData  = stripslashes_deep($_POST);
        $forceSend = false;
        $debugCode = '';
        if (!empty($postData['debugCode'])) {
            $debugCode = $this->sanitize->sanitizeString($postData['debugCode']);
        }

        if (!empty($postData['forceSend'])) {
            $forceSend = trim((string)$this->sanitize->sanitizeBool($postData['forceSend']));
        }

        $loggedInUser      = wp_get_current_user();
        $loggedInUserEmail = '';
        if (!empty($loggedInUser->user_email)) {
            $loggedInUserEmail = $this->sanitize->sanitizeEmail($loggedInUser->user_email);
        }

        $response = $this->sendDebugLog($loggedInUserEmail, $debugCode, $forceSend);
        wp_send_json(['response' => $response]);
    }




    public function getBundledLogs(): array
    {
        $postfix         = sanitize_file_name(strtolower(wp_hash(uniqid())));
        $tempDirectory   = trailingslashit($this->getTempDirectoryForLogsAttachments());
        $maxSizeDebugLog = self::MAX_SIZE_DEBUG_LOG * KB_IN_BYTES;
        $zipFilePath     = $tempDirectory . sprintf('wpstg-bundled-logs-%s.zip', $postfix);
        $logFiles        = [];

        $isEncodeProLicenseBefore = $this->systemInfo->getEncodeProLicense();
        $this->systemInfo->setEncodeProLicense(true);
        $systemInfoData = $this->systemInfo->get();
        $this->systemInfo->setEncodeProLicense($isEncodeProLicenseBefore);

        $systemInformationFile = $tempDirectory . sprintf('system_information-%s.txt', $postfix);
        $systemInformationData = $this->debugLogReader->maybeFixHtmlEntityDecode($systemInfoData);

        if (!empty($systemInformationData)) {
            $this->copyDataToFile($systemInformationFile, $systemInformationData);
            $logFiles[] = $systemInformationFile;
        }

        if (defined('WPSTG_DEBUG_LOG_FILE') && file_exists(WPSTG_DEBUG_LOG_FILE) && is_readable(WPSTG_DEBUG_LOG_FILE)) {
            $destinationWpstgDebugFilePath = $tempDirectory . sprintf('wpstg_debug-%s.log', $postfix);
            $this->copyDataToFile($destinationWpstgDebugFilePath, $this->debugLogReader->getLastLogEntries($maxSizeDebugLog, true, false));
            $logFiles[] = $destinationWpstgDebugFilePath;
        }

 
        $debugLogFile = ini_get('error_log');

        if (file_exists($debugLogFile) && is_readable($debugLogFile)) {
            $destinationDebugLogFilePath = $tempDirectory . sprintf('debug-%s.log', $postfix);

            $this->copyDataToFile($destinationDebugLogFilePath, $this->debugLogReader->getLastLogEntries($maxSizeDebugLog, false));
            $logFiles[] = $destinationDebugLogFilePath;
        }

        $retentionLogFiles = $this->debugLogReader->getRetentionLogFiles(self::RETENTATION_LOG_DAYS);

        if (!empty($retentionLogFiles)) {
            foreach ($retentionLogFiles as $logFileType => $logFileArray) {
                if (empty($logFileArray)) {
                    continue;
                }

                foreach ($logFileArray as $logFileNum => $logFilePath) {
                    if (!file_exists($logFilePath) || !is_readable($logFilePath)) {
                        continue;
                    }

                    $fileCount              = $logFileNum + 1;
                    $destinationLogFilePath = $tempDirectory . $logFileType . '-' . $postfix . '-' . $fileCount . '.log';
                    $this->copyDataToFile($destinationLogFilePath, file_get_contents($logFilePath));
                    $logFiles[] = $destinationLogFilePath;
                }
            }
        }

        if (empty($logFiles)) {
            return [];
        }

        if (file_exists($zipFilePath)) {
            unlink($zipFilePath);
        }

        if (!class_exists('ZipArchive', false) || !Hooks::applyFilters(self::FILTER_MAILS_REPORT_BUNDLED_LOGS_USE_ZIPARCHIVE, true)) {
            return $logFiles;
        }

        $zip = new \ZipArchive();
        if (!$zip->open($zipFilePath, \ZipArchive::CREATE)) {
            return $logFiles;
        }

        foreach ($logFiles as $filePath) {
            $zip->addFile($filePath, 'wpstg-bundled-logs/' . basename($filePath));
        }

 
 
        $zip->close();

        if (file_exists($zipFilePath)) {
            return [$zipFilePath];
        }

        return [];
    }




    public function deleteBundledLogs()
    {
        $dirPath     = trailingslashit($this->getTempDirectoryForLogsAttachments());
        $dirIterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dirPath));

        $logFileType    = $this->debugLogReader->getAvailableLogFileTypes();
        $logFilePrefix  = array_merge($logFileType, ['wpstg-bundled-logs', 'system_information', 'wpstg_debug', 'debug']);
        $logPrefixMatch = implode('|', $logFilePrefix);

        foreach ($dirIterator as $fileInfo) {
            if (!$fileInfo->isFile() || $fileInfo->isLink() || !in_array($fileInfo->getExtension(), ['log', 'txt', 'zip'])) {
                continue;
            }

            $filePath = $fileInfo->getRealPath();
            $fileName = $fileInfo->getFilename();

            if (!preg_match('@^(' . $logPrefixMatch . ')@', $fileName)) {
                continue;
            }

            unlink($filePath);
        }
    }








    protected function copyDataToFile(string $destinationFile, string $data)
    {
        try {
            $ft = fopen($destinationFile, "wb");
            if (!$ft) {
                debug_log('Fail to copy data to file, because cannot open destination file.');
                return;
            }

            fputs($ft, $data);
            fclose($ft);
        } catch (\Throwable $th) {
            debug_log('Fail to copy data to file. Error message: ' . $th->getMessage());
            return;
        }
    }









    private function sendFeedback(string $from, string $text, array $attachments): bool
    {
        $success = $this->notifications->sendEmail(self::WPSTG_SUPPORT_EMAIL, self::EMAIL_SUBJECT, $text, $from, $attachments, Notifications::DISABLE_FOOTER_MESSAGE);
        $this->deleteBundledLogs();

        return (bool)$success;
    }





    private function getTempDirectoryForLogsAttachments(): string
    {
        $tempDirectory = trailingslashit(wp_normalize_path($this->directory->getPluginUploadsDirectory() . self::TEMP_DIRECTORY));
        wp_mkdir_p($tempDirectory);

        return $tempDirectory;
    }




    private function getLicenseKey(): string
    {
        $licenseKey = get_option('wpstg_license_key');
        return !empty($licenseKey) ? $licenseKey : 'Unregistered';
    }
}
