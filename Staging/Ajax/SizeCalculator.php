<?php

namespace WPStaging\Staging\Ajax;

use RuntimeException;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Filesystem\DirectorySize;
use WPStaging\Framework\Filesystem\DiskWriteCheck;
use WPStaging\Framework\Filesystem\LegacyFileRulesTrait;
use WPStaging\Framework\Filesystem\PathChecker;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\Filesystem\Scanning\ScanConst;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Framework\Utils\Math;
use WPStaging\Framework\Utils\WpDefaultDirectories;

/**
 * Calculates the selected non-push staging size without starting a legacy scan job.
 */
class SizeCalculator extends AbstractTemplateComponent
{
    use LegacyFileRulesTrait;

    /** @var Directory */
    private $directory;

    /** @var DirectorySize */
    private $directorySize;

    /** @var DiskWriteCheck */
    private $diskWriteCheck;

    /** @var PathChecker */
    private $pathChecker;

    /** @var PathIdentifier */
    private $pathIdentifier;

    /** @var Math */
    private $math;

    /** @var WpDefaultDirectories */
    private $wpDefaultDirectories;

    public function __construct(
        TemplateEngine $templateEngine,
        Directory $directory,
        DirectorySize $directorySize,
        DiskWriteCheck $diskWriteCheck,
        PathChecker $pathChecker,
        PathIdentifier $pathIdentifier,
        Math $math,
        WpDefaultDirectories $wpDefaultDirectories
    ) {
        parent::__construct($templateEngine);
        $this->directory            = $directory;
        $this->directorySize        = $directorySize;
        $this->diskWriteCheck       = $diskWriteCheck;
        $this->pathChecker          = $pathChecker;
        $this->pathIdentifier       = $pathIdentifier;
        $this->math                 = $math;
        $this->wpDefaultDirectories = $wpDefaultDirectories;
    }

    /**
     * @return void
     */
    public function ajaxSize()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        $excludedDirectories = isset($_POST['excludedDirectories']) ? Sanitize::sanitizeString($_POST['excludedDirectories']) : '';
        $extraDirectories    = isset($_POST['extraDirectories']) ? Sanitize::sanitizeString($_POST['extraDirectories']) : '';
        $isUploadsSymlinked  = isset($_POST['isUploadsSymlinked']) && Sanitize::sanitizeBool($_POST['isUploadsSymlinked']);
        $databaseSize        = isset($_POST['databaseSize']) ? Sanitize::sanitizeInt($_POST['databaseSize']) : 0;

