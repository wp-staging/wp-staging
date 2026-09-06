<?php

namespace WPStaging\Framework\Traits;




trait RestoresPreservedOptionsTrait
{








    protected function restorePreservedOption(string $optionName, $optionValue, bool $autoload)
    {
        global $wp_filter;

        $hook              = 'sanitize_option_' . $optionName;
        $sanitizeCallbacks = isset($wp_filter[$hook]) ? clone $wp_filter[$hook] : null;

        remove_all_filters($hook);
        update_option($optionName, $optionValue, $autoload);

        if ($sanitizeCallbacks !== null) {
            $wp_filter[$hook] = $sanitizeCallbacks;
        }
    }
}
