<?php

namespace WPStaging\Framework\Job\Dto\Database;

class RowsExporterDto
{
 
    protected $tableIndex = 0;

 
    protected $tableName = '';

 
    protected $numericPrimaryKey = null;

 
    protected $totalRows = 0;

 
    protected $totalRowsExported = 0;

 
    protected $rowsOffset = 0;






    protected $lastInsertedNumericPrimaryKeyValue = -PHP_INT_MAX;

 
    protected $locked = false;

    public function reset()
    {
        $this->tableIndex                         = 0;
        $this->tableName                          = '';
        $this->totalRows                          = 0;
        $this->rowsOffset                         = 0;
        $this->locked                             = false;
        $this->numericPrimaryKey                  = null;
        $this->lastInsertedNumericPrimaryKeyValue = -PHP_INT_MAX;
    }

    public function init(int $tableIndex, string $tableName, int $totalRows)
    {
        $this->reset();
        $this->tableIndex = $tableIndex;
        $this->tableName  = $tableName;
        $this->totalRows  = $totalRows;
    }

    public function getTableIndex(): int
    {
        return $this->tableIndex;
    }





    public function setTableIndex(int $tableIndex)
    {
        $this->tableIndex = $tableIndex;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }





    public function setTableName(string $tableName)
    {
        $this->tableName = $tableName;
    }




    public function getNumericPrimaryKey()
    {
        return $this->numericPrimaryKey;
    }





    public function setNumericPrimaryKey($numericPrimaryKey)
    {
        $this->numericPrimaryKey = $numericPrimaryKey;
    }

    public function getTotalRows(): int
    {
        return $this->totalRows;
    }





    public function setTotalRows(int $totalRows)
    {
        $this->totalRows = $totalRows;
    }

    public function getTotalRowsExported(): int
    {
        return $this->totalRowsExported;
    }





    public function setTotalRowsExported(int $rowsExported)
    {
        $this->totalRowsExported = $rowsExported;
    }

    public function getRowsOffset(): int
    {
        return $this->rowsOffset;
    }





    public function setRowsOffset(int $rowsOffset)
    {
        $this->rowsOffset = $rowsOffset;
    }

    public function getLastInsertedNumericPrimaryKeyValue(): int
    {
        return $this->lastInsertedNumericPrimaryKeyValue;
    }





    public function setLastInsertedNumericPrimaryKeyValue(int $lastInsertedNumericPrimaryKeyValue)
    {
        $this->lastInsertedNumericPrimaryKeyValue = $lastInsertedNumericPrimaryKeyValue;
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }





    public function setLocked(bool $locked)
    {
        $this->locked = $locked;
    }




    public function isFinished(): bool
    {
        return $this->rowsOffset >= $this->totalRows;
    }
}
