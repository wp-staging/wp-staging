<?php

namespace WPStaging\Framework\Utils\Cache;

class TransientCache
{
    /** @var string used to prevent checking the backup file index with every page reload */
    const KEY_INVALID_BACKUP_FILE_INDEX = 'is_invalid_backup_file_index';

    /**
     * Get the value of the cache key.
     * @param string $key
     * @param int $expirationTimeSeconds
     * @param callable $callback
     * @return false|mixed
     */
    public function get($key, $expirationTimeSeconds = 10, $callback = null)
    {
        $value = get_transient($key);
        if ($value !== false) {
            return $value;
        }

        if (is_callable($callback)) {
            $value = call_user_func($callback);
            set_transient($key, (string)$value, $expirationTimeSeconds);
        }

        return $value;
    }

    public function delete($key)
    {
        delete_transient($key);
    }
}
