<?php
/**
 * IMPORTANT use only named queries or WP queries
 */
/** @noinspection PhpUndefinedClassInspection */

namespace WPStaging\Service\Adapter;

use wpdb;
use WPStaging\Service\Adapter\Database\InterfaceDatabase;
use WPStaging\Service\Adapter\Database\WpDbAdapter;
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
        $this->client = $this->wpdba;
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
     * @param string|null $prefix
     *
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
     *
     * @return bool
     */
    public function exec($statement)
    {
        return (bool)$this->client->exec($statement);
    }

    /**
     * @param string $sql
     * @param array $conditions
     *
     * @return SplObjectStorage|null
     */
    public function find($sql, array $conditions = [])
    {
        return $this->client->find($sql, $conditions);
    }
}
