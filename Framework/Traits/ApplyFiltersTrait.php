<?php

namespace WPStaging\Framework\Traits;









trait ApplyFiltersTrait
{






    protected function applyFilters(string $filter, $value, ...$args)
    {
        if (class_exists('\WPStaging\Framework\Facades\Hooks')) {
            return \WPStaging\Framework\Facades\Hooks::applyFilters($filter, $value, ...$args);
        }

 
 
        if (class_exists('\WpstgRestorer\FilterConfig') && \WpstgRestorer\FilterConfig::has($filter)) {
            return \WpstgRestorer\FilterConfig::get($filter, $value);
        }

        return $value;
    }
}
