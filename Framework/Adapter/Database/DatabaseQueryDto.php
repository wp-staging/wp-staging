<?php

namespace WPStaging\Framework\Adapter\Database;

class DatabaseQueryDto
{
 
    private $tableName;

 
    private $data = [];

 
    private $dataValueMap = [];

 
    private $conditions = [];

 
    private $conditionsValueMap = [];




    public function getTableName()
    {
        return $this->tableName;
    }




    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }




    public function getData()
    {
        return $this->data;
    }

    public function setData(array $data = [])
    {
        $this->data = $data;
    }




    public function getDataValueMap()
    {
        return $this->dataValueMap;
    }

    public function setDataValueMap(array $dataValueMap = [])
    {
        $this->dataValueMap = $dataValueMap;
    }




    public function getConditions()
    {
        return $this->conditions;
    }

    public function setConditions(array $conditions = [])
    {
        $this->conditions = $conditions;
    }




    public function getConditionsValueMap()
    {
        return $this->conditionsValueMap;
    }

    public function setConditionsValueMap(array $conditionsValueMap = [])
    {
        $this->conditionsValueMap = $conditionsValueMap;
    }
}
