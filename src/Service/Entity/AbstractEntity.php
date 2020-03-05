<?php

//TODO PHP7.x; declare(strict_types=1);

namespace WPStaging\Service\Entity;

use Serializable;
use JsonSerializable;
use WPStaging\Service\Interfaces\ArrayableInterface;
use WPStaging\Service\Interfaces\HydrateableInterface;
use WPStaging\Service\Traits\ArrayableTrait;
use WPStaging\Service\Traits\HydrateTrait;

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
