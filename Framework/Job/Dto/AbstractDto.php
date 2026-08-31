<?php

 
 

namespace WPStaging\Framework\Job\Dto;

use JsonSerializable;
use Serializable;
use WPStaging\Framework\Interfaces\ArrayableInterface;
use WPStaging\Framework\Traits\ArrayableTrait;
use WPStaging\Framework\Traits\HydrateTrait;

abstract class AbstractDto implements JsonSerializable, Serializable, ArrayableInterface
{
    use ArrayableTrait;
    use HydrateTrait;




    public function serialize()
    {
        return serialize($this->toArray());
    }

    public function __serialize()
    {
        return $this->toArray();
    }




    public function unserialize($serialized)
    {
        $this->hydrate((array)unserialize($serialized, ['allowed_classes' => [\stdClass::class]]));
    }

    public function __unserialize($serialized)
    {
        return $this->hydrate($serialized);
    }





    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
