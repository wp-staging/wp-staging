<?php

/** @noinspection PhpComposerExtensionStubsInspection */

namespace WPStaging\Framework\Adapter\Database;

use Exception;
use WP_SQLite_Translator;

class SqliteAdapter implements InterfaceDatabaseClient
{
    /** @var WP_SQLite_Translator|null */
    public $link; // @phpstan-ignore-line

    /**
     * @var bool
     */
    public $isSQLite = true;

    /**
     * @var int
     */
    private $currentFetchAssocRowIndex = 0;

    /**
     * @var int
     */
    private $currentFetchRowIndex = 0;

    public function __construct($link = null)
    {
        $this->link = $link;
    }

    /**
     * @throws Exception
     */
    public function query($query)
    {
        return $this->link->query($query); // @phpstan-ignore-line
    }

    /**
     * @throws Exception
     */
    public function realQuery($query, $isExecOnly = false)
    {
        return $this->link->query($query); // @phpstan-ignore-line
    }

    public function escape($input)
    {
        $escapedString = $input;

        // Escape backslashes, single quotes, double quotes, and null characters
        $escapedString = str_replace(
            ["\\", "\x00", "\n", "\r", "'", '"', "\x1a"],
            ["\\\\", "\\0", "\\n", "\\r", "\\'", '\\"', "\\Z"],
            $escapedString
        );

        return $escapedString;
    }

    public function errno()
    {
        return 0;
    }

    /**
     * @throws Exception
     */
    public function error(): string
    {
        return $this->link->get_error_message(); // @phpstan-ignore-line
    }

    public function version()
    {
        return $this->link->versionString(); // @phpstan-ignore-line
    }

    public function fetchAll($result): array
    {
        $data = [];

        while ($row = $result) {
            $data[] = $row;
        }

        return $data;
    }

    public function fetchAssoc($result)
    {
        // @phpstan-ignore-next-line
        if (empty($result)) {
            return [];
        }

        // Convert result object to an array of rows if not already done
        $resultArray = [];

        foreach ($result as $row) {
            // Ensure each row is an associative array and store in result array
            if (is_object($row)) {
                $resultArray[] = (array)$row; // Convert each row to an associative array
            }
        }

        // Return the current row based on the index, or null if no more rows
        if (isset($resultArray[$this->currentFetchAssocRowIndex])) {
            // Fetch the current row and advance the counter
            $currentRow = $resultArray[$this->currentFetchAssocRowIndex];
            $this->currentFetchAssocRowIndex++;
            return $currentRow;
        } else {
            // Reset the counter if we reach the end of the result set
            $this->currentFetchAssocRowIndex = 0;
        }

        return [];
    }

    /**
     * Get the estimated average row length for a table in SQLite.
     *
     * @return float|null The estimated average row length, or null if the table is empty.
     */
    public function getAverageRowLengthSQLite(string $tableName)
    {
        // Get the number of rows in the table
        $rowCount = $this->link->get_pdo()->query("SELECT COUNT(1) FROM `" . $tableName . "`")->fetchColumn(0); // @phpstan-ignore-line

        // If the table is empty, return null to avoid division by zero
        if ($rowCount == 0) {
            return null;
        }

        $pageSize  = $this->getSQLitePageSize();
        $pageCount = $this->getSQLitePageCount();

        // Calculate the total size of the table in bytes
        $totalSize = $pageCount * $pageSize;

        // Calculate and return the average row length
        return $totalSize / $rowCount;
    }

    /**
     * @param $result
     * @return array|null
     */
    public function fetchRow($result)
    {
        // Convert result object to an array if not already done
        $resultArray = $this->castObjectToArrayRecursive($result);

        // Check if we have more rows to fetch
        if (isset($resultArray[$this->currentFetchRowIndex])) {
            // Get the current row
            $row = $resultArray[$this->currentFetchRowIndex];
            $this->currentFetchRowIndex++; // Advance the index

            // Return only the first value in an array format [$tableName]
            return [reset($row)];
        } else {
            // Reset index and return null when all rows are fetched
            $this->currentFetchRowIndex = 0;
            return null;
        }
    }

    /**
     * Recursively casts a nested object to an associative array.
     *
     * @param object $input The input object or array to cast.
     * @return array The resulting associative array.
     */
    private function castObjectToArrayRecursive($input): array
    {
        if (is_object($input)) {
            $input = get_object_vars($input); // Convert object properties to an associative array
        }

        if (is_array($input)) {
            foreach ($input as &$value) {
                // Recursively cast each element if it's an object or array
                if (is_object($value) || is_array($value)) {
                    $value = $this->castObjectToArrayRecursive($value);
                }
            }
        }

        return $input;
    }

    public function fetchObject($result)
    {
        // For SQLite, use PDO or SQLite3 fetch as an object
        if (is_array($result) && isset($result[0]) && is_object($result[0])) {
            return $result[0]; // Return the object at index 0
        }

        return null;
    }

    public function numRows($result): int
    {
        $count = 0;
        while ($result) {
            $count++;
        }
        return $count;
    }

    public function freeResult($result)
    {
        $this->currentFetchAssocRowIndex = 0;
        $this->currentFetchRowIndex      = 0;
        return null;
    }

    public function insertId()
    {
        return $this->link->lastInsertRowID(); // @phpstan-ignore-line
    }

    public function foundRows()
    {
        return $this->link->changes(); // @phpstan-ignore-line
    }

    public function getLink()
    {
        return $this->link; // @phpstan-ignore-line
    }

    public function getSQLitePageSize(): int
    {
        static $pageSize = null;

        if ($pageSize !== null) {
            return $pageSize;
        }

        // @phpstan-ignore-next-line
        $pageSize = $this->link->get_pdo()->query('PRAGMA page_size')->fetchColumn(0);
        return $pageSize;
    }

    public function getSQLitePageCount(): int
    {
        static $pageCount = null;

        if ($pageCount !== null) {
            return $pageCount;
        }

        // @phpstan-ignore-next-line
        $pageCount = $this->link->get_pdo()->query('PRAGMA page_count')->fetchColumn(0);
        return $pageCount;
    }
}
