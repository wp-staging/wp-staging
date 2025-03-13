<?php

namespace WPStaging\Notifications\Transporter;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\TemplateEngine\TemplateEngine;

class EmailTemplateBuilder extends AbstractTemplateComponent
{
    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $message;

    /**
     * @var array
     */
    private $details = [];

    /**
     * @var bool
     */
    private $isBasic = false;

    /**
     * @var string
     */
    private $recipient = '';

    /**
     * @param TemplateEngine $templateEngine
     */
    public function __construct(TemplateEngine $templateEngine)
    {
        parent::__construct($templateEngine);
        $this->isBasic = WPStaging::isBasic();
    }

    /**
     * Create a new email template builder
     * @param TemplateEngine $templateEngine
     * @return self
     */
    public static function create(TemplateEngine $templateEngine)
    {
        return new self($templateEngine);
    }

    /**
     * Set the email title
     * @param string $title
     * @return self
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Set the main message
     * @param string $message
     * @return self
     */
    public function setMessage(string $message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Add details to the email template
     * @param array $details
     * @return self
     */
    public function setDetails(array $details)
    {
        $this->details = $details;
        return $this;
    }

    /**
     * Set the recipient email address
     * @param string $recipient
     * @return self
     */
    public function setRecipient(string $recipient = '')
    {
        $this->recipient = $recipient;
        return $this;
    }

    /**
     * Get the template path
     * @return string
     */
    protected function getTemplate(): string
    {
        return 'notifications/email-template.php';
    }

    /**
     * Prepare data for template rendering
     * @return array
     */
    protected function getRenderData(): array
    {
        return [
            'encodedSvg'  => $this->getEncodedLogo(),
            'htmlMessage' => $this->processMessage(),
            'details'     => $this->details,
            'isBasic'     => $this->isBasic,
            'recipient'   => $this->recipient,
            'year'        => date('Y'),
            'siteUrl'     => get_site_url(),
            'pluginName'  => $this->isBasic ? 'WP Staging free backup and staging plugin' : 'WP Staging plugin'
        ];
    }

    /**
     * Process the message with JSON beautification and URL conversion
     * @return string
     */
    private function processMessage(): string
    {
        $message = $this->beautifyJsonInMessage();
        $message = nl2br($message);
        return $this->convertUrlsToLinks($message);
    }

    /**
     * Get encoded logo SVG
     * @return string
     */
    private function getEncodedLogo(): string
    {
        $logoUrl = WPSTG_PLUGIN_DIR . 'assets/svg/notification-logo.svg';
        if (file_exists($logoUrl)) {
            return 'data:image/svg+xml,' . rawurlencode(file_get_contents($logoUrl));
        }

        return '';
    }

    /**
     * Convert URLs in text to clickable links
     * @param string $text Text containing URLs
     * @return string Text with clickable links
     */
    private function convertUrlsToLinks(string $text): string
    {
        return preg_replace_callback('/\b(https?:\/\/(?:www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b(?:[-a-zA-Z0-9()@:%_\+.~#?&\/\/=]*))/i', function ($matches) {
            $url = rtrim($matches[1], '.,;:!?');
            $sanitizedUrl = filter_var($url, FILTER_SANITIZE_URL);
            if (filter_var($sanitizedUrl, FILTER_VALIDATE_URL)) {
                return sprintf(
                    '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
                    htmlspecialchars($sanitizedUrl),
                    htmlspecialchars($url)
                );
            }

            return $url;
        }, $text);
    }

    /**
     * Generate the HTML email template
     * @return string
     */
    public function generate(): string
    {
        return $this->templateEngine->render(
            $this->getTemplate(),
            $this->getRenderData()
        );
    }

    /**
     * Beautifies JSON blocks inside the message.
     * @return string
     */
    private function beautifyJsonInMessage(): string
    {
        return preg_replace_callback('/\{(?:[^{}]*|(?R))*\}/s', function ($matches) {
            $jsonString  = $matches[0];
            $decodedJson = json_decode($jsonString, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $prettyJson = json_encode($decodedJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                return '<pre style="background-color: #f4f4f4; padding: 10px; white-space: break-spaces;">' . htmlspecialchars($prettyJson) . '</pre>';
            }

            return nl2br($jsonString);
        }, $this->message);
    }
}
