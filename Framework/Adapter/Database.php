<?php

/**
 * IMPORTANT use only named queries or WP queries
 */

/** @noinspection PhpUndefinedClassInspection */

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
    /** @var MysqliAdapter|SqliteAdapter */
    private $client;

    /** @var WpDbAdapter */
    private $wpdba;

    /** @var wpdb */
    private $wpdb;

    /** @var string */
    private $productionPrefix;

    /** @var string|null */
    private $mysqlVersion;

    /**
     * @param wpdb $wpDatabase
     */
    public function __construct($wpDatabase = null)
    {
        $this->setWpDatabase($wpDatabase);
    }

    /**
     * @param wpdb|null $wpDatabase
     * @return void
     */
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

    /**
     * @return WpDbAdapter
     * @noinspection PhpUnused
     */
    public function getWpdba()
    {
        return $this->wpdba;
    }

    /**
     * Will always return prefix in the lowercase for Windows.
     * In *nix it will return prefix in whatever the case it was written in wp-config.
     *
     * @return string
     */
    public function getPrefix(): string
    {
        if (WPStaging::isWindowsOs() || $this->getLowerTablesNameSettings() === '1') {
            return strtolower($this->wpdb->prefix);
        }

        return $this->wpdb->prefix;
    }

    /**
     * @return string
     */
    public function getBasePrefix(): string
    {
        return $this->wpdb->base_prefix;
    }

    /**
     * @return string
     */
    public function getProductionPrefix()
    {
        return $this->productionPrefix;
    }

    /**
     * @return bool
     */
    public function isExternal()
    {
        return !($this->wpdb->__get('dbhost') === DB_HOST && $this->wpdb->__get('dbname') === DB_NAME);
    }

    /**
     * Return escaped prefix to use it in sql queries like 'wp\_'
     *
     * _ is interpreted as a single-character wildcard when
     * executed in a LIKE SQL statement.
     *
     * @param string|null $prefix
     * @return string|string[]
     */
    public function escapeSqlPrefixForLIKE($prefix = null)
    {
        if (!$prefix) {
            $prefix = $this->getPrefix();
        }

        return str_replace('_', '\_', $prefix);
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getCharset()
    {
        return $this->wpdb->get_charset_collate();
    }

    /**
     * @param string $statement
     * @return bool
     */
    public function exec($statement)
    {
        return $this->wpdba->exec($statement);
    }

    /**
     * TODO: use client directly
     * @param string $sql
     * @param array $conditions
     * @return SplObjectStorage|null
     */
    public function find($sql, array $conditions = [])
    {
        return $this->wpdba->find($sql, $conditions);
    }

    /**
     * @param bool $compact if true return just the version with other info
     * @param bool $refresh if true fetch info again from server
     * @return string
     */
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

    /**
     * @return string
     */
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

    /**
     * @return MysqliAdapter|SqliteAdapter
     */
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

    /**
     * @return wpdb
     */
    public function getWpdb()
    {
        return $this->wpdb;
    }

    /**
     * @param int $subsiteId
     * @return string
     */
    public function getPrefixBySubsiteId(int $subsiteId): string
    {
        if ($subsiteId === 0 || $subsiteId === 1) {
            return $this->getBasePrefix();
        }

        return $this->getBasePrefix() . $subsiteId . '_';
    }

    /**
     * @return string
     */
    public function getLowerTablesNameSettings(): string
    {
        $result = $this->getClient()->query("SHOW VARIABLES LIKE 'lower_case_table_names'");
        if (!$result) {
            return 'N/A';
        }

        $resultRow = $this->getClient()->fetchAssoc($result);
        // Note: Use "empty" to check it is an array and not an empty array, false or null.
        //       Use "isset" to check for the existence of a "Value" key regardless of its data.
        if (!empty($resultRow) && isset($resultRow['Value'])) {
            // Should return either 0, 1, 2
            return $resultRow['Value'];
        }

        return 'N/A';
    }
}
