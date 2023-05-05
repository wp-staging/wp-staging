<?php

namespace WPStaging\Framework\Database;

use wpdb;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Filesystem\Scanning\ScanConst;

class SelectedTables
{
    /** @var array */
    private $includedTables = '';

    /** @var array */
    private $excludedTables = '';

    /** @var array */
    private $selectedTablesWithoutPrefix = '';

    /** @var bool */
    private $allTablesExcluded = false;

    /** @var bool */
    private $includeAllTables = false;

    /** @var wpdb|null */
    private $wpdb;

    /** @var string|null */
    private $prefix;

    public function __construct($includedTables = '', $excludedTables = '', $selectedTablesWithoutPrefix = '')
    {
        $this->includedTables              = $includedTables === '' ? [] : explode(ScanConst::DIRECTORIES_SEPARATOR, $includedTables);
        $this->excludedTables              = $excludedTables === '' ? [] : explode(ScanConst::DIRECTORIES_SEPARATOR, $excludedTables);
        $this->selectedTablesWithoutPrefix = $selectedTablesWithoutPrefix === '' ? [] : explode(ScanConst::DIRECTORIES_SEPARATOR, $selectedTablesWithoutPrefix);
        $this->wpdb                        = null;
        $this->prefix                      = null;
    }

    /** @param bool $areAllTablesExcluded  */
    public function setAllTablesExcluded($areAllTablesExcluded)
    {
        $this->allTablesExcluded = $areAllTablesExcluded;
    }

    /** @param bool $includeAllTables  */
    public function shouldIncludeAllTables($includeAllTables)
    {
        $this->includeAllTables = $includeAllTables;
    }

    /**
     * @param bool $isNetworkClone
     * @return array
     */
    public function getSelectedTables($isNetworkClone)
    {
        if (!empty($this->includedTables)) {
            return array_merge($this->includedTables, $this->selectedTablesWithoutPrefix);
        }

        $selectedTables = $this->getPrefixedTables($isNetworkClone);
        return array_merge($selectedTables, $this->selectedTablesWithoutPrefix);
    }

    /**
     * @param string $server
     * @param string $username
     * @param string $password
     * @param string $database
     * @param string $prefix
     * @param string $useSsl
     */
    public function setDatabaseInfo($server, $username, $password, $database, $prefix, $useSsl = false)
    {
        if (empty($username) || empty($database)) {
            $this->wpdb = WPStaging::getInstance()->get("wpdb");
        } else {
            if ($useSsl && !defined('MYSQL_CLIENT_FLAGS')) {
                // phpcs:disable PHPCompatibility.Constants.NewConstants.mysqli_client_ssl_dont_verify_server_certFound
                define('MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_SSL | MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT);
            }
            $this->wpdb = new wpdb($username, str_replace("\\\\", "\\", $password), $database, $server);
        }

        $this->wpdb->prefix = $prefix;
        $this->prefix       = $prefix;
    }

    /**W
     * @param wpdb $wpdb
     * @param string $prefix
     */
    public function setWpdb($wpdb, $prefix)
    {
        $this->wpdb         = $wpdb;
        $this->wpdb->prefix = $prefix;
        $this->prefix       = $prefix;
    }

    /**
     * Get Prefixed Table excluding the excluded tables
     * @param bool $isNetworkClone
     * @param bool $includeSize
     */
    public function getPrefixedTables($isNetworkClone, $includeSize = false)
    {
        if ($this->allTablesExcluded) {
            return [];
        }

        if ($this->wpdb === null) {
            $this->wpdb = WPStaging::getInstance()->get("wpdb");
        }

        if ($this->prefix === null) {
            $this->prefix = WPStaging::getTablePrefix();
        }

        $sql    = "SHOW TABLE STATUS";
        $tables = $this->wpdb->get_results($sql);

        $selectedTables = [];

        foreach ($tables as $table) {
            if (!$this->includeAllTables && !$this->isPrefixedTable($table->Name, $this->prefix, is_multisite(), is_main_site(), $isNetworkClone)) {
                continue;
            }

            if (in_array($table->Name, $this->excludedTables)) {
                continue;
            }

            if ($table->Comment === "VIEW") {
                continue;
            }

            if (!$includeSize) {
                $selectedTables[] = $table->Name;
                continue;
            }

            $selectedTables[] = [
                "name" => $table->Name,
                "size" => ($table->Data_length + $table->Index_length)
            ];
        }

        return $selectedTables;
    }

    /**
     * @param string $tableName
     * @param string $tablePrefix
     * @param bool $isMultisite
     * @param bool $isMainsite
     * @param bool $isNetwork
     *
     * @return bool
     */
    public function isPrefixedTable($tableName, $tablePrefix, $isMultisite, $isMainsite, $isNetwork)
    {
        if (!empty($tablePrefix) && strpos($tableName, $tablePrefix) !== 0) {
            return false;
        }

        /**
         * We also need to skip subsite tables for multisite mainsite if it is not a network clone
         * i.e. tables like wpstg0_1_*, wpstg0_2_* will not be selected it is a single site clone from multisite mainsite if the prefix is wpstg0
         */
        if ($isMultisite && $isMainsite && !$isNetwork && preg_match('/^' . $tablePrefix . '\d+_/', $tableName)) {
            return false;
        }

        return true;
    }
}
