<?php

namespace WPStaging\Framework\CloningProcess\Data;

use WPStaging\Backend\Modules\Jobs\Job as CloningJob;
use WPStaging\Framework\CloningProcess\CloningDto;

class DataCloningDto extends CloningDto
{
    /**
     * @var CloningJob
     */
    protected $job;

    /**
     * @var integer
     */
    protected $stepNumber;

    /**
     * @var string
     */
    protected $prefix;

    /**
     * Tables e.g wpstg3_options
     * @var array
     */
    protected $tables;

    /**
     * @var string
     */
    protected $destinationDir;

    /**
     * @var string
     */
    protected $stagingSiteUrl;

    /**
     * @var string
     */
    protected $stagingSiteDomain;

    /**
     * @var string
     */
    protected $stagingSitePath;

    /**
     * @var string
     */
    protected $uploadFolder;

    /**
     * @var object
     */
    protected $settings;

    /**
     * @var string
     */
    protected $homeUrl;

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var string
     */
    protected $mainJob;

    /**
     * DataCloningDto constructor.
     * @param CloningJob $job
     * @param \wpdb $stagingDb
     * @param \wpdb $productionDb
     * @param bool $isExternal
     * @param bool $isMultisite
     * @param string $externalDatabaseHost
     * @param string $externalDatabaseUser
     * @param string $externalDatabasePassword
     * @param string $externalDatabaseName
     * @param int $stepNumber
     * @param string $prefix
     * @param array $tables
     * @param string $destinationDir
     * @param string $stagingSiteUrl
     * @param string $uploadFolder
     * @param object $settings
     * @param string $homeUrl
     * @param string $baseUrl
     * @param string $mainJob
     * @param bool $externalDatabaseSsl
     */
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

    /**
     * @return object
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @return int
     */
    public function getStepNumber()
    {
        return $this->stepNumber;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @return array
     */
    public function getTables()
    {
        return $this->tables;
    }

    /**
     * @return string
     */
    public function getDestinationDir()
    {
        return $this->destinationDir;
    }

    /**
     * @return string
     */
    public function getStagingSiteUrl()
    {
        return $this->stagingSiteUrl;
    }

    /**
     * @return string
     */
    public function getStagingSiteDomain()
    {
        if (!isset($this->stagingSiteDomain) || $this->stagingSiteDomain === '') {
            $this->stagingSiteDomain = parse_url(str_ireplace('//www.', '//', $this->getStagingSiteUrl()), PHP_URL_HOST);
        }

        return $this->stagingSiteDomain;
    }

    /**
     * @return string
     */
    public function getStagingSitePath()
    {
        if (!isset($this->stagingSitePath) || $this->stagingSitePath === '') {
            $parsedUrl = parse_url(str_ireplace('//www.', '//', $this->getStagingSiteUrl()));

            if (isset($parsedUrl['path'])) {
                $this->stagingSitePath = $parsedUrl['path'];
            } else {
                $this->stagingSitePath = '/';
            }
        }

        return $this->stagingSitePath;
    }

    /**
     * @return string
     */
    public function getUploadFolder()
    {
        return $this->uploadFolder;
    }

    /**
     * @return string
     */
    public function getHomeUrl()
    {
        return $this->homeUrl;
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @return string
     */
    public function getMainJob()
    {
        return $this->mainJob;
    }
}
