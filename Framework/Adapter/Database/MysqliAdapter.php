<?php

/** @noinspection PhpComposerExtensionStubsInspection */

namespace WPStaging\Framework\Adapter\Database;

class MysqliAdapter implements InterfaceDatabaseClient
{
    /** @var \mysqli|null */
    public $link;

    /**
     * MysqliAdapter constructor.
     *
     * @param \mysqli|null $link
     */
    public function __construct($link = null)
    {
        $this->link = $link;
    }

    /**
     * @param $query
     * @return bool|\mysqli_result|resource
     */
    public function query($query)
    {
        return mysqli_query($this->link, $query);
    }

    /**
     * @param $query
     * @param $isExecOnly
     * @return bool|\mysqli_result|resource
     */
    public function realQuery($query, $isExecOnly = false)
    {
        if ($isExecOnly) {
            return mysqli_real_query($this->link, $query);
        }

        if (!mysqli_real_query($this->link, $query)) {
            return false;
        }

        if (defined('MYSQLI_STORE_RESULT_COPY_DATA')) {
            return mysqli_store_result($this->link, MYSQLI_STORE_RESULT_COPY_DATA);
        }

        return mysqli_store_result($this->link);
    }

    /**
     * @param $input
     * @return string
     */
    public function escape($input)
    {
        return mysqli_real_escape_string($this->link, $input);
    }

    /**
     * @return int
     */
    public function errno()
    {
        return mysqli_errno($this->link);
    }

    /**
     * @return string
     */
    public function error()
    {
        return mysqli_error($this->link);
    }

    /**
     * @return string
     */
    public function version()
    {
        return mysqli_get_server_info($this->link);
    }

    /**
     * @param $result
     * @return array
     */
    public function fetchAll($result)
    {
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     * @param $result
     * @return array|false|null
     */
    public function fetchAssoc($result)
    {
        return mysqli_fetch_assoc($result);
    }

    /**
     * @param $result
     * @return array|false|null
     */
    public function fetchRow($result)
    {
        return mysqli_fetch_row($result);
    }

    /**
     * @param $result
     * @return \$1|array|false|object|\stdClass|null
     */
    public function fetchObject($result)
    {
        return mysqli_fetch_object($result);
    }

    /**
     * @param $result
     * @return int|string
     */
    public function numRows($result)
    {
        return mysqli_num_rows($result);
    }

    /**
     * @param $result
     * @return null
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
     * @return int|string
     */
    public function insertId()
    {
        return mysqli_insert_id($this->link);
    }

    /**
     * @return int|string
     */
    public function foundRows()
    {
        return mysqli_affected_rows($this->link);
    }

    /**
     * @return mixed|mysqli|null
     */
    public function getLink()
    {
        return $this->link;
    }
}
