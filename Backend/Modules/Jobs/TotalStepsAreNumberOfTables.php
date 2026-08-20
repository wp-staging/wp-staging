<?php

namespace WPStaging\Backend\Modules\Jobs;

trait TotalStepsAreNumberOfTables
{




    protected function calculateTotalSteps()
    {
        $this->options->totalSteps = $this->total === 0 ? 1 : $this->total;
    }
}
