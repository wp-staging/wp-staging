<?php

namespace WPStaging\Staging\Ajax;

use InvalidArgumentException;
use Throwable;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Staging\Service\AbstractStagingSetup;
use WPStaging\Staging\Service\DirectoryScanner;
use WPStaging\Staging\Sites;

/**
 * Renders lazily requested directory children for staging operations.
 */
class DirectoryChildren extends AbstractTemplateComponent
{
    /** @var AbstractStagingSetup */
    private $stagingSetup;

    /** @var DirectoryScanner */
    private $directoryScanner;

    /** @var Directory */
    private $directory;

    /** @var Sites */
    private $stagingSites;

    public function __construct(
        TemplateEngine $templateEngine,
        AbstractStagingSetup $stagingSetup,
        DirectoryScanner $directoryScanner,
        Directory $directory,
        Sites $stagingSites
    ) {
        parent::__construct($templateEngine);
        $this->stagingSetup     = $stagingSetup;
        $this->directoryScanner = $directoryScanner;
        $this->directory        = $directory;
        $this->stagingSites     = $stagingSites;
    }

    /**
     * @return void
     */
    public function ajaxRender()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        try {
            $jobType      = isset($_POST['jobType']) ? Sanitize::sanitizeString($_POST['jobType']) : AbstractStagingSetup::JOB_NEW_STAGING_SITE;
            $cloneId      = isset($_POST['cloneId']) ? Sanitize::sanitizeString($_POST['cloneId']) : '';
            $dirPath      = isset($_POST['dirPath']) ? Sanitize::sanitizePath($_POST['dirPath']) : '';
            $prefix       = isset($_POST['prefix']) ? Sanitize::sanitizeString($_POST['prefix']) : '';
            $parentChecked = isset($_POST['isChecked']) && Sanitize::sanitizeBool($_POST['isChecked']);
            $forceDefault = isset($_POST['forceDefault']) && Sanitize::sanitizeBool($_POST['forceDefault']);

            $this->setupScanner($jobType, $cloneId);
            $basePath = $this->getBasePath($prefix);
            $path     = $this->resolvePathWithinBase($dirPath, $basePath);

            $directories = $this->directoryScanner->scanDirectory($path, $basePath, $prefix);
            $listing     = $this->directoryScanner->directoryListing($directories, $parentChecked, $forceDefault);

            wp_send_json_success(['directoryListing' => $listing]);
        } catch (Throwable $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    private function setupScanner(string $jobType, string $cloneId)
    {
        if ($jobType === AbstractStagingSetup::JOB_UPDATE || $jobType === AbstractStagingSetup::JOB_RESET) {
            if ($cloneId === '') {
                throw new InvalidArgumentException('Invalid clone ID provided.');
            }

            $stagingSiteDto = $this->stagingSites->getStagingSiteDtoByCloneId($cloneId);
            if ($jobType === AbstractStagingSetup::JOB_RESET) {
                $this->stagingSetup->initResetJob($stagingSiteDto);
            } else {
                $this->stagingSetup->initUpdateJob($stagingSiteDto);
            }
        } else {
            $this->stagingSetup->initNewStagingSite();
        }

        $this->directoryScanner->setStagingSetup($this->stagingSetup);
    }

    private function getBasePath(string $prefix): string
    {
        if ($prefix === PathIdentifier::IDENTIFIER_ABSPATH) {
            return trailingslashit(wp_normalize_path($this->directory->getAbsPath()));
        }

        if ($prefix === PathIdentifier::IDENTIFIER_WP_CONTENT) {
            return trailingslashit(wp_normalize_path($this->directory->getWpContentDirectory()));
        }

        throw new InvalidArgumentException('Invalid directory path identifier.');
    }

    private function resolvePathWithinBase(string $dirPath, string $basePath): string
    {
        $resolvedBase = realpath($basePath);
        $resolvedPath = realpath($basePath . ltrim($dirPath, '/\\'));
        if ($resolvedBase === false || $resolvedPath === false) {
            throw new InvalidArgumentException('Invalid directory path.');
        }

        $resolvedBase = wp_normalize_path($resolvedBase);
        $resolvedPath = wp_normalize_path($resolvedPath);
        if ($resolvedPath !== $resolvedBase && strpos(trailingslashit($resolvedPath), trailingslashit($resolvedBase)) !== 0) {
            throw new InvalidArgumentException('Directory path is outside the allowed base path.');
        }

        return $resolvedPath;
    }
}
