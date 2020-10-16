<?php

//TODO PHP7.x; declare(strict_types=1);
//TODO PHP7.x; return types

namespace WPStaging\Framework\Dto;

use JsonSerializable;
use WPStaging\Framework\Traits\ArrayableTrait;

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
