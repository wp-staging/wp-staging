<?php

namespace WPStaging\Staging\Dto\Service;

class RowsCopierDto
{
    /** @var int */
    protected $tableIndex = 0;

    /** @var string */
    protected $srcTable = '';

    /** @var string */
    protected $destTable = '';

    /** @var string|null */
    protected $numericPrimaryKey = null;

    /** @var int */
    protected $totalRows = 0;

    /** @var int */
    protected $rowsCopied = 0;

    /** @var int */
    protected $rowsOffset = 0;

    /** @var int */
    protected $lastInsertedNumericPrimaryKeyValue = -PHP_INT_MAX;

    /** @var bool */
    protected $locked = false;

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

    public function init(int $tableIndex, string $srcTable, string $destTable, int $totalRows)
    {
        $this->reset();
        $this->tableIndex = $tableIndex;
        $this->srcTable   = $srcTable;
        $this->destTable  = $destTable;
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

    public function getSrcTable(): string
    {
        return $this->srcTable;
    }

    /**
     * @param string $srcTable
     * @return void
     */
    public function setSrcTable(string $srcTable)
    {
        $this->srcTable = $srcTable;
    }

    public function getDestTable(): string
    {
        return $this->destTable;
    }

    /**
     * @param string $destTable
     * @return void
     */
    public function setDestTable(string $destTable)
    {
        $this->destTable = $destTable;
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

    public function getRowsCopied(): int
    {
        return $this->rowsCopied;
    }

    /**
     * @param int $rowsCopied
     * @return void
     */
    public function setRowsCopied(int $rowsCopied)
    {
        $this->rowsCopied = $rowsCopied;
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
        return $this->rowsCopied >= $this->totalRows;
    }
}
