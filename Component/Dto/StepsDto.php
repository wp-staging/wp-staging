<?php

// TODO PHP7.x declare(strict_types=1);
// TODO PHP7.x type-hints & return types

namespace WPStaging\Component\Dto;

class StepsDto extends AbstractDto
{
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
        $this->total = (int) $total;
    }

    /**
     * @return int
     */
    public function getCurrent()
    {
        return $this->current;
    }

    /**
     * @param int $current
     */
    public function setCurrent($current)
    {
        $this->current = (int) $current;
    }

    /**
     * @return int
     */
    public function getPercentage()
    {
        if ($this->total < 1) {
            return 100;
        }

        $percentage = (int) round(($this->current / $this->total) * 100);
        return ($percentage > 100) ? 100 : $percentage;
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

    public function finish()
    {
        $this->current = $this->total;
    }
}
