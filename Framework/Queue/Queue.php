<?php

 
 

namespace WPStaging\Framework\Queue;

use WPStaging\Framework\Queue\Storage\CacheStorage;
use WPStaging\Framework\Queue\Storage\StorageInterface;
use WPStaging\Framework\Job\Task\AbstractTask;





class Queue implements QueueInterface
{
 
    private $name;

 
    private $storage;




    public function setName($name)
    {
        $this->name = $name;
        $this->init();
    }




    public function getName()
    {
        return $this->name;
    }




    public function setStorage(StorageInterface $storage)
    {
        $this->storage = $storage;
        $this->init();

        return $this;
    }




    public function getStorage()
    {
        return $this->storage;
    }




    public function count()
    {
        return $this->storage->count();
    }






    public function current()
    {
        return $this->storage->current();
    }




    public function pop()
    {
        return $this->storage->first();
    }

    public function last()
    {
        return $this->storage->last();
    }




    public function push($value)
    {
        $this->storage->append($value);
    }




    public function pushAsArray(array $value = [])
    {
        foreach ($value as $item) {
            $this->storage->append($item);
        }
    }




    public function prepend($value)
    {
        $this->storage->prepend($value);
    }

    protected function init()
    {
        if (!$this->name || !$this->storage) {
            return;
        }

        $this->storage->setKey($this->name);
    }




    public function reset()
    {
        $this->storage->reset();
    }




    public function reverse()
    {
        $this->storage->reverse();
    }




    public function save()
    {
        $this->storage->commit();
    }
}
