<?php





 

namespace WPStaging\Framework\Adapter;

use RuntimeException;
use wpdb;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Database\MysqliAdapter;
use WPStaging\Framework\Adapter\Database\SqliteAdapter;
use WPStaging\Framework\Adapter\Database\WpDbAdapter;
use WPStaging\Framework\Adapter\Database\InterfaceDatabaseClient;
use SplObjectStorage;
use WP_SQLite_Translator;

class Database implements DatabaseInterface
{
 
    private $client;

 
    private $wpdba;

 
    private $wpdb;

 
    private $productionPrefix;

 
    private $mysqlVersion;




    public function __construct($wpDatabase = null)
    {
        $this->setWpDatabase($wpDatabase);
    }





    public function setWpDatabase($wpDatabase = null)
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        if ($wpDatabase !== null && $wpDatabase !== $wpdb) {
            $this->wpdb = $wpDatabase;
        }

        $this->mysqlVersion     = null;
        $this->productionPrefix = $wpdb->prefix;
        $this->wpdba            = new WpDbAdapter($this->wpdb);
        $this->client           = $this->findClient();
    }

    public function getClient(): InterfaceDatabaseClient
    {
        return $this->client;
    }





    public function getWpdba()
    {
        return $this->wpdba;
    }







    public function getPrefix(): string
    {
        if (WPStaging::isWindowsOs() || $this->getLowerTablesNameSettings() === '1') {
            return strtolower($this->wpdb->prefix);
        }

        return $this->wpdb->prefix;
    }




    public function getBasePrefix(): string
    {
        return $this->wpdb->base_prefix;
    }




    public function getProductionPrefix()
    {
        return $this->productionPrefix;
    }




    public function isExternal()
    {
        return !($this->wpdb->__get('dbhost') === DB_HOST && $this->wpdb->__get('dbname') === DB_NAME);
    }










    public function escapeSqlPrefixForLIKE($prefix = null)
    {
        if (!$prefix) {
            $prefix = $this->getPrefix();
        }

        return str_replace('_', '\_', $prefix);
    }





    public function getCharset()
    {
        return $this->wpdb->get_charset_collate();
    }





    public function exec($statement)
    {
        return $this->wpdba->exec($statement);
    }







    public function find($sql, array $conditions = [])
    {
        return $this->wpdba->find($sql, $conditions);
    }






    public function getSqlVersion(bool $compact = false, bool $refresh = false): string
    {
        if ($refresh || empty($this->mysqlVersion)) {
            $this->mysqlVersion = $this->wpdb->get_var('SELECT VERSION()');
        }

        if (empty($this->mysqlVersion)) {
            $this->mysqlVersion = $this->wpdb->db_version();
        }

        if (!$compact) {
            return $this->mysqlVersion;
        }

        return explode('-', explode(' ', explode('_', $this->mysqlVersion)[0])[0])[0];
    }




    public function getServerType()
    {
        $dbInfo = $this->getSqlVersion();
        if (strpos($dbInfo, 'maria')) {
            return 'MariaDB';
        }

        if (strpos($dbInfo, 'percona')) {
            return 'Percona';
        }

        return 'MySQL';
    }




    private function findClient()
    {
        $link = $this->wpdb->dbh;
        if (!$link) {
            throw new RuntimeException('Database handler / link is not set');
        }

        // @phpstan-ignore-next-line
        if ($link instanceof WP_SQLite_Translator) {
            return new SqliteAdapter($link);
        }

        if (isset($this->wpdb->use_mysqli) && (bool)$this->wpdb->use_mysqli !== true) {
            throw new RuntimeException('Use of mysql_* functions is not allowed');
        }

        return new MysqliAdapter($link);
    }




    public function getWpdb()
    {
        return $this->wpdb;
    }





    public function getPrefixBySubsiteId(int $subsiteId): string
    {
        if ($subsiteId === 0 || $subsiteId === 1) {
            return $this->getBasePrefix();
        }

        return $this->getBasePrefix() . $subsiteId . '_';
    }




    public function getLowerTablesNameSettings(): string
    {
        $result = $this->getClient()->query("SHOW VARIABLES LIKE 'lower_case_table_names'");
        if (!$result) {
            return 'N/A';
        }

        $resultRow = $this->getClient()->fetchAssoc($result);
 
 
        if (!empty($resultRow) && isset($resultRow['Value'])) {
 
            return $resultRow['Value'];
        }

        return 'N/A';
    }
}
