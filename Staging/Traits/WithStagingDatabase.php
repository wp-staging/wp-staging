<?php

namespace WPStaging\Staging\Traits;

use wpdb;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Collection\Collection;
use WPStaging\Framework\Database\TableDto;
use WPStaging\Framework\Database\TableService;
use WPStaging\Staging\Dto\StagingSiteDto;

trait WithStagingDatabase
{
    /** @var Database */
    private $stagingDb;

    /** @var TableService */
    private $tableService = null;

    public function initStagingDatabase(StagingSiteDto $stagingSiteDto)
    {
        if ($this->stagingDb === null) {
            $this->stagingDb = new Database();
        }

        if (!$stagingSiteDto->getIsExternalDatabase()) {
            return;
        }

        $wpdb = new wpdb(
            $stagingSiteDto->getDatabaseUser(),
            $stagingSiteDto->getDatabasePassword(),
            $stagingSiteDto->getDatabaseDatabase(),
            $stagingSiteDto->getDatabaseServer()
        );

        $wpdb->prefix = $stagingSiteDto->getDatabasePrefix();

        $this->stagingDb->setWpDatabase($wpdb);
    }

    /**
     * @param string $prefix
     * @return TableDto[]|Collection|null
     */
    public function getStagingTablesStatus(string $prefix)
    {
        if ($this->tableService === null) {
            $this->tableService = new TableService($this->stagingDb);
        }

        return $this->tableService->findTableStatusStartsWith($prefix);
    }

    /**
     * @param string $prefix
     * @return string[]
     */
    public function getStagingTables(string $prefix)
    {
        if ($this->tableService === null) {
            $this->tableService = new TableService($this->stagingDb);
        }

        return $this->tableService->findTableNamesStartWith($prefix);
    }

    /**
     * @param string $prefix
     * @return string[]
     */
    public function getStagingViews(string $prefix)
    {
        if ($this->tableService === null) {
            $this->tableService = new TableService($this->stagingDb);
        }

        return $this->tableService->findViewsNamesStartWith($prefix);
    }
}
