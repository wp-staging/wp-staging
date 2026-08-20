<?php

namespace WPStaging\Framework\CloningProcess\Data;

use WPStaging\Backend\Modules\Jobs\Job as CloningJob;
use WPStaging\Framework\CloningProcess\CloningDto;

class DataCloningDto extends CloningDto
{



    protected $job;




    protected $stepNumber;




    protected $prefix;





    protected $tables;




    protected $destinationDir;




    protected $stagingSiteUrl;




    protected $stagingSiteDomain;




    protected $stagingSitePath;




    protected $uploadFolder;




    protected $settings;




    protected $homeUrl;




    protected $baseUrl;




    protected $mainJob;
























    public function __construct(
        CloningJob $job,
        \wpdb $stagingDb,
        \wpdb $productionDb,
        $isExternal,
        $isMultisite,
        $externalDatabaseHost,
        $externalDatabaseUser,
        $externalDatabasePassword,
        $externalDatabaseName,
        $stepNumber,
        $prefix,
        array $tables,
        $destinationDir,
        $stagingSiteUrl,
        $uploadFolder,
        $settings,
        $homeUrl,
        $baseUrl,
        $mainJob,
        $externalDatabaseSsl = false
    ) {
        parent::__construct($job, $stagingDb, $productionDb, $isExternal, $isMultisite, $externalDatabaseHost, $externalDatabaseUser, $externalDatabasePassword, $externalDatabaseName, $externalDatabaseSsl);
        $this->stepNumber        = $stepNumber;
        $this->prefix            = $prefix;
        $this->tables            = $tables;
        $this->destinationDir    = $destinationDir;
        $this->stagingSiteUrl    = $stagingSiteUrl;
        $this->uploadFolder      = $uploadFolder;
        $this->settings          = $settings;
        $this->homeUrl           = $homeUrl;
        $this->baseUrl           = $baseUrl;
        $this->mainJob           = $mainJob;
        $this->stagingSiteDomain = '';
        $this->stagingSitePath   = '';
    }




    public function getSettings()
    {
        return $this->settings;
    }




    public function getStepNumber()
    {
        return $this->stepNumber;
    }




    public function getPrefix()
    {
        return $this->prefix;
    }




    public function getTables()
    {
        return $this->tables;
    }




    public function getDestinationDir()
    {
        return $this->destinationDir;
    }




    public function getStagingSiteUrl()
    {
        return $this->stagingSiteUrl;
    }




    public function getStagingSiteDomain()
    {
        if (empty($this->stagingSiteDomain)) {
            $this->stagingSiteDomain = parse_url($this->getStagingSiteUrl(), PHP_URL_HOST);
        }

        if (defined('DOMAIN_CURRENT_SITE') && strpos(DOMAIN_CURRENT_SITE, 'www.') !== 0) {
            $this->stagingSiteDomain = str_ireplace('www.', '', $this->stagingSiteDomain);
        }

        return $this->stagingSiteDomain;
    }




    public function getStagingSitePath()
    {
        if (empty($this->stagingSitePath)) {
            $parsedUrl = parse_url($this->getStagingSiteUrl());

            if (isset($parsedUrl['path'])) {
                $this->stagingSitePath = $parsedUrl['path'];
            } else {
                $this->stagingSitePath = '/';
            }
        }

        return $this->stagingSitePath;
    }




    public function getUploadFolder()
    {
        return $this->uploadFolder;
    }




    public function getHomeUrl()
    {
        return $this->homeUrl;
    }




    public function getBaseUrl()
    {
        return $this->baseUrl;
    }




    public function getMainJob()
    {
        return $this->mainJob;
    }
}
