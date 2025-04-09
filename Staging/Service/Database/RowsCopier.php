<?php

namespace WPStaging\Staging\Service\Database;

use Exception;
use Throwable;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Adapter\Database\InterfaceDatabaseClient;
use WPStaging\Framework\Database\QueryBuilder\SelectQuery;
use WPStaging\Framework\Database\SearchReplace;
use WPStaging\Framework\Database\TableService;
use WPStaging\Framework\Job\Dto\JobDataDto;
use WPStaging\Framework\Traits\DatabaseSearchReplaceTrait;
use WPStaging\Framework\Traits\MySQLRowsGeneratorTrait;
use WPStaging\Framework\Traits\SerializeTrait;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Staging\Dto\Service\RowsCopierDto;
use WPStaging\Staging\Interfaces\StagingOperationDtoInterface;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

/**
 * This class is similar to the src\Framework\CloningProcess\Database\DatabaseCloningService class.
 * It is adjusted to use Backuper Logic (from RowsExporter and DatabaseImporter) and it focus on copying rows from source table
 * to destination table in the staging site with adjusted urls.
 * @see src\Framework\CloningProcess\Database\DatabaseCloningService for existing logic
 * @see src\Staging\Service\Database\TableCreateService for logic related to creating tables
 */
class RowsCopier
{
    use MySQLRowsGeneratorTrait;
    use DatabaseSearchReplaceTrait;
    use SerializeTrait;

    /** @var LoggerInterface */
    protected $logger;

    /** @var SelectQuery */
    protected $selectQuery;

    /** @var Database */
    protected $productionDb;

    /** @var Strings */
    protected $strings;

    /** @var Database */
    protected $stagingDb;

    /** @var TableService */
    protected $tableService;

    /** @var RowsCopierDto */
    protected $rowsCopierDto;

    /** @var JobDataDto|StagingOperationDtoInterface */
    protected $jobDataDto;

    /** @var SearchReplace */
    protected $searchReplace;

    /** @var string */
    protected $databaseName;

    /** @var string */
    protected $productionPrefix;

    /** @var string */
    protected $stagingPrefix;

    /** @var int */
    protected $lastInsertedNumericPrimaryKeyValue = -PHP_INT_MAX;

    /** @var int */
    protected $tableRowsOffset = 0;

    /** @var array Fields/Option name which need special care for search replace */
    protected $specialFields;

    /** @var array */
    protected $prefixedValues = [];

    /** @var int */
    protected $smallerSearchLength;

    /** @var array */
    protected $tablesExcludedFromSearchReplace = [];

    /** @var array */
    protected $tablesExcludedForData = [];

    /**
     * @param SelectQuery $selectQuery
     * @param Database $productionDb
     */
    public function __construct(SelectQuery $selectQuery, Database $productionDb, Strings $strings)
    {
        $this->selectQuery   = $selectQuery;
        $this->productionDb  = $productionDb;
        $this->strings       = $strings;
        $this->specialFields = ['user_roles', 'capabilities', 'user_level', 'dashboard_quick_press_last_post_id', 'user-settings', 'user-settings-time'];
    }

    public function setup(LoggerInterface $logger, JobDataDto $jobDataDto, RowsCopierDto $rowsCopierDto, Database $stagingDb)
    {
        $this->logger           = $logger;
        $this->jobDataDto       = $jobDataDto;
        $this->rowsCopierDto    = $rowsCopierDto;
        $this->stagingDb        = $stagingDb;
        $this->databaseName     = $this->productionDb->getWpdba()->getClient()->__get('dbname');
        $this->tableService     = new TableService($this->stagingDb);
        $this->productionPrefix = $this->productionDb->getPrefix();
        $this->stagingPrefix    = $this->stagingDb->getPrefix();
    }

    /**
     * @param int $tableIndex
     * @param string $destTableName
     * @param string $srcTableName
     * @return int
     */
    public function setTablesInfo(int $tableIndex, string $srcTableName, string $destTableName): int
    {
        if ($srcTableName === $this->rowsCopierDto->getSrcTable()) {
            return $this->rowsCopierDto->getTotalRows();
        }

        if (in_array($srcTableName, $this->tablesExcludedForData)) {
            $this->logger->info("Skipping table {$srcTableName} as it is excluded from data copying...");
            return 0;
        }

        // Note: fix for SQLITE
        $tableName = empty($this->databaseName) ? "`" . $srcTableName . "`" : "`" . $this->databaseName . "`.`" . $srcTableName . "`";
        $rowsCount = $this->tableService->getRowsCount($tableName, false);

        $this->rowsCopierDto->init($tableIndex, $srcTableName, $destTableName, $rowsCount);

        $numericPrimaryKey = null;
        try {
            $numericPrimaryKey = $this->tableService->getNumericPrimaryKey($this->databaseName, $srcTableName);
        } catch (Exception $e) {
            if ($rowsCount > 300000) {
                $this->logger->warning("The table {$srcTableName} does not have a compatible primary key, so it will get slower the more rows from it be copied and will be locked...");
            } else {
                $this->logger->warning("The table {$srcTableName} does not have a compatible primary key, so it will be locked...");
            }

            $this->tableService->lockTable($srcTableName);
            $this->rowsCopierDto->setLocked(true);
        }

        $this->rowsCopierDto->setNumericPrimaryKey($numericPrimaryKey);
        $this->logger->info("Found table {$srcTableName} with {$rowsCount} rows to copy to {$destTableName}...");

        return $rowsCount;
    }

