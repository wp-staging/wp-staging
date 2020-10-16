<?php

namespace WPStaging\Utils;

use WPStaging\Backend\Modules\SystemInfo;
use WPStaging\DI\InjectionAware;

class Report extends InjectionAware
{

    /**
     * Send customer issue report
     *
     * @param string $email User e-mail
     * @param string $message User message
     * @param integer $terms User accept terms
     *
     * @return array
     */
    public function send($email, $message, $terms, $syslog, $provider = null)
    {
        $errors      = array();
        $attachments = array();
        $maxFileSize = 512 * 1024;
        $message     .= "\n\n'Hosting provider: " . $provider;

        if ( ! empty($syslog)) {
            $message .= "\n\n'" . $this->getSyslog();

            $debugLogFile = WP_CONTENT_DIR . '/debug.log';
            if (filesize($debugLogFile) && filesize($debugLogFile) < $maxFileSize) {
                $attachments[] = $debugLogFile;
            }
        }

        if ( ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = __('Email address is not valid.', 'wp-staging');
        } elseif (empty($message)) {
            $errors[] = __('Please enter your issue.', 'wp-staging');
        } elseif (empty($terms)) {
            $errors[] = __('Please accept our privacy policy.', 'wp-staging');
        } else {

            if (false === $this->sendMail($email, $message, $attachments)) {
                $errors[] = 'Can not send report. <br>Please send us a mail to<br>support@wp-staging.com';
            }
        }

        return $errors;
    }

    private function getSyslog()
    {

        $syslog = new SystemInfo($this->di);

        return $syslog->get();
    }

    /**
     * send feedback via email
     *
     * @return boolean
     */
    private function sendMail($from, $text, $attachments)
    {

        $headers = array();

        $headers[] = "From: $from";
        $headers[] = "Reply-To: $from";

        $subject = 'Report Issue!';

        $success = wp_mail('support@wp-staging.com', $subject, $text, $headers, $attachments);

        if ($success) {
            return true;
        } else {
            return false;
        }
        die();
    }

}
