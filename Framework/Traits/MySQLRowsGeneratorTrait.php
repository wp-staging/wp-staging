<?php








namespace WPStaging\Framework\Traits;

use Generator;
use WPStaging\Framework\Adapter\Database\InterfaceDatabaseClient;
use WPStaging\Framework\Adapter\Database\MysqliAdapter;
use WPStaging\Framework\Adapter\Database\SqliteAdapter;
use WPStaging\Framework\Job\Dto\JobDataDto;






trait MySQLRowsGeneratorTrait
{
    use ResourceTrait;
    use BatchSizeCalculateTrait;

 
    protected $useMemoryExhaustFix = false;

 
    protected $columnToExclude = '';

 
    protected $valuesToExclude = '';




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
















        $rows      = [];
        $lastFetch = false;
        $batchSize = $this->calculateBatchSize($databaseName, $table, $offset, $requestId, $jobDataDto, $db);

        do {
            if (empty($rows)) {
                if ($lastFetch) {
                    break;
                }

                if ($this->columnToExclude && $this->valuesToExclude) {
                    $query                 = $this->getQueryForExclusion($numericPrimaryKey, $table, $offset, (string)$batchSize);
                    $this->columnToExclude = '';
                    $this->valuesToExclude = '';
                } else {
                    $query = $this->getQueryWithoutExclusion($numericPrimaryKey, $table, $offset, (string)$batchSize);
                }

                $jobDataDto->setLastQueryInfoJSON(json_encode([$requestId, $table, $offset, $batchSize]));

                $requestStartTime = microtime(true);

                $result = $db->query($query);

                $jobDataDto->setDbRequestTime(microtime(true) - $requestStartTime);

 
                if ($jobDataDto->getDbRequestTime() > 10) {
                    $jobDataDto->setIsSlowMySqlServer(true);
                }

                if ($result === false) {
                    $errorMessage = $db->error();
                    if (stripos($errorMessage, 'corrupt') !== false || stripos($errorMessage, 'repair') !== false) {
                        throw new \RuntimeException(sprintf(
                            'Storage engine for the table "%s" does not support SQL command REPAIR TABLE. Please read this https://wp-staging.com/phpmyadmin-repair-and-optimize-database-tables-tutorial/ to learn how to repair the table manually first.',
                            esc_html($table)
                        ));
                    }

                    throw new \RuntimeException('DB error: ' . $errorMessage . ' Query: ' . $query . ' requestId: ' . $requestId . ' table: ' . $table . ' Offset: ' . $offset . ' Batch Size: ' . $batchSize);
                }

                $rows = [];
 
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

 
                $lastFetch = count($rows) < $batchSize;
            }

 
            $row = array_pop($rows);

            if ($row === null) {
 
                break;
            }









            if ($batchSize <= 100 && $jobDataDto->getDbRequestTime() > 1) {
                $this->setTimeLimit((int)$this->findExecutionTimeLimit() + 10);
            }

 
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









    private function getQueryForExclusion($numericPrimaryKey, string $table, string $offset, string $batchSize): string
    {
        if (empty($numericPrimaryKey)) {
            return "SELECT * FROM `{$table}` WHERE `{$this->columnToExclude}` NOT IN ({$this->valuesToExclude}) AND LIMIT {$offset}, {$batchSize}";
        }

 
        return <<<SQL
SELECT  * 
FROM `{$table}` 
WHERE `{$numericPrimaryKey}` > {$offset} 
AND `{$this->columnToExclude}` NOT IN ({$this->valuesToExclude}) 
ORDER BY `{$numericPrimaryKey}` ASC 
LIMIT 0, {$batchSize} 
SQL;
    }









    private function getQueryWithoutExclusion($numericPrimaryKey, string $table, string $offset, string $batchSize): string
    {
        if (empty($numericPrimaryKey)) {
            return "SELECT * FROM `{$table}` LIMIT {$offset}, {$batchSize}";
        }

 
        return <<<SQL
SELECT  *
FROM `{$table}`
WHERE `{$numericPrimaryKey}` > {$offset}
ORDER BY `{$numericPrimaryKey}` ASC
LIMIT 0, {$batchSize}
SQL;
    }
}
