<?php

namespace WPStaging\Framework\Utils;

/**
 * Class Strings
 * @package WPStaging\Service\Strings
 */
class Strings
{
    /**
     * Replace first occurrence of certain string
     * @param string $search
     * @param string $replace
     * @param string $subject
     * @return string
     */
    public function str_replace_first($search, $replace, $subject)
    {

        if (empty($search)) {
            return $subject;
        }

        $pos = strpos($subject, $search);
        if ($pos !== false) {
            return substr_replace($subject, $replace, $pos, strlen($search));
        }
        return $subject;
    }

    /**
     * Get last string after last certain element in string
     * Example: getLastElemAfterString('/', '/path/stagingsite/subfolder') returns 'subfolder'
     * @param string $needle
     * @param string $haystack
     * @return string
     */
    public function getLastElemAfterString($needle, $haystack)
    {
        $pos = strrpos($haystack, $needle);
        return $pos === false ? $haystack : substr($haystack, $pos + 1);
    }

    /**
     * Return url without scheme
     * @param string $str
     * @return string
     */
    public function getUrlWithoutScheme($str)
    {
        return preg_replace('#^https?://#', '', rtrim($str, '/'));
    }

    /**
     * Replace backward slash with forward slash directory separator
     * Escape Windows Backward Slash -  Compatibility Fix
     * @param string $path Path
     *
     * @return string
     */
    public function sanitizeDirectorySeparator($path)
    {
        $string = preg_replace('/[\\\\]+/', '/', $path);
        return str_replace('//', '/', $string);
    }

    /**
     * Check if a strings start with a specific string
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public function startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return ($needle === substr($haystack, 0, $length));
    }

    /**
     * Search & Replace last occurrence of string in haystack
     * @param string $needle
     * @param string $replace
     * @param string $haystack
     * @return string
     */
    function replaceLastMatch($needle, $replace, $haystack)
    {
        $result = $haystack;
        $pos = strrpos($haystack, $needle);
        if ($pos !== false) {
            $result = substr_replace($haystack, $replace, $pos, strlen($needle));
        }
        return $result;
    }
}
