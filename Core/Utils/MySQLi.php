<?php

namespace WPStaging\Core\Utils;

/**
 * Description of MySQL
 *
 * @todo Confirm if it's deprecated and remove.
 */
class MySQLi
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
        return mysqli_query($this->wpdb->dbh, $input, MYSQLI_STORE_RESULT);
    }

    /**
     * Escape string input for mysql query
     *
     * @param  string $input String to escape
     * @return string
     */
    public function escape($input)
    {
        return mysqli_real_escape_string($this->wpdb->dbh, $input);
    }

    /**
     * Return the error code for the most recent function call
     *
     * @return integer
     */
    public function errno()
    {
        return mysqli_errno($this->wpdb->dbh);
    }

    /**
     * Return a string description of the last error
     *
     * @return string
     */
    public function error()
    {
        return mysqli_error($this->wpdb->dbh);
    }

    /**
     * Return server version
     *
     * @return string
     */
    public function version()
    {
        return mysqli_get_server_info($this->wpdb->dbh);
    }

    /**
     * Return the result from MySQL query as associative array
     *
     * @param  resource $result MySQL resource
     * @return array
     */
    public function fetchAssoc($result)
    {
        return mysqli_fetch_assoc($result);
    }

    /**
     * Return the result from MySQL query as row
     *
     * @param  resource $result MySQL resource
     * @return array
     */
    public function fetchRow($result)
    {
        return mysqli_fetch_row($result);
    }

    /**
     * Return the number for rows from MySQL results
     *
     * @param  resource $result MySQL resource
     * @return integer
     */
    public function numRows($result)
    {
        return mysqli_num_rows($result);
    }

    /**
     * Free MySQL result memory
     *
     * @param  resource $result MySQL resource
     * @return void
     */
    public function freeResult($result)
    {
        mysqli_free_result($result);
    }
}
