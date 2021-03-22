<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types

namespace WPStaging\Command\Database\Snapshot;

class SnapshotDto
{
    const SNAPSHOT_DEFAULT_NAME = 'snapshot';

    /** @var string */
    private $name;

    /** @var string|null */
    private $notes;

    /** @var string */
    private $targetPrefix;

    /** @var string|null */
    private $sourcePrefix;

    /** @var int|null */
    private $step;

    /** @var bool */
    private $isSaveRecords = true;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name ?: self::SNAPSHOT_DEFAULT_NAME;
    }

    /**
     * @param string|null $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getNotes()
    {
        return $this->notes ?: null;
    }

    /**
     * @param string|null $notes
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;
    }

    /**
     * @return string
     */
    public function getTargetPrefix()
    {
        return $this->targetPrefix;
    }

    /**
     * @param string $targetPrefix
     */
    public function setTargetPrefix($targetPrefix)
    {
        $this->targetPrefix = $targetPrefix;
    }

    /**
     * @return string|null
     */
    public function getSourcePrefix()
    {
        return $this->sourcePrefix;
    }

    /**
     * @param string|null $sourcePrefix
     */
    public function setSourcePrefix($sourcePrefix)
    {
        $this->sourcePrefix = $sourcePrefix;
    }

    /**
     * @return int|null
     */
    public function getStep()
    {
        return $this->step;
    }

    /**
     * @param int|null $step
     */
    public function setStep($step)
    {
        $this->step = $step;
    }

    /**
     * @return bool
     */
    public function isSaveRecords()
    {
        return $this->isSaveRecords;
    }

    /**
     * @param bool $isSaveRecords
     */
    public function setIsSaveRecords($isSaveRecords)
    {
        $this->isSaveRecords = (bool) $isSaveRecords;
    }
}

