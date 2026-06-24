<?php

namespace WPStaging\Framework\Utils;

use Throwable;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Traits\SerializeTrait;
use wpdb;

use function WPStaging\functions\debug_log;

/**
 * Class DatabaseOptions
 *
 * Provides direct, cache-free access to the WordPress options table.
 * This bypasses the wp_options caching layer (e.g., object cache, transients)
 * to allow raw interaction with the database, which is useful authentication of login links.
 *
 * @package WPStaging\Framework\Utils
 */
class DatabaseOptions
{
    use SerializeTrait;

    /** @var wpdb */
    private $db;

    /** @var string */
    private $optionsTable;

    /**
     * @param Database $database
     */
    public function __construct(Database $database)
    {
        $this->db           = $database->getWpdb();
        $this->optionsTable = $database->getPrefix() . 'options';
    }

    /**
     * @param string $optionName
     * @param mixed  $defaultValue
     * @return mixed
     */
    public function getOption(string $optionName, $defaultValue = false)
    {
        if (!$this->optionExists($optionName)) {
            return $defaultValue;
        }

        $sql = $this->db->prepare(
            "SELECT option_value FROM `{$this->optionsTable}` WHERE option_name = %s LIMIT 1",
            $optionName
        );

        $result = $this->db->get_var($sql);

        if ($result === null) {
            return $defaultValue;
        }

        return $this->maybeUnserialize($result);
    }

    /**
     * @param string $optionName
     * @param mixed $optionValue
     * @param bool $autoload
     * @return bool
     */
    public function updateOption(string $optionName, $optionValue, bool $autoload = true): bool
    {
        try {
            $serializedValue = serialize($optionValue);
            $autoloadValue   = $autoload ? 'yes' : 'no';
            if ($this->optionExists($optionName)) {
                $sql = $this->db->prepare(
                    "UPDATE `{$this->optionsTable}`
                     SET option_value = %s, autoload = %s
                     WHERE option_name = %s",
                    $serializedValue,
                    $autoloadValue,
                    $optionName
                );
            } else {
                $sql = $this->db->prepare(
                    "INSERT INTO `{$this->optionsTable}` (option_name, option_value, autoload)
                     VALUES (%s, %s, %s)",
                    $optionName,
                    $serializedValue,
                    $autoloadValue
                );
            }

            $result = $this->db->query($sql);
            if ($result === false) {
                debug_log(sprintf('Failed to update database option "%s". DB Error: %s', $optionName, $this->getLastDbError()));

                return false;
            }

            $this->clearOptionCache($optionName);

            return true;
        } catch (Throwable $ex) {
            debug_log(sprintf('Exception while updating database option "%s": %s', $optionName, $ex->getMessage()));

            return false;
        }
    }

    /**
     * @param string $optionName
     * @return bool
     */
    public function deleteOption(string $optionName): bool
    {
        if (!$this->optionExists($optionName)) {
            return false;
        }

        $sql = $this->db->prepare(
            "DELETE FROM `{$this->optionsTable}` WHERE option_name = %s LIMIT 1",
            $optionName
        );

        $result = $this->db->query($sql);
        if ($result === false) {
            return false;
        }

        $this->clearOptionCache($optionName);

        return true;
    }

    /**
     * @param string $optionName
     * @return bool
     */
    private function optionExists(string $optionName): bool
    {
        $sql = $this->db->prepare(
            "SELECT 1 FROM `{$this->optionsTable}` WHERE option_name = %s LIMIT 1",
            $optionName
        );

        return (bool) $this->db->get_var($sql);
    }

    /**
     * @param string $value
     * @return mixed
     */
    private function maybeUnserialize(string $value)
    {
        if ($this->isSerialized($value)) {
            return @unserialize($value);
        }

        return $value;
    }

    /**
     * @return string
     */
    private function getLastDbError(): string
    {
        return !empty($this->db->last_error) ? $this->db->last_error : 'Unknown database error';
    }

    /**
     * @param string $optionName
     * @return void
     */
    private function clearOptionCache(string $optionName)
    {
        if (!function_exists('wp_cache_delete')) {
            return;
        }

        wp_cache_delete($optionName, 'options');
        wp_cache_delete('alloptions', 'options');
        wp_cache_delete('notoptions', 'options');
    }
}
