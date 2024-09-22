<?php

namespace WPStaging\Framework\Traits;

/**
 * Provide a wrapper method for apply_filters function to not filter when the function is not available
 * Useful in standalone tool
 * Trait ApplyFiltersTrait
 * @package WPStaging\Framework\Traits
 */
trait ApplyFiltersTrait
{
    /**
     * @param string $hookName
     * @param mixed $value
     * @param mixed ...$args
     * @return mixed
     */
    protected function applyFilters(string $filter, $value, ...$args)
    {
        if (class_exists('\WPStaging\Framework\Facades\Hooks')) {
            return \WPStaging\Framework\Facades\Hooks::applyFilters($filter, $value, ...$args);
        }

        return $value;
    }
}
