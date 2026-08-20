<?php

namespace WPStaging\Framework\Utils;

use WPStaging\Framework\Traits\UrlTrait;














class Strings
{
    use UrlTrait;











    public function str_replace_first($search, $replace, $subject)
    {
        return $this->strReplaceFirst($search, $replace, $subject);
    }








    public function strReplaceFirst($search, $replace, $subject)
    {
        if (empty($search)) {
            return $subject;
        }

        $pos = strpos($subject, $search);
        if ($pos !== false) {
            if ($replace === null) {
                $replace = '';
            }

            return substr_replace($subject, $replace, $pos, strlen($search));
        }

        return $subject;
    }








    public function getLastElemAfterString($needle, $haystack)
    {
        $pos = strrpos($haystack, $needle);
        return $pos === false ? $haystack : substr($haystack, $pos + 1);
    }








    public function sanitizeDirectorySeparator($path)
    {
        $string = preg_replace('/[\\\\]+/', '/', $path);
        return str_replace('//', '/', $string);
    }







    public function startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return ($needle === substr($haystack, 0, $length));
    }







    public function startsWithAnyFromList(array $needlesList, string $haystack): bool
    {
        foreach ($needlesList as $needle) {
            if ($this->startsWith($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }







    public function endsWith($haystack, $needle)
    {
        $haystack = strrev($haystack);
        $needle = strrev($needle);
        return strpos($haystack, $needle) === 0;
    }








    public function replaceLastMatch($needle, $replace, $haystack)
    {
        $result = $haystack;
        $pos = strrpos($haystack, $needle);
        if ($pos !== false) {
            $result = substr_replace($haystack, $replace, $pos, strlen($needle));
        }

        return $result;
    }






    public function maybeAppendUnderscore(string $string): string
    {
 
        if (substr($string, -1) === '_') {
            return $string;
        }

        return $string . '_';
    }











    public function replaceStartWith(string $needle, string $replace, string $haystack): string
    {
        if (strpos($haystack, $needle) === 0) {
            return $replace . substr($haystack, strlen($needle));
        }

        return $haystack;
    }





    public function maskEmail(string $email): string
    {
        if (empty($email)) {
            return '';
        }

        list($username, $domain) = explode('@', $email);
        $usernameLength = strlen($username);
        $firstChar      = substr($username, 0, 1);
        $lastChar       = substr($username, -1);
        if ($usernameLength <= 2) {
            $maskedUsername = $firstChar . str_repeat('*', max(0, $usernameLength - 1));
        } else {
            $maskedUsername = $firstChar . str_repeat('*', $usernameLength - 2) . $lastChar;
        }

        list($domainName, $topLevelDomain) = explode('.', $domain);
        $maskedDomainName = substr($domainName, 0, 1) . str_repeat('*', max(0, strlen($domainName) - 1));
        return $maskedUsername . '@' . $maskedDomainName . '.' . $topLevelDomain;
    }












    public function maskBackupFilename(string $filename): string
    {
        if ($filename === '') {
            return '';
        }

        $basename = basename($filename);

 
        if (preg_match('/^(.+?)_([0-9]{8}-[0-9]{6}_[a-f0-9]+)(\.(wpstgdb|otherfiles|rootfiles|themes|plugins|muplugins)\.\d+\.wpstg)$/', $basename, $matches)) {
            $prefix = substr($matches[1], 0, 6);
            $suffix = substr($matches[2], -6);
            return $prefix . '*********' . $suffix . $matches[3];
        }

 
        if (preg_match('/^(.+?)_([0-9]{8}-[0-9]{6}_[a-f0-9]+)(\.wpstg)$/', $basename, $matches)) {
            $prefix = substr($matches[1], 0, 6);
            $suffix = substr($matches[2], -6);
            return $prefix . '*********' . $suffix . $matches[3];
        }

 
        $nameWithoutExt = pathinfo($basename, PATHINFO_FILENAME);
        $extension      = pathinfo($basename, PATHINFO_EXTENSION);
        $extension      = $extension ? '.' . $extension : '';

        $length = strlen($nameWithoutExt);
        if ($length <= 2) {
            return substr($nameWithoutExt, 0, 1) . '*********' . substr($nameWithoutExt, -1) . $extension;
        }

        return substr($nameWithoutExt, 0, 6) . '*********' . substr($nameWithoutExt, -6) . $extension;
    }
}
