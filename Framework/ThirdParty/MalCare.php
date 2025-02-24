<?php

declare(strict_types=1);

namespace WPStaging\Framework\ThirdParty;

use wpdb;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Adapter\WpAdapter;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Staging\Sites;

class MalCare
{
    /**
     * @var string
     */
    const OPTION_MALCARE_CONFIG = 'mcconfig';

    /**
     * @var Database
     */
    private $database;

    /**
     * @var WpAdapter
     */
    protected $wpAdapter;

    /**
     * @var Sites
     */
    protected $sites;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @param WpAdapter $wpAdapter
     * @param Sites $sites
     */
    public function __construct(WpAdapter $wpAdapter, Sites $sites, Filesystem $filesystem)
    {
        $this->wpAdapter    = $wpAdapter;
        $this->sites        = $sites;
        $this->filesystem   = $filesystem;
    }

    /**
     * @param object $options
     * @return void
     */
    public function maybeDisableMalCare($options)
    {
        if (empty($options->clone) || empty($options->destinationDir) || empty($options->prefix)) {
            return;
        }

        if (!$this->sites->isExistingClone($options->clone)) {
            return;
        }

        if (!$this->isMalCareActive()) {
            return;
        }

        $this->initializeDatabase($options);
        if ($this->database === null) {
            return;
        }

        $this->cleanMalCareConfig($options->prefix);
        $this->maybeRemoveMalCareInclude($options->destinationDir . '/wp-config.php');
    }

    /**
     * Check if MalCare plugin is active
     * @return bool
     */
    private function isMalCareActive(): bool
    {
        return $this->wpAdapter->isPluginActive('malcare-security/malcare.php');
    }

    /**
     * Initialize the database connection
     * @param object $options
     * @return void
     */
    private function initializeDatabase($options)
    {
        if ($this->database === null) {
            $this->database = new Database();
        }

        if (!$this->isExternalDatabase($options)) {
            return;
        }

        $wpdb = new wpdb(
            $options->databaseUser,
            $options->databasePassword,
            $options->databaseDatabase,
            $options->databaseServer
        );

        $wpdb->prefix = $options->prefix;

        $this->database->setWpDatabase($wpdb);
    }

    /**
     * Delete MalCare configuration
     * @param string $prefix
     * @return void
     */
    private function cleanMalCareConfig(string $prefix)
    {
        $optionName = self::OPTION_MALCARE_CONFIG;
        $tableName  = $prefix . 'options';

        $result = $this->database->getClient()->query("SELECT option_value FROM `$tableName` WHERE option_name = '$optionName'");
        if ($this->database->getClient()->numRows($result) === 0) {
            return;
        }

        $this->database->getClient()->query("DELETE FROM `$tableName` WHERE option_name = '$optionName'");
    }

    /**
     * Remove the malcare-waf.php include from wp-config.php
     *
     * @param string $filePath Path to the wp-config.php file
     * @return bool
     */
    private function maybeRemoveMalCareInclude(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return false;
        }

        $pattern = '/^\s*(require_once|require|include|@include)\s*\(?\s*[\'"][^\'"]*malcare-waf\.php[\'"]\s*\)?\s*;\s*$/m';
        $content = preg_replace($pattern, '', $content);

        if ($this->filesystem->create($filePath, $content) === false) {
            return false;
        }

        return true;
    }

    /**
     * @param $options
     * @return bool
     */
    private function isExternalDatabase($options): bool
    {
        return !(empty($options->databaseUser) ||
            empty($options->databasePassword) ||
            empty($options->databaseDatabase) ||
            empty($options->databaseServer));
    }
}
