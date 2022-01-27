<?php

/**
 * Provides methods to fetch potentially unlimited rows from a database table
 * with resource-usage awareness.
 *
 * @package WPStaging\Framework\Traits
 */

namespace WPStaging\Framework\Traits;

use Generator;

/**
 * Trait DbRowsGeneratorTrait
 *
 * @package WPStaging\Framework\Traits
 */
trait DbRowsGeneratorTrait
{
    use ResourceTrait;

    protected $tableName = '';

    /** @var object */
    private $stagingSiteDb;

    /** @var null  */
    public $hasNumericPrimaryKey = true;

    /**
     * @return string The primary key of the current table, if any.
     */
    protected function getPrimaryKey()
    {

        if (!$this->hasNumericPrimaryKey) {
            return false;
        }

        $dbname = $this->stagingSiteDb->dbname;

        $query = "SELECT COLUMN_NAME 
                  FROM INFORMATION_SCHEMA.COLUMNS
                  WHERE TABLE_NAME = '$this->tableName'
                  AND TABLE_SCHEMA = '$dbname'
                  AND IS_NULLABLE = 'NO'
                  AND DATA_TYPE IN ('int', 'bigint', 'smallint', 'mediumint')
                  AND COLUMN_KEY = 'PRI'
                  AND EXTRA like '%auto_increment%';";

        $primaryKey = $this->stagingSiteDb->get_results($query, ARRAY_A);

        $this->stagingSiteDb->flush();

        if (!$primaryKey) {
            return false;
        }

        if (!is_array($primaryKey[0])) {
            return false;
        }

        if (!array_key_exists('COLUMN_NAME', $primaryKey[0])) {
            return false;
        }

        if (empty($primaryKey[0]['COLUMN_NAME'])) {
            return false;
        }

        return $primaryKey[0]['COLUMN_NAME'];
    }

    /**
     * Returns a generator of rows.
     *
     * The Generator will fetch the candidate rows to process in batches and return
     * them transparently to the caller code.
     * If the current thread is over 80% memory or execution time, then the Generator will yield `null` to stop
     * the processing.
     *
     * @param string $table The prefixed name of the table to pull rows from.
     * @param int $offset The number of row to start the work from.
     * @param int $limit The maximum number of rows to try and process; the actual number of
     *                       processed will depend on the server available memory and max request execution time.
     * @param \wpdb|null A reference to the database instance to fetch rows from.
     *
     * @return Generator  A generator yielding rows one by one; refetching them if and when required.
     */
    protected function rowsGenerator($table, $offset, $limit, \wpdb $db = null)
    {
        /*        if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
                    \WPStaging\functions\debug_log(
                        sprintf(
                            'DbRowsGeneratorTrait: max-memory-limit=%s; script-memory-limit=%s; memory-usage=%s; execution-time-limit=%s; running-time=%s; is-threshold=%s',
                            $this->getMaxMemoryLimit(),
                            $this->getScriptMemoryLimit(),
                            $this->getMemoryUsage(),
                            $this->findExecutionTimeLimit(),
                            $this->getRunningTime(),
                            ($this->isThreshold() ? 'yes' : 'no')
                        )
                    );
                }*/

        $this->tableName = $table;

        if (null === $db) {
            global $wpdb;
            $db = $wpdb;
        }

        $this->stagingSiteDb = $db;

        $numericPrimaryKey = ($key = $this->getPrimaryKey()) ? $key : false;

        $suppressErrorsOriginal = $db->suppress_errors;
        $db->suppress_errors(false);

        // Sets the execution time limit to either a detected value below 10s and above 1s, or a safe 10s value.
        $this->setTimeLimit(min(10, max((int)$this->findExecutionTimeLimit(), 1)));

        $rows = [];
        $processed = 0;
        // More rows equals more memory; to process more let's reduce the memory footprint by using smaller fetch sizes.
        $batchSize = $limit / 5;
        $lastFetch = false;
        $batchSize = ceil($batchSize);

        do {
            if (count($rows) === 0) {
                if ($lastFetch) {
                    break;
                }

                // Optimal! We have Primary Keys so it doesn't get slower on large offsets.
                if (!empty($numericPrimaryKey)) {
                    $query = <<<SQL
SELECT  *
FROM `{$table}`
WHERE `{$numericPrimaryKey}` > {$offset}
ORDER BY `{$numericPrimaryKey}` ASC
LIMIT 0, {$batchSize}
SQL;
                } else {
                    $query = "SELECT * FROM `{$table}` LIMIT {$offset}, {$batchSize}";
                }

                $rows = $db->get_results($query, ARRAY_A);

                // Call to mysql_free_result
                $db->flush();

                if (!empty($db->last_error)) {
                    \WPStaging\functions\debug_log($db->last_error);
                }

                // We're done here.
                if (empty($rows)) {
                    break;
                }

                if (!is_array($rows)) {
                    \WPStaging\functions\debug_log(sprintf('DbRowsGenerator: $rows is not an array. Actual type: %s', gettype($rows)));
                }

                $offset += $batchSize;
                // If we got less than the batch size, then this is the last fetch.
                $lastFetch = count($rows) < $batchSize;
            }

            // Take the next row from the ready set.
            $row = array_shift($rows);

            // We're done, no more rows to process.
            if (null === $row) {
                break;
            }

            yield $row;

            // It's actually processed when the caller code returns the control to the Generator.
            $processed++;
        } while (!$this->isThreshold() && $processed < $limit);

        $db->suppress_errors($suppressErrorsOriginal);
    }
}
