<?php

namespace WPStaging\Framework\Filesystem;

/**
 * Normalizes legacy file exclusion rules for filesystem scanners.
 */
trait LegacyFileRulesTrait
{
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
