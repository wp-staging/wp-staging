<?php

namespace WPStaging\Framework\CloningProcess\Data;

use WPStaging\Backend\Modules\Jobs\CloningProcess;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\CloningProcess\Data\DataCloningDto;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Utils\Urls;
use WPStaging\Framework\Utils\WpDefaultDirectories;





abstract class Job extends CloningProcess
{



    private $prefix;





    private $homeUrl;





    private $siteUrl;





    protected $baseUrl;





    protected $tables;

 
    protected $steps = [];




    public function initialize()
    {
        $this->initializeDbObjects();
        $this->initializeSteps();

        $this->prefix = $this->options->prefix;

        $this->homeUrl = (new Urls())->getHomeUrl();
        $this->siteUrl = (new Urls())->getSiteUrl();
        $this->baseUrl = (new Urls())->getBaseUrl();

 
        if ($this->options->currentStep === 0) {
            $this->options->currentStep = 0;
        }
    }





    public function start()
    {
 
        $this->run();

 
        $this->saveOptions();

        return (object)$this->response;
    }

    abstract protected function initializeSteps();





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






    protected function execute()
    {
 
        if ($this->isOverThreshold()) {
 
            $this->prepareResponse(false, false);
            $this->saveOptions();
            return false;
        }

 
        if ($this->isFinished()) {
            $this->prepareResponse(true, false);
            return false;
        }

        $step = $this->steps[$this->options->currentStep];

 
        $stepService = WPStaging::make($step);
        $stepService->setDataCloningDto($this->getCloningDto($this->options->currentStep));

        if (!$stepService->execute()) {
            $this->prepareResponse(false, false);
            return false;
        }

 
        $this->prepareResponse();

 
        return true;
    }





    protected function isFinished()
    {
        return
            !$this->isRunning() ||
            $this->options->currentStep > $this->options->totalSteps ||
            $this->options->currentStep >= count($this->steps);
    }





    protected function isSubDir()
    {
        return (new SiteInfo())->isInstalledInSubDir();
    }





    protected function getInstallSubDir()
    {
        $home    = get_option('home');
        $siteurl = get_option('siteurl');

        if (empty($home) || empty($siteurl) || !$this->isSubDir() || $siteurl === str_replace([$home], '', $siteurl)) {
            return '';
        }

        return trim(wp_parse_url($siteurl, PHP_URL_PATH), '/');
    }





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

 
            $multisitePath = defined('PATH_CURRENT_SITE') ? PATH_CURRENT_SITE : '/';
            return rtrim($this->baseUrl, '/\\') . $multisitePath . $this->options->cloneDirectoryName;
        }

        if ($this->getInstallSubDir()) {
            return trailingslashit($this->homeUrl) . trailingslashit($this->getInstallSubDir()) . $this->options->cloneDirectoryName;
        }

        return trailingslashit($this->siteUrl) . $this->options->cloneDirectoryName;
    }
}
