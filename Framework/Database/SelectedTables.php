<?php

namespace WPStaging\Framework\Database;

use wpdb;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Filesystem\Scanning\ScanConst;

class SelectedTables
{
 
    private $includedTables = [];

 
    private $excludedTables = [];

 
    private $selectedTablesWithoutPrefix = [];

 
    private $allTablesExcluded = false;

 
    private $includeAllTables = false;

 
    private $keepUserTablesTogether = false;

 
    private $wpdb;

 
    private $prefix;

    public function __construct($includedTables = '', $excludedTables = '', $selectedTablesWithoutPrefix = '')
    {
        $this->includedTables              = $includedTables === '' ? [] : explode(ScanConst::DIRECTORIES_SEPARATOR, $includedTables);
        $this->excludedTables              = $excludedTables === '' ? [] : explode(ScanConst::DIRECTORIES_SEPARATOR, $excludedTables);
        $this->selectedTablesWithoutPrefix = $selectedTablesWithoutPrefix === '' ? [] : explode(ScanConst::DIRECTORIES_SEPARATOR, $selectedTablesWithoutPrefix);
        $this->wpdb                        = null;
        $this->prefix                      = null;
    }





    public function setIncludedTables($tables)
    {
        if (is_array($tables)) {
            $this->includedTables = $tables;
            return;
        }

        $this->includedTables = $tables === '' ? [] : explode(ScanConst::DIRECTORIES_SEPARATOR, $tables);
    }





    public function setExcludedTables($tables)
    {
        if (is_array($tables)) {
            $this->excludedTables = $tables;
            return;
        }

        $this->excludedTables = $tables === '' ? [] : explode(ScanConst::DIRECTORIES_SEPARATOR, $tables);
    }





    public function setSelectedTablesWithoutPrefix($tables)
    {
        if (is_array($tables)) {
            $this->selectedTablesWithoutPrefix = $tables;
            return;
        }

        $this->selectedTablesWithoutPrefix = $tables === '' ? [] : explode(ScanConst::DIRECTORIES_SEPARATOR, $tables);
    }





    public function setAllTablesExcluded(bool $areAllTablesExcluded = false)
    {
        $this->allTablesExcluded = $areAllTablesExcluded;
    }






    public function setKeepUserTablesTogether()
    {
        $this->keepUserTablesTogether = true;
    }





    public function shouldIncludeAllTables(bool $includeAllTables = false)
    {
        $this->includeAllTables = $includeAllTables;
    }





    public function getSelectedTables(bool $isNetworkClone = false)
    {
 
 
 
 
        $selectedTables = !empty($this->includedTables) ? $this->includedTables : $this->getPrefixedTables($isNetworkClone);
        return $this->pairUserTables(array_values(array_unique(array_merge($selectedTables, $this->selectedTablesWithoutPrefix))));
    }









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

        $this->prefix = $prefix;
    }





    public function setWpdb($wpdb, $prefix)
    {
        $this->wpdb   = $wpdb;
        $this->prefix = $prefix;
    }






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
                "size" => ($table->Data_length + $table->Index_length),
            ];
        }

        return $selectedTables;
    }










    public function isPrefixedTable($tableName, $tablePrefix, $isMultisite, $isMainsite, $isNetwork)
    {
        if (!empty($tablePrefix) && strpos($tableName, $tablePrefix) !== 0) {
            return false;
        }





        if ($isMultisite && $isMainsite && !$isNetwork && preg_match('/^' . $tablePrefix . '\d+_/', $tableName)) {
            return false;
        }

        return true;
    }





    private function pairUserTables(array $tables)
    {
        $basePrefix = WPStaging::getInstance()->getVar("database")->normalizeTableNameCase(WPStaging::getTableBasePrefix());
        $userTables = [$basePrefix . 'usermeta', $basePrefix . 'users'];
        if (!$this->keepUserTablesTogether || !array_intersect($userTables, $tables)) {
            return $tables;
        }

 
        $userTables = array_filter($userTables, function ($table) use ($tables) {
            return in_array($table, $tables, true) || $this->isBaseTableInSourceDatabase($table);
        });

 
        return array_values(array_unique(array_merge($userTables, $tables)));
    }





    private function isBaseTableInSourceDatabase(string $table): bool
    {
        $wpdb   = WPStaging::getInstance()->get("wpdb");
        $status = $wpdb->get_row($wpdb->prepare("SHOW TABLE STATUS LIKE %s", $wpdb->esc_like($table)));

        return $status !== null && $status->Comment !== "VIEW";
    }
}
