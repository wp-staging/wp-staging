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
use WPStaging\Pro\Backup\Dto\JobDataDto;

/**
 * Trait MySQLRowsGeneratorTrait
 *
 * @package WPStaging\Framework\Traits
 */
trait MySQLRowsGeneratorTrait
{
    use ResourceTrait;

    /**
     * Returns a generator of rows.
     *
     * The Generator will fetch the candidate rows to process in batches and return
     * them transparently to the caller code.
     * If the current thread is over 80% memory or execution time, then the Generator will yield `null` to stop
     * the processing.
     *
     * @param string                                $databaseName The database name.
     * @param string                                $table     The prefixed name of the table to pull rows from.
     * @param int                                   $offset    The number of row to start the work from.
     *                                                         processed will depend on the server available memory and max request execution time.
     * @param string                                $requestId A unique identifier for the job/task this generator is running on, as to make sure
     *                                                         that if we need to retry a query, we retry for this request.
     * @param InterfaceDatabaseClient|MysqliAdapter $db        A reference to the database instance to fetch rows from.
     *
     * @return Generator  A generator yielding rows one by one; refetching them if and when required.
     */
    protected function rowsGenerator($databaseName, $table, $numericPrimaryKey, $offset, $requestId, InterfaceDatabaseClient $db, JobDataDto $jobDataDto)
    {
/*        if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
            error_log(
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

        $rows = [];
        $lastFetch = false;

        $batchSize = null;

        $freeMemory = $this->getScriptMemoryLimit() - $this->getMemoryUsage();

        // Fetch the average row length of the current table, if need be.
        if (empty($jobDataDto->getTableAverageRowLength())) {
            $averageRowLength = $db->query("SELECT AVG_ROW_LENGTH FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = '$table' AND TABLE_SCHEMA = '$databaseName';")->fetch_assoc();
            if (!empty($averageRowLength) && is_array($averageRowLength) && array_key_exists('AVG_ROW_LENGTH', $averageRowLength)) {
                $jobDataDto->setTableAverageRowLength(max(absint($averageRowLength['AVG_ROW_LENGTH']), 1));

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
                    list($requestId, $table, $offset, $batchSize) = array_replace([$requestId, $table, $offset, $batchSize], $lastQueryInfo);

                    $batchSize = $batchSize / 3;

                    if ($batchSize < 1) {
                        throw new \RuntimeException(sprintf(
                            'There is one row in the database that is bigger than the memory available to PHP, which makes it impossible to extract using PHP. More info: Maximum PHP Memory: %s | Row is in table: %s | Offset: %s',
                            size_format($this->getScriptMemoryLimit()),
                            $table,
                            $offset
                        ));
                    }
                }
            }
        }

        // At least 1, max 5k, integer
        $batchSize = max(1, $batchSize);
        $batchSize = min(5000, $batchSize);
        $batchSize = ceil($batchSize);

        do {
            if (count($rows) === 0) {
                if ($lastFetch) {
                    break;
                }

                if (!empty($numericPrimaryKey)) {
                    // Optimal! We have Primary Keys so it doesn't get slower on large offsets.
                    $query = <<<SQL
SELECT  *
FROM `{$table}`
WHERE `{$numericPrimaryKey}` > {$offset}
ORDER BY `{$numericPrimaryKey}` ASC
LIMIT 0, {$batchSize}
SQL;
                } else {
                    $query = "SELECT * FROM {$table} LIMIT {$offset}, {$batchSize}";
                }
                $jobDataDto->setLastQueryInfoJSON(json_encode([$requestId, $table, $offset, $batchSize]));
                $result = $db->query($query);

                if ($result === false) {
                    throw new \RuntimeException('DB error:' . $db->error() . ' Query: ' . $query . ' requestId: ' . $requestId . ' table: ' . $table . ' Offset: ' . $offset . ' Batch Size: ' . $batchSize);
                }

                $rows = [];
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                }
                $rows = array_reverse($rows);
                $db->freeResult($result);
                $jobDataDto->setLastQueryInfoJSON('');

                if (!empty($db->error())) {
                    if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
                        error_log($db->error());
                    }
                }

                if (empty($rows)) {
                    // We're done here.
                    break;
                }

                if (!is_array($rows)) {
                    if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
                        error_log(sprintf('$rows is not an array. Actual type: %s', gettype($rows)));
                    }
                }

                // If we got less than the batch size, then this is the last fetch.
                $lastFetch = count($rows) < $batchSize;
            }

            // Take the next row from the ready set.
            $row = array_pop($rows);

            if (null === $row) {
                // We're done, no more rows to process.
                break;
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
}
