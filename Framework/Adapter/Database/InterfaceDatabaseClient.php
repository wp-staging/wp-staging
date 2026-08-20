<?php

 

namespace WPStaging\Framework\Adapter\Database;

interface InterfaceDatabaseClient
{





    public function query($query);







    public function realQuery($query, $isExecOnly = false);






    public function escape($input);





    public function errno();





    public function error();





    public function version();






    public function fetchAll($result);






    public function fetchAssoc($result);






    public function fetchRow($result);






    public function fetchObject($result);






    public function numRows($result);






    public function freeResult($result);







    public function insertId();












    public function foundRows();




    public function getLink();
}
