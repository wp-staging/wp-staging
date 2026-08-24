<?php

namespace WPStaging\Core\DTO;





class Settings
{




    const OPTION_BACKUP_BEFORE_UPDATE_MODE = 'wpstg_backup_before_update_mode';






    const OPTION_BACKUP_BEFORE_UPDATE_INTRO_SEEN = 'wpstg_backup_before_update_intro_seen';




    protected $_raw;




    protected $queryLimit;




    protected $querySRLimit;




    protected $fileLimit;




    protected $maxFileSize;




    protected $batchSize;




    protected $cpuLoad;




    protected $delayRequests;




    protected $unInstallOnDelete;




    protected $optimizer;




    protected $disableAdminLogin;





    protected $keepPermalinks;




    protected $debugMode;






    protected $userRoles = [];





    protected $usersWithStagingAccess = "";





    protected $adminBarColor = "";




    protected $enableCompression;




    protected $enableBackupBeforeUpdate;




    public function __construct()
    {
        $stored     = get_option("wpstg_settings", []);
        $this->_raw = $stored;

        if (!empty($stored)) {
            $this->hydrate($stored);
        }
    }





    public function hydrate($settings = [])
    {
        $this->_raw = $settings;
        if (!is_array($settings) && !is_object($settings)) {
            $this->_raw = [];
            return $this;
        }

        foreach ($settings as $key => $value) {
            if (is_object($this) && is_string($key) && property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }

        return $this;
    }





    public function getRaw()
    {
        return $this->_raw;
    }




    public function getQueryLimit()
    {
        return $this->queryLimit;
    }




    public function setQueryLimit($queryLimit)
    {
        $this->queryLimit = $queryLimit;
    }




    public function getQuerySRLimit()
    {
        return $this->querySRLimit;
    }




    public function setQuerySRLimit($querySRLimit)
    {
        $this->querySRLimit = $querySRLimit;
    }




    public function getFileLimit()
    {
        return $this->fileLimit;
    }




    public function getMaxFileSize()
    {
        return isset($this->maxFileSize) ? $this->maxFileSize : '8';
    }




    public function setFileLimit($fileLimit)
    {
        $this->fileLimit = $fileLimit;
    }




    public function getBatchSize()
    {
        return $this->batchSize;
    }




    public function setBatchSize($batchSize)
    {
        $this->batchSize = $batchSize;
    }




    public function getCpuLoad()
    {
        return $this->cpuLoad;
    }












    public function setCpuLoad($cpuLoad)
    {
        $this->cpuLoad = $cpuLoad;
    }




    public function isUnInstallOnDelete()
    {
        return ($this->unInstallOnDelete == '1');
    }




    public function setUnInstallOnDelete($unInstallOnDelete)
    {
        $this->unInstallOnDelete = $unInstallOnDelete;
    }




    public function isOptimizer()
    {
        return ($this->optimizer == '1');
    }




    public function setOptimizer($optimizer)
    {
        $this->optimizer = $optimizer;
    }




    public function isDisableAdminLogin()
    {
        return ($this->disableAdminLogin == '1');
    }




    public function setDisableAdminLogin($disableAdminLogin)
    {
        $this->disableAdminLogin = $disableAdminLogin;
    }




    public function isDebugMode()
    {
        return ($this->debugMode == '1');
    }




    public function setDebugMode($debugMode)
    {
        $this->debugMode = $debugMode;
    }




    public function setUserRoles($userRoles)
    {
        $this->userRoles = $userRoles;
    }




    public function setUsersWithStagingAccess($usersWithStagingAccess)
    {
        $this->usersWithStagingAccess = $usersWithStagingAccess;
    }




    public function setAdminBarColor($adminBarColor)
    {
        $this->adminBarColor = $adminBarColor;
    }




    public function getAdminBarColor()
    {
        return $this->adminBarColor;
    }

    public function getEnableCompression(): bool
    {
        return $this->enableCompression;
    }

    public function setEnableCompression(bool $enableCompression)
    {
        $this->enableCompression = $enableCompression;
    }




    public function isBackupBeforeUpdateEnabled(): bool
    {
        if ($this->enableBackupBeforeUpdate === null) {
            return true;
        }

        return (bool)$this->enableBackupBeforeUpdate;
    }





    public function setDefault()
    {
        if (!isset($this->_raw)) {
            $this->_raw = [];
        }

        if (
            empty($this->queryLimit) ||
            empty($this->querySRLimit) ||
            empty($this->batchSize) ||
            empty($this->cpuLoad) ||
            empty($this->maxFileSize) ||
            empty($this->fileLimit)
        ) {
            $settings = (object)json_decode(json_encode($this->_raw));
            $settings->queryLimit = "10000";
            $settings->querySRLimit = "20000";

            if (defined('WPSTG_IS_DEV') && WPSTG_IS_DEV) {
                $settings->fileLimit = "500";
                $settings->cpuLoad = 'high';
            } else {
                $settings->fileLimit = "50";
                $settings->cpuLoad = 'low';
            }

            $settings->batchSize = "2";
            $settings->maxFileSize = "8";
            $settings->optimizer = "1";
 
            update_option('wpstg_settings', json_decode(json_encode($settings), true));

            return $this->hydrate($settings)->_raw;
        }

        return $this->_raw;
    }
}