    /**
     * @return void
     */
    public function execute()
    {
        $requestId = "rowsCopier_" . $this->jobDataDto->getId();

        $this->setupSearchReplace();

        if ($this->rowsCopierDto->isLocked()) {
            $this->tableService->lockTable($this->rowsCopierDto->getSrcTable());
            $this->tableService->lockTable($this->rowsCopierDto->getDestTable());
        }

        do {
            $numericPrimaryKey                        = $this->rowsCopierDto->getNumericPrimaryKey();
            $this->tableRowsOffset                    = $this->rowsCopierDto->getRowsOffset();
            $this->lastInsertedNumericPrimaryKeyValue = (int)$this->rowsCopierDto->getLastInsertedNumericPrimaryKeyValue();

            $data = $this->rowsGenerator($this->databaseName, $this->rowsCopierDto->getSrcTable(), $numericPrimaryKey, $this->tableRowsOffset, $requestId, $this->getSrcDatabaseClient(), $this->jobDataDto);
            $tableColumns = $this->tableService->getColumnTypes($this->rowsCopierDto->getSrcTable());

            foreach ($data as $row) {
                if ($this->checkLastInsertedNumericKeyValue($numericPrimaryKey ?? '', $row)) {
                    continue;
                }

                $query = $this->buildInsertQuery($row, $tableColumns);
                $this->executeInsertQuery($query);
                $this->updateRowsCopierDto($numericPrimaryKey ?? '');
            }
        } while (!$this->isThreshold() && !$this->isTableCopyingFinished());

        $this->unlockTable();
    }

    public function unlockTable()
    {
        if ($this->rowsCopierDto->isLocked()) {
            $this->tableService->unlockTables();
        }
    }

    /**
     * @return void.
     */
    protected function setupSearchReplace()
    {
        $searchReplaceParams = $this->getSearchReplaceParams();
        $this->searchReplace = new SearchReplace(
            $searchReplaceParams['search'],
            $searchReplaceParams['replace'],
            true,
            []
        );

        // Any query that has fewer characters than this can be safely ignored for S/R.
        $this->smallerSearchLength = min($this->searchReplace->getSmallerSearchLength(), 2);
    }

    /**
     * @return bool
     */
    public function isTableCopyingFinished(): bool
    {
        return $this->rowsCopierDto->isFinished();
    }

    public function getRowsCopierDto(): RowsCopierDto
    {
        return $this->rowsCopierDto;
    }

    protected function getSrcDatabaseClient(): InterfaceDatabaseClient
    {
        return $this->productionDb->getClient();
    }

    /**
     * @return void
     */
    public function prefixSpecialFields()
    {
        $prefix = $this->productionPrefix;

        /**
         * We do an array_flip for performance reasons, to use the performant
         * isset($this->prefixedValues['someValue']),
         * since this function can be called millions of times.
         */
        $this->prefixedValues = array_flip(array_map(function ($unprefixedValue) use ($prefix) {
            return $prefix . $unprefixedValue;
        }, $this->specialFields));
    }

    /**
     * @param string $numericPrimaryKey
     * @param array $row
     * @return bool
     */
    protected function checkLastInsertedNumericKeyValue(string $numericPrimaryKey, array $row): bool
    {
        if (empty($numericPrimaryKey)) {
            return false;
        }

        $lastInsertedValue = (int)$row[$numericPrimaryKey];
        if ($lastInsertedValue <= $this->lastInsertedNumericPrimaryKeyValue) {
            return true;
        }

        $this->lastInsertedNumericPrimaryKeyValue = $lastInsertedValue;

        return false;
    }

    /**
     * @param string $numericPrimaryKey
     * @return void
     */
    protected function updateRowsCopierDto(string $numericPrimaryKey)
    {
        $this->rowsCopierDto->setRowsCopied($this->rowsCopierDto->getRowsCopied() + 1);
        if (!empty($numericPrimaryKey)) {
            $this->tableRowsOffset = $this->lastInsertedNumericPrimaryKeyValue;
        } else {
            $this->tableRowsOffset = $this->rowsCopierDto->getRowsOffset() + 1;
        }

        $this->rowsCopierDto->setRowsOffset($this->tableRowsOffset);
        $this->rowsCopierDto->setLastInsertedNumericPrimaryKeyValue($this->lastInsertedNumericPrimaryKeyValue);
    }

