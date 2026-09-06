<?php

namespace WPStaging\Framework\Database;

use WPStaging\Framework\Facades\Hooks;




class OptionNameExclusions
{
 
    const FILTER_DATA_EXCLUDED_ROWS = 'wpstg_data_excl_rows';




    public static function getFilteredOptionNames(): array
    {
        $defaultOptionNames = [
            'wp_mail_smtp',
            'wp_mail_smtp_version',
            'wp_mail_smtp_debug',
            'db_version',
        ];

        $optionNames = Hooks::applyFilters(self::FILTER_DATA_EXCLUDED_ROWS, $defaultOptionNames);
        if (!is_array($optionNames)) {
            return $defaultOptionNames;
        }

        $optionNames = array_filter($optionNames, function ($optionName) {
            return is_string($optionName) && trim($optionName) !== '';
        });

        return array_values(array_unique($optionNames));
    }
}
