<?php

namespace WPStaging\Backup;

use WPStaging\Backup\Service\Database\DatabaseImporter;
use WPStaging\Framework\Database\TableService;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Security\AccessToken;
use WPStaging\Framework\ThirdParty\NinjaForms;

class AfterRestore
{
 
    const FILTER_BACKUP_IMPORT_DATABASE_DROP_OLD_TABLES_AFTER_RESTORE = 'wpstg.backup.import.database.dropOldTablesAfterRestore';




    protected $tableService;




    protected $accessToken;




    protected $ninjaForms;






    public function __construct(TableService $tableService, AccessToken $accessToken, NinjaForms $ninjaForms)
    {
        $this->tableService = $tableService;
        $this->accessToken  = $accessToken;
        $this->ninjaForms   = $ninjaForms;
    }





    public function loginAfterRestore()
    {
 
        if (get_option('wpstg.restore.justRestored') !== 'yes') {
            return;
        }

 
        add_filter('automatic_updater_disabled', '__return_false');

        if (Hooks::applyFilters(self::FILTER_BACKUP_IMPORT_DATABASE_DROP_OLD_TABLES_AFTER_RESTORE, true)) {
            $this->tableService->deleteTablesStartWith(DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP, [], true);
        }

        $this->ninjaForms->mayBeDisableMaintenanceMode();
        $this->accessToken->generateNewToken();
        delete_option('wpstg.restore.justRestored');
        delete_option('wpstg.restore.justRestored.metadata');
    }
}
