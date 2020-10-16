<?php

namespace WPStaging\Component\Job;

use WPStaging\Component\Dto\AbstractDto;

class QueueJobDto extends AbstractDto
{
    /** @var string|int|null */
    protected $id;

    /** @var bool */
    protected $init;

    /** @var bool */
    protected $finished;

    /** @var bool */
    protected $statusCheck;

    /** @var string|null */
    protected $currentStatusTitle;

    /**
     * @return string|int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string|int|null $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return bool
     */
    public function isInit()
    {
        return $this->init;
    }

    /**
     * @param bool $init
     */
    public function setInit($init)
    {
        $this->init = $init;
    }

    /**
     * @return bool
     */
    public function isFinished()
    {
        return $this->finished;
    }

    /**
     * @param bool $finished
     */
    public function setFinished($finished)
    {
        $this->finished = $finished;
    }

    /**
     * @return bool
     */
    public function isStatusCheck()
    {
        return $this->statusCheck;
    }

    /**
     * @param bool $statusCheck
     */
    public function setStatusCheck($statusCheck)
    {
        $this->statusCheck = $statusCheck;
    }

    /**
     * @return string|null
     */
    public function getCurrentStatusTitle()
    {
        return $this->currentStatusTitle;
    }

    /**
     * @param string|null $currentStatusTitle
     */
    public function setCurrentStatusTitle($currentStatusTitle)
    {
        $this->currentStatusTitle = $currentStatusTitle;
    }
}