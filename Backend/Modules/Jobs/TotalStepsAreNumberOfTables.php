<?php

namespace WPStaging\Backend\Modules\Jobs;

trait TotalStepsAreNumberOfTables
{
    /**
     * Calculate Total Steps in This Job and Assign It to $this->options->totalSteps
     * @return void
     */
    protected function calculateTotalSteps()
    {
        $this->options->totalSteps = $this->total === 0 ? 1 : $this->total;
    }
}
