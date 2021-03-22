<?php

namespace WPStaging\Core\Utils;

/**
 * Description of MySQL
 *
 * @todo Confirm if it's deprecated and remove.
 * @see \WPStaging\Framework\Adapter\Database\MysqlAdapter Similar class
 */
class MySQL
{

    private $wpdb;

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    /**
     * Run MySQL query
     *
     * @param  string   $input SQL query
     * @return resource
     */
    public function query($input)
    {
        // phpcs:ignore PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
        return mysql_query($input, $this->wpdb->dbh);
    }

    /**
     * Escape string input for mysql query
     *
     * @param  string $input String to escape
     * @return string
     */
    public function escape($input)
    {
        // phpcs:ignore PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
        return mysql_real_escape_string($input, $this->wpdb->dbh);
    }

    /**
     * Return the error code for the most recent function call
     *
     * @return integer
     */
    public function errno()
    {
        // phpcs:ignore PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
        return mysql_errno($this->wpdb->dbh);
    }

    /**
     * Return a string description of the last error
     *
     * @return string
     */
    public function error()
    {
        // phpcs:ignore PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
        return mysql_error($this->wpdb->dbh);
    }

    /**
     * Return server version
     *
     * @return string
     */
    public function version()
    {
        // phpcs:ignore PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
        return mysql_get_server_info($this->wpdb->dbh);
    }

    /**
     * Return the result from MySQL query as associative array
     *
     * @param  resource $result MySQL resource
     * @return array
     */
    public function fetchAssoc($result)
    {
        // phpcs:ignore PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
        return mysql_fetch_assoc($result);
    }

    /**
     * Return the result from MySQL query as row
     *
     * @param  resource $result MySQL resource
     * @return array
     */
    public function fetchRow($result)
    {
        // phpcs:ignore PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
        return mysql_fetch_row($result);
    }

    /**
     * Return the number for rows from MySQL results
     *
     * @param  resource $result MySQL resource
     * @return integer
     */
    public function numRows($result)
    {
        // phpcs:ignore PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
        return mysql_num_rows($result);
    }

    /**
     * Free MySQL result memory
     *
     * @param  resource $result MySQL resource
     * @return void
     */
    public function freeResult($result)
    {
        // phpcs:ignore PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
        mysql_free_result($result);
    }
}
