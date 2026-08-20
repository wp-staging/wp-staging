<?php

namespace WPStaging\Framework\CloningProcess;

use WPStaging\Backend\Modules\Jobs\Job;

class CloningDto
{



    protected $job;




    protected $stagingDb;




    protected $productionDb;




    protected $isExternal;




    protected $isMultisite;




    protected $externalDatabaseHost;




    protected $externalDatabaseUser;




    protected $externalDatabasePassword;




    protected $externalDatabaseName;




    protected $externalDatabaseSsl;














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




    public function getStagingDb()
    {
        return $this->stagingDb;
    }




    public function getProductionDb()
    {
        return $this->productionDb;
    }




    public function getJob()
    {
        return $this->job;
    }





    public function setJob($job)
    {
        $this->job = $job;
    }




    public function isExternal()
    {
        return $this->isExternal;
    }




    public function isMultisite()
    {
        return $this->isMultisite;
    }




    public function getExternalDatabaseHost()
    {
        return $this->externalDatabaseHost;
    }




    public function getExternalDatabaseUser()
    {
        return $this->externalDatabaseUser;
    }




    public function getExternalDatabasePassword()
    {
        return $this->externalDatabasePassword;
    }




    public function getExternalDatabaseName()
    {
        return $this->externalDatabaseName;
    }




    public function getExternalDatabaseSsl()
    {
        return $this->externalDatabaseSsl;
    }
}
