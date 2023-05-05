<?php

namespace WPStaging\Framework\Adapter;

use stdClass;
use WPStaging\Core\WPStaging;
use wpdb;

class SourceDatabase
{
    /** @var wpdb */
    private $wpdb;

    /** @var object */
    private $options;

    public function __construct($options = stdClass::class)
    {
        $this->wpdb = WPStaging::make('wpdb');
        $this->options = $options;
    }

    /**
     * @return bool
     */
    public function isExternalDatabase()
    {
        return !(empty($this->options->databaseUser) ||
            empty($this->options->databasePassword) ||
            empty($this->options->databaseDatabase) ||
            empty($this->options->databaseServer));
    }

    /**
     * @return object
     */
    private function getExternalDb()
    {
        return new wpdb($this->options->databaseUser, str_replace("\\\\", "\\", $this->options->databasePassword), $this->options->databaseDatabase, $this->options->databaseServer);
    }

    /**
     * Check if source database is a local or external one and get the corresponding database object
     *
     * @return wpdb
     *
     */
    public function getDatabase()
    {
        if ($this->isExternalDatabase()) {
            return $this->getExternalDb();
        }
        return $this->wpdb;
    }

    /**
     * @param  object $options
     * @return void
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }
}
