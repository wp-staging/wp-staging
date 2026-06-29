<?php

namespace WPStaging\Staging\Service;

use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Collection\Collection;
use WPStaging\Framework\Database\TableDto;
use WPStaging\Framework\Database\TableService;
use WPStaging\Framework\TemplateEngine\TemplateEngine;

/**
 * Scans database tables available for staging setup selection.
 */
class TableScanner
{
    /** @var string */
    const STAGING_TABLE_PREFIX = 'wpstg';

    /** @var string */
    const FILTER_SHOW_STAGING_TABLES = 'wpstg_show_staging_tables_in_staging_setup';

    /**
     * @var TemplateEngine
     */
    protected $templateEngine;

    /**
     * @var Database
     */
    protected $database;

    /**
     * @var TableService
     */
    protected $tableService;

    /**
     * @var AbstractStagingSetup
     */
    protected $stagingSetup;

    /**
     * @var string[]
     */
    protected $excludedTables = [];

    /**
     * @var TableDto[]
     */
    protected $currentTables = [];

    public function __construct(TemplateEngine $templateEngine, Database $database, TableService $tableService)
    {
        $this->templateEngine = $templateEngine;
        $this->database       = $database;
        $this->tableService   = $tableService;
    }

    /**
     * @return void
     */
    public function setStagingSetup(AbstractStagingSetup $stagingSetup)
    {
        $this->stagingSetup = $stagingSetup;
    }

    public function renderTablesSelection()
    {
        $this->scanTables();
        $result = $this->templateEngine->render('staging/_partials/tables-selection.php', [
            'dbPrefix'       => $this->database->getPrefix(),
            'stagingSetup'   => $this->stagingSetup,
            'stagingSiteDto' => $this->stagingSetup->getStagingSiteDto(),
            'tables'         => $this->currentTables,
            'excludedTables' => $this->excludedTables,
        ]);

        echo $result; // phpcs:ignore
    }

    /**
     * @return void
     */
    protected function scanTables()
    {
        /**
         * @var Collection|TableDto[] $tables
         */
        $tables            = $this->tableService->findAllTableStatus();
        $dbPrefix          = $this->database->getPrefix();
        $showStagingTables = $this->shouldShowStagingTables($dbPrefix);

        // reset the excluded tables
        $this->excludedTables = [];
        $this->currentTables  = [];

        foreach ($tables as $table) {
            $tableName = $table->getName();
            if ($table->getIsView() || !$this->shouldRenderTable($dbPrefix, $tableName, $showStagingTables)) {
                continue;
            }

            // Create array of unchecked tables
            // On the main website of a multisite installation, do not select network site tables beginning with wp_1_, wp_2_ etc.
            // (On network sites, the correct tables are selected anyway)
            if ($this->isTableExcluded($dbPrefix, $tableName)) {
                $this->excludedTables[] = $tableName;
            }

            $this->currentTables[] = $table;
        }
    }

    protected function shouldRenderTable(string $dbPrefix, string $tableName, bool $showStagingTables): bool
    {
        if ($showStagingTables) {
            return true;
        }

        if (!$this->isStagingTable($tableName)) {
            return true;
        }

        return $this->isCurrentSiteTable($dbPrefix, $tableName);
    }

    protected function shouldShowStagingTables(string $dbPrefix): bool
    {
        return (bool) apply_filters(self::FILTER_SHOW_STAGING_TABLES, false, $dbPrefix, $this->stagingSetup);
    }

    protected function isStagingTable(string $tableName): bool
    {
        return strpos($tableName, self::STAGING_TABLE_PREFIX) === 0;
    }

    protected function isCurrentSiteTable(string $dbPrefix, string $tableName): bool
    {
        return !empty($dbPrefix) && strpos($tableName, $dbPrefix) === 0;
    }

    protected function isTableExcluded(string $dbPrefix, string $tableName): bool
    {
        if ((!empty($dbPrefix) && strpos($tableName, $dbPrefix) !== 0)) {
            return true;
        }

        if (!$this->isMultisiteMainSite()) {
            return false;
        }

        if ($this->stagingSetup->getStagingSiteDto()->getNetworkClone()) {
            return false;
        }

        return preg_match('/^' . $dbPrefix . '\d+_/', $tableName);
    }

    protected function isMultisiteMainSite(): bool
    {
        return is_multisite() && is_main_site();
    }
}
