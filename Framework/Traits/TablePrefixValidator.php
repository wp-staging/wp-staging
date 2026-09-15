<?php

namespace WPStaging\Framework\Traits;

use RuntimeException;

trait TablePrefixValidator
{
    public function isWpStagingReservedPrefix(string $prefix): bool
    {
        return in_array($prefix, ['wpstg', 'wpstg_'], true);
    }







    public function isValidTablePrefix(string $prefix): bool
    {
        return (bool)preg_match('/^[A-Za-z0-9_]+$/D', $prefix);
    }






    public function requireValidTablePrefix(string $prefix): string
    {
        if (!$this->isValidTablePrefix($prefix)) {
            throw new RuntimeException($this->getInvalidPrefixErrorMessage($prefix));
        }

        return $prefix;
    }





    public function getInvalidPrefixErrorMessage(string $prefix): string
    {
        return sprintf(
            __('The prefix "%s" is not a valid database table prefix. Use letters, numbers and underscores only.', 'wp-staging'),
            esc_html($prefix)
        );
    }

    public function getReservedPrefixErrorMessage(string $prefix): string
    {
        return sprintf(
            __('The prefix "%s" is reserved by WP STAGING and cannot be used. Please use a different prefix like "wpstg0_", "wpstg1_", etc.', 'wp-staging'),
            esc_html($prefix)
        );
    }
}
