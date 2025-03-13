<?php

namespace WPStaging\Staging\Dto\Task;

use WPStaging\Framework\Job\Dto\AbstractTaskDto;
use WPStaging\Staging\Dto\Service\RowsCopierDto;

class DatabaseRowsCopyTaskDto extends AbstractTaskDto
{
    /** @var int */
    public $tableIndex = 0;

    /** @var string */
    public $srcTable = '';

    /** @var string */
    public $destTable = '';

    /** @var string|null */
    public $numericPrimaryKey = null;

    /** @var int */
    public $totalRows = 0;

    /** @var int */
    public $rowsCopied = 0;

    /** @var int */
    public $rowsOffset = 0;

    /** @var int */
    public $lastInsertedNumericPrimaryKeyValue = -PHP_INT_MAX;

    /** @var bool */
    public $locked = false;

    public function toRowCopierDto(): RowsCopierDto
    {
        $rowsCopierDto = new RowsCopierDto();
        $rowsCopierDto->init($this->tableIndex, $this->srcTable, $this->destTable, $this->totalRows);

        $rowsCopierDto->setNumericPrimaryKey($this->numericPrimaryKey);
        $rowsCopierDto->setRowsCopied($this->rowsCopied);
        $rowsCopierDto->setRowsOffset($this->rowsOffset);
        $rowsCopierDto->setLastInsertedNumericPrimaryKeyValue($this->lastInsertedNumericPrimaryKeyValue);
        $rowsCopierDto->setLocked($this->locked);

        return $rowsCopierDto;
    }

    /**
     * @param RowsCopierDto $rowsCopierDto
     * @return void
     */
    public function fromRowCopierDto(RowsCopierDto $rowsCopierDto)
    {
        $this->tableIndex        = $rowsCopierDto->getTableIndex();
        $this->srcTable          = $rowsCopierDto->getSrcTable();
        $this->destTable         = $rowsCopierDto->getDestTable();
        $this->numericPrimaryKey = $rowsCopierDto->getNumericPrimaryKey();
        $this->totalRows         = $rowsCopierDto->getTotalRows();
        $this->rowsCopied        = $rowsCopierDto->getRowsCopied();
        $this->rowsOffset        = $rowsCopierDto->getRowsOffset();
        $this->locked            = $rowsCopierDto->isLocked();

        $this->lastInsertedNumericPrimaryKeyValue = $rowsCopierDto->getLastInsertedNumericPrimaryKeyValue();
    }

    public function reset()
    {
        $this->tableIndex = 0;
        $this->srcTable   = '';
        $this->destTable  = '';
        $this->totalRows  = 0;
        $this->rowsCopied = 0;
        $this->rowsOffset = 0;
        $this->locked     = false;

        $this->numericPrimaryKey = null;
        $this->lastInsertedNumericPrimaryKeyValue = -PHP_INT_MAX;
    }
}
