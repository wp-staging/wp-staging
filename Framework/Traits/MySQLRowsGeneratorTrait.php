<?php

/**
 * Provides methods to fetch potentially unlimited rows from a database table
 * with resource-usage awareness using raw MySQL(i) queries.
 *
 * @package WPStaging\Framework\Traits
 */

namespace WPStaging\Framework\Traits;

use Generator;
use WPStaging\Framework\Adapter\Database\InterfaceDatabaseClient;
use WPStaging\Framework\Adapter\Database\MysqliAdapter;
use WPStaging\Framework\Adapter\Database\SqliteAdapter;
use WPStaging\Framework\Job\Dto\JobDataDto;

/**
 * Trait MySQLRowsGeneratorTrait
 *
 * @package WPStaging\Framework\Traits
 */
trait MySQLRowsGeneratorTrait
{
    use ResourceTrait;
    use BatchSizeCalculateTrait;

    /** @var bool */
    protected $useMemoryExhaustFix = false;

    /** @var string */
    protected $columnToExclude = '';

    /** @var string */
    protected $valuesToExclude = '';

    /**
     * @param bool $useMemoryExhaustFix
     */
    public function setUseMemoryExhaustFix(bool $useMemoryExhaustFix)
    {
        $this->useMemoryExhaustFix = $useMemoryExhaustFix;
    }

    /**
     * Returns a generator of rows.
     *
     * The Generator will fetch the candidate rows to process in batches and return
     * them transparently to the caller code.
     * If the current thread is over 80% memory or execution time, then the Generator will yield `null` to stop
     * the processing.
     *
     * @param string $databaseName The database name.
     * @param string $table The prefixed name of the table to pull rows from.
     * @param string|null $numericPrimaryKey
     * @param int $offset The number of row to start the work from.
     *                                                         processed will depend on the server available memory and max request execution time.
     * @param string $requestId A unique identifier for the job/task this generator is running on, as to make sure
     *                                                         that if we need to retry a query, we retry for this request.
     * @param InterfaceDatabaseClient|MysqliAdapter|SqliteAdapter $db A reference to the database instance to fetch rows from.
     * @param JobDataDto $jobDataDto
     *
     * @return Generator  A generator yielding rows one by one; refetching them if and when required.
     * @phpstan-ignore-next-line
     */
    protected function rowsGenerator(string $databaseName, string $table, $numericPrimaryKey, int $offset, string $requestId, InterfaceDatabaseClient $db, JobDataDto $jobDataDto): Generator
    {
        /* Kept for debugging purpose
        if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
            \WPStaging\functions\debug_log(
                sprintf(
                    'MySQLRowsGeneratorTrait: max-memory-limit=%s; script-memory-limit=%s; memory-usage=%s; execution-time-limit=%s; running-time=%s; is-threshold=%s',
                    size_format($this->getMaxMemoryLimit()),
                    size_format($this->getScriptMemoryLimit()),
                    size_format($this->getMemoryUsage()),
                    $this->findExecutionTimeLimit(),
                    $this->getRunningTime(),
                    ($this->isThreshold() ? 'yes' : 'no')
                )
            );
        }
        */

        $rows      = [];
        $lastFetch = false;
        $batchSize = $this->calculateBatchSize($databaseName, $table, $offset, $requestId, $jobDataDto, $db);

        do {
            if (empty($rows)) {
                if ($lastFetch) {
                    break;
                }

                if ($this->columnToExclude && $this->valuesToExclude) {
                    $query                 = $this->getQueryForExclusion($numericPrimaryKey, $table, $offset, $batchSize);
                    $this->columnToExclude = '';
                    $this->valuesToExclude = '';
                } else {
                    $query = $this->getQueryWithoutExclusion($numericPrimaryKey, $table, $offset, $batchSize);
                }

                $jobDataDto->setLastQueryInfoJSON(json_encode([$requestId, $table, $offset, $batchSize]));

                $requestStartTime = microtime(true);

                $result = $db->query($query);

                $jobDataDto->setDbRequestTime(microtime(true) - $requestStartTime);

                // If a single sql query takes more than 10 seconds, the sql server is treated as a very slow one.
                if ($jobDataDto->getDbRequestTime() > 10) {
                    $jobDataDto->setIsSlowMySqlServer(true);
                }

                if ($result === false) {
                    throw new \RuntimeException('DB error:' . $db->error() . ' Query: ' . $query . ' requestId: ' . $requestId . ' table: ' . $table . ' Offset: ' . $offset . ' Batch Size: ' . $batchSize);
                }

                $rows = [];
                //while ($row = $result->fetch_assoc()) {
                while ($row = $db->fetchAssoc($result)) {
                    $rows[] = $row;
                }

                $db->freeResult($result);

                $rows = array_reverse($rows);

                $jobDataDto->setLastQueryInfoJSON('');

                if (!empty($db->error())) {
                    \WPStaging\functions\debug_log($db->error());
                }

                if (empty($rows)) {
                    break;
                }

                if (!is_array($rows)) {
                    \WPStaging\functions\debug_log(sprintf('$rows is not an array. Actual type: %s', gettype($rows)));
                }

                // If we got less than the batch size, then this is the last fetch.
                $lastFetch = count($rows) < $batchSize;
            }

            // Take the next row from the ready set.
            $row = array_pop($rows);

            if ($row === null) {
                // We're done, no more rows to process.
                break;
            }

            /**
             * Extend execution time.
             * If sql query is slow and takes more than 1 second even with low batch size, extend the available execution time.
             * Otherwise, it can lead to a slow-down at worse one row per request
             *
             * Can happen on very slow mySQL servers.
             * Fixes #1945 https://github.com/wp-staging/wp-staging-pro/issues/1945
             */
            if ($batchSize <= 100 && $jobDataDto->getDbRequestTime() > 1) {
                $this->setTimeLimit((int)$this->findExecutionTimeLimit() + 10);
            }

            // Check memory usage every 10 rows
            if (rand(0, 10) === 10 && $this->isThreshold()) {
                $jobDataDto->setLastQueryInfoJSON(json_encode([$requestId, $table, $offset, $batchSize]));
                break;
            }

            yield $row;

            if (empty($numericPrimaryKey)) {
                $offset++;
            } else {
                $offset = $row[$numericPrimaryKey];
            }
        } while (!$this->isThreshold());
    }

