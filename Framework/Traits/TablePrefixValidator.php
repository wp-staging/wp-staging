<?php

namespace WPStaging\Framework\Traits;

trait TablePrefixValidator
{
    public function isWpStagingReservedPrefix(string $prefix): bool
    {
        return in_array($prefix, ['wpstg', 'wpstg_'], true);
    }

    public function getReservedPrefixErrorMessage(string $prefix): string
    {
        return sprintf(
            __('The prefix "%s" is reserved by WP STAGING and cannot be used. Please use a different prefix like "wpstg0_", "wpstg1_", etc.', 'wp-staging'),
            esc_html($prefix)
        );
    }
}
