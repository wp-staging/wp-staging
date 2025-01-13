<?php
namespace WPStaging\Backup\Service\Database\Importer\Insert;
use WPStaging\Backup\Dto\Service\DatabaseImporterDto;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Adapter\Database\InterfaceDatabaseClient;
use WPStaging\Backup\Dto\Job\JobRestoreDataDto;
use WPStaging\Framework\Traits\ApplyFiltersTrait;
use WPStaging\Framework\Traits\I18nTrait;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
abstract class QueryInserter
{
    use I18nTrait;
    use ApplyFiltersTrait;
    protected $client;
    protected $databaseImporterDto;
    protected $limitedMaxAllowedPacket;
    protected $realMaxAllowedPacket;
    protected $maxInnoDbLogSize;
    protected $currentDbVersion;
    protected $backupDbVersion;
    protected $warnings = [];

    public function setDbVersions(string $currentDbVersion, string $backupDbVersion)
    {
        $this->currentDbVersion = $currentDbVersion;
        $this->backupDbVersion  = $backupDbVersion;
    }

    protected $error = false;

    public function initialize(InterfaceDatabaseClient $client, DatabaseImporterDto $databaseImporterDto)
    {
        $this->client              = $client;
        $this->databaseImporterDto = $databaseImporterDto;
        $this->setMaxAllowedPackage();
        $this->setInnoDbLogFileSize();
        $this->warnings = [];
    }

    public function getWarnings(): array
    {
        return $this->warnings;
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
        try {
            if (isset($this->client->isSQLite) && $this->client->isSQLite) {
                $realMaxAllowedPacket = 16777216;
            } else {
                $result = $this->client->query("SHOW VARIABLES LIKE 'max_allowed_packet'");
                $row    = $this->client->fetchAssoc($result);
                $this->client->freeResult($result);
                $realMaxAllowedPacket = $this->getNumberFromResult($row);
            }
            $limitedMaxAllowedPacket = max(16 * KB_IN_BYTES, 0.9 * $realMaxAllowedPacket);
            $limitedMaxAllowedPacket = min(2 * MB_IN_BYTES, $limitedMaxAllowedPacket);
        } catch (\Exception $e) {
            $limitedMaxAllowedPacket = (1 * MB_IN_BYTES) * 0.9;
        } catch (\Error $ex) {
            $limitedMaxAllowedPacket = (1 * MB_IN_BYTES) * 0.9;
        }
        $limitedMaxAllowedPacket = $this->applyFilters('wpstg.restore.database.maxAllowedPacket', $limitedMaxAllowedPacket);
        $this->limitedMaxAllowedPacket = (int)$limitedMaxAllowedPacket;
        $this->realMaxAllowedPacket = (int)$realMaxAllowedPacket;
    }

    protected function setInnoDbLogFileSize()
    {
        try {
            $innoDbLogFileSize       = $this->client->query("SHOW VARIABLES LIKE 'innodb_log_file_size';");
            $innoDbLogFileSizeResult = $this->client->fetchAssoc($innoDbLogFileSize);
            $innoDbLogFileSize       = $this->getNumberFromResult($innoDbLogFileSizeResult);
            $innoDbLogFileGroups       = $this->client->query("SHOW VARIABLES LIKE 'innodb_log_files_in_group';");
            $innoDbLogFileGroupsResult = $this->client->fetchAssoc($innoDbLogFileGroups);
            $innoDbLogFileGroups       = $this->getNumberFromResult($innoDbLogFileGroupsResult);
            $innoDbLogSize = $innoDbLogFileSize * $innoDbLogFileGroups;
            $innoDbLogSize = max(1 * MB_IN_BYTES, $innoDbLogSize * 0.9);
            $innoDbLogSize = min(64 * MB_IN_BYTES, $innoDbLogSize);
        } catch (\Exception $e) {
            $innoDbLogSize = 9 * MB_IN_BYTES;
        } catch (\Error $ex) {
            $innoDbLogSize = 9 * MB_IN_BYTES;
        }
        $innoDbLogSize = $this->applyFilters('wpstg.restore.database.innoDbLogSize', $innoDbLogSize);
        $this->maxInnoDbLogSize = (int)$innoDbLogSize;
    }

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

    public function getLastError()
    {
        return $this->error;
    }

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

    protected function addWarning(string $message)
    {
        $this->warnings[] = $message;
    }
}
