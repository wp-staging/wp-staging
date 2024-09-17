<?php

// TODO PHP7.x declare(strict_types=1);
// TODO PHP7.x type-hints & return types

namespace WPStaging\Framework\Job\Dto;

class StepsDto extends AbstractDto
{
    /** @var int */
    private $total;

    /** @var int */
    private $current;

    /** @var int */
    private $manualPercentage;

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
     * Sometimes we can't know how many steps there will be in total,
     * so we can mimic an percentage using this method.
     *
     * For instance: FileScannerTask doesn't know how many total
     * steps it will have to process, so we can set a manual estimate.
     *
     * @param int $manualPercentage
     */
    public function setManualPercentage($manualPercentage)
    {
        $this->manualPercentage = (int)$manualPercentage;
    }

    /**
     * @return int
     */
    public function getPercentage()
    {
        if (!empty($this->manualPercentage)) {
            return $this->manualPercentage;
        }

        if ($this->total < 1) {
            return 100;
        }

        $percentage = (int) round(($this->current / $this->total) * 100);
        return max(0, min(100, $percentage));
    }

    public function incrementCurrentStep()
    {
        if ($this->current < $this->total) {
            $this->current++;
        }
    }

    public function decreaseCurrentStep()
    {
        if ($this->current > 0) {
            $this->current--;
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
