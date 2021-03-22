<?php
/**
 * IMPORTANT use only named queries or WP queries
 */
/** @noinspection PhpUndefinedClassInspection */

namespace WPStaging\Framework\Adapter;

use RuntimeException;
use wpdb;
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

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
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
     * @return string
     */
    public function getPrefix()
    {
        return $this->wpdb->prefix;
    }

    /**
     * Return escaped prefix to use it in sql queries like 'wp\_'
     * @param string|null $prefix
     * @return string|string[]
     */
    public function provideSqlPrefix($prefix = null)
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
