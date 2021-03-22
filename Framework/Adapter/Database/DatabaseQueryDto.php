<?php

namespace WPStaging\Framework\Adapter\Database;

class DatabaseQueryDto
{
    /** @var string */
    private $tableName;

    /** @var array */
    private $data = [];

    /** @var array  */
    private $dataValueMap = [];

    /** @var array  */
    private $conditions = [];

    /** @var array  */
    private $conditionsValueMap = [];

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    public function setData(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getDataValueMap()
    {
        return $this->dataValueMap;
    }

    public function setDataValueMap(array $dataValueMap = [])
    {
        $this->dataValueMap = $dataValueMap;
    }

    /**
     * @return array
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    public function setConditions(array $conditions = [])
    {
        $this->conditions = $conditions;
    }

    /**
     * @return array
     */
    public function getConditionsValueMap()
    {
        return $this->conditionsValueMap;
    }

    public function setConditionsValueMap(array $conditionsValueMap = [])
    {
        $this->conditionsValueMap = $conditionsValueMap;
    }
}
