<?php

namespace WPStaging\Framework\Database;

use wpdb;

class WpDbInfo implements iDbInfo
{
    /**
     * Default version to use when the version cannot be determined.
     * @var int
     */
    const DEFAULT_VERSION = -1;

    /**
     * @var wpdb
     */
    protected $wpdb;

    /**
     * @param wpdb $wpdb
     */
    public function __construct(wpdb $wpdb)
    {
        $this->wpdb = $wpdb;
    }

    /**
     * Get the database default collation: collation_database
     * @return string
     */
    public function getDbCollation(): string
    {
        return $this->getVariableByName('collation_database');
    }

    /**
     * @return string
     */
    public function getDbEngine(): string
    {
        return $this->getVariableByName('default_storage_engine');
    }

    /**
     * @return int
     */
    public function getMySqlServerVersion(): int
    {
        if (!is_null($this->wpdb->dbh)) {
            return $this->wpdb->dbh->server_version;
        }

        $value = $this->wpdb->get_var("SELECT @@version");

        return is_null($value) ? self::DEFAULT_VERSION : $this->versionToInt($value);
    }

    /**
     * @return int
     */
    public function getMySqlClientVersion(): int
    {
        if (!is_null($this->wpdb->dbh)) {
            return $this->wpdb->dbh->client_version;
        }

        return self::DEFAULT_VERSION;
    }

    /**
     * @return string
     */
    public function getServerIp(): string
    {
        return $this->getVariableByName('hostname');
    }

    /**
     * @return int
     */
    public function getServerPort(): int
    {
        return (int)$this->getVariableByName('port');
    }

    /**
     * Return the server name and port as server:port
     * @return string
     */
    public function getServer(): string
    {
        return $this->getServerIp() . ':' . $this->getServerPort();
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'db_engine'     => $this->getDbEngine(),
            'db_collation'  => $this->getDbCollation(),
            'db_server_ver' => $this->getMySqlServerVersion(),
            'db_client_ver' => $this->getMySqlClientVersion()
        ];
    }

    /**
     * Fetch the database variable value by name.
     * @param string $varName
     * @return string
     */
    protected function getVariableByName(string $varName): string
    {
        $query = "SHOW VARIABLES WHERE Variable_name = '" . $varName . "';";
        $value = $this->wpdb->get_var($query, 1);

        return is_null($value) ? '' : $value;
    }

    /**
     * Convert version string to integer.
     *
     * @param string $version MySQL server version
     * @return int
     */
    protected static function versionToInt(string $version): int
    {
        $match = explode('.', $version);

        return (int)sprintf('%d%02d%02d', $match[0], $match[1], intval($match[2]));
    }
}
