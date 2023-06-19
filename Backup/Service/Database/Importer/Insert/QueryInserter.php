<?php

namespace WPStaging\Backup\Service\Database\Importer\Insert;

use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Adapter\Database\WpDbAdapter;
use WPStaging\Framework\Adapter\Database\InterfaceDatabaseClient;
use WPStaging\Framework\Traits\ResourceTrait;
use WPStaging\Backup\Dto\Job\JobRestoreDataDto;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

abstract class QueryInserter
{
    use ResourceTrait;

    /** @var WpDbAdapter */
    protected $wpdb;

    /** @var InterfaceDatabaseClient */
    protected $client;

    /** @var LoggerInterface */
    protected $logger;

    /** @var JobRestoreDataDto */
    protected $jobRestoreDataDto;

    /** @var int */
    protected $limitedMaxAllowedPacket;

    /** @var int */
    protected $realMaxAllowedPacket;

    /** @var int */
    protected $maxInnoDbLogSize;

    /** @var Database */
    protected $database;

    /**
     * Error message if any, false if no error
     *
     * @var bool|string
     */
    protected $error = false;

    /**
     * @param Database $database
     * @param JobRestoreDataDto $jobRestoreDataDto
     * @param LoggerInterface $logger
     */
    public function initialize(Database $database, JobRestoreDataDto $jobRestoreDataDto, LoggerInterface $logger)
    {
        $this->database = $database;
        $this->client = $database->getClient();
        $this->wpdb = $database->getWpdba();
        $this->jobRestoreDataDto = $jobRestoreDataDto;
        $this->logger = $logger;

        $this->setMaxAllowedPackage();
        $this->setInnoDbLogFileSize();
    }

    abstract public function processQuery(&$insertQuery);

    abstract public function commit();

    protected function exec(&$query)
    {
        $result = $this->client->query($query);

        return $result !== false;
    }

    protected function setMaxAllowedPackage()
    {
        // todo: This value should be dynamic if the task fails, similar to the batch size on backup creation
        try {
            $result = $this->client->query("SHOW VARIABLES LIKE 'max_allowed_packet'");
            $row = $this->client->fetchAssoc($result);

            // Free up memory
            $this->client->freeResult($result);

            $realMaxAllowedPacket = $this->getNumberFromResult($row);

            // Minimum: 16 KB | 90% of database max allowed packet to allow overhead safety
            $limitedMaxAllowedPacket = max(16 * KB_IN_BYTES, 0.9 * $realMaxAllowedPacket);

            // Maximum: 2MB | Limit the value. The lower the value, the faster the backup restore.
            $limitedMaxAllowedPacket = min(2 * MB_IN_BYTES, $limitedMaxAllowedPacket);
        } catch (\Exception $e) {
            // The default for MySQL 5.5 is 1 MB, so we stick with a little less by default.
            $limitedMaxAllowedPacket = (1 * MB_IN_BYTES) * 0.9;
        } catch (\Error $ex) {
            $limitedMaxAllowedPacket = (1 * MB_IN_BYTES) * 0.9;
        }

        $limitedMaxAllowedPacket = apply_filters('wpstg.restore.database.maxAllowedPacket', $limitedMaxAllowedPacket);

        $this->limitedMaxAllowedPacket = (int)$limitedMaxAllowedPacket;

        $this->realMaxAllowedPacket = (int)$realMaxAllowedPacket;
    }

    protected function setInnoDbLogFileSize()
    {
        try {
            // Default values:
            // mySQL5.5 = 5MB
            // mySQL5.4.0 = 13MB
            // mySQL5.4.2 = 13MB
            // mariaDB10.5 = 96MB
            // mariaDB10.4 = 48MB
            $innoDbLogFileSize = $this->wpdb->getClient()->get_row("SHOW VARIABLES LIKE 'innodb_log_file_size';", ARRAY_A);
            $innoDbLogFileSize = $this->getNumberFromResult($innoDbLogFileSize);

            // Default values:
            // mySQL5.5 = 2
            // mySQL5.4.0 = 3
            // mySQL5.4.1 = 3
            // mySQL5.4.2 = 3
            $innoDbLogFileGroups = $this->wpdb->getClient()->get_row("SHOW VARIABLES LIKE 'innodb_log_files_in_group';", ARRAY_A);
            $innoDbLogFileGroups = $this->getNumberFromResult($innoDbLogFileGroups);

            $innoDbLogSize = $innoDbLogFileSize * $innoDbLogFileGroups;

            // Minimum: 1MB | Reduces it just to be extra-safe in case it was maxed out, to give some breathing room.
            $innoDbLogSize = max(1 * MB_IN_BYTES, $innoDbLogSize * 0.9);

            // Maximum: 64MB
            $innoDbLogSize = min(64 * MB_IN_BYTES, $innoDbLogSize);
        } catch (\Exception $e) {
            // The default for MySQL is 10 MB (5MB * 2), so we stick with a little less by default
            $innoDbLogSize = 9 * MB_IN_BYTES;
        } catch (\Error $ex) {
            $innoDbLogSize = 9 * MB_IN_BYTES;
        }

        $innoDbLogSize = apply_filters('wpstg.restore.database.innoDbLogSize', $innoDbLogSize);

        $this->maxInnoDbLogSize = (int)$innoDbLogSize;
    }

    /**
     * @throws \Exception
     *
     * @param $result
     *
     * @return int
     */
    private function getNumberFromResult($result)
    {
        if (
            is_array($result) &&
            array_key_exists('Value', $result) &&
            is_numeric($result['Value']) &&
            (int)$result['Value'] > 0
        ) {
            return (int)$result['Value'];
        } else {
            throw new \UnexpectedValueException();
        }
    }

    /** @return bool|string */
    public function getLastError()
    {
        return $this->error;
    }

    /**
     * Do the query size exceeds max allowed packet size
     *
     * @param string $query
     * @return bool
     */
    protected function doQueryExceedsMaxAllowedPacket($query)
    {
        $this->error = false;
        if (strlen($query) >= $this->realMaxAllowedPacket) {
            $this->error = sprintf(
                'Query: "%s" was skipped because it exceeded the mySQL maximum allowed packet size. Query size: %s | max_allowed_packet: %s. Follow this link: %s for details ',
                substr($query, 0, 1000) . '...',
                size_format(strlen($query)),
                size_format($this->limitedMaxAllowedPacket),
                'https://wp-staging.com/docs/increase-max_allowed_packet-size-in-mysql/'
            );

            return true;
        }

        return false;
    }
}
