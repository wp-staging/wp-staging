<?php
/** @noinspection PhpComposerExtensionStubsInspection */

namespace WPStaging\Framework\Adapter\Database;

use mysqli_result;

interface InterfaceDatabaseClient
{
    /**
     * Runs given MySQL query
     * @param string $query
     * @param bool $isExecOnly
     * @return resource
     */
    public function query($query, $isExecOnly = false);

    /**
     * Escapes given input for mysql query
     * @param string $input
     * @return string
     */
    public function escape($input);

    /**
     * Returns the error code for the most recent function call
     * @return int
     */
    public function errno();

    /**
     * Returns the string description of the last error
     * @return string
     */
    public function error();

    /**
     * Returns server version
     * @return string
     */
    public function version();

    /**
     * Returns the results from MySQL query resource as associative array
     * @param resource|mysqli_result $result
     * @return array
     */
    public function fetchAssoc($result);

    /**
     * Returns the result from MySQL query resource as row
     * @param resource|mysqli_result $result
     * @return array
     */
    public function fetchRow($result);

    /**
     * Returns the number for rows from MySQL results
     * @param resource|mysqli_result $result
     * @return integer
     */
    public function numRows($result);

    /**
     * Free MySQL result memory
     * @param resource|mysqli_result $result
     * @return void
     */
    public function freeResult($result);
}
