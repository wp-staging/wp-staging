<?php
/** @noinspection PhpComposerExtensionStubsInspection */

namespace WPStaging\Framework\Adapter\Database;

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

}
