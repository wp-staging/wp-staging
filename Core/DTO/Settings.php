<?php

namespace WPStaging\Core\DTO;

/**
 * Class Settings
 * @package WPStaging\Core\DTO
 */
class Settings
{

    /**
     * @var array
     */
    protected $_raw;

    /**
     * @var int
     */
    protected $queryLimit;

    /**
     * @var int
     */
    protected $querySRLimit;

    /**
     * @var int
     */
    protected $fileLimit;

    /**
     * @var int
     */
    protected $maxFileSize;

    /**
     * @var int
     */
    protected $batchSize;

    /**
     * @var string
     */
    protected $cpuLoad;

    /**
     * @var int
     */
    protected $delayRequests;

    /**
     * @var bool
     */
    protected $unInstallOnDelete;

    /**
     * @var bool
     */
    protected $optimizer;

    /**
     * @var bool
     */
    protected $disableAdminLogin;


    /**
     * @var bool
     */
    protected $keepPermalinks;

    /**
     * @var bool
     */
    protected $checkDirectorySize;

    /**
     * @var bool
     */
    protected $debugMode;


    /**
     * User roles to access the staging site
     * @var array
     */
    protected $userRoles = [];

    /**
     * Users with access to staging site regardless of role (comma-separated list)
     * @var string
     */
    protected $usersWithStagingAccess = "";

    /**
     * Color of the admin bar in hexadecimal format
     * @var string
     */
    protected $adminBarColor = "";

    /**
     * Settings constructor.
     */
    public function __construct()
    {
        $this->_raw = get_option("wpstg_settings", []);

        if (!empty($this->_raw)) {
            $this->hydrate($this->_raw);
        }
    }

    /**
     * @param array $settings
     * @return $this
     */
    public function hydrate($settings = [])
    {
        $this->_raw = $settings;
        if (!is_array($settings) && !is_object($settings)) {
            $this->_raw = [];
            return $this;
        }

        foreach ($settings as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }

        return $this;
    }


    /**
     * @return array
     */
    public function getRaw()
    {
        return $this->_raw;
    }

    /**
     * @return int
     */
    public function getQueryLimit()
    {
        return $this->queryLimit;
    }

    /**
     * @param int $queryLimit
     */
    public function setQueryLimit($queryLimit)
    {
        $this->queryLimit = $queryLimit;
    }

    /**
     * @return int
     */
    public function getQuerySRLimit()
    {
        return $this->querySRLimit;
    }

    /**
     * @param int $querySRLimit
     */
    public function setQuerySRLimit($querySRLimit)
    {
        $this->querySRLimit = $querySRLimit;
    }

    /**
     * @return int
     */
    public function getFileLimit()
    {
        return $this->fileLimit;
    }

    /**
     * @param int $fileLimit
     */
    public function setFileLimit($fileLimit)
    {
        $this->fileLimit = $fileLimit;
    }

    /**
     * @return int
     */
    public function getBatchSize()
    {
        return $this->batchSize;
    }

    /**
     * @param int $batchSize
     */
    public function setBatchSize($batchSize)
    {
        $this->batchSize = $batchSize;
    }

    /**
     * @return string
     */
    public function getCpuLoad()
    {
        return $this->cpuLoad;
    }

    /**
     * @return int
     */
/*    public function getDelayRequests()
    {
        return $this->delayRequests;
    }*/

    /**
     * @param string $cpuLoad
     */
    public function setCpuLoad($cpuLoad)
    {
        $this->cpuLoad = $cpuLoad;
    }

    /**
     * @return bool
     */
    public function isUnInstallOnDelete()
    {
        return ($this->unInstallOnDelete == '1');
    }

    /**
     * @param bool $unInstallOnDelete
     */
    public function setUnInstallOnDelete($unInstallOnDelete)
    {
        $this->unInstallOnDelete = $unInstallOnDelete;
    }

    /**
     * @return bool
     */
    public function isOptimizer()
    {
        return ($this->optimizer == '1');
    }

    /**
     * @param bool $optimizer
     */
    public function setOptimizer($optimizer)
    {
        $this->optimizer = $optimizer;
    }

    /**
     * @return bool
     */
    public function isDisableAdminLogin()
    {
        return ($this->disableAdminLogin == '1');
    }

    /**
     * @param bool $disableAdminLogin
     */
    public function setDisableAdminLogin($disableAdminLogin)
    {
        $this->disableAdminLogin = $disableAdminLogin;
    }


    /**
     * @return bool
     */
    public function isCheckDirectorySize()
    {
        return ($this->checkDirectorySize == '1');
    }

    /**
     * @param bool $checkDirectorySize
     */
    public function setCheckDirectorySize($checkDirectorySize)
    {
        $this->checkDirectorySize = $checkDirectorySize;
    }

    /**
     * @return bool
     */
    public function isDebugMode()
    {
        return ($this->debugMode == '1');
    }

    /**
     * @param bool $debugMode
     */
    public function setDebugMode($debugMode)
    {
        $this->debugMode = $debugMode;
    }

    /**
     * @param array $userRoles
     */
    public function setUserRoles($userRoles)
    {
        $this->userRoles = $userRoles;
    }

    /**
     * @param string $usersWithStagingAccess
     */
    public function setUsersWithStagingAccess($usersWithStagingAccess)
    {
        $this->usersWithStagingAccess = $usersWithStagingAccess;
    }

    /**
     * @param string $adminBarColor
     */
    public function setAdminBarColor($adminBarColor)
    {
        $this->adminBarColor = $adminBarColor;
    }

    /**
     * @return string
     */
    public function getAdminBarColor()
    {
        return $this->adminBarColor;
    }

    /**
     * Set default values for settings
     */
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

            if (defined('WPSTG_DEV') && WPSTG_DEV) {
                $settings->fileLimit = "500";
                $settings->cpuLoad = 'high';
            } else {
                $settings->fileLimit = "50";
                $settings->cpuLoad = 'low';
            }

            $settings->batchSize = "2";
            $settings->maxFileSize = "8";
            $settings->optimizer = "1";
            // Save settings in form on array
            update_option('wpstg_settings', json_decode(json_encode($settings), true));

            return $this->hydrate($settings)->_raw;
        }

        return $this->_raw;
    }
}
