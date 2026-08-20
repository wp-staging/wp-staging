<?php

 
 

namespace WPStaging\Framework\Job\Dto;

class StepsDto extends AbstractDto
{
 
    private $total;

 
    private $current;

 
    private $manualPercentage;




    public function getTotal()
    {
        return $this->total;
    }




    public function setTotal($total)
    {
        $this->total = (int) $total;
    }




    public function getCurrent()
    {
        return $this->current;
    }




    public function setCurrent($current)
    {
        $this->current = (int) $current;
    }










    public function setManualPercentage($manualPercentage)
    {
        $this->manualPercentage = (int)$manualPercentage;
    }




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
