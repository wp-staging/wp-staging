<?php

namespace WPStaging\Framework\Database;

use WPStaging\Vendor\Psr\Log\LoggerInterface;
use RuntimeException;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Adapter\Database\InterfaceDatabaseClient;
use WPStaging\Framework\Filesystem\File;
use WPStaging\Core\Utils\Logger;

class DatabaseRestore
{
    /** @var File */
    private $file;

    /** @var callable */
    private $shouldStop;

    /** @var int */
    private $totalLines;

    /** @var int */
    private $currentLine;

    /** @var bool */
    private $isTransactionStarted;

    /** @var bool */
    private $isCommitted;

    /** @var InterfaceDatabaseClient */
    private $client;

    /** @var LoggerInterface */
    private $logger;

    /** @var SearchReplace|null */
    private $searchReplace;

    /** @var Database\WpDbAdapter  */
    private $wpdb;

    public function __construct(Database $database)
    {
        $this->client = $database->getClient();
        $this->wpdb = $database->getWpdba();
    }

    /**
     * @param string $filePath Full file path
     * @return $this
     */
    public function setFile($filePath)
    {
        $this->file = new File($filePath);
        $this->totalLines = $this->file->totalLines();
        return $this;
    }

    public function seekLine($line)
    {
        if (!$this->file) {
            throw new RuntimeException('Restore file is not set');
        }
        $this->file->seek($line);
        $this->currentLine = $line;
        return $this;
    }

    public function restore()
    {
        if (!$this->file) {
            throw new RuntimeException('Restore file is not set');
        }

        $result = null;
        while(!$this->stopExecution()) {
            $result = $this->execute();
        }

        return $result;
    }

    public function stopExecution()
    {
        return $this->isShouldStop() || $this->file->eof();
    }

    public function setShouldStop(callable $shouldStop = null)
    {
        $this->shouldStop = $shouldStop;
        return $this;
    }

