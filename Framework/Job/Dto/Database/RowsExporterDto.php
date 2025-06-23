<?php

namespace WPStaging\Framework\Job\Dto\Database;

class RowsExporterDto
{
    /** @var int */
    protected $tableIndex = 0;

    /** @var string */
    protected $tableName = '';

    /** @var string|null */
    protected $numericPrimaryKey = null;

    /** @var int */
    protected $totalRows = 0;

    /** @var int */
    protected $totalRowsExported = 0;

    /** @var int */
    protected $rowsOffset = 0;

    /**
     * @var int
     * We are starting with -PHP_INT_MAX to ensure that the first inserted value is always greater than this.
     * Because some tables can contain negative values, we cannot start with 0.
     */
    protected $lastInsertedNumericPrimaryKeyValue = -PHP_INT_MAX;

    /** @var bool */
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

    /**
     * @param int $tableIndex
     * @return void
     */
    public function setTableIndex(int $tableIndex)
    {
        $this->tableIndex = $tableIndex;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     * @return void
     */
    public function setTableName(string $tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * @return string|null
     */
    public function getNumericPrimaryKey()
    {
        return $this->numericPrimaryKey;
    }

    /**
     * @param string|null $numericPrimaryKey
     * @return void
     */
    public function setNumericPrimaryKey($numericPrimaryKey)
    {
        $this->numericPrimaryKey = $numericPrimaryKey;
    }

    public function getTotalRows(): int
    {
        return $this->totalRows;
    }

    /**
     * @param int $totalRows
     * @return void
     */
    public function setTotalRows(int $totalRows)
    {
        $this->totalRows = $totalRows;
    }

    public function getTotalRowsExported(): int
    {
        return $this->totalRowsExported;
    }

    /**
     * @param int $rowsExported
     * @return void
     */
    public function setTotalRowsExported(int $rowsExported)
    {
        $this->totalRowsExported = $rowsExported;
    }

    public function getRowsOffset(): int
    {
        return $this->rowsOffset;
    }

    /**
     * @param int $rowsOffset
     * @return void
     */
    public function setRowsOffset(int $rowsOffset)
    {
        $this->rowsOffset = $rowsOffset;
    }

    public function getLastInsertedNumericPrimaryKeyValue(): int
    {
        return $this->lastInsertedNumericPrimaryKeyValue;
    }

    /**
     * @param int $lastInsertedNumericPrimaryKeyValue
     * @return void
     */
    public function setLastInsertedNumericPrimaryKeyValue(int $lastInsertedNumericPrimaryKeyValue)
    {
        $this->lastInsertedNumericPrimaryKeyValue = $lastInsertedNumericPrimaryKeyValue;
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    /**
     * @param bool $locked
     * @return void
     */
    public function setLocked(bool $locked)
    {
        $this->locked = $locked;
    }

    /**
     * @return bool
     */
    public function isFinished(): bool
    {
        return $this->rowsOffset >= $this->totalRows;
    }
}
