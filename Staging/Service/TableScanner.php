<?php

namespace WPStaging\Staging\Service;

use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Collection\Collection;
use WPStaging\Framework\Database\TableDto;
use WPStaging\Framework\Database\TableService;
use WPStaging\Framework\TemplateEngine\TemplateEngine;

class TableScanner
{
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
            'excludedTables' => $this->excludedTables
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
        $tables   = $this->tableService->findAllTableStatus();
        $dbPrefix = $this->database->getPrefix();

        // reset the excluded tables
        $this->excludedTables = [];
        $this->currentTables  = [];

        foreach ($tables as $table) {
            // Create array of unchecked tables
            // On the main website of a multisite installation, do not select network site tables beginning with wp_1_, wp_2_ etc.
            // (On network sites, the correct tables are selected anyway)
            if ($this->isTableExcluded($dbPrefix, $table->getName())) {
                $this->excludedTables[] = $table->getName();
            }

            if (!$table->getIsView()) {
                $this->currentTables[] = $table;
            }
        }
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
