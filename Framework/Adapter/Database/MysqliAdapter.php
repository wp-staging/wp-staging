<?php

/** @noinspection PhpComposerExtensionStubsInspection */

namespace WPStaging\Framework\Adapter\Database;

use mysqli;

class MysqliAdapter implements InterfaceDatabaseClient
{
    /** @var mysqli|null */
    public $link;

    /**
     * MysqlAdapter constructor.
     *
     * @param mysqli|null $link
     */
    public function __construct($link = null)
    {
        $this->link = $link;
    }

    /**
     * @inheritDoc
     */
    public function query($query)
    {
        return mysqli_query($this->link, $query);
    }

    /**
     * @inheritDoc
     */
    public function realQuery($query, $isExecOnly = false)
    {
        if ($isExecOnly) {
            return mysqli_real_query($this->link, $query);
        }

        if (!mysqli_real_query($this->link, $query)) {
            return false;
        }

        // Copy results from the internal mysqlnd buffer into the PHP variables fetched
        if (defined('MYSQLI_STORE_RESULT_COPY_DATA')) {
            return mysqli_store_result($this->link, MYSQLI_STORE_RESULT_COPY_DATA);
        }

        return mysqli_store_result($this->link);
    }

    /**
     * @inheritDoc
     */
    public function escape($input)
    {
        return mysqli_real_escape_string($this->link, $input);
    }

    /**
     * @inheritDoc
     */
    public function errno()
    {
        return mysqli_errno($this->link);
    }

    /**
     * @inheritDoc
     */
    public function error()
    {
        return mysqli_error($this->link);
    }

    /**
     * @inheritDoc
     */
    public function version()
    {
        return mysqli_get_server_info($this->link);
    }

    /**
     * @inheritDoc
     */
    public function fetchAssoc($result)
    {
        return mysqli_fetch_assoc($result);
    }

    /**
     * @inheritDoc
     */
    public function fetchRow($result)
    {
        return mysqli_fetch_row($result);
    }

    /**
     * @inheritDoc
     */
    public function fetchObject($result)
    {
        return mysqli_fetch_object($result);
    }

    /**
     * @inheritDoc
     */
    public function numRows($result)
    {
        return mysqli_num_rows($result);
    }

    /**
     * @inheritDoc
     */
    public function freeResult($result)
    {
        if ($result === null) {
            return null;
        }

        mysqli_free_result($result);

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function insertId()
    {
        return mysqli_insert_id($this->link);
    }

    /**
     * {@inheritdoc}
     */
    public function foundRows()
    {
        return mysqli_affected_rows($this->link);
    }
}