        wp_send_json($this->calculate($excludedDirectories, $extraDirectories, $databaseSize, $isUploadsSymlinked, $this->resolveExclusionRules()));
    }

    /**
     * The same exclude rules the client sends for the real clone, so the estimate drops the same files.
     * Missing rules fall back to the copy defaults.
     *
     * @return array{extensions: string[], fileRules: string[], folderRules: string[], maxBytes: int}
     */
    private function resolveExclusionRules(): array
    {
        $userExtensions = array_map('strtolower', $this->requestRules('excludeExtensionRules'));
        $maxSizeMb      = isset($_POST['excludeSizeGreaterThan']) && $_POST['excludeSizeGreaterThan'] !== ''
            ? max(0, Sanitize::sanitizeInt($_POST['excludeSizeGreaterThan']))
            : Directory::DEFAULT_MAX_FILE_SIZE_MB;

        return [
            'extensions'  => $this->directory->getExcludedFileExtensions($userExtensions),
            'fileRules'   => $this->requestRules('excludeFileRules'),
            'folderRules' => $this->requestRules('excludeFolderRules'),
            'maxBytes'    => $maxSizeMb * MB_IN_BYTES,
        ];
    }

    /**
     * @param string $key
     * @return string[]
     */
    private function requestRules(string $key): array
    {
        if (empty($_POST[$key])) {
            return [];
        }

        $value = wpstg_urldecode(Sanitize::sanitizeString($_POST[$key]));

        return array_values(array_filter(array_map('trim', explode(',', $value)), function ($rule) {
            return $rule !== '';
        }));
    }

    /**
     * @param string                                                                     $excludedDirectories
     * @param string                                                                     $extraDirectories
     * @param int                                                                        $databaseSize
     * @param bool                                                                       $isUploadsSymlinked
     * @param array{extensions: string[], fileRules: string[], folderRules: string[], maxBytes: int} $rules
     * @return array{requiredSpace: string, errorMessage: string|null}
     */
    public function calculate(string $excludedDirectories, string $extraDirectories, int $databaseSize, bool $isUploadsSymlinked, array $rules): array
    {
        $absPath             = $this->directory->getAbsPath();
        $selectedDirectories = $this->wpDefaultDirectories->getWpCoreDirectories();
        $excludedDirectories = $this->wpDefaultDirectories->getExcludedDirectories($excludedDirectories);

        if ($isUploadsSymlinked) {
            $uploadDirectory = rtrim(str_replace($absPath, PathIdentifier::IDENTIFIER_ABSPATH, $this->directory->getMainSiteUploadsDirectory()), '/');
            $excludedDirectories[] = $uploadDirectory;
        }

        $excludedDirectories = array_merge($excludedDirectories, array_map('untrailingslashit', $this->directory->getDefaultExcludedDirectories()));

        $size = max(0, $databaseSize) + $this->getRootFilesSize($absPath, $rules);
        foreach ($selectedDirectories as $directory) {
            if ($this->isPathInDirectories($directory, $excludedDirectories)) {
                continue;
            }

            $size += $this->getDirectorySize($directory, $excludedDirectories, $rules);
        }

        if ($extraDirectories !== '') {
            $extraDirectories = explode(ScanConst::DIRECTORIES_SEPARATOR, wpstg_urldecode($extraDirectories));
            $extraDirectories = array_unique(array_map(function ($directory) {
                return $this->pathIdentifier->transformIdentifiableToRelativePath($directory);
            }, $extraDirectories));

            foreach ($extraDirectories as $directory) {
                if ($directory === '' || $this->isNestedInDirectories($directory, $extraDirectories)) {
                    continue;
                }

                $size += $this->getDirectorySize($absPath . $directory, $excludedDirectories, $rules);
            }
        }

        $errorMessage = null;
        try {
            $this->diskWriteCheck->checkPathCanStoreEnoughBytes($absPath, $size);
        } catch (RuntimeException $ex) {
            $errorMessage = $ex->getMessage();
        } catch (DiskNotWritableException $ex) {
            $errorMessage = $ex->getMessage();
        }

        return [
            'requiredSpace' => $this->math->formatSize($size),
            'errorMessage'  => $errorMessage,
        ];
    }

    /**
     * @param string   $directory
     * @param string[] $excludedDirectories
     * @param array    $rules
     * @return int
     */
    private function getDirectorySize(string $directory, array $excludedDirectories, array $rules): int
    {
        return $this->directorySize->getSizeInclSubdirs($directory, function ($path) use ($excludedDirectories, $rules) {
            if (is_dir($path)) {
                return $this->isExcludedFolderByRules($path, $rules['folderRules']) || $this->isPathInDirectories($path, $excludedDirectories);
            }

            return $this->isExcludedFileByRules($path, $rules['extensions'], $rules['fileRules'], $rules['maxBytes']);
        });
    }

    private function getRootFilesSize(string $directory, array $rules): int
    {
        $entries = glob(rtrim($directory, '/') . '/*', GLOB_NOSORT);
        if ($entries === false) {
            return 0;
        }

        $size = 0;
        foreach ($entries as $entry) {
            // Symlinks are not copied to the staging site, so they must not be counted.
            if (is_link($entry) || !is_file($entry) || $this->isExcludedFileByRules($entry, $rules['extensions'], $rules['fileRules'], $rules['maxBytes'])) {
                continue;
            }

            $size += (int)filesize($entry);
        }

        return $size;
    }

    /**
     * @param string   $path
     * @param string[] $directories
     * @return bool
     */
    private function isPathInDirectories(string $path, array $directories): bool
    {
        return $this->pathChecker->isPathInPathsList($path, $directories, true);
    }

    /**
     * @param string   $directory
     * @param string[] $directories
     * @return bool
     */
    private function isNestedInDirectories(string $directory, array $directories): bool
    {
        foreach ($directories as $candidate) {
            if ($candidate === $directory) {
                continue;
            }

            if (strpos($directory, rtrim($candidate, '/') . '/') === 0) {
                return true;
            }
        }

        return false;
    }
}
