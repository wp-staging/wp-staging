<?php

// TODO PHP7.x declare(strict_types=1);
// TODO PHP7.x type-hints & return types

namespace WPStaging\Component\Job\Dto;

use WPStaging\Command\Database\Snapshot\SnapshotHandler;

// TODO Remove while refactoring Snapshots in the future; 1/2 step refactor
class SnapshotCreateDto
{
    const JOB_AUTOMATIC = 'automatic';
    const JOB_MANUAL = 'manual';

    /** @var string */
    private $name;

    /** @var string|null */
    private $notes;

    /** @var string */
    private $increment;

    /** @var string */
    private $job;

    public function hydrate(array $data = [])
    {
        $this->setName($data['name']);
        $this->setJob($data['job']);
        $this->setNotes($data['notes']?: null);
        $this->setIncrement($data['increment']?: null);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName($name = null)
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getNotes()
    {
        return $this->notes;
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
    public function getIncrement()
    {
        return $this->increment;
    }

    /**
     * @param string|null $increment
     */
    public function setIncrement($increment = null)
    {
        $this->increment = (int) $increment;
    }

    /**
     * @return string
     */
    public function getJobDbPrefix()
    {
        if (self::JOB_MANUAL === $this->job) {
            return SnapshotHandler::PREFIX_MANUAL . $this->increment . '_';
        }

        if (self::JOB_AUTOMATIC === $this->job) {
            return SnapshotHandler::PREFIX_AUTOMATIC . $this->increment . '_';
        }

        return $this->increment . '_';
    }

    /**
     * @return string
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * @param string $job
     */
    public function setJob($job)
    {
        $this->job = $job;
    }

    /**
     * @return array
     */
    public function getAvailableJobs()
    {
        return [
            self::JOB_AUTOMATIC,
            self::JOB_MANUAL,
        ];
    }
}
