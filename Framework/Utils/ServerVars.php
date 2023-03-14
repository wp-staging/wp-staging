<?php

namespace WPStaging\Framework\Utils;

use WPStaging\Framework\Utils\Sanitize as UtilsSanitize;

class ServerVars
{
    /** @var UtilsSanitize */
    private $sanitize;

    /** @var string */
    private $serverSoftware = null;

    public function __construct(UtilsSanitize $sanitize)
    {
        $this->sanitize = $sanitize;
    }

    /** @return string */
    public function getServerSoftware()
    {
        if ($this->serverSoftware === null) {
            $this->serverSoftware = isset($_SERVER['SERVER_SOFTWARE']) ? $this->sanitize->sanitizeString($_SERVER['SERVER_SOFTWARE']) : '';
        }

        return $this->serverSoftware;
    }

    /**
     * @param int $seconds
     */
    public function setTimeLimit($seconds = 0)
    {
        // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.safe_modeDeprecatedRemoved
        if (!in_array("set_time_limit", explode(',', ini_get("disable_functions")))) {
            set_time_limit($seconds);
        }
    }

    /**
     * @return bool
     */
    public function isApache()
    {
        return stripos($this->getServerSoftware(), 'apache') === 0; 
    }

    /**
     * @return bool
     */
    public function isLitespeed()
    {
        return stripos($this->getServerSoftware(), 'LiteSpeed') === 0; 
    }
}
