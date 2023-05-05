<?php

namespace WPStaging\Framework\CloningProcess\Data;

use WPStaging\Backend\Modules\Jobs\CloningProcess;
use WPStaging\Framework\CloningProcess\Data\DataCloningDto;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Utils\Urls;
use WPStaging\Framework\Utils\WpDefaultDirectories;

/**
 * Class Job
 * @package WPStaging\Framework\CloningProcess\Data
 */
abstract class Job extends CloningProcess
{
    /**
     * @var string
     */
    private $prefix;

    /**
     *
     * @var string
     */
    private $homeUrl;

    /**
     *
     * @var string
     */
    private $siteUrl;

    /**
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * Tables e.g wpstg3_options
     * @var array
     */
    protected $tables;

    /**
     * Initialize
     */
    public function initialize()
    {
        $this->initializeDbObjects();

        $this->prefix = $this->options->prefix;

        $this->homeUrl = (new Urls())->getHomeUrl();
        $this->siteUrl = (new Urls())->getSiteUrl();
        $this->baseUrl = (new Urls())->getBaseUrl();

        // Reset current step
        if ($this->options->currentStep === 0) {
            $this->options->currentStep = 0;
        }
    }

    /**
     * Start Module
     * @return object
     */
    public function start()
    {
        // Execute steps
        $this->run();

        // Save option, progress
        $this->saveOptions();

        return (object)$this->response;
    }

    /**
     * @param int $stepNumber
     * @return DataCloningDto
     */
    protected function getCloningDto($stepNumber)
    {
        return new DataCloningDto(
            $this,
            $this->stagingDb,
            $this->productionDb,
            $this->isExternalDatabase(),
            $this->isMultisiteAndPro(),
            $this->isExternalDatabase() ? $this->options->databaseServer : null,
            $this->isExternalDatabase() ? $this->options->databaseUser : null,
            $this->isExternalDatabase() ? $this->options->databasePassword : null,
            $this->isExternalDatabase() ? $this->options->databaseDatabase : null,
            $stepNumber,
            $this->prefix,
            $this->tables,
            $this->getOptions()->destinationDir,
            $this->getStagingSiteUrl(),
            (new WpDefaultDirectories())->getRelativeUploadPath(),
            $this->settings,
            $this->homeUrl,
            $this->baseUrl,
            $this->options->mainJob,
            $this->isExternalDatabase() ? $this->options->databaseSsl : false
        );
    }

    /**
     * Execute the Current Step
     * Returns false when over threshold limits are hit or when the job is done, true otherwise
     * @return bool
     */
    protected function execute()
    {
        // Over limits threshold
        if ($this->isOverThreshold()) {
            // Prepare response and save current progress
            $this->prepareResponse(false, false);
            $this->saveOptions();
            return false;
        }

        // No more steps, finished
        if ($this->isFinished()) {
            $this->prepareResponse(true, false);
            return false;
        }

        // Execute step
        $stepMethodName = "step" . $this->options->currentStep;
        if (!$this->{$stepMethodName}()) {
            $this->prepareResponse(false, false);
            return false;
        }

        // Prepare Response
        $this->prepareResponse();

        // Not finished
        return true;
    }

    /**
     * Checks Whether There is Any Job to Execute or Not
     * @return bool
     */
    protected function isFinished()
    {
        return
            !$this->isRunning() ||
            $this->options->currentStep > $this->options->totalSteps ||
            !method_exists($this, "step" . $this->options->currentStep);
    }

    /**
     * Check if WP is installed in subdir
     * @return boolean
     */
    protected function isSubDir()
    {
        return (new SiteInfo())->isInstalledInSubDir();
    }

    /**
     * Get the install sub directory if WP is installed in sub directory
     * @return string
     */
    protected function getInstallSubDir()
    {
        $home    = get_option('home');
        $siteurl = get_option('siteurl');

        if (empty($home) || empty($siteurl) || !$this->isSubDir() || $siteurl === str_replace([$home], '', $siteurl)) {
            return '';
        }

        return trim(wp_parse_url($siteurl, PHP_URL_PATH), '/');
    }

    /**
     * Return URL of staging site
     * @return string
     */
    protected function getStagingSiteUrl()
    {
        if (isset($this->options->url)) {
            return $this->options->url;
        }

        if (!empty($this->options->cloneHostname)) {
            return $this->options->cloneHostname;
        }

        if ($this->isMultisiteAndPro()) {
            if ($this->getInstallSubDir()) {
                return trailingslashit($this->baseUrl) . trailingslashit($this->getInstallSubDir()) . $this->options->cloneDirectoryName;
            }

            // Get the path to the main multisite without a trailingslash e.g. wordpress
            $multisitePath = defined('PATH_CURRENT_SITE') ? PATH_CURRENT_SITE : '/';
            return rtrim($this->baseUrl, '/\\') . $multisitePath . $this->options->cloneDirectoryName;
        }

        if ($this->getInstallSubDir()) {
            return trailingslashit($this->homeUrl) . trailingslashit($this->getInstallSubDir()) . $this->options->cloneDirectoryName;
        }

        return trailingslashit($this->siteUrl) . $this->options->cloneDirectoryName;
    }
}
