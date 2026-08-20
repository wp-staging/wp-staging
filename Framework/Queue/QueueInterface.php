<?php

 
 

namespace WPStaging\Framework\Queue;

use WPStaging\Framework\Queue\Storage\StorageInterface;

interface QueueInterface
{





    public function setName($name);




    public function getName();






    public function setStorage(StorageInterface $storage);




    public function getStorage();






    public function count();





    public function pop();






    public function push($value);





    public function pushAsArray(array $value = []);






    public function prepend($value);




    public function reset();




    public function save();
}
