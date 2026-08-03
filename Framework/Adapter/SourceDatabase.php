<?php

namespace WPStaging\Framework\Adapter;

use stdClass;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Database\ExternalDatabaseConfiguration;
use wpdb;

/**
 * Resolves the database connection for a classic staging site.
 */
class SourceDatabase
{
    /** @var wpdb */
    private $wpdb;

    /** @var object */
    private $options;

    /** @var ExternalDatabaseConfiguration */
    private $externalDatabaseConfiguration;

    public function __construct($options = stdClass::class)
    {
        $this->options = $options;
        $this->wpdb    = WPStaging::make('wpdb');
        $this->externalDatabaseConfiguration = new ExternalDatabaseConfiguration();
    }

    /**
     * @return bool
     */
    public function isExternalDatabase()
    {
        return $this->externalDatabaseConfiguration->isEnabled($this->options);
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
