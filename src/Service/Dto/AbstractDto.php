<?php

//TODO PHP7.x; declare(strict_types=1);


namespace WPStaging\Service\Dto;


use JsonSerializable;
use WPStaging\Service\Traits\ArrayableTrait;

abstract class AbstractDto implements JsonSerializable
{
    use ArrayableTrait;

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
