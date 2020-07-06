<?php

namespace WPStaging\Service\Utils;

/**
 * Class Strings
 * @package WPStaging\Service\Strings
 */
class Strings
{
    /**
     * This function ensures backwards compatibility with Wordpress prior to the 4.7 release. sanitize_textarea_field
     * was introduced with that version.
     * @param $str
     *
     * @return string
     */
    public function sanitizeTextareaField($str)
    {
        if (function_exists('sanitize_textarea_field')) {
            return sanitize_textarea_field($str);
        } else {
            return sanitize_text_field($str);
        }
    }
}
