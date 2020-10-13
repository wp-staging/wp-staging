<?php

namespace WPStaging\Component\Job\Dto;

use DateTime;


class JobDto
{
    // TODO PHP7.0; constant visibility
    const STATUS_FINISHED = 'finished';
    const STATUS_PROCESSING = 'processing';
    const DEFAULT_STATUS = self::STATUS_PROCESSING;

    // TODO PHP7.0; constant visibility
    // TODO PHP5.6; constant arrays; const AVAILABLE_STATUSES = [];
    private $availableStatuses = [
        self::STATUS_FINISHED,
    ];

    // TODO; what is this?
    /** @var int */
    private $number;

    /** @var string */
    private $version;

    /** @var string */
    private $status;

    // TODO serialization problem, implement serializable when serializing this class
    /** @var DateTime */
    private $createdAt;

    /**
     * @return int
     * @noinspection PhpUnused
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param int $number
     * @noinspection PhpUnused
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param string $version
     * @noinspection PhpUnused
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getStatus()
    {
        // TODO PHP7.0; $this->status ?? self::DEFAULT_STATUS = clear condition check; isset && !null
        return $this->status?: self::DEFAULT_STATUS;
    }

    /**
     * @param string $status
     * @noinspection PhpUnused
     */
    public function setStatus($status)
    {
        if (!in_array($status, $this->availableStatuses, true)) {
            $status = self::DEFAULT_STATUS;
        }
        $this->status = $status;
    }

    /**
     * @return DateTime
     * @noinspection PhpUnused
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     * @noinspection PhpUnused
     */
    public function setCreatedAt(DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }
}
