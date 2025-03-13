<?php

namespace WPStaging\Notifications;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Notifications\NotificationsProvider;
use WPStaging\Notifications\Transporter\EmailTemplateBuilder;

class Notifications
{
    /**
     * @var string
     */
    const OPTION_BACKUP_SCHEDULE_REPORT_EMAIL = 'wpstg_backup_schedules_report_email';

    /**
     * @var bool
     */
    const DISABLE_FOOTER_MESSAGE = false;

    /**
     * @var bool
     */
    const ENABLE_FOOTER_MESSAGE = true;

    /**
     * @var string
     */
    const OPTION_SEND_EMAIL_AS_HTML = 'wpstg_send_email_as_html';

    /**
     * @var object
     */
    private $transporter;

    /**
     * @var NotificationsProvider
     */
    private $notificationProvider;

    /**
     * @param NotificationsProvider $notificationProvider
     */
    public function __construct(NotificationsProvider $notificationProvider)
    {
        $this->notificationProvider = $notificationProvider;
        $this->getTransporter();
    }

    /**
     * @return void
     */
    private function getTransporter()
    {
        $providers = $this->notificationProvider->getProviders();

        $this->transporter = new \stdClass();
        foreach ($providers as $provider) {
            $providerName                       = lcfirst(basename(str_replace('\\', '/', $provider)));
            $this->transporter->{$providerName} = WPStaging::make($provider);
        }
    }

    /**
     * @param string $to Recipient
     * @param string $subject Subject
     * @param string $message Content
     * @param string $from (Optional) Sender
     * @param array $attachments (Optional) Attachments
     * @param bool $isAddFooterMessage (Optional) Enable footer message
     * @return bool
     */
    public function sendEmail(string $to, string $subject, string $message, string $from = '', array $attachments = [], bool $isAddFooterMessage = self::ENABLE_FOOTER_MESSAGE): bool
    {
        if (empty($this->transporter->emailNotification) || !is_object($this->transporter->emailNotification)) {
            return false;
        }

        $this->transporter->emailNotification->setSender($from)
            ->setRecipient($to)
            ->setSubject($subject)
            ->setAttachment($attachments)
            ->setIsAddFooterMessage($isAddFooterMessage);

        return $this->transporter->emailNotification->send($message);
    }

    /**
     * @param string $webhook Slack Webhook
     * @param string $title title
     * @param string $message Content
     * @param bool $isAddFooterMessage (Optional) Enable footer message
     * @return bool
     */
    public function sendSlack(string $webhook, string $title, string $message, bool $isAddFooterMessage = self::ENABLE_FOOTER_MESSAGE): bool
    {
        if (empty($this->transporter->slackNotification) || !is_object($this->transporter->slackNotification)) {
            return false;
        }

        $this->transporter->slackNotification->setWebhook($webhook)
            ->setTitle($title)
            ->setIsAddFooterMessage($isAddFooterMessage);

        return $this->transporter->slackNotification->send($message);
    }

    /**
     * @param string $to Recipient
     * @param string $subject Subject
     * @param string $message Content
     * @param string $from (Optional) Sender
     * @param array $details (Optional) Details
     * @param array $attachments (Optional) Attachments
     * @return bool
     */
    public function sendEmailAsHTML(string $to, string $subject, string $message = '', string $from = '', array $details = [], array $attachments = []): bool
    {
        if (empty($this->transporter->emailNotification) || !is_object($this->transporter->emailNotification)) {
            return false;
        }

        $this->transporter->emailNotification->setUseHtml(true);
        $templateEngine = WPStaging::make(TemplateEngine::class);
        $emailTemplate  = EmailTemplateBuilder::create($templateEngine)
            ->setTitle($subject)
            ->setRecipient($to)
            ->setMessage($message)
            ->setDetails($details)
            ->generate();

        return $this->sendEmail($to, $subject, $emailTemplate, $from, $attachments, false);
    }
}
