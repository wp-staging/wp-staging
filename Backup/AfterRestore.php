<?php

namespace WPStaging\Backup;

use WPStaging\Framework\Database\TableService;
use WPStaging\Framework\Security\AccessToken;
use WPStaging\Backup\Ajax\Restore\PrepareRestore;

class AfterRestore
{
    protected $tableService;
    protected $accessToken;

    public function __construct(TableService $tableService, AccessToken $accessToken)
    {
        $this->tableService = $tableService;
        $this->accessToken = $accessToken;
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

        if (apply_filters('wpstg.backup.import.database.dropOldTablesAfterRestore', true)) {
            $this->tableService->deleteTablesStartWith(PrepareRestore::TMP_DATABASE_PREFIX_TO_DROP, [], true);
        }

        $this->accessToken->generateNewToken();
        delete_option('wpstg.restore.justRestored');
        delete_option('wpstg.restore.justRestored.metadata');
    }
}