    protected function buildInsertQuery(array $row, array $tableColumns): string
    {
        $query = "INSERT INTO `{$this->getOutputTableName()}` VALUES (";

        foreach ($row as $column => $value) {
            // Null value
            if (is_null($value)) {
                $query .= "NULL, ";
                continue;
            }

            // binary, varbinary, blob
            $columnLower = strtolower($column);
            if (
                isset($tableColumns[$columnLower]) && (
                strpos($tableColumns[$columnLower], 'binary') !== false ||
                strpos($tableColumns[$columnLower], 'blob') !== false )
            ) {
                $value  = bin2hex($value);
                $query .= "UNHEX('" . $value . "'), ";
                continue;
            }

            if (empty($value)) {
                $query .= "'', ";
                continue;
            }

            /**
             * Save S/R effort on very small queries.
             * -2 comes from the surrounding quotes 'foo' => 3
             */
            if ($this->smallerSearchLength > strlen($value)) {
                $query .= "'{$this->mySqlRealEscape($value)}', ";
                continue;
            }

            // Early bail as there is no need to perform search replace for excluded tables
            if (!$this->shouldSearchReplace($query)) {
                $query .= "'{$this->mySqlRealEscape($value)}', ";
                continue;
            }

            if ($this->isSerialized($value)) {
                $value = $this->searchReplace->replaceExtended($value);
            } else {
                $value = $this->searchReplace->replaceExtended($value);
            }

            $query .= "'{$this->mySqlRealEscape($value)}', ";
        }

        $query = rtrim($query, ', ');

        $query .= ');';

        return $query;
    }

    protected function executeInsertQuery(string $query)
    {
        $client = $this->getInserterDatabaseClient();
        try {
            $result = $client->query($query);
        } catch (Throwable $exception) {
            $this->logger->error("Error while copying rows: " . $exception->getMessage());
            throw $exception;
        }

        if ($result === false) {
            $this->logger->error("Error while copying rows: " . $client->error());
            throw new Exception("Error while copying rows: " . $client->error());
        }
    }

    protected function getInserterDatabaseClient(): InterfaceDatabaseClient
    {
        return $this->stagingDb->getClient();
    }

    protected function getOutputTableName(): string
    {
        return $this->rowsCopierDto->getDestTable();
    }

    protected function shouldSearchReplace($query)
    {
        // Early bail: for older backups
        if (empty($this->tablesExcludedFromSearchReplace)) {
            return true;
        }

        preg_match('#^INSERT INTO `(.+?(?=`))` VALUES#', $query, $insertIntoExploded);
        $tableName = $insertIntoExploded[0];

        return !in_array($tableName, $this->tablesExcludedFromSearchReplace);
    }

    /**
     * Revert the effects of real_escape_string
     *
     * It is very unlikely to have LIKE statement in INSERT query,
     * So there is no need to escape % and _ at the moment
     *
     * @see  https://www.php.net/manual/en/mysqli.real-escape-string.php
     * @link https://dev.mysql.com/doc/refman/8.0/en/string-literals.html#character-escape-sequences
     *
     * @param string $query The query to revert real_escape_string
     *
     */
    protected function undoMySqlRealEscape(&$query)
    {
        $replacementMap = [
            "\\0"  => "\0",
            "\\n"  => "\n",
            "\\r"  => "\r",
            "\\t"  => "\t",
            "\\Z"  => chr(26),
            "\\b"  => chr(8),
            '\"'   => '"',
            "\'"   => "'",
            '\\\\' => '\\',
        ];

        return strtr($query, $replacementMap);
    }

    /**
     * Mimics MySQLi real_escape_string, without having to open a DB connection.
     *
     * It is very unlikely to have LIKE statement in INSERT query,
     * So there is no need to escape % and _ at the moment
     *
     * @see  https://www.php.net/manual/en/mysqli.real-escape-string.php
     * @link https://dev.mysql.com/doc/refman/8.0/en/string-literals.html#character-escape-sequences
     *
     * @param string $query
     */
    protected function mySqlRealEscape(&$query)
    {
        $replacementMap = [
            "\0"    => "\\0",
            "\n"    => "\\n",
            "\r"    => "\\r",
            "\t"    => "\\t",
            chr(26) => "\\Z",
            chr(8)  => "\\b",
            '"'     => '\"',
            "'"     => "\'",
            '\\'    => '\\\\',
        ];

        return strtr($query, $replacementMap);
    }

    protected function getSearchReplaceParams(): array
    {
        $search  = $this->generateHostnamePatterns($this->getSourceHostname());
        $replace = $this->generateHostnamePatterns($this->jobDataDto->getStagingSiteUrl());

        return [
            'search'  => array_merge([$this->productionPrefix], $search),
            'replace' => array_merge([$this->stagingPrefix], $replace),
        ];
    }
}
