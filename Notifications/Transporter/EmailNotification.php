<?php

namespace WPStaging\Notifications\Transporter;

use WPStaging\Core\WPStaging;
use WPStaging\Notifications\Interfaces\NotificationsInterface;

use function WPStaging\functions\debug_log;

class EmailNotification implements NotificationsInterface
{



    private $sender = '';




    private $replyTo = '';




    private $recipient = '';




    private $subject = '';




    private $attachments = [];




    private $headers = [];




    private $isUseHtml = false;




    private $isAddFooterMessage = true;





    public function setSubject(string $subject)
    {
        $this->subject = $subject;
        return $this;
    }





    public function setSender(string $sender)
    {
        if (empty($sender)) {
            $this->sender = $sender;
            return $this;
        }

        if (preg_match('/(.*)<(.+)>/', $sender, $matches)) {
            $this->sender = $sender;
            return $this;
        }

        $senderName   = strtok($sender, '@');
        $this->sender = $senderName . ' <' . $sender . '>';

        return $this;
    }





    public function setReplyTo(string $replyTo)
    {
        $this->replyTo = $replyTo;
        return $this;
    }





    public function setRecipient(string $recipient)
    {
        $this->recipient = $recipient;
        return $this;
    }





    public function setAttachment(array $attachments)
    {
        $this->attachments = $attachments;
        return $this;
    }





    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
        return $this;
    }





    public function setUseHtml(bool $isUseHtml = false)
    {
        $this->isUseHtml = $isUseHtml;
        return $this;
    }





    public function setIsAddFooterMessage(bool $isAddFooterMessage = false)
    {
        $this->isAddFooterMessage = $isAddFooterMessage;
        return $this;
    }




    public function reset()
    {
        $this->sender             = '';
        $this->replyTo            = '';
        $this->recipient          = '';
        $this->attachments        = [];
        $this->headers            = [];
        $this->isUseHtml          = false;
        $this->isAddFooterMessage = true;
    }





    private function addFooterMessage(string $message): string
    {
        if (empty($message) || !$this->isAddFooterMessage) {
            return $message;
        }

        $siteUrl = get_site_url();

        $message .= "\r\n\r\n" . "--";
        if (WPStaging::isPro()) {
            $message .= "\r\n" . sprintf(esc_html__('This message was sent by the WP Staging plugin from the website %s', 'wp-staging'), $siteUrl);
        } else {
            $message .= "\r\n" . sprintf(esc_html__('This message was sent by the WP Staging free backup and staging plugin from the website %s', 'wp-staging'), $siteUrl);
        }

        $message .= "\r\n" . sprintf(esc_html__('It was sent to the email address %s which can be set up on %s', 'wp-staging'), $this->recipient, $siteUrl . '/wp-admin/admin.php?page=wpstg-settings');

        if (!WPStaging::isPro()) {
            $message .= "\r\n\r\n" . sprintf(esc_html__('Get more control over your notifications by using WP Staging Pro %s.', 'wp-staging'), 'https://wp-staging.com/');
        }

        $message .= "\r\n\r\n" . esc_html__('Please do not reply to this email.', 'wp-staging');
        return $message;
    }





    public function send(string $message): bool
    {
        if (empty($message)) {
            return false;
        }

        $headers = [];

        if (!empty($this->sender)) {
            $headers[] = 'From: ' . $this->sender;
        }

        $replyTo = !empty($this->replyTo) ? $this->replyTo : $this->sender;
        if (!empty($replyTo)) {
            $headers[] = 'Reply-To: ' . $replyTo;
        }

        if ($this->isUseHtml) {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
        }

        if (!empty($this->headers)) {
            $headers = array_merge($headers, $this->headers);
        }

        add_action('wp_mail_failed', function ($wpError) {
            debug_log(sprintf('[EmailNotification] %s', $wpError->get_error_message()), 'info', false);
        });

        $message = $this->addFooterMessage($message);
        if (!$this->isUseHtml) {
            $message = $this->cleanHtmlEntitiesAndTags($message);
        }

        return wp_mail($this->recipient, $this->subject, $message, $headers, $this->attachments);
    }






    private function cleanHtmlEntitiesAndTags(string $message): string
    {
        $message = html_entity_decode($message, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $message = wp_kses($message, []);
        return str_replace(['&gt;', '&amp;'], ['>', '&'], $message);
    }
}
