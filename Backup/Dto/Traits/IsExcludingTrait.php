<?php

namespace WPStaging\Backup\Dto\Traits;

use WPStaging\Core\WPStaging;

trait IsExcludingTrait
{
    /** @var bool */
    private $isExcludingSpamComments = false;

    /** @var bool */
    private $isExcludingPostRevision = false;

    /** @var bool */
    private $isExcludingDeactivatedPlugins = false;

    /** @var bool */
    private $isExcludingUnusedThemes = false;

    /** @var bool */
    private $isExcludingLogs = false;

    /** @var bool */
    private $isExcludingCaches = false;

    /** @var bool */
    private $isSmartExclusion = false;

    /**
     * @param  bool $checkSmartExclusion
     *
     * @return bool
     */
    public function getIsExcludingSpamComments($checkSmartExclusion = true): bool
    {
        if ($checkSmartExclusion) {
            return WPStaging::isPro() && $this->isSmartExclusion &&  $this->isExcludingSpamComments;
        }

        return $this->isExcludingSpamComments;
    }

    /**
     * @param  bool $isExcludingSpamComments
     * @return void
     */
    public function setIsExcludingSpamComments($isExcludingSpamComments)
    {
        $this->isExcludingSpamComments = $isExcludingSpamComments;
    }

    /**
     * @param  bool $checkSmartExclusion
     *
     * @return bool
     */
    public function getIsExcludingPostRevision($checkSmartExclusion = true): bool
    {
        if ($checkSmartExclusion) {
            return WPStaging::isPro() && $this->isSmartExclusion &&  $this->isExcludingPostRevision;
        }

        return $this->isExcludingPostRevision;
    }

    /**
     * @param  bool $isExcludingPostRevision
     * @return void
     */
    public function setIsExcludingPostRevision($isExcludingPostRevision)
    {
        $this->isExcludingPostRevision = $isExcludingPostRevision;
    }

    /**
     * @param  bool $checkSmartExclusion
     *
     * @return bool
     */
    public function getIsExcludingDeactivatedPlugins($checkSmartExclusion = true): bool
    {
        if ($checkSmartExclusion) {
            return WPStaging::isPro() && $this->isSmartExclusion &&  $this->isExcludingDeactivatedPlugins;
        }

        return $this->isExcludingDeactivatedPlugins;
    }

    /**
     * @param  bool $isExcludingDeactivatedPlugins
     * @return void
     */
    public function setIsExcludingDeactivatedPlugins($isExcludingDeactivatedPlugins)
    {
        $this->isExcludingDeactivatedPlugins = $isExcludingDeactivatedPlugins;
    }

    /**
     * @param  bool $checkSmartExclusion
     *
     * @return bool
     */
    public function getIsExcludingUnusedThemes($checkSmartExclusion = true): bool
    {
        if ($checkSmartExclusion) {
            return WPStaging::isPro() && $this->isSmartExclusion &&  $this->isExcludingUnusedThemes;
        }

        return $this->isExcludingUnusedThemes;
    }

    /**
     * @param  bool $isExcludingUnusedThemes
     * @return void
     */
    public function setIsExcludingUnusedThemes($isExcludingUnusedThemes)
    {
        $this->isExcludingUnusedThemes = $isExcludingUnusedThemes;
    }

    /**
     * @param  bool $checkSmartExclusion
     *
     * @return bool
     */
    public function getIsExcludingLogs($checkSmartExclusion = true): bool
    {
        if ($checkSmartExclusion) {
            return WPStaging::isPro() && $this->isSmartExclusion &&  $this->isExcludingLogs;
        }

        return $this->isExcludingLogs;
    }

    /**
     * @param  bool $isExcludingLogs
     * @return void
     */
    public function setIsExcludingLogs($isExcludingLogs)
    {
        $this->isExcludingLogs = $isExcludingLogs;
    }

    /**
     * @param  bool $checkSmartExclusion
     *
     * @return bool
     */
    public function getIsExcludingCaches($checkSmartExclusion = true): bool
    {
        if ($checkSmartExclusion) {
            return WPStaging::isPro() && $this->isSmartExclusion &&  $this->isExcludingCaches;
        }

        return $this->isExcludingCaches;
    }

    /**
     * @param  bool $isExcludingCaches
     * @return void
     */
    public function setIsExcludingCaches($isExcludingCaches)
    {
        $this->isExcludingCaches = $isExcludingCaches;
    }

    /**
     * @return bool
     */
    public function getIsSmartExclusion(): bool
    {
        return $this->isSmartExclusion;
    }

    /**
     * @param  bool $isSmartExclusion
     * @return void
     */
    public function setIsSmartExclusion($isSmartExclusion)
    {
        $this->isSmartExclusion = $isSmartExclusion;
    }
}
