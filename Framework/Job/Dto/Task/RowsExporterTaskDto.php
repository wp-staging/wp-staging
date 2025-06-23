<?php

namespace WPStaging\Framework\Job\Dto\Task;

use WPStaging\Framework\Job\Dto\AbstractTaskDto;
use WPStaging\Framework\Job\Dto\Database\RowsExporterDto;

class RowsExporterTaskDto extends AbstractTaskDto
{
    /** @var int */
    public $tableIndex = 0;

    /** @var string */
    public $tableName = '';

    /** @var string|null */
    public $numericPrimaryKey = null;

    /** @var int */
    public $totalRows = 0;

    /** @var int */
    public $totalRowsExported = 0;

    /** @var int */
    public $rowsOffset = 0;

    /** @var int */
    public $lastInsertedNumericPrimaryKeyValue = -PHP_INT_MAX;

    /** @var bool */
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

    /**
     * @param RowsExporterDto $rowsExporterDto
     * @return void
     */
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
