<?php

namespace WPStaging\Framework\Job\Interfaces;

interface FileTaskInterface
{
    /** @return string */
    public static function getTaskTitle();

    /** @return void */
    public function retryLastActionInNextRequest();

    /** @return bool */
    public function isThreshold();
}
