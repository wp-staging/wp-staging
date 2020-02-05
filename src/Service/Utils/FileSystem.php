<?php

// TODO PHP7.x; declare(strict_types=1);

namespace WPStaging\Service\Utils;

class FileSystem
{
    /*
     * Makes sure all paths contain linux separator (/) which works fine on all windows systems, too
     * Windows understands both / and \
     */
    public function compatiblePath($path)
    {
        if ('/' === DIRECTORY_SEPARATOR) {
            return $path;
        }

        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    public function replaceWindowsDirSeparator($path)
    {
        return preg_replace('/[\\\\]+/', '/', $path);
    }

    /**
     * Checks if the passed string would match the given shell wildcard pattern.
     * This function emulates [[fnmatch()]], which may be unavailable at certain environment, using PCRE.
     * @param string $pattern the shell wildcard pattern.
     * @param string $string the tested string.
     * @param array $options options for matching. Valid options are:
     *
     * - caseSensitive: bool, whether pattern should be case sensitive. Defaults to `true`.
     * - escape: bool, whether backslash escaping is enabled. Defaults to `true`.
     * - filePath: bool, whether slashes in string only matches slashes in the given pattern. Defaults to `false`.
     *
     * @return bool whether the string matches pattern or not.
     */
    public function wpstg_fnmatch($pattern, $string, $options = array())
    {
        if ($pattern === '*' && empty($options['filePath'])) {
            return true;
        }
        $replacements = array(
            '\\\\\\\\' => '\\\\',
            '\\\\\\*' => '[*]',
            '\\\\\\?' => '[?]',
            '\*' => '.*',
            '\?' => '.',
            '\[\!' => '[^',
            '\[' => '[',
            '\]' => ']',
            '\-' => '-',
        );
        if (isset($options['escape']) && !$options['escape']) {
            unset($replacements['\\\\\\\\']);
            unset($replacements['\\\\\\*']);
            unset($replacements['\\\\\\?']);
        }
        if (!empty($options['filePath'])) {
            $replacements['\*'] = '[^/\\\\]*';
            $replacements['\?'] = '[^/\\\\]';
        }
        $pattern = strtr(preg_quote($pattern, '#'), $replacements);
        $pattern = '#^' . $pattern . '$#us';
        if (isset($options['caseSensitive']) && !$options['caseSensitive']) {
            $pattern .= 'i';
        }
        if (preg_match($pattern, $string) === 1){
            return true;
        }
        return false;
    }


    /**
     * @param string $file full path + filename
     * @param array $excludedFiles List of filenames. Can be wildcard pattern like data.php, data*.php, *.php, .php
     * @return boolean
     */
    public function isFilenameExcluded($file, $excludedFiles)
    {
        $filename = basename($file);

        // Regular filenames
        if (in_array($filename, $excludedFiles)) {
            return true;
        }

        // Wildcards
        foreach ($excludedFiles as $pattern) {
            if ($this->wpstg_fnmatch($pattern, $filename)) {
                return true;
            }
        }
        return false;
    }
}
