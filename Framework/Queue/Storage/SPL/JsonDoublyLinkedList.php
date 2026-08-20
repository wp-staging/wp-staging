<?php

namespace WPStaging\Framework\Queue\Storage\SPL;

use WPStaging\Framework\Interfaces\ArrayableInterface;

class JsonDoublyLinkedList extends \SplDoublyLinkedList implements \JsonSerializable, ArrayableInterface
{



    public function hydrate($jsonData)
    {
        foreach (json_decode($jsonData, true) as &$item) {
            $this->push($item);
            unset($item);
        }
    }

    public function toArray()
    {
        $data = [];

        while (!$this->isEmpty()) {
            $data[] = $this->shift();
        }

        return $data;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
