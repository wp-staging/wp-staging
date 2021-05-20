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

    /**
     * Returns a generator of rows.
     *
     * The Generator will fetch the candidate rows to process in batches and return
     * them transparently to the caller code.
     * If the current thread is over 80% memory or execution time, then the Generator will yield `null` to stop
     * the processing.
     *
     * @param string $table  The prefixed name of the table to pull rows from.
     * @param int    $offset The number of row to start the work from.
     * @param int    $limit  The maximum number of rows to try and process; the actual number of
     *                       processed will depend on the server available memory and max request execution time.
     * @param \wpdb|null A reference to the database instance to fetch rows from.
     *
     * @return Generator  A generator yielding rows one by one; refetching them if and when required.
     */
    protected function rowsGenerator($table, $offset, $limit, \wpdb $db = null)
    {
/*        if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
            error_log(
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


        if (null === $db) {
            global $wpdb;
            $db = $wpdb;
        }

        $suppressErrorsOriginal = $db->suppress_errors;
        $db->suppress_errors(false);

        // Sets the execution time limit to either a detected value below 10s and above 1s, or a safe 10s value.
        $this->setTimeLimit(min(10, max((int)$this->findExecutionTimeLimit(), 1)));

        $rows = [];
        $processed = 0;
        // More rows equals more memory; to process more let's reduce the memory footprint by using smaller fetch sizes.
        $batchSize = $limit / 5;
        $lastFetch = false;

        do {
            if (count($rows) === 0) {
                if ($lastFetch) {
                    break;
                }

                $batchSize = ceil($batchSize);

                $rows = $db->get_results("SELECT * FROM {$table} LIMIT {$offset}, {$batchSize}", ARRAY_A);

                if (!empty($db->last_error)) {
                    if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
                        error_log($db->last_error);
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

                $offset += $batchSize;
                // If we got less than the batch size, then this is the last fetch.
                $lastFetch = count($rows) < $batchSize;
            }

            // Take the next row from the ready set.
            $row = array_shift($rows);

            if (null === $row) {
                // We're done, no more rows to process.
                break;
            }

            yield $row;

            // It's actually processed when the caller code returns the control to the Generator.
            $processed++;
        } while (!$this->isThreshold() && $processed < $limit);

        $db->suppress_errors($suppressErrorsOriginal);
    }
}
