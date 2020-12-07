<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Entity\Settings;
use WPStaging\Framework\Utils\Size;
use WPStaging\Repository\SettingsRepository;

trait ResourceTrait
{
    use TimerTrait;

    /** @var SettingsRepository */
    protected $settingsRepository;

    /** @var Settings */
    protected $settings;

    /** @var int|null */
    protected $timeLimit;

    /**
     * @return bool
     */
    public function isThreshold()
    {
        return $this->isMemoryLimit() || $this->isTimeLimit();
    }

    /**
     * @return bool
     */
    public function isMemoryLimit()
    {
        $limit = (new Size)->toBytes(ini_get('memory_limit'));

        if (!is_int($limit) || $limit < 64000000){
            $limit = 64000000;
        }
        $allowed = $limit - 1024;
        return $allowed <= $this->getMemoryUsage();
    }

    /**
     * @return bool
     */
    public function isTimeLimit()
    {
        $timeLimit = $this->getSettings()->findExecutionTimeLimit();

        if ($this->timeLimit !== null) {
            $timeLimit = $this->timeLimit;
        }
        return $timeLimit <= $this->getRunningTime();
    }
    // TODO Recursion for xDebug? Recursion is bad idea will cause more resource usage, need to avoid it.

    /**
     * @param bool $realUsage
     * @return int
     */
    protected function getMemoryUsage($realUsage = true)
    {
        return memory_get_usage($realUsage);
    }

    /**
     * @param bool $realUsage
     * @return int
     */
    protected function getMemoryPeakUsage($realUsage = true)
    {
        return memory_get_peak_usage($realUsage);
    }

    /**
     * @return SettingsRepository
     */
    public function getSettingsRepository()
    {
        if (!$this->settingsRepository) {
            $this->settingsRepository = new SettingsRepository;
        }
        return $this->settingsRepository;
    }

    /**
     * @param SettingsRepository $settingsRepository
     */
    public function setSettingsRepository(SettingsRepository $settingsRepository)
    {
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * @return Settings
     */
    public function getSettings()
    {
        if (!$this->settings) {
            $this->settings = $this->getSettingsRepository()->find();
        }
        return $this->settings;
    }

    /**
     * @return int|null
     */
    public function getTimeLimit()
    {
        return $this->timeLimit;
    }

    /**
     * @param int|null $timeLimit
     */
    public function setTimeLimit($timeLimit)
    {
        $this->timeLimit = $timeLimit;
    }
}
