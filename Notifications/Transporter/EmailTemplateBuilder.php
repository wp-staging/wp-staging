<?php

namespace WPStaging\Notifications\Transporter;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\TemplateEngine\TemplateEngine;

class EmailTemplateBuilder extends AbstractTemplateComponent
{



    private $title;




    private $message;




    private $details = [];




    private $isBasic = false;




    private $recipient = '';




    public function __construct(TemplateEngine $templateEngine)
    {
        parent::__construct($templateEngine);
        $this->isBasic = WPStaging::isBasic();
    }






    public static function create(TemplateEngine $templateEngine)
    {
        return new self($templateEngine);
    }






    public function setTitle(string $title)
    {
        $this->title = $title;
        return $this;
    }






    public function setMessage(string $message)
    {
        $this->message = $message;
        return $this;
    }






    public function setDetails(array $details)
    {
        $this->details = $details;
        return $this;
    }






    public function setRecipient(string $recipient = '')
    {
        $this->recipient = $recipient;
        return $this;
    }





    protected function getTemplate(): string
    {
        return 'notifications/email-template.php';
    }





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
            'pluginName'  => $this->isBasic ? 'WP Staging free backup and staging plugin' : 'WP Staging plugin',
        ];
    }





    private function processMessage(): string
    {
        $message = $this->beautifyJsonInMessage();
        $message = nl2br($message);
        return $this->convertUrlsToLinks($message);
    }





    private function getEncodedLogo(): string
    {
        $logoUrl = WPSTG_PLUGIN_DIR . 'assets/svg/notification-logo.svg';
        if (file_exists($logoUrl)) {
            return 'data:image/svg+xml,' . rawurlencode(file_get_contents($logoUrl));
        }

        return '';
    }






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





    public function generate(): string
    {
        return $this->templateEngine->render(
            $this->getTemplate(),
            $this->getRenderData()
        );
    }





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
