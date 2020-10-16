<?php

namespace WPStaging\Component\Job;

use WPStaging\Component\Task\TaskResponseDto;

interface JobInterface
{
    /**
     * @return TaskResponseDto
     */
    public function execute();
}
