<?php

namespace WPStaging\Framework\Job\Interfaces;

interface FileTaskInterface
{
 
    public static function getTaskTitle();

 
    public function retryLastActionInNextRequest();

 
    public function isThreshold();
}
