<?php

namespace WPStaging\Framework\ThirdParty;

use WPStaging\Framework\Adapter\DatabaseInterface;
use WPStaging\Framework\Adapter\WpAdapter;

class NinjaForms
{
    /**
     * @var DatabaseInterface
     */
    protected $database;

    /**
     * @var WpAdapter
     */
    protected $wpAdapter;

    /**
     * @param WpAdapter $wpAdapter
     * @param DatabaseInterface $database
     */
    public function __construct(WpAdapter $wpAdapter, DatabaseInterface $database)
    {
        $this->wpAdapter = $wpAdapter;
        $this->database  = $database;
    }

    /**
     * @return void
     */
    public function mayBeDisableMaintenanceMode()
    {
        // Early bail if Ninja Forms is disabled
        if (!$this->isNinjaFormsActive()) {
            return;
        }

        $this->disableMaintenanceMode();
    }

    /**
     * @return bool
     */
    private function isNinjaFormsActive(): bool
    {
        return $this->wpAdapter->isPluginActive('ninja-forms/ninja-forms.php');
    }

    /**
     * @return void
     */
    private function disableMaintenanceMode()
    {
        // Check if the nf3_upgrades table exists
        $tableName = $this->database->getPrefix() . 'nf3_upgrades';
        $result = $this->database->getClient()->query("SHOW TABLES LIKE '$tableName'");
        if ((int)$result->num_rows === 0) {
            return;
        }

        $this->database->getClient()->query("UPDATE `$tableName` SET maintenance = 0");
    }
}
