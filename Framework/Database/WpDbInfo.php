<?php

namespace WPStaging\Framework\Database;

use wpdb;

class WpDbInfo implements iDbInfo
{




    const DEFAULT_VERSION = -1;




    protected $wpdb;




    public function __construct(wpdb $wpdb)
    {
        $this->wpdb = $wpdb;
    }





    public function getDbCollation(): string
    {
        return $this->getVariableByName('collation_database');
    }




    public function getDbEngine(): string
    {
        return $this->getVariableByName('default_storage_engine');
    }




    public function getMySqlServerVersion(): int
    {
        if (!is_null($this->wpdb->dbh)) {
            return $this->wpdb->dbh->server_version;
        }

        $value = $this->wpdb->get_var("SELECT @@version");

        return is_null($value) ? self::DEFAULT_VERSION : $this->versionToInt($value);
    }




    public function getMySqlClientVersion(): int
    {
        if (!is_null($this->wpdb->dbh)) {
            return $this->wpdb->dbh->client_version;
        }

        return self::DEFAULT_VERSION;
    }




    public function getServerIp(): string
    {
        return $this->getVariableByName('hostname');
    }




    public function getServerPort(): int
    {
        return (int)$this->getVariableByName('port');
    }





    public function getServer(): string
    {
        return $this->getServerIp() . ':' . $this->getServerPort();
    }




    public function toArray(): array
    {
        return [
            'db_engine'     => $this->getDbEngine(),
            'db_collation'  => $this->getDbCollation(),
            'db_server_ver' => $this->getMySqlServerVersion(),
            'db_client_ver' => $this->getMySqlClientVersion(),
        ];
    }






    protected function getVariableByName(string $varName): string
    {
        $query = "SHOW VARIABLES WHERE Variable_name = '" . $varName . "';";
        $value = $this->wpdb->get_var($query, 1);

        return is_null($value) ? '' : $value;
    }







    protected static function versionToInt(string $version): int
    {
        $match = explode('.', $version);

        return (int)sprintf('%d%02d%02d', $match[0], $match[1], intval($match[2]));
    }
}
