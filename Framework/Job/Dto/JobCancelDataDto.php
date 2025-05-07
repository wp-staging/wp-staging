<?php

namespace WPStaging\Framework\Job\Dto;

class JobCancelDataDto extends JobDataDto
{
    /**
     * @var string
     */
    private $type = '';

    /**
     * @var string
     */
    private $jobIdBeingCancelled = '';

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type)
    {
        $this->type = $type;
    }

    public function getJobIdBeingCancelled(): string
    {
        return $this->jobIdBeingCancelled;
    }

    public function setJobIdBeingCancelled(string $jobIdBeingCancelled)
    {
        $this->jobIdBeingCancelled = $jobIdBeingCancelled;
    }
}
