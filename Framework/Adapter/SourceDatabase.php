<?php

namespace WPStaging\Framework\Adapter;

use stdClass;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Database\ExternalDatabaseConfiguration;
use wpdb;




class SourceDatabase
{
 
    private $wpdb;

 
    private $options;

 
    private $externalDatabaseConfiguration;

    public function __construct($options = stdClass::class)
    {
        $this->options = $options;
        $this->wpdb    = WPStaging::make('wpdb');
        $this->externalDatabaseConfiguration = new ExternalDatabaseConfiguration();
    }




    public function isExternalDatabase()
    {
        return $this->externalDatabaseConfiguration->isEnabled($this->options);
    }




    private function getExternalDb()
    {
        return new wpdb($this->options->databaseUser, str_replace("\\\\", "\\", $this->options->databasePassword), $this->options->databaseDatabase, $this->options->databaseServer);
    }






    public function getDatabase()
    {
        if ($this->isExternalDatabase()) {
            return $this->getExternalDb();
        }

        return $this->wpdb;
    }





    public function setOptions($options)
    {
        $this->options = $options;
    }
}
