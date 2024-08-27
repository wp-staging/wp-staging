<?php

namespace WPStaging\Framework\Job\Dto;

abstract class AbstractTaskDto extends AbstractDto
{
    /**
     * @inheritDoc
     */
    public function unserialize($serialized)
    {
        $this->hydrateProperties(unserialize($serialized));
    }

    public function __unserialize($serialized)
    {
        return $this->hydrateProperties($serialized);
    }
}
