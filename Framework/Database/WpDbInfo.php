<?php

namespace WPStaging\Framework\Database;

class WpDbInfo implements iDbInfo
{
    /**
     * @var \wpdb
     */
    protected $wpdb;

    /**
     * @param \wpdb $wpdb
     */
    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    /**
     * Get the database default collation: collation_database
     * @return string
     */
    public function getDbCollation(): string
    {
        $result = $this->wpdb->dbh->query("SHOW VARIABLES LIKE 'collation_database'");

        $output = '';
        if ($obj = $result->fetch_object()) {
            $output = $obj->Value;
        }

        return $output;
    }

    /**
     * @return string
     */
    public function getDbEngine(): string
    {
        $result = $this->wpdb->dbh->query("SHOW VARIABLES LIKE 'default_storage_engine'");

        $output = '';
        if ($obj = $result->fetch_object()) {
            $output = $obj->Value;
        }

        return $output;
    }

    /**
     * @return int
     */
    public function getMySqlServerVersion(): int
    {
        return $this->wpdb->dbh->server_version;
    }

    /**
     * @return int
     */
    public function getMySqlClientVersion(): int
    {
        return $this->wpdb->dbh->client_version;
    }

    /**
     * @return string
     */
    public function getServerIp(): string
    {
        $queryToFindHost = "SHOW VARIABLES WHERE Variable_name = 'hostname';";
        return $this->wpdb->get_var($queryToFindHost, 1);
    }

    /**
     * @return int
     */
    public function getServerPort(): int
    {
        $queryToFindPort = "SHOW VARIABLES WHERE Variable_name = 'port';";
        return (int)$this->wpdb->get_var($queryToFindPort, 1);
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
}