    /**
     * @param string|null $numericPrimaryKey
     * @param string $table
     * @param string $offset
     * @param string $batchSize
     *
     * @return string
     */
    private function getQueryForExclusion($numericPrimaryKey, string $table, string $offset, string $batchSize): string
    {
        if (empty($numericPrimaryKey)) {
            return "SELECT * FROM `{$table}` WHERE `{$this->columnToExclude}` NOT IN ({$this->valuesToExclude}) AND LIMIT {$offset}, {$batchSize}";
        }

        // Optimal! We have Primary Keys, so it doesn't get slower on large offsets.
        return <<<SQL
SELECT  * 
FROM `{$table}` 
WHERE `{$numericPrimaryKey}` > {$offset} 
AND `{$this->columnToExclude}` NOT IN ({$this->valuesToExclude}) 
ORDER BY `{$numericPrimaryKey}` ASC 
LIMIT 0, {$batchSize} 
SQL;
    }

    /**
     * @param string|null $numericPrimaryKey
     * @param string $table
     * @param string $offset
     * @param string $batchSize
     *
     * @return string
     */
    private function getQueryWithoutExclusion($numericPrimaryKey, string $table, string $offset, string $batchSize): string
    {
        if (empty($numericPrimaryKey)) {
            return "SELECT * FROM `{$table}` LIMIT {$offset}, {$batchSize}";
        }

        // Optimal! We have Primary Keys, so it doesn't get slower on large offsets.
        return <<<SQL
SELECT  *
FROM `{$table}`
WHERE `{$numericPrimaryKey}` > {$offset}
ORDER BY `{$numericPrimaryKey}` ASC
LIMIT 0, {$batchSize}
SQL;
    }
}
