<?php

namespace WPStaging\Framework\Utils\Cache;

use function WPStaging\functions\debug_log;

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
            try {
                $value = call_user_func($callback);
                set_transient($key, (string)$value, $expirationTimeSeconds);
            } catch (\Exception $e) {
                $error = 'TransientCache->get() Error: Can not execute callback: "' . (!empty($callback[1]) ? $callback[1] : "unknown callback") . '" Error: ' . $e->getMessage();
                debug_log($error, 'ERROR');
                $value = false;
            }
        }

        return $value;
    }

    public function delete($key)
    {
        delete_transient($key);
    }
}
