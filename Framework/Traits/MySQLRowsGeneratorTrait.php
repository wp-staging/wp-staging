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
use WPStaging\Backup\Dto\JobDataDto;

/**
 * Trait MySQLRowsGeneratorTrait
 *
 * @package WPStaging\Framework\Traits
 */
trait MySQLRowsGeneratorTrait
{
    use ResourceTrait;

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
     * @param InterfaceDatabaseClient|MysqliAdapter $db A reference to the database instance to fetch rows from.
     * @param JobDataDto $jobDataDto
     *
     * @return Generator  A generator yielding rows one by one; refetching them if and when required.
     */
    protected function rowsGenerator(string $databaseName, string $table, $numericPrimaryKey, int $offset, string $requestId, InterfaceDatabaseClient $db, JobDataDto $jobDataDto): Generator
    {
        /*        if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
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
                }*/

        $rows      = [];
        $lastFetch = false;

        $batchSize = null;

        $freeMemory = $this->getScriptMemoryLimit() - $this->getMemoryUsage();

        // Fetch the average row length of the current table, if need be. This is to get the maximum possible batch size
        if (empty($jobDataDto->getTableAverageRowLength())) {
            $averageRowLength = $db->query("SELECT AVG_ROW_LENGTH FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = '$table' AND TABLE_SCHEMA = '$databaseName';")->fetch_assoc();
            if (!empty($averageRowLength) && is_array($averageRowLength) && array_key_exists('AVG_ROW_LENGTH', $averageRowLength)) {
                $jobDataDto->setTableAverageRowLength(max(absint($averageRowLength['AVG_ROW_LENGTH']), 1));

                // @phpstan-ignore-next-line
                $batchSize = ($freeMemory / $jobDataDto->getTableAverageRowLength()) / 4;
            }
        } else {
            $batchSize = ($freeMemory / $jobDataDto->getTableAverageRowLength()) / 4;
        }

        // This can happen if we can't fetch the AVG_ROW_LENGTH from the table
        if ($batchSize === null) {
            $batchSize = 5000;
        }

        // Lower the fetch limits if we couldn't store them in memory last time
        if (!empty($jobDataDto->getLastQueryInfoJSON())) {
            $lastQueryInfo = json_decode($jobDataDto->getLastQueryInfoJSON(), true);
            if (count($lastQueryInfo) === 4) {
                $previousRequestId = $lastQueryInfo[0];
                if ($previousRequestId === $requestId) {
                    list($requestId, $table, $oldOffset, $batchSize) = array_replace([$requestId, $table, $offset, $batchSize], $lastQueryInfo);

                    if ($batchSize <= 1000) {
                        $batchSize = $batchSize / 2;
                    } else {
                        $batchSize = $batchSize / 3;
                    }

                    if ((!$this->useMemoryExhaustFix) || ($offset > $oldOffset)) {
                        $offset = $oldOffset;
                    }

                    /*                    if ($batchSize < 1) {
                                          throw new \RuntimeException(sprintf(
                                          'There is one row in the database that is bigger than the memory available to PHP, which makes it impossible to extract using PHP. More info: Maximum PHP Memory: %s | Row is in table: %s | Offset: %s',
                                          size_format($this->getScriptMemoryLimit()),
                                          $table,
                                          $offset
                                         ));
                                        }*/
                }
            }
        }

        // At least 1, max 5k, integer
        $maxBatchSize = $jobDataDto->getIsSlowMySqlServer() ? 100 : 5000;
        $minBatchSize = 1;
        $batchSize    = max($minBatchSize, $batchSize);
        $batchSize    = min($maxBatchSize, $batchSize);
        $batchSize    = ceil($batchSize);

        $jobDataDto->setBatchSize($batchSize);

        do {
            if (empty($rows)) {
                if ($lastFetch) {
                    break;
                }

                if ($this->columnToExclude && $this->valuesToExclude) {
                    $query = $this->getQueryForExclusion($numericPrimaryKey, $table, $offset, $batchSize);
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
                while ($row = $result->fetch_assoc()) {
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

    /**
     * @return void
     */
    private function initDbExclusionValues()
    {
        if ($this->jobDataDto->getIsExcludingSpamComments() && strpos($this->tableName, $this->database->getPrefix() . 'comment') !== false) {
            $this->columnToExclude  = 'comment_id';
            $rowsToExclude          = $this->getSpamComments();
            $this->valuesToExclude  = implode(',', $rowsToExclude);
        } elseif ($this->jobDataDto->getIsExcludingPostRevision() && strpos($this->tableName, $this->database->getPrefix() . 'posts') !== false) {
            $this->columnToExclude  = 'id';
            $rowsToExclude          = $this->getPostsRevision();
            $this->valuesToExclude  = implode(',', $rowsToExclude);
        }
    }

    /**
     * @return array
     */
    private function getSpamComments(): array
    {
        $args = [
            'status'  => 'spam',
            'fields'  => 'ids',
        ];

        return get_comments($args);
    }

    /**
     * @return array
     */
    private function getPostsRevision(): array
    {
        $args = [
            'post_type'   => 'revision',
            'post_status' => 'any',
            'fields'      => 'ids',
        ];

        return get_posts($args);
    }
}
