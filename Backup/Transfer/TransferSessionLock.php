<?php

namespace WPStaging\Backup\Transfer;





class TransferSessionLock
{
 
    const OPTION_PREFIX = 'wpstg_transfer_lock_';

 
    const STALE_LOCK_SECONDS = 15 * MINUTE_IN_SECONDS;

 
    const SYNC_STALE_LOCK_SECONDS = 5 * MINUTE_IN_SECONDS;







    public function acquire(string $lockKey, int $staleAfterSeconds = self::STALE_LOCK_SECONDS): bool
    {
        $optionName = $this->getOptionName($lockKey);

        if (add_option($optionName, time(), '', false)) {
            return true;
        }

        $storedValue = $this->readStoredValue($optionName);

        if ($storedValue === null) {
            return add_option($optionName, time(), '', false);
        }

        if ((int)$storedValue !== 0 && (time() - (int)$storedValue) < $staleAfterSeconds) {
            return false;
        }

        return $this->takeOverStaleLock($optionName, $storedValue);
    }

 
    public function release(string $lockKey)
    {
        delete_option($this->getOptionName($lockKey));
    }

    private function getOptionName(string $lockKey): string
    {
        return self::OPTION_PREFIX . md5($lockKey);
    }






    private function readStoredValue(string $optionName)
    {
        global $wpdb;

        return $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", $optionName));
    }

 
    private function takeOverStaleLock(string $optionName, string $staleValue): bool
    {
        global $wpdb;

        $updated = $wpdb->update($wpdb->options, ['option_value' => (string)time()], ['option_name' => $optionName, 'option_value' => $staleValue]);

        wp_cache_delete($optionName, 'options');

        return $updated === 1;
    }
}
