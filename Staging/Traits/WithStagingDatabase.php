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
    private $stagingDb = null;

    /** @var TableService */
    private $tableService = null;

    public function initStagingDatabase(StagingSiteDto $stagingSiteDto)
    {
        if (!empty($this->stagingDb)) {
            return;
        }

        if (!$stagingSiteDto->getIsExternalDatabase()) {
            $stagingWpdb = new wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
            $stagingWpdb->set_prefix($this->jobDataDto->getDatabasePrefix());
            $this->stagingDb = new Database($stagingWpdb);
            return;
        }

        $wpdb = new wpdb(
            $stagingSiteDto->getDatabaseUser(),
            $stagingSiteDto->getDatabasePassword(),
            $stagingSiteDto->getDatabaseDatabase(),
            $stagingSiteDto->getDatabaseServer()
        );

        $wpdb->prefix = $stagingSiteDto->getDatabasePrefix();

        $this->stagingDb = new Database($wpdb);
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
