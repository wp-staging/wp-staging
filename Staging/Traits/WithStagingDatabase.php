<?php

namespace WPStaging\Staging\Traits;

use wpdb;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Collection\Collection;
use WPStaging\Framework\Database\ExternalDatabaseConfiguration;
use WPStaging\Framework\Database\TableDto;
use WPStaging\Framework\Database\TableService;
use WPStaging\Staging\Dto\StagingSiteDto;

trait WithStagingDatabase
{
 
    protected $stagingDb = null;

 
    protected $tableService = null;

    public function initStagingDatabase(StagingSiteDto $stagingSiteDto)
    {
        if (!empty($this->stagingDb)) {
            return;
        }

        if ($stagingSiteDto->getIsCustomDatabaseConnection()) {
            (new ExternalDatabaseConfiguration())->validateConnectionTarget($stagingSiteDto->toArray());
        }

        if (!$stagingSiteDto->getIsExternalDatabase()) {
            $wpdb = new wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
        } else {
            $wpdb = new wpdb(
                $stagingSiteDto->getDatabaseUser(),
                $stagingSiteDto->getDatabasePassword(),
                $stagingSiteDto->getDatabaseDatabase(),
                $stagingSiteDto->getDatabaseServer()
            );
        }

        $wpdb->prefix      = $stagingSiteDto->getUsedPrefix();
        $wpdb->base_prefix = $wpdb->prefix;
        $this->stagingDb   = new Database($wpdb);
    }





    public function getStagingTablesStatus(string $prefix)
    {
        if ($this->tableService === null) {
            $this->tableService = new TableService($this->stagingDb);
        }

        return $this->tableService->findTableStatusStartsWith($prefix);
    }





    public function getStagingTables(string $prefix)
    {
        if ($this->tableService === null) {
            $this->tableService = new TableService($this->stagingDb);
        }

        return $this->tableService->findTableNamesStartWith($prefix);
    }





    public function getStagingViews(string $prefix)
    {
        if ($this->tableService === null) {
            $this->tableService = new TableService($this->stagingDb);
        }

        return $this->tableService->findViewsNamesStartWith($prefix);
    }
}
