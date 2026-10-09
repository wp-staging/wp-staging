<?php

 

namespace WPStaging\Framework\Adapter\Database;

use Exception;
use WP_SQLite_Translator;

class SqliteAdapter implements InterfaceDatabaseClient
{
 
    public $link; // @phpstan-ignore-line




    public $isSQLite = true;




    private $currentFetchAssocRowIndex = 0;






    private $currentFetchAssocResult;




    private $currentFetchRowIndex = 0;






    private $currentFetchRowResult;

    public function __construct($link = null)
    {
        $this->link = $link;
    }




    public function query($query)
    {
        return $this->link->query($query); // @phpstan-ignore-line
    }




    public function realQuery($query, $isExecOnly = false)
    {
        return $this->link->query($query); // @phpstan-ignore-line
    }

    public function escape($input)
    {
        $escapedString = $input;

 
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
        if (!is_array($result)) {
            return [];
        }

        return array_map(function ($row) {
            return (array)$row;
        }, $result);
    }

    public function fetchAssoc($result)
    {
        // @phpstan-ignore-next-line
        if (empty($result)) {
            return [];
        }

        if ($result !== $this->currentFetchAssocResult) {
            $this->currentFetchAssocResult   = $result;
            $this->currentFetchAssocRowIndex = 0;
        }

 
        $resultArray = [];

        foreach ($result as $row) {
 
            if (is_object($row)) {
                $resultArray[] = (array)$row; 
            }
        }

 
        if (isset($resultArray[$this->currentFetchAssocRowIndex])) {
 
            $currentRow = $resultArray[$this->currentFetchAssocRowIndex];
            $this->currentFetchAssocRowIndex++;
            return $currentRow;
        } else {
 
            $this->currentFetchAssocRowIndex = 0;
            $this->currentFetchAssocResult   = null;
        }

        return [];
    }






    public function getAverageRowLengthSQLite(string $tableName)
    {
 
        $rowCount = $this->link->get_pdo()->query("SELECT COUNT(1) FROM `" . $tableName . "`")->fetchColumn(0); // @phpstan-ignore-line

 
        if ($rowCount == 0) {
            return null;
        }

        $pageSize  = $this->getSQLitePageSize();
        $pageCount = $this->getSQLitePageCount();

 
        $totalSize = $pageCount * $pageSize;

 
        return $totalSize / $rowCount;
    }





    public function fetchRow($result)
    {
        if ($result !== $this->currentFetchRowResult) {
            $this->currentFetchRowResult = $result;
            $this->currentFetchRowIndex  = 0;
        }

 
        $resultArray = $this->castObjectToArrayRecursive($result);

 
        if (isset($resultArray[$this->currentFetchRowIndex])) {
 
            $row = $resultArray[$this->currentFetchRowIndex];
            $this->currentFetchRowIndex++; 

 
            return [reset($row)];
        } else {
 
            $this->currentFetchRowIndex  = 0;
            $this->currentFetchRowResult = null;
            return null;
        }
    }







    private function castObjectToArrayRecursive($input): array
    {
        if (is_object($input)) {
            $input = get_object_vars($input); 
        }

        if (is_array($input)) {
            foreach ($input as &$value) {
 
                if (is_object($value) || is_array($value)) {
                    $value = $this->castObjectToArrayRecursive($value);
                }
            }
        }

        return $input;
    }

    public function fetchObject($result)
    {
 
        if (is_array($result) && isset($result[0]) && is_object($result[0])) {
            return $result[0]; 
        }

        return null;
    }

    public function numRows($result): int
    {
        return is_array($result) ? count($result) : 0;
    }

    public function freeResult($result)
    {
        $this->currentFetchAssocRowIndex = 0;
        $this->currentFetchAssocResult   = null;
        $this->currentFetchRowIndex      = 0;
        $this->currentFetchRowResult     = null;
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
