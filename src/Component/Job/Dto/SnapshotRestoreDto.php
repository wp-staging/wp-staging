<?php

// TODO PHP7.x declare(strict_types=1);
// TODO PHP7.x type-hints & return types

namespace WPStaging\Component\Job\Dto;

class SnapshotRestoreDto
{
    /** @var string */
    private $prefix;

    /** @var bool */
    private $reset;

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param string $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * @return bool
     */
    public function isReset()
    {
        return $this->reset;
    }

    /**
     * @param bool $reset
     */
    public function setReset($reset)
    {
        $this->reset = $reset;
    }
}
