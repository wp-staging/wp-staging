<?php

namespace WPStaging\Framework\Utils;

use WPStaging\Framework\Traits\UrlTrait;

/**
 * Utility class for string manipulation and formatting operations
 *
 * Provides a collection of string utility methods including:
 * - String replacement operations (first occurrence, last occurrence, conditional)
 * - String inspection (startsWith, endsWith, substring extraction)
 * - Path and directory separator sanitization
 * - String masking for privacy (email addresses, backup filenames)
 * - Prefix/suffix manipulation
 *
 * Used throughout the plugin for consistent string handling, especially for
 * file paths, URLs, and sensitive data that needs to be displayed safely.
 */
class Strings
{
    use UrlTrait;

    /**
     * Replace first occurrence of certain string
     * @param string $search
     * @param string $replace
     * @param string $subject
     * @return string
     *
     * @deprecated use strReplaceFirst instead
     * @todo replace all usage of str_replace_first with strReplaceFirst
     */
    public function str_replace_first($search, $replace, $subject)
    {
        return $this->strReplaceFirst($search, $replace, $subject);
    }

    /**
     * Replace first occurrence of certain string
     * @param string $search
     * @param string $replace
     * @param string $subject
     * @return string
     */
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
     * Check if a string start with a specific string
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
     * Check if a string start with a specific string from a list of strings
     * @param string[] $needlesList
     * @param string $haystack
     * @return bool
     */
    public function startsWithAnyFromList(array $needlesList, string $haystack): bool
    {
        foreach ($needlesList as $needle) {
            if ($this->startsWith($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a string ends with a specific string
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public function endsWith($haystack, $needle)
    {
        $haystack = strrev($haystack);
        $needle = strrev($needle);
        return strpos($haystack, $needle) === 0;
    }

    /**
     * Search & Replace last occurrence of string in haystack
     * @param string $needle
     * @param string $replace
     * @param string $haystack
     * @return string
     */
    public function replaceLastMatch($needle, $replace, $haystack)
    {
        $result = $haystack;
        $pos = strrpos($haystack, $needle);
        if ($pos !== false) {
            $result = substr_replace($haystack, $replace, $pos, strlen($needle));
        }

        return $result;
    }

    /**
     * Make sure prefix ends with underscore
     * @param string $string
     * @return string
     */
    public function maybeAppendUnderscore(string $string): string
    {
        // Early bail, if underscore is already the last character
        if (substr($string, -1) === '_') {
            return $string;
        }

        return $string . '_';
    }

    /**
     * If $haystack starts with $needle, replace it with $replace
     * Example: replaceStartWith('www.', '', 'www.example.com') returns 'example.com'
     * But replaceStartWith('www.', '', 'https://wwww.example.com') returns 'https://www.example.com and remains unchanged'
     *
     * @param string $needle
     * @param string $replace
     * @param string $haystack
     * @return string
     */
    public function replaceStartWith(string $needle, string $replace, string $haystack): string
    {
        if (strpos($haystack, $needle) === 0) {
            return $replace . substr($haystack, strlen($needle));
        }

        return $haystack;
    }

    /**
     * @param string $email
     * @return string
     */
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

/**
 * Mask a backup filename so we can show it in logs/UI without leaking the full site name.
 *
 * Rules:
 * - Keep only the first 6 chars of the site prefix and last 6 chars of the timestamp/hash.
 * - Preserve the multipart suffix (e.g. ".plugins.001.wpstg") or ".wpstg".
 * - If the filename doesn't match known backup patterns, mask the middle of the base name.
 *
 * @param string $filename Full path or basename of the backup file.
 * @return string Masked filename safe for display/logging.
 */
    public function maskBackupFilename(string $filename): string
    {
        if ($filename === '') {
            return '';
        }

        $basename = basename($filename);

        // Handle multipart backup suffix patterns (preserve them entirely)
        if (preg_match('/^(.+?)_([0-9]{8}-[0-9]{6}_[a-f0-9]+)(\.(wpstgdb|otherfiles|rootfiles|themes|plugins|muplugins)\.\d+\.wpstg)$/', $basename, $matches)) {
            $prefix = substr($matches[1], 0, 6);
            $suffix = substr($matches[2], -6);
            return $prefix . '*********' . $suffix . $matches[3];
        }

        // Handle single backup file (not multipart)
        if (preg_match('/^(.+?)_([0-9]{8}-[0-9]{6}_[a-f0-9]+)(\.wpstg)$/', $basename, $matches)) {
            $prefix = substr($matches[1], 0, 6);
            $suffix = substr($matches[2], -6);
            return $prefix . '*********' . $suffix . $matches[3];
        }

        // Fallback: generic middle masking
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
