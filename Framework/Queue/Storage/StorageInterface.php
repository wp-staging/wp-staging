<?php

 
 

namespace WPStaging\Framework\Queue\Storage;

use WPStaging\Framework\Utils\Cache\AbstractCache;

interface StorageInterface
{







    public function setKey($key);






    public function commit();





    public function count();








    public function append($value);








    public function prepend($value);





    public function first();





    public function current();





    public function last();




    public function reset();




    public function reverse();




    public function getCache();
}
