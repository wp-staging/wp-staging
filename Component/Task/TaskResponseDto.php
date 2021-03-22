<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types

namespace WPStaging\Component\Task;

use WPStaging\Component\Dto\AbstractDto;
use WPStaging\Framework\Traits\ArrayableTrait;

class TaskResponseDto extends AbstractDto
{
    use ArrayableTrait {
        toArray as traitToArray;
    }

    /** @var boolean */
    protected $status;

    /** @var int */
    protected $percentage;

    /** @var int */
    protected $total;

    /** @var int */
    protected $step;

    /** @var string */
    protected $task;

    /** @var string */
    protected $job;

    /** @var string */
    protected $statusTitle;

    /** @var array */
    protected $messages;

    /** @var float */
    protected $runTime;

    public function toArray()
    {
        $data = $this->traitToArray();

        $lastMsg = null;
        if ($data['messages']) {
            $lastMsg = end($data['messages']);
        }

        // TODO REF: Remove
        $data['last_msg'] = $lastMsg;
        $data['running_time'] = $data['runTime']?: 0;
        $data['isForceSave'] = true;
        $data['job_done'] = $data['status'];

        return $data;
    }

    public function addMessage($message)
    {
        if (!is_array($this->messages)) {
            $this->messages = [];
        }
        $this->messages[] = $message;
    }

    /**
     * @return bool
     */
    public function isStatus()
    {
        return $this->status;
    }

    /**
     * @param bool $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getPercentage()
    {
        return $this->percentage;
    }

    /**
     * @param int $percentage
     */
    public function setPercentage($percentage)
    {
        $this->percentage = $percentage;
    }

    /**
     * @return int
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @param int $total
     */
    public function setTotal($total)
    {
        $this->total = $total;
    }

    /**
     * @return int
     */
    public function getStep()
    {
        return $this->step;
    }

    /**
     * @param int $step
     */
    public function setStep($step)
    {
        $this->step = $step;
    }

    /**
     * @return string
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * @param string $task
     */
    public function setTask($task)
    {
        $this->task = $task;
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
     * @return string
     */
    public function getStatusTitle()
    {
        return $this->statusTitle;
    }

    /**
     * @param string $statusTitle
     */
    public function setStatusTitle($statusTitle)
    {
        $this->statusTitle = $statusTitle;
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    public function setMessages(array $messages)
    {
        $this->messages = $messages;
    }

    /**
     * @return float
     */
    public function getRunTime()
    {
        return $this->runTime;
    }

    /**
     * @param float $runTime
     */
    public function setRunTime($runTime)
    {
        $this->runTime = $runTime;
    }
}