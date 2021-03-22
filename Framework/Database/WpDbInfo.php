<?php

namespace WPStaging\Framework\Database;

class WpDbInfo implements iDbInfo
{
    /*
     * @var \wpdb
     */
    protected $wpdb;

    /*
     * @param \wpdb $wpdb
     */
    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    /*
     * Get the database default collation: collation_database
     * @return array
     */
    public function getDbCollation()
    {
        $result = $this->wpdb->dbh->query("SHOW VARIABLES LIKE 'collation_database'");

        $output = null;
        if ($obj = $result->fetch_object()) {
            $output = $obj->Value;
        }

        return $output;
    }

    /*
     * @return string|null
     */
    public function getDbEngine()
    {
        $result = $this->wpdb->dbh->query("SHOW VARIABLES LIKE 'default_storage_engine'");

        $output = null;
        if ($obj = $result->fetch_object()) {
            $output = $obj->Value;
        }

        return $output;
    }

    /*
     * @return int
     */
    public function getMySqlServerVersion()
    {
        return $this->wpdb->dbh->server_version;
    }

    /*
     * @return int
     */
    public function getMySqlClientVersion()
    {
        return $this->wpdb->dbh->client_version;
    }

    /*
     * @return array
     */
    public function toArray()
    {
        return [
            'db_engine' => $this->getDbEngine(),
            'db_collation' => $this->getDbCollation(),
            'db_server_ver' => $this->getMySqlServerVersion(),
            'db_client_ver' => $this->getMySqlClientVersion()
        ];
    }
}
