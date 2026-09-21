<?php

namespace WPStaging\Framework\Utils;




class WordPressUrl
{






    public static function isValid(string $url): bool
    {
        $url = rtrim($url, '/');
        return preg_match('#http(s?)://(.+)#i', $url) === 1 && (bool)parse_url($url, PHP_URL_HOST);
    }
}
