<?php

namespace WPStaging\Framework\Job\Dto;

abstract class AbstractTaskDto extends AbstractDto
{



    public function unserialize($serialized)
    {
        $this->hydrateProperties((array)unserialize($serialized, ['allowed_classes' => [\stdClass::class]]));
    }

    public function __unserialize($serialized)
    {
        return $this->hydrateProperties($serialized);
    }
}
