<?php

namespace WPStaging\Core\Utils;

 
if (!defined("WPINC")) {
    die;
}

class Strings
{








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








    public function getLastElemAfterString($needle, $haystack)
    {
        $pos = strrpos($haystack, $needle);
        return $pos === false ? $haystack : substr($haystack, $pos + 1);
    }






    public function getUrlWithoutScheme($str)
    {
        return preg_replace('#^https?://#', '', rtrim($str, '/'));
    }








    public function sanitizeDirectorySeparator($path)
    {
        return preg_replace('/[\\\\]+/', '/', $path);
    }
}
