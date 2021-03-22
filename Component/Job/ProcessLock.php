<?php

// TODO PHP7.x; declare(strict_type=1);

namespace WPStaging\Component\Job;

use DateTime;
use Exception;
use WPStaging\Core\Utils\Cache;
use WPStaging\Core\WPStaging;

class ProcessLock
{
    /** @var Cache */
    private $cache;

    /** @var array */
    private $options;

    public function __construct()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->cache = new Cache(-1, WPStaging::getContentDir());
        // TODO RPoC; DTO
        $this->options = $this->cache->get('clone_options');
    }

    public function isRunning()
    {
        if (!$this->options || !isset($this->options->isRunning, $this->options->expiresAt)) {
            return false;
        }

        try {
            $now = new DateTime;
            $expiresAt = new DateTime($this->options->expiresAt);
            return $this->options->isRunning === true && $now < $expiresAt;
        }
        catch (Exception $e) {
            return false;
        }
    }

    /** @noinspection PhpUnhandledExceptionInspection */
    public function restart()
    {
        // TODO RPoC
        unset($this->options->isRunning);
        $this->cache->delete('clone_options');
        $this->cache->delete('files_to_copy');
    }
}