    public function getShouldStop()
    {
        return $this->shouldStop;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @param SearchReplace|null $searchReplace
     * @return $this
     */
    public function setSearchReplace(SearchReplace $searchReplace = null)
    {
        $this->searchReplace = $searchReplace;
        return $this;
    }

    /**
     * @return int
     */
    public function getCurrentLine()
    {
        return $this->currentLine;
    }

    public function getTotalLines()
    {
        return $this->totalLines;
    }

    private function execute()
    {
        $query = $this->findExecutableQuery();
        if (!$query || $this->file->eof()) {
            return true;
        }

        $query = $this->searchReplace($query);

        $isTransactionQuery = stripos($query, 'start transaction;') !== false;
        $isCommitQuery = stripos($query, 'commit;') !== false;

        $this->exec( "SET SESSION sql_mode = ''" );

        $this->maybeStartTransaction($query);

        $query = $this->replaceTableCollations( $query );

        $result = $this->exec($query);

        // Replace table engines (Azure)
        if ( $this->client->errno() === 1030 ) {
            $query = $this->replaceTableEngines( $query );
            $result = $this->exec( $query );
        }

        // Replace table row format (MyISAM and InnoDB)
        if ( $this->client->errno() === 1071 || $this->client->errno() === 1709 ) {
            $query = $this->replaceTableRowFormat( $query );
            $result = $this->exec( $query );
        }

        // Several possible further errors
        if ( $this->client->errno() === 1226 ) {
            if ( stripos( $this->client->error(), 'max_queries_per_hour' ) !== false ) {
                throw new \Exception(
                    'Your server has reached the maximum allowed queries per hour set by your admin or hosting provider. ' .
                    'Please increase MySQL max_queries_per_hour limit. ' .
                    '<a href="https://wp-staging.com/docs/mysql-database-error-codes/" target="_blank">Technical details</a>',
                    503
                );
            } elseif ( stripos( $this->client->error(), 'max_updates_per_hour' ) !== false ) {
                throw new \Exception(
                    'Your server has reached the maximum allowed updates per hour set by your admin or hosting provider. ' .
                    'Please increase MySQL max_updates_per_hour limit. ' .
                    '<a href="https://wp-staging.com/docs/mysql-database-error-codes/" target="_blank">Technical details</a>',
                    503
                );
            } elseif ( stripos( $this->client->error(), 'max_connections_per_hour' ) !== false ) {
                throw new \Exception(
                    'Your server has reached the maximum allowed connections per hour set by your admin or hosting provider. ' .
                    'Please increase MySQL max_connections_per_hour limit. ' .
                    '<a href="https://wp-staging.com/docs/mysql-database-error-codes/" target="_blank">Technical details</a>',
                    503
                );
            } elseif ( stripos( $this->client->error(), 'max_user_connections' ) !== false ) {
                throw new \Exception(
                    'Your server has reached the maximum allowed user connections set by your admin or hosting provider. ' .
                    'Please increase MySQL max_user_connections limit. ' .
                    '<a href="https://wp-staging.com/docs/mysql-database-error-codes/" target="_blank">Technical details</a>',
                    503
                );
            }
        }

        if (!$result) {
            $this->log(sprintf(
                'Failed to execute query on line: %d. Reason: %d - %s',
                $this->currentLine,
                $this->client->errno(),
                $this->client->error()
            ));
        }
        if ($isTransactionQuery && $result !== false) {
            $this->isTransactionStarted = true;
        }

        if ($isCommitQuery && $result !== false) {
            $this->isCommitted = true;
            $this->isTransactionStarted = false;
        }

        $this->maybeCommit();

        return $this->file->eof();
    }

    private function searchReplace($query)
    {
        if (!$this->searchReplace) {
            return $query;
        }

        // Replace only values area, not anything else
        // Matches (combination of either following);
        // insert into "wp_options" values(1,2,3,'something', "another")
        // INSERT INTO `other_table` VALUES (1,2,3, "Some Other stuff")
        // INSERT INTO `other_table`VALUES (1,2,3, "Some Other stuff")
        $patternValues = '#INSERT[ ]{0,}INTO[ ]{0,}[\`\"][a-zA-Z_\-0-9]+[\`\"][ ]{0,}VALUES[ ]{0,}\((.*?)\)#iU';
        if (!preg_match_all($patternValues, $query, $matches)) {
            return $query;
        }

        foreach ($matches[1] as $values) {
            $query = $this->searchReplaceValues($query, $values);
        }

        return $query;
    }

    private function searchReplaceValues($query, $values)
    {
        $replacedValues = $values;
        // No serialized strings, replace all values
        if (!preg_match_all('#\'([a0Os]:.*})\'#iu', $values, $serialized)) {
            $replacedValues = $this->searchReplace->replace($values);
            return str_replace($values, $replacedValues, $query);
        }

        // Replaced serialized string first
        foreach ($serialized[1] as $item) {
            $replacedItem = $this->searchReplace->replace($item);
            $replacedValues = str_replace($item, $replacedItem, $values);
        }

        // Replace other values
        $replacedValues = $this->searchReplace->replace($replacedValues);
        return str_replace($values, $replacedValues, $query);
    }

    private function findExecutableQuery()
    {
        while (!$this->file->eof() && !$this->isShouldStop()) {
            $line = $this->getLine();
            if ($this->isExecutableQuery($line)) {
                return $line;
            }
            $this->file->next();
        }
        return null;
    }

    private function getLine()
    {
        if ($this->file->eof()) {
            return null;
        }

        $line = trim($this->file->fgets());
        $this->currentLine++;

        return $line;
    }

    /**
     * Checks if given query / line is a valid, executable query
     * Valid SQL query for the moment means; only when it is not empty line or just a comment
     * @param string|null $query
     * @return string|null
     */
    private function isExecutableQuery($query = null)
    {
        if (!$query) {
            return false;
        }

        // Line starts with -- or # (to the end of the line) comments
        $first2Chars = substr($query, 0, 2);
        if ($first2Chars === '--' || strpos($query, '#') === 0) {
            return false;
        }

        // Line is not inline comments
        return !preg_match_all('#/\*(.*?)\*/#', $query, $matches)
            || (strlen(implode('', $matches[0])) < strlen($query))
        ;
    }

    private function isShouldStop()
    {
        return $this->shouldStop;
    }

    private function exec($query)
    {
        $result = $this->client->query($query, true);
        return $result !== false;
    }

    private function log($msg, $level = Logger::TYPE_WARNING)
    {
        if ($this->logger) {
            $this->logger->log($level, $msg);
        }
    }

    /**
     * Starts transaction if necessary
     * @param string $query
     */
    private function maybeStartTransaction($query)
    {
        if ($this->isTransactionStarted || strpos($query, 'INSERT INTO') !== 0) {
            return;
        }

        if ($this->exec('START TRANSACTION;')) {
            $this->isTransactionStarted = true;
            return;
        }

        $this->log(sprintf(
            'Failed to start transaction for the query line; %s. Reason: %d - %s',
            $this->currentLine,
            $this->client->errno(),
            $this->client->error()
        ));
    }

    /**
     * Commits the transaction if necessary
     */
    private function maybeCommit()
    {
        if (!$this->isTransactionStarted || $this->isCommitted || !$this->isShouldStop()) {
            return;
        }

        if ($this->exec('COMMIT;')) {
            return;
        }

        $this->log(sprintf(
            'Failed to commit for safe stop; %s. Reason: %d - %s',
            $this->currentLine,
            $this->client->errno(),
            $this->client->error()
        ));
    }

    /**
     * Replace table collations
     *
     * @param  string $input SQL statement
     * @return string
     */
    private function replaceTableCollations($input ) {
        static $search  = [];
        static $replace = [];

        // Replace table collations
        if ( empty( $search ) || empty( $replace ) ) {
            if ( ! $this->wpdb->getClient()->has_cap( 'utf8mb4_520' ) ) {
                if ( ! $this->wpdb->getClient()->has_cap( 'utf8mb4' ) ) {
                    $search  = [ 'utf8mb4_0900_ai_ci', 'utf8mb4_unicode_520_ci', 'utf8mb4' ];
                    $replace = [ 'utf8_unicode_ci', 'utf8_unicode_ci', 'utf8' ];
                } else {
                    $search  = [ 'utf8mb4_0900_ai_ci', 'utf8mb4_unicode_520_ci' ];
                    $replace = [ 'utf8mb4_unicode_ci', 'utf8mb4_unicode_ci' ];
                }
            } else {
                $search  = [ 'utf8mb4_0900_ai_ci' ];
                $replace = [ 'utf8mb4_unicode_520_ci' ];
            }
        }

        return str_replace( $search, $replace, $input );
    }

    /**
     * Replace table engines
     *
     * @param  string $input SQL statement
     * @return string
     */
    protected function replaceTableEngines( $input ) {
        // Set table replace engines
        $search  = [
            'ENGINE=MyISAM',
            'ENGINE=Aria',
        ];
        $replace = [
            'ENGINE=InnoDB',
            'ENGINE=InnoDB',
        ];

        return str_ireplace( $search, $replace, $input );
    }

    /**
     * Replace table row format
     *
     * @param  string $input SQL statement
     * @return string
     */
    protected function replaceTableRowFormat( $input ) {
        // Set table replace row format
        $search  = [
            'ENGINE=InnoDB',
            'ENGINE=MyISAM',
        ];
        $replace = [
            'ENGINE=InnoDB ROW_FORMAT=DYNAMIC',
            'ENGINE=MyISAM ROW_FORMAT=DYNAMIC',
        ];

        return str_ireplace( $search, $replace, $input );
    }
}
