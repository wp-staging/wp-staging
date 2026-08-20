<?php

 
 

namespace WPStaging\Framework\Queue\Storage;

use WPStaging\Framework\Utils\Cache\AbstractCache;

class ArrayStorage implements StorageInterface
{
 
    private $items;

    public function commit()
    {
        \WPStaging\functions\debug_log('ArrayStorage does not implement commit.');
    }





    public function setKey($key)
    {
        \WPStaging\functions\debug_log('ArrayStorage does not implement setKey.');

        return $this;
    }




    public function count()
    {
        return count((array) $this->items);
    }




    public function append($value)
    {
        $this->items[] = $value;
    }




    public function prepend($value)
    {
        array_unshift($this->items, $value);
    }




    public function first()
    {
        return array_shift($this->items);
    }




    public function last()
    {
        return array_pop($this->items);
    }




    public function reset()
    {
        $this->items = [];
    }




    public function getCache()
    {
        return null;
    }
    public function reverse()
    {
        $this->items = array_reverse($this->items);

        return $this->items;
    }

    public function current()
    {
        return current($this->items);
    }
}
