<?php

//TODO PHP7.x; declare(strict_types=1);

namespace WPStaging\Framework\Collection;

use SplObjectStorage;
use JsonSerializable;
use WPStaging\Framework\Interfaces\ArrayableInterface;
use WPStaging\Framework\Interfaces\HydrateableInterface;

class Collection extends SplObjectStorage implements JsonSerializable
{
    /** @var string */
    protected $storedClass;

    /**
     * @param string $storedClass
     */
    public function __construct($storedClass)
    {
        $this->storedClass = $storedClass;
    }

    public function toArray()
    {
        $collection = [];
        /** @var ArrayableInterface $item */
        foreach ($this as $item) {
            if (method_exists($item, 'toArray')) {
                $collection[] = $item->toArray();
            } else {
                $collection[] = $item;
            }
        }

        return $collection;
    }

    public function attachAllByArray(array $data = [])
    {
        foreach ($data as $item) {
            if ($item instanceof $this->storedClass) {
                $this->attach($item);
                continue;
            }

            /** @var HydrateableInterface $object */
            $object = new $this->storedClass();
            $object->hydrate((array) $item);
            $this->attach($object);
        }
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
