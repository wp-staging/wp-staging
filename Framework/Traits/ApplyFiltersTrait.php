<?php

namespace WPStaging\Framework\Traits;

/**
 * Wrapper for apply_filters that works in both the WordPress plugin and the standalone restore tool.
 *
 * In the WordPress plugin context, delegates to the Hooks facade which calls WordPress apply_filters().
 * In the standalone restore tool (wpstg-restore.php), falls back to FilterConfig which reads
 * filter values from a JSON config file (wpstg-restore-config.json). See dev/docs/wpstg-restore/filters.md.
 * If neither is available, returns the default value unchanged.
 */
trait ApplyFiltersTrait
{
    /**
     * @param string $filter
     * @param mixed $value
     * @param mixed ...$args
     * @return mixed
     */
    protected function applyFilters(string $filter, $value, ...$args)
    {
        if (class_exists('\WPStaging\Framework\Facades\Hooks')) {
            return \WPStaging\Framework\Facades\Hooks::applyFilters($filter, $value, ...$args);
        }

        // Standalone restore tool: FilterConfig is in the WpstgRestorer namespace after bundling.
        // In the WordPress plugin context, this class_exists check is always false (no-op).
        if (class_exists('\WpstgRestorer\FilterConfig') && \WpstgRestorer\FilterConfig::has($filter)) {
            return \WpstgRestorer\FilterConfig::get($filter, $value);
        }

        return $value;
    }
}
