<?php

namespace WPStaging\Backup;

use WPStaging\Backup\Service\Database\DatabaseImporter;
use WPStaging\Framework\Database\TableService;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Security\AccessToken;
use WPStaging\Framework\ThirdParty\NinjaForms;

class AfterRestore
{
    /** @var string */
    const FILTER_BACKUP_IMPORT_DATABASE_DROP_OLD_TABLES_AFTER_RESTORE = 'wpstg.backup.import.database.dropOldTablesAfterRestore';

    /**
     * @var TableService
     */
    protected $tableService;

    /**
     * @var AccessToken
     */
    protected $accessToken;

    /**
     * @var NinjaForms
     */
    protected $ninjaForms;

    /**
     * @param TableService $tableService
     * @param AccessToken $accessToken
     * @param NinjaForms $ninjaForms
     */
    public function __construct(TableService $tableService, AccessToken $accessToken, NinjaForms $ninjaForms)
    {
        $this->tableService = $tableService;
        $this->accessToken  = $accessToken;
        $this->ninjaForms   = $ninjaForms;
    }

    /**
     * @action wp_login
     * @see \WPStaging\Backup\BackupServiceProvider::addHooks
     */
    public function loginAfterRestore()
    {
        // Early bail: Not a login after a successful restore
        if (get_option('wpstg.restore.justRestored') !== 'yes') {
            return;
        }

        // Disable WordPress automatic background updates on this request.
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
