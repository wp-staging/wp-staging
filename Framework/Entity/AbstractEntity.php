<?php

//TODO PHP7.x; declare(strict_types=1);

namespace WPStaging\Framework\Entity;

use Serializable;
use JsonSerializable;
use WPStaging\Framework\Interfaces\ArrayableInterface;
use WPStaging\Framework\Interfaces\HydrateableInterface;
use WPStaging\Framework\Traits\ArrayableTrait;
use WPStaging\Framework\Traits\HydrateTrait;

abstract class AbstractEntity implements Serializable, JsonSerializable, ArrayableInterface, HydrateableInterface
{
    use ArrayableTrait;
    use HydrateTrait;

    /**
     * @inheritDoc
     */
    public function serialize()
    {
        return serialize($this->toArray());
    }

    /**
     * @inheritDoc
     */
    public function unserialize($serialized)
    {
        $this->hydrate(unserialize($serialized));
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
