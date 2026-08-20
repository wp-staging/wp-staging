<?php

namespace WPStaging\Staging\Service;

use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Collection\Collection;
use WPStaging\Framework\Database\TableDto;
use WPStaging\Framework\Database\TableService;
use WPStaging\Framework\TemplateEngine\TemplateEngine;




class TableScanner
{
 
    const STAGING_TABLE_PREFIX = 'wpstg';

 
    const FILTER_SHOW_STAGING_TABLES = 'wpstg_show_staging_tables_in_staging_setup';




    protected $templateEngine;




    protected $database;




    protected $tableService;




    protected $stagingSetup;




    protected $excludedTables = [];




    protected $currentTables = [];

    public function __construct(TemplateEngine $templateEngine, Database $database, TableService $tableService)
    {
        $this->templateEngine = $templateEngine;
        $this->database       = $database;
        $this->tableService   = $tableService;
    }




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




    protected function scanTables()
    {



        $tables            = $this->tableService->findAllTableStatus();
        $dbPrefix          = $this->database->getPrefix();
        $showStagingTables = $this->shouldShowStagingTables($dbPrefix);

 
        $this->excludedTables = [];
        $this->currentTables  = [];

        foreach ($tables as $table) {
            $tableName = $table->getName();
            if ($table->getIsView() || !$this->shouldRenderTable($dbPrefix, $tableName, $showStagingTables)) {
                continue;
            }

 
 
 
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
