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
    protected $fileLimit;

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
     * @var type array
     */
    protected $userRoles = [];

    /**
     * Users with access to staging site regardless of role (comma-separated list)
     * @var type string
     */
    protected $usersWithStagingAccess = "";

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
        return ( int )$this->queryLimit;
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
    public function getFileLimit()
    {
        return ( int )$this->fileLimit;
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
        return ( int )$this->batchSize;
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


    public function getDelayRequests()
    {
        return $this->delayRequests;
    }

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
        return ($this->unInstallOnDelete === '1');
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
        return ($this->optimizer === '1');
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
        return ($this->disableAdminLogin === '1');
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
        return ($this->checkDirectorySize === '1');
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
        return ($this->debugMode === '1');
    }

    /**
     * @param bool $debugMode
     */
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

}
