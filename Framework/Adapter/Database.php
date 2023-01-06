<?php

/**
 * IMPORTANT use only named queries or WP queries
 */

/** @noinspection PhpUndefinedClassInspection */

namespace WPStaging\Framework\Adapter;

use RuntimeException;
use wpdb;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Database\InterfaceDatabase;
use WPStaging\Framework\Adapter\Database\InterfaceDatabaseClient;
use WPStaging\Framework\Adapter\Database\MysqlAdapter;
use WPStaging\Framework\Adapter\Database\MysqliAdapter;
use WPStaging\Framework\Adapter\Database\WpDbAdapter;
use SplObjectStorage;

class Database
{
    /** @var InterfaceDatabase  */
    private $client;

    /** @var WpDbAdapter */
    private $wpdba;

    /** @var wpdb */
    private $wpdb;

    /** @var string */
    private $productionPrefix;

    /** @var string */
    private $mysqlVersion;

    /**
     * @param wpdb $wpDatabase
     */
    public function __construct($wpDatabase = null)
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        if ($wpDatabase !== null && $wpDatabase !== $wpdb) {
            $this->wpdb = $wpDatabase;
        }

        $this->mysqlVersion = null;
        $this->productionPrefix = $wpdb->prefix;
        $this->wpdba = new WpDbAdapter($this->wpdb);
        $this->client = $this->findClient();
    }

    /**
     * @return InterfaceDatabase|null
     */
    public function getClient()
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
    public function getPrefix()
    {
        if (WPStaging::isWindowsOs()) {
            return strtolower($this->wpdb->prefix);
        }

        return $this->wpdb->prefix;
    }

    /**
     * @return string
     */
    public function getBasePrefix()
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
        return (bool)$this->wpdba->exec($statement);
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
    public function getSqlVersion($compact = false, $refresh = false)
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
     * @return InterfaceDatabaseClient
     */
    private function findClient()
    {
        $link = $this->wpdb->dbh;
        if (!$link) {
            throw new RuntimeException('Database handler / link is not set');
        }

        if ($this->wpdb->use_mysqli) {
            return new MysqliAdapter($link);
        }

        return new MysqlAdapter($link);
    }
}
