<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; return types && type-hints

namespace WPStaging\Framework\Filesystem;

use WPStaging\Vendor\Symfony\Component\Finder\Finder;

class FileService
{
    /**
     * @param string $directory
     * @param string $depth
     * @param array|null $excludedFileNames
     *
     * @return Finder|null
     */
    public function scan($directory, $depth = null, array $excludedFileNames = null)
    {
        $finder = (new Finder)
            ->files()
            ->in($directory)
        ;

        if ($excludedFileNames) {
            /** @noinspection PhpParamsInspection */
            $finder->notName($excludedFileNames);
        }

        if ($depth !== null) {
            $finder->depth($depth);
        }

        $finderHasResults = count($finder) > 0;

        if (!$finderHasResults) {
            return null;
        }

        return $finder;
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
        if (in_array($filename, $excludedFiles, true)) {
            return true;
        }

        // Wildcards
        foreach ($excludedFiles as $pattern) {
            if ($this->fnmatch($pattern, $filename)) {
                return true;
            }
        }
        return false;
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
    protected function fnmatch($pattern, $string, $options = [])
    {
        if ($pattern === '*' && empty($options['filePath'])) {
            return true;
        }

        $replacements = [
            '\\\\\\\\' => '\\\\',
            '\\\\\\*' => '[*]',
            '\\\\\\?' => '[?]',
            '\*' => '.*',
            '\?' => '.',
            '\[\!' => '[^',
            '\[' => '[',
            '\]' => ']',
            '\-' => '-',
        ];

        if (isset($options['escape']) && !$options['escape']) {
            unset($replacements['\\\\\\\\'], $replacements['\\\\\\*'], $replacements['\\\\\\?']);
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

        return preg_match($pattern, $string) === 1;
    }
}
