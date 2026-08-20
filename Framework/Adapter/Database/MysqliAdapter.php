<?php

 

namespace WPStaging\Framework\Adapter\Database;

class MysqliAdapter implements InterfaceDatabaseClient
{
 
    public $link;






    public function __construct($link = null)
    {
        $this->link = $link;
    }





    public function query($query)
    {
        return mysqli_query($this->link, $query);
    }






    public function realQuery($query, $isExecOnly = false)
    {
        if ($isExecOnly) {
            return mysqli_real_query($this->link, $query);
        }

        if (!mysqli_real_query($this->link, $query)) {
            return false;
        }

        if (defined('MYSQLI_STORE_RESULT_COPY_DATA')) {
            return mysqli_store_result($this->link, MYSQLI_STORE_RESULT_COPY_DATA);
        }

        return mysqli_store_result($this->link);
    }





    public function escape($input)
    {
        return mysqli_real_escape_string($this->link, $input);
    }




    public function errno()
    {
        return mysqli_errno($this->link);
    }




    public function error()
    {
        return mysqli_error($this->link);
    }




    public function version()
    {
        return mysqli_get_server_info($this->link);
    }





    public function fetchAll($result)
    {
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }

        return $data;
    }





    public function fetchAssoc($result)
    {
        return mysqli_fetch_assoc($result);
    }





    public function fetchRow($result)
    {
        return mysqli_fetch_row($result);
    }





    public function fetchObject($result)
    {
        return mysqli_fetch_object($result);
    }





    public function numRows($result)
    {
        return mysqli_num_rows($result);
    }





    public function freeResult($result)
    {
        if ($result === null) {
            return null;
        }


        mysqli_free_result($result);
        return null;
    }




    public function insertId()
    {
        return mysqli_insert_id($this->link);
    }




    public function foundRows()
    {
        return mysqli_affected_rows($this->link);
    }




    public function getLink()
    {
        return $this->link;
    }
}
