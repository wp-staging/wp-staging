<?php

namespace WPStaging\Core;

use WPStaging\Backend\Modules\Jobs\Cloning;

class CloningJobProvider
{
 
    private $cloningJob;

    public function __construct(Cloning $cloningJob)
    {
        $this->cloningJob = $cloningJob;
    }




    public function getCloningJob(): Cloning
    {
        return $this->cloningJob;
    }
}
