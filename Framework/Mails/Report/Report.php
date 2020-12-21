<?php

namespace WPStaging\Framework\Mails\Report;

use WPStaging\Backend\Modules\SystemInfo;

class Report
{
    /**
     * WP Staging Support Email
     *
     * @var string 
     */
    const WPSTG_SUPPORT_EMAIL = "support@wp-staging.com";

    /**
     * Email Subject for Issue Email
     *
     * @var string 
     */
    const EMAIL_SUBJECT = "Report Issue!";

    /**
     * Send customer issue report
     *
     * @param string $email User e-mail
     * @param string $message User message
     * @param integer $terms User accept terms
     * @param boolean $syslog User selected syslog
     * @param string $provider User site provider
     * @param boolean $forceSend force send mail even if already sent
     *
     * @return array
     */
    public function send($email, $message, $terms, $syslog, $provider = null, $forceSend = false)
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

        $attachments = [];
        $maxFileSize = 512 * 1024;
        if ($provider) {
            $message .= "\n\n'Hosting provider: " . $provider;
        }
        
        if (!empty($syslog)) {
            $message .= "\n\n'" . $this->getSyslog();
            $debugLogFile = WP_CONTENT_DIR . '/debug.log';
            if (filesize($debugLogFile) && $maxFileSize > filesize($debugLogFile)) {
                $attachments[] = $debugLogFile;
            }
        }

        $transient = new ReportSubmitTransient();
        if ($transient->getTransient() && !$forceSend) {
            // to show alert using js
            $errors[] = [
                "status" => 'already_submitted',
                "message" => __("You've already submitted a ticket.<br/>" .
                    "Do you really want to send another one?", 'wp-staging')
            ];
            return  $errors;
        }

        if ($this->sendMail($email, $message, $attachments) === false) {
            $errors[] = __('Can not send mail. <br>Please write us a mail to<br>support@wp-staging.com', 'wp-staging');
            return $errors;
        }

        $transient->setTransient();
        return $errors;
    }

    private function getSyslog()
    {
        $syslog = new SystemInfo();

        return $syslog->get();
    }

    /**
     * send feedback via email
     *
     * @param $from
     * @param $text
     * @param $attachments
     * @return boolean
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
