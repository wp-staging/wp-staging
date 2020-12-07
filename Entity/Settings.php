<?php

//TODO PHP7.x; declare(strict_types=1);
//TODO PHP7.x; type-hints and return types
// TODO PHP7.1; constant visibility

namespace WPStaging\Entity;

use WPStaging\Framework\Entity\AbstractEntity;
use WPStaging\Framework\Traits\ArrayableTrait;
use WPStaging\Framework\Traits\HydrateTrait;

class Settings extends AbstractEntity
{
    use ArrayableTrait {
        toArray as traitToArray;
    }

    use HydrateTrait {
        hydrate as traitHydrate;
    }

    const CPU_LOAD_HIGH = 'fast'; // 0 ms;
    const CPU_LOAD_MEDIUM = 'medium'; // 250 ms
    const CPU_LOAD_LOW = 'low'; // 500 ms

    const DEFAULT_DELAY_BETWEEN_REQUESTS_IN_MS = 0;

    const DEFAULT_MAX_EXECUTION_TIME_IN_SECONDS = 30;
    const EXECUTION_TIME_GAP_IN_SECONDS = 5;

    /** @var DatabaseSettings */
    private $databaseSettings;

    /** @var FilesystemSettings */
    private $filesystemSettings;

    // This is in milliseconds
    /** @var int */
    private $delayBetweenRequests;

    /** @var bool */
    private $keepPermaLinks;

    /** @var bool */
    private $debug;

    /** @var bool */
    private $optimizer;

    /** @var bool */
    private $removeDataOnUninstall;

    /** @var array */
    private $accessPermissions;

    public function hydrate(array $data = [])
    {
        $compatible = [
            'databaseSettings' => [
                'copyQueryLimit' => !empty($data['queryLimit']) ? $data['queryLimit'] : null,
                'searchReplaceLimit' => !empty($data['querySRLimit']) ? $data['querySRLimit'] : null,
            ],
            'filesystemSettings' => [
                'fileCopyLimit' => !empty($data['fileLimit']) ? $data['fileLimit'] : null,
                'maximumFileSize' => !empty($data['maxFileSize']) ? $data['maxFileSize'] : null,
                'fileCopyBatchSize' => !empty($data['batchSize']) ? $data['batchSize'] : null,
                'checkDirectorySize' => !empty($data['checkDirectorySize']) ? $data['checkDirectorySize'] : null,
            ],
            'delayBetweenRequests' => !empty($data['delayRequests']) ? $data['delayRequests'] : null,
            'keepPermaLinks' => !empty($data['keepPermalinks']) ? $data['keepPermalinks'] : null,
            'debug' => !empty($data['debugMode']) ? $data['debugMode'] : null,
            'optimizer' => !empty($data['optimizer']) ? $data['optimizer'] : null,
            'removeDataOnUninstall' => !empty($data['unInstallOnDelete']) ? $data['unInstallOnDelete'] : null,
            'accessPermissions' => !empty($data['userRoles']) ? $data['userRoles'] : [],
        ];

        return $this->traitHydrate($compatible);
    }

    public function toArray()
    {
        $data = $this->traitToArray();
        if (isset($data['databaseSettings']) && $data['databaseSettings']) {
            $data = array_merge($data, $data['databaseSettings']);
        }
        if (isset($data['filesystemSettings']) && $data['filesystemSettings']) {
            $data = array_merge($data, $data['filesystemSettings']);
        }
        unset($data['databaseSettings'], $data['filesystemSettings']);

        $compatibleKeys = [
            'copyQueryLimit' => 'queryLimit',
            'searchReplaceLimit' => 'querySRLimit',

            'fileCopyLimit' => 'fileLimit',
            'maximumFileSize' => 'maxFileSize',
            'fileCopyBatchSize' => 'batchSize',
            'checkDirectorySize' => 'checkDirectorySize',

            'delayBetweenRequests' => 'delayRequests',
            'keepPermaLinks' => 'keepPermalinks',
            'debug' => 'debugMode',
            'optimizer' => 'optimizer',
            'removeDataOnUninstall' => 'unInstallOnDelete',
            'accessPermissions' => 'userRoles',
        ];

        foreach ($data as $key => $value) {
            $compatibleKey = isset($compatibleKeys[$key])? $compatibleKeys[$key] : $key;
            unset($data[$key]);
            $data[$compatibleKey] = is_bool($value)?  (string) (int) $value : $value;
        }

        return $data;
    }

