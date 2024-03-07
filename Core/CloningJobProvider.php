<?php

namespace WPStaging\Core;

use WPStaging\Backend\Modules\Jobs\Cloning;

class CloningJobProvider
{
    /** @var Cloning */
    private $cloningJob;

    public function __construct(Cloning $cloningJob)
    {
        $this->cloningJob = $cloningJob;
    }

    /**
     * @return Cloning
     */
    public function getCloningJob(): Cloning
    {
        return $this->cloningJob;
    }
}
