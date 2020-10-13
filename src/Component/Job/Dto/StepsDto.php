<?php

namespace WPStaging\Component\Job\Dto;

use WPStaging\Framework\Traits\ArrayableTrait;
use WPStaging\Framework\Traits\HydrateTrait;

class StepsDto
{
    use ArrayableTrait;
    use HydrateTrait;

    /** @var int */
    private $total;

    /** @var int */
    private $current;

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
        // TODO PHP7.0; type-hint then no need casting
        $this->total = (int)$total;
    }

    /**
     * @return int
     */
    public function getCurrent()
    {
        return $this->current;
    }

    /**
     * @noinspection PhpUnused
     * @param int $current
     */
    public function setCurrent($current)
    {
        // TODO PHP7.0; type-hint then no need casting
        $this->current = (int)$current;
    }

    /**
     * @return int
     */
    public function getPercentage()
    {
        if ($this->total < 1) {
            return 100;
        }

        $percentage = (int)round(($this->current / $this->total) * 100);
        return (100 < $percentage) ? 100 : $percentage;
    }

    public function incrementCurrentStep()
    {
        if ($this->current < $this->total) {
            $this->current++;
        }
    }

    public function isFinished()
    {
        return $this->total <= $this->current;
    }
}
