<?php

namespace WPStaging\Framework\ThirdParty;

use WPStaging\Framework\Adapter\DatabaseInterface;
use WPStaging\Framework\Adapter\WpAdapter;

class NinjaForms
{



    protected $database;




    protected $wpAdapter;





    public function __construct(WpAdapter $wpAdapter, DatabaseInterface $database)
    {
        $this->wpAdapter = $wpAdapter;
        $this->database  = $database;
    }




    public function mayBeDisableMaintenanceMode()
    {
 
        if (!$this->isNinjaFormsActive()) {
            return;
        }

        $this->disableMaintenanceMode();
    }




    private function isNinjaFormsActive(): bool
    {
        return $this->wpAdapter->isPluginActive('ninja-forms/ninja-forms.php');
    }




    private function disableMaintenanceMode()
    {
 
        $tableName = $this->database->getPrefix() . 'nf3_upgrades';
        $result = $this->database->getClient()->query("SHOW TABLES LIKE '$tableName'");
        if ((int)$result->num_rows === 0) {
            return;
        }

        $this->database->getClient()->query("UPDATE `$tableName` SET maintenance = 0");
    }
}
