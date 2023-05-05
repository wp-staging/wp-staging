<?php

namespace WPStaging\Framework\CloningProcess;

use WPStaging\Backend\Modules\Jobs\Job;

class CloningDto
{
    /**
     * @var Job
     */
    protected $job;

    /**
     * @var \wpdb
     */
    protected $stagingDb;

    /**
     * @var \wpdb
     */
    protected $productionDb;

    /**
     * @var bool
     */
    protected $isExternal;

    /**
     * @var bool
     */
    protected $isMultisite;

    /**
     * @var string
     */
    protected $externalDatabaseHost;

    /**
     * @var string
     */
    protected $externalDatabaseUser;

    /**
     * @var string
     */
    protected $externalDatabasePassword;

    /**
     * @var string
     */
    protected $externalDatabaseName;

    /**
     * @var bool
     */
    protected $externalDatabaseSsl;

    /**
     * CloningDto constructor.
     * @param Job $job
     * @param \wpdb $stagingDb
     * @param \wpdb $productionDb
     * @param bool $isExternal
     * @param bool $isMultisite
     * @param string $externalDatabaseHost
     * @param string $externalDatabaseUser
     * @param string $externalDatabasePassword
     * @param string $externalDatabaseName
     * @param bool $externalDatabaseSsl
     */
    public function __construct(Job $job, \wpdb $stagingDb, \wpdb $productionDb, $isExternal, $isMultisite, $externalDatabaseHost, $externalDatabaseUser, $externalDatabasePassword, $externalDatabaseName, $externalDatabaseSsl = false)
    {
        $this->job                      = $job;
        $this->stagingDb                = $stagingDb;
        $this->productionDb             = $productionDb;
        $this->isExternal               = $isExternal;
        $this->isMultisite              = $isMultisite;
        $this->externalDatabaseHost     = $externalDatabaseHost;
        $this->externalDatabaseUser     = $externalDatabaseUser;
        $this->externalDatabasePassword = $externalDatabasePassword;
        $this->externalDatabaseName     = $externalDatabaseName;
        $this->externalDatabaseSsl      = $externalDatabaseSsl;
    }

    /**
     * @return \wpdb
     */
    public function getStagingDb()
    {
        return $this->stagingDb;
    }

    /**
     * @return \wpdb
     */
    public function getProductionDb()
    {
        return $this->productionDb;
    }

    /**
     * @return Job
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * @return bool
     */
    public function isExternal()
    {
        return $this->isExternal;
    }

    /**
     * @return bool
     */
    public function isMultisite()
    {
        return $this->isMultisite;
    }

    /**
     * @return string
     */
    public function getExternalDatabaseHost()
    {
        return $this->externalDatabaseHost;
    }

    /**
     * @return string
     */
    public function getExternalDatabaseUser()
    {
        return $this->externalDatabaseUser;
    }

    /**
     * @return string
     */
    public function getExternalDatabasePassword()
    {
        return $this->externalDatabasePassword;
    }

    /**
     * @return string
     */
    public function getExternalDatabaseName()
    {
        return $this->externalDatabaseName;
    }

    /**
     * @return bool
     */
    public function getExternalDatabaseSsl()
    {
        return $this->externalDatabaseSsl;
    }
}
