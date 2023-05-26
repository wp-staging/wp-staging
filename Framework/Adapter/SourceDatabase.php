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
     * Add or update a cloned site option in the database.
     *
     * No need to serialize values. If the value needs to be serialized,
     *  then it will be serialized before it is inserted into the database.
     *
     * If the option does not exist, it will be created.
     *
     * @param  string $optionName
     * @param  mixed $optionValue
     *
     * @return int|false int for the number of rows affected during the updating of the clone's DB, or false on failure.
     */
    public function addOrUpdateClonedSiteOption($optionName, $optionValue)
    {
        if (!isset($this->options->prefix)) {
            return false;
        }

        $cloneOptionsTable = $this->options->prefix . 'options';
        $cloneOptions = $this->wpdb->query("SELECT * FROM  {$cloneOptionsTable} WHERE option_name='{$optionName}';");
        if (empty($cloneOptions)) {
            $result = $this->addOption($optionName, $optionValue);
        } else {
            $result = $this->wpdb->update(
                $cloneOptionsTable,
                [
                    'option_value' => maybe_serialize($optionValue),
                ],
                ['option_name' => $optionName]
            );
        }
        return $result;
    }

    /**
     * Add option to cloned site.
     *
     * No need to serialize values. If the value needs to be serialized,
     *  then it will be serialized before it is inserted into the database.
     *
     * @param  string $optionName
     * @param  mixed $optionValue
     *
     * @return int|false int for the number of rows affected during the updating of the clone's DB, or false on failure.
     */
    public function addOption($optionName, $optionValue)
    {
        if (!isset($this->options->prefix)) {
            return false;
        }

        $cloneOptionsTable = $this->options->prefix . 'options';
        $cloneOptions = $this->wpdb->query("SELECT * FROM  {$cloneOptionsTable} WHERE option_name='{$optionName}';");
        if (!empty($cloneOptions)) {
            return false;
        }

        $result = $this->wpdb->insert(
            $cloneOptionsTable,
            [
                'option_name' => $optionName,
                'option_value' => maybe_serialize($optionValue),
            ]
        );
        return $result;
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
