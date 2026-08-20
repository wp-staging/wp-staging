<?php

namespace WPStaging\Framework\Job\Dto\Task;

use WPStaging\Framework\Job\Dto\AbstractTaskDto;
use WPStaging\Framework\Job\Dto\Database\RowsExporterDto;

class RowsExporterTaskDto extends AbstractTaskDto
{
 
    public $tableIndex = 0;

 
    public $tableName = '';

 
    public $numericPrimaryKey = null;

 
    public $totalRows = 0;

 
    public $totalRowsExported = 0;

 
    public $rowsOffset = 0;

 
    public $lastInsertedNumericPrimaryKeyValue = -PHP_INT_MAX;

 
    public $locked = false;

    public function toRowsExporterDto(): RowsExporterDto
    {
        $rowsExporterDto = new RowsExporterDto();
        $rowsExporterDto->init($this->tableIndex, $this->tableName, $this->totalRows);

        $rowsExporterDto->setNumericPrimaryKey($this->numericPrimaryKey);
        $rowsExporterDto->setTotalRowsExported($this->totalRowsExported);
        $rowsExporterDto->setRowsOffset($this->rowsOffset);
        $rowsExporterDto->setLastInsertedNumericPrimaryKeyValue($this->lastInsertedNumericPrimaryKeyValue);
        $rowsExporterDto->setLocked($this->locked);

        return $rowsExporterDto;
    }





    public function fromRowExporterDto(RowsExporterDto $rowsExporterDto)
    {
        $this->tableIndex                         = $rowsExporterDto->getTableIndex();
        $this->tableName                          = $rowsExporterDto->getTableName();
        $this->numericPrimaryKey                  = $rowsExporterDto->getNumericPrimaryKey();
        $this->totalRows                          = $rowsExporterDto->getTotalRows();
        $this->totalRowsExported                  = $rowsExporterDto->getTotalRowsExported();
        $this->rowsOffset                         = $rowsExporterDto->getRowsOffset();
        $this->locked                             = $rowsExporterDto->isLocked();
        $this->lastInsertedNumericPrimaryKeyValue = $rowsExporterDto->getLastInsertedNumericPrimaryKeyValue();
    }

    public function reset()
    {
        $this->tableIndex                         = 0;
        $this->tableName                          = '';
        $this->rowsOffset                         = 0;
        $this->locked                             = false;
        $this->numericPrimaryKey                  = null;
        $this->lastInsertedNumericPrimaryKeyValue = -PHP_INT_MAX;
    }
}
