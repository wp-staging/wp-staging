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
    protected $tableName = '';

    /** @var object */
    private $stagingSiteDb;

    /** @var int|bool */
    public $lastFetchedPrimaryKeyValue = false;

    /** @var string */
    public $numericPrimaryKey = null;

    /** @var bool */
    public $noResultRows = false;

    /**
     * Used by unit tests
     * @var bool
     */
    public $executeNumericPrimaryKeyQuery = true;


    /**
     * Get the numeric primary key.
     * Return false if there is more than one primary key in the table or the table is not describeable or the primary key is non numeric
     * @return string|false
     */
    protected function getNumericPrimaryKey()
    {
        // Used by unit tests
        if (!$this->executeNumericPrimaryKeyQuery) {
            return false;
        }

        $primaryKeys = [];
        $fields      = $this->stagingSiteDb->get_results('DESCRIBE ' . $this->tableName);

        // Either there was an error or the table has no columns.
        if (empty($fields)) {
            return false;
        }

        if (is_array($fields)) {
            foreach ($fields as $column) {
                if ($column->Key === 'PRI') {
                    $primaryKeys[] = $column;
                }
            }
        }

        if (empty($primaryKeys)) {
            return false;
        }

        if (count($primaryKeys) > 1) {
            return false;
        }

        $primaryKey = $primaryKeys[0];

        // make sure only numeric primary key is return
        if (
            strpos($primaryKey->Type, 'int') === 0 ||
            strpos($primaryKey->Type, 'bigint') === 0 ||
            strpos($primaryKey->Type, 'smallint') === 0 ||
            strpos($primaryKey->Type, 'mediumint') === 0
        ) {
            $this->numericPrimaryKey = $primaryKey->Field;
            return $this->numericPrimaryKey;
        }

        return false;
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
     *                        In case of numeric primary table it is the last primary key value fetched.
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

        $numericPrimaryKey = $this->getNumericPrimaryKey();

        $suppressErrorsOriginal = $db->suppress_errors;
        $db->suppress_errors(false);

        // Sets the execution time limit to either a detected value below 10s and above 1s, or a safe 10s value.
        $this->setTimeLimit(min(10, max((int)$this->findExecutionTimeLimit(), 1)));

        $rows = [];
        $processed = 0;
        // More rows equals more memory; to process more let's reduce the memory footprint by using smaller fetch sizes.
        $batchSize = $limit / 5;
        $batchSize = ceil($batchSize);
        $lastFetch = false;

        do {
            if (count($rows) === 0) {
                if ($lastFetch) {
                    break;
                }

                // Optimal! We have a Primary Key so it doesn't get slower on large offsets.
                if (!empty($numericPrimaryKey)) {
                    $whereCondition = '';
                    if ($this->lastFetchedPrimaryKeyValue !== false) {
                        $whereCondition = "WHERE `{$numericPrimaryKey}` > {$this->lastFetchedPrimaryKeyValue}";
                    }

                    $query = <<<SQL
SELECT  *
FROM `{$table}`
{$whereCondition}
ORDER BY `{$numericPrimaryKey}` ASC
LIMIT 0, {$batchSize}
SQL;
                } else {
                    $query = "SELECT * FROM `{$table}` LIMIT {$offset}, {$batchSize}";
                }

                $this->noResultRows = false;
                $rows = $db->get_results($query, ARRAY_A);

                // Call to mysql_free_result
                $db->flush();

                if (!empty($db->last_error)) {
                    \WPStaging\functions\debug_log($db->last_error);
                }

                // We're done here.
                if (empty($rows) || count($rows) === 0) {
                    $this->noResultRows = true;
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

            // save the last fetched primary key for next requests
            if (!empty($numericPrimaryKey)) {
                $this->lastFetchedPrimaryKeyValue = $row[$numericPrimaryKey];
            }

            yield $row;

            // It's actually processed when the caller code returns the control to the Generator.
            $processed++;
        } while (!$this->isThreshold() && $processed < $limit);

        $db->suppress_errors($suppressErrorsOriginal);
    }
}
