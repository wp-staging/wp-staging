<?php

namespace WPStaging\DTO;

/**
 * Class Settings
 * @package WPStaging\DTO
 */
class Settings {

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
    protected $wpSubDirectory;

    /**
     * @var bool
     */
    protected $checkDirectorySize;

    /**
     * @var bool
     */
    protected $debugMode;


    /**
     * @var array
     */
    protected $blackListedPlugins = array();

   
    /**
     * Settings constructor.
     */
    public function __construct() {
      $this->_raw = get_option( "wpstg_settings", array() );

      if (!empty($this->_raw)){
         $this->hydrate( $this->_raw );
      }
   }

   /**
     * @param array $settings
     * @return $this
     */
   public function hydrate( $settings = array() ) {
        $this->_raw = $settings;

      foreach ( $settings as $key => $value ) {
         if( property_exists( $this, $key ) ) {
                $this->{$key} = $value;
            }
        }

        return $this;
    }

    /**
     * @return bool
     */
   public function save() {
      $data = array();

      foreach ( get_object_vars( $this ) as $key => $value ) {
         if( 0 == strpos( $key, '_' ) ) {
            continue;
         }

         $data[$key] = $value;
      }
      
      return update_option( "wpstg_settings", $data );
   }

    /**
     * @return array
     */
   public function getRaw() {
        return $this->_raw;
    }

    /**
     * @return int
     */
   public function getQueryLimit() {
      return ( int ) $this->queryLimit;
    }

    /**
     * @param int $queryLimit
     */
   public function setQueryLimit( $queryLimit ) {
        $this->queryLimit = $queryLimit;
    }

    /**
     * @return int
     */
   public function getFileLimit() {
      return ( int ) $this->fileLimit;
    }

    /**
    * @param int $fileCopyLimit
    */
   public function setFileLimit( $fileLimit ) {
      $this->fileLimit = $fileLimit;
   }

   /**
    * @return int
    */
   public function getBatchSize() {
      return ( int ) $this->batchSize;
   }

   /**
     * @param int $batchSize
     */
   public function setBatchSize( $batchSize ) {
        $this->batchSize = $batchSize;
    }

    /**
     * @return string
     */
   public function getCpuLoad() {
        return $this->cpuLoad;
    }

    /**
     * @param string $cpuLoad
     */
   public function setCpuLoad( $cpuLoad ) {
        $this->cpuLoad = $cpuLoad;
    }

    /**
     * @return bool
     */
   public function isUnInstallOnDelete() {
        return ('1' === $this->unInstallOnDelete);
    }

    /**
     * @param bool $unInstallOnDelete
     */
   public function setUnInstallOnDelete( $unInstallOnDelete ) {
        $this->unInstallOnDelete = $unInstallOnDelete;
    }

    /**
     * @return bool
     */
   public function isOptimizer() {
        return ('1' === $this->optimizer);
    }

    /**
     * @param bool $optimizer
     */
   public function setOptimizer( $optimizer ) {
        $this->optimizer = $optimizer;
    }

    /**
     * @return bool
     */
   public function isDisableAdminLogin() {
        return ('1' === $this->disableAdminLogin);
    }

    /**
     * @param bool $disableAdminLogin
     */
   public function setDisableAdminLogin( $disableAdminLogin ) {
        $this->disableAdminLogin = $disableAdminLogin;
    }

    /**
     * @return bool
     */
   public function isWpSubDirectory() {
        return ('1' === $this->wpSubDirectory);
    }

    /**
     * @param bool $wpSubDirectory
     */
   public function setWpSubDirectory( $wpSubDirectory ) {
        $this->wpSubDirectory = $wpSubDirectory;
    }

    /**
     * @return bool
     */
   public function isCheckDirectorySize() {
        return ('1' === $this->checkDirectorySize);
    }

    /**
     * @param bool $checkDirectorySize
     */
   public function setCheckDirectorySize( $checkDirectorySize ) {
        $this->checkDirectorySize = $checkDirectorySize;
    }

    /**
     * @return bool
     */
   public function isDebugMode() {
        return ('1' === $this->debugMode);
    }

    /**
     * @param bool $debugMode
     */
   public function setDebugMode( $debugMode ) {
        $this->debugMode = $debugMode;
    }

    /**
     * @return array
     */
   public function getBlackListedPlugins() {
        return $this->blackListedPlugins;
    }

    /**
     * @param array $blackListedPlugins
     */
   public function setBlackListedPlugins( $blackListedPlugins ) {
        $this->blackListedPlugins = $blackListedPlugins;
    }
}