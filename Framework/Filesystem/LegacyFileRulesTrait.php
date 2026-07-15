<?php

namespace WPStaging\Framework\Filesystem;

/**
 * Normalizes and evaluates legacy file exclusion rules, so scanners and size estimates
 * agree on which files and folders the copy leaves out.
 */
trait LegacyFileRulesTrait
{
    /**
     * True when the copy would skip this file: by name rule, extension, or size limit.
     *
     * @param string   $path
     * @param string[] $excludedExtensions Lowercase extensions (e.g. "log").
     * @param string[] $fileNameRules      Rules in "position value" form (e.g. "name_ends_with .bak").
     * @param int      $maxFileSizeBytes   Files bigger than this are excluded; 0 disables the limit.
     * @return bool
     */
    protected function isExcludedFileByRules(string $path, array $excludedExtensions, array $fileNameRules, int $maxFileSizeBytes): bool
    {
        $name = basename($path);

        foreach ($fileNameRules as $rule) {
            if ($this->ruleMatch($rule, $name)) {
                return true;
            }
        }

        if (in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), $excludedExtensions, true)) {
            return true;
        }

        if ($maxFileSizeBytes > 0) {
            $size = filesize($path);
            if ($size !== false && $size > $maxFileSizeBytes) {
                return true;
            }
        }

        return false;
    }

    /**
     * True when the copy would skip this folder by a folder name rule.
     *
     * @param string   $path
     * @param string[] $folderNameRules Rules in "position value" form.
     * @return bool
     */
    protected function isExcludedFolderByRules(string $path, array $folderNameRules): bool
    {
        $name = basename($path);
        foreach ($folderNameRules as $rule) {
            if ($this->ruleMatch($rule, $name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $fileRules
     * @return array
     */
    protected function extractLegacyFileNameRules(array $fileRules): array
    {
        $normalizedRules = [];

        foreach ($fileRules as $fileRule) {
            if (!is_string($fileRule)) {
                continue;
            }

            $fileRule = trim($fileRule);
            if ($fileRule === '') {
                continue;
            }

            $fileRule = $this->reduceLegacyPathRuleToFileName($fileRule);

            if ($fileRule !== '') {
                $normalizedRules[] = $fileRule;
            }
        }

        return array_values(array_unique($normalizedRules));
    }

    /**
     * @param array $fileRules
     * @return array
     */
    protected function extractFileExtensions(array $fileRules): array
    {
        $extensions = [];

        foreach ($fileRules as $fileRule) {
            if (!is_string($fileRule)) {
                continue;
            }

            $fileRule = trim($fileRule);
            if ($this->getLegacyVcsDirectoryName($fileRule) !== '') {
                continue;
            }

            // Only glob extension rules (`*.log`) exclude by extension; a filename rule like
            // `wp-staging-optimizer.php` must not turn `.php` into an ignored extension.
            if (strpos($fileRule, '*.') !== 0) {
                continue;
            }

            $fileRule = ltrim($fileRule, '*');
            $position = strrpos($fileRule, '.');
            if ($position === false || $position === strlen($fileRule) - 1) {
                continue;
            }

            $extension = strtolower(substr($fileRule, $position + 1));
            if ($extension !== '') {
                $extensions[] = $extension;
            }
        }

        return array_values(array_unique($extensions));
    }

    /**
     * @param array $fileRules
     * @return array
     */
    protected function extractLegacyFolderNameRules(array $fileRules): array
    {
        $folderRules = [];

        foreach ($fileRules as $fileRule) {
            if (!is_string($fileRule)) {
                continue;
            }

            $vcsDirectoryName = $this->getLegacyVcsDirectoryName($fileRule);
            if ($vcsDirectoryName === '') {
                continue;
            }

            $folderRules[] = 'name_exact_matches ' . $vcsDirectoryName;
        }

        return array_values(array_unique($folderRules));
    }

    /**
     * Matches a "position value" rule (e.g. "name_ends_with .bak") against a file or folder name.
     *
     * @param string $rule
     * @param string $name
     * @return bool
     */
    protected function ruleMatch(string $rule, string $name): bool
    {
        $rule = trim($rule);
        if (strpos($rule, ' ') === false) {
            return false;
        }

        list($ruleType, $ruleValue) = explode(' ', $rule, 2);
        switch ($ruleType) {
            case 'name_contains':
                return strpos($name, $ruleValue) !== false;
            case 'name_begins_with':
                return strpos($name, $ruleValue) === 0;
            case 'name_ends_with':
                return substr($name, -strlen($ruleValue)) === $ruleValue;
            case 'name_exact_matches':
                return $name === $ruleValue;
            default:
                return false;
        }
    }

    private function getLegacyVcsDirectoryName(string $fileRule): string
    {
        $fileRule = trim($fileRule);
        if ($fileRule === '') {
            return '';
        }

        $fileRule = strtolower($this->reduceLegacyPathRuleToFileName($fileRule));
        if (strpos($fileRule, '*.') === 0) {
            $directoryName = substr($fileRule, 2);
        } elseif (strpos($fileRule, '.') === 0) {
            $directoryName = substr($fileRule, 1);
        } else {
            return '';
        }

        if (!in_array($directoryName, ['git', 'svn', 'hg'], true)) {
            return '';
        }

        return '.' . $directoryName;
    }

    private function reduceLegacyPathRuleToFileName(string $fileRule): string
    {
        if (strpos($fileRule, '/') !== false || strpos($fileRule, '\\') !== false) {
            return basename(str_replace('\\', '/', $fileRule));
        }

        return $fileRule;
    }
}