    /**
     * @return float|int
     */
    public function findExecutionTimeLimit()
    {
        $executionTime = (int) ini_get('max_execution_time');
        // TODO don't overwrite when CLI / SAPI and / or add setting to not overwrite for devs
        if (!$executionTime || $executionTime > self::DEFAULT_MAX_EXECUTION_TIME_IN_SECONDS) {
            $executionTime = self::DEFAULT_MAX_EXECUTION_TIME_IN_SECONDS;
        }
        return $executionTime - self::EXECUTION_TIME_GAP_IN_SECONDS;
    }

    /**
     * @return DatabaseSettings
     */
    public function getDatabaseSettings()
    {
        if (!$this->databaseSettings) {
            $this->databaseSettings = new DatabaseSettings;
        }
        return $this->databaseSettings;
    }

    /**
     * @param DatabaseSettings $databaseSettings
     */
    public function setDatabaseSettings(DatabaseSettings $databaseSettings)
    {
        $this->databaseSettings = $databaseSettings;
    }

    /**
     * @return FilesystemSettings
     */
    public function getFilesystemSettings()
    {
        if (!$this->filesystemSettings) {
            $this->filesystemSettings = new FilesystemSettings;
        }
        return $this->filesystemSettings;
    }

    /**
     * @param FilesystemSettings $filesystemSettings
     */
    public function setFilesystemSettings(FilesystemSettings $filesystemSettings)
    {
        $this->filesystemSettings = $filesystemSettings;
    }

    /**
     * @return int
     */
    public function getDelayBetweenRequests()
    {
        return $this->delayBetweenRequests?: self::DEFAULT_DELAY_BETWEEN_REQUESTS_IN_MS;
    }

    /**
     * @param int $delayBetweenRequests
     */
    public function setDelayBetweenRequests($delayBetweenRequests)
    {
        $this->delayBetweenRequests = $delayBetweenRequests;
    }

    /**
     * @return bool
     */
    public function isKeepPermaLinks()
    {
        return $this->keepPermaLinks;
    }

    /**
     * @param bool $keepPermaLinks
     */
    public function setKeepPermaLinks($keepPermaLinks)
    {
        if (!is_bool($keepPermaLinks)) {
            $keepPermaLinks = $keepPermaLinks === 'true' || $keepPermaLinks === '1' || $keepPermaLinks === 1;
        }
        $this->keepPermaLinks = $keepPermaLinks;
    }

    /**
     * @return bool
     */
    public function isDebug()
    {
        return $this->debug;
    }

    /**
     * @param bool $debug
     */
    public function setDebug($debug)
    {
        if (!is_bool($debug)) {
            $debug = $debug === 'true' || $debug === '1' || $debug === 1;
        }
        $this->debug = $debug;
    }

    /**
     * @return bool
     */
    public function isOptimizer()
    {
        return $this->optimizer;
    }

    /**
     * @param bool $optimizer
     */
    public function setOptimizer($optimizer)
    {
        if (!is_bool($optimizer)) {
            $optimizer = $optimizer === 'true' || $optimizer === '1' || $optimizer === 1;
        }
        $this->optimizer = $optimizer;
    }

    /**
     * @return bool
     */
    public function isRemoveDataOnUninstall()
    {
        return $this->removeDataOnUninstall;
    }

    /**
     * @param bool $removeDataOnUninstall
     */
    public function setRemoveDataOnUninstall($removeDataOnUninstall)
    {
        if (!is_bool($removeDataOnUninstall)) {
            $removeDataOnUninstall = $removeDataOnUninstall === 'true' || $removeDataOnUninstall === '1' || $removeDataOnUninstall === 1;
        }
        $this->removeDataOnUninstall = $removeDataOnUninstall;
    }

    /**
     * @return array
     */
    public function getAccessPermissions()
    {
        return $this->accessPermissions?: [];
    }

    /**
     * @param array $accessPermissions
     */
    public function setAccessPermissions(array $accessPermissions = null)
    {
        $this->accessPermissions = $accessPermissions;
    }

    // TODO PHP5.6; const array
    /**
     * @return array
     */
    public function getAvailableCpuLoads()
    {
        return [
            self::CPU_LOAD_HIGH,
            self::CPU_LOAD_MEDIUM,
            self::CPU_LOAD_LOW,
        ];
    }
}