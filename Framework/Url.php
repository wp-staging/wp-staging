<?php

namespace WPStaging\Framework;

use WPStaging\Framework\Utils\Sanitize;

class Url
{

    /** @var Sanitize */
    private $sanitize;

    public function __construct(Sanitize $sanitize)
    {
        $this->sanitize = $sanitize;
    }

    /**
     * Outputs something like "/example/page.php"
     * @return string
     * @todo check if there is a better name for class and method
     *
     */
    public function getCurrentRoute()
    {

        if (empty($_SERVER['HTTP_HOST']) || empty($_SERVER['REQUEST_URI'])) {
            return '';
        }

        $requestPath = $this->sanitize->sanitizeUrl($_SERVER['REQUEST_URI']);
        $httpHost    = $this->sanitize->sanitizeString($_SERVER['HTTP_HOST']);

        $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $httpHost . $requestPath;

        $parsed_url = parse_url($url);
        return isset($parsed_url['path']) ? $parsed_url['path'] : '';
    }
}
