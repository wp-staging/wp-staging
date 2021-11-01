<?php

/** @noinspection PhpComposerExtensionStubsInspection */

namespace WPStaging\Framework\Adapter\Database;

use mysqli_result;

/**
 * Class MysqlAdapter
 *
 * @todo check if this is still being used and maybe remove
 * @see \WPStaging\Core\Utils\MySQL Similar class
 *
 * @package WPStaging\Framework\Adapter\Database
 */
class MysqlAdapter implements InterfaceDatabaseClient
{
    /** @var string|null */
    private $link;

    /**
     * MysqlAdapter constructor.
     * @param string|null $link
     */
    public function __construct($link = null)
    {
        $this->link = $link;
    }

    /**
     * @inheritDoc
     */
    public function query($query, $isExecOnly = false)
    {
        // phpcs:ignore PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
        return mysql_query($query, $this->link);
    }

    public function realQuery($query, $isExecOnly = false)
    {
        \WPStaging\functions\debug_log('mysql_real_query() doesn\'t exist in PHP. However, mysqli_real_query() exists.');

        return $this->query($query, $isExecOnly);
    }

    /**
     * @inheritDoc
     */
    public function escape($input)
    {
        // phpcs:ignore PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
        return mysql_real_escape_string($input, $this->link);
    }

    /**
     * @inheritDoc
     */
    public function errno()
    {
        // phpcs:ignore PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
        return mysql_errno($this->link);
    }

    /**
     * @inheritDoc
     */
    public function error()
    {
        // phpcs:ignore PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
        return mysql_error($this->link);
    }

    /**
     * @inheritDoc
     */
    public function version()
    {
        // phpcs:ignore PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
        return mysql_get_server_info($this->link);
    }

    /**
     * @inheritDoc
     */
    public function fetchAssoc($result)
    {
        // phpcs:ignore PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
        return mysql_fetch_assoc($result);
    }

    /**
     * @inheritDoc
     */
    public function fetchRow($result)
    {
        // phpcs:ignore PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
        return mysql_fetch_row($result);
    }

    public function fetchObject($result)
    {
        // phpcs:ignore PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
        return mysql_fetch_object($result);
    }

    /**
     * @inheritDoc
     */
    public function numRows($result)
    {
        // phpcs:ignore PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
        return mysql_num_rows($result);
    }

    /**
     * @inheritDoc
     */
    public function freeResult($result)
    {
        if ($result === null) {
            return null;
        }

        // phpcs:ignore PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
        mysql_free_result($result);

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function insertId()
    {
        // phpcs:ignore PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
        return mysql_insert_id($this->link);
    }

    /**
     * {@inheritdoc}
     */
    public function foundRows()
    {
        // phpcs:ignore PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
        return mysql_affected_rows($this->link);
    }
}
