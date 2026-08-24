<?php

namespace WPStaging\Backup\Dto\Traits;

use WPStaging\Core\WPStaging;

trait IsExcludingTrait
{
 
    private $isExcludingSpamComments = false;

 
    private $isExcludingPostRevision = false;

 
    private $isExcludingDeactivatedPlugins = false;

 
    private $isExcludingUnusedThemes = false;

 
    private $isExcludingLogs = false;

 
    private $isExcludingCaches = false;

 
    private $isSmartExclusion = false;






    public function getIsExcludingSpamComments($checkSmartExclusion = true): bool
    {
        if ($checkSmartExclusion) {
            return WPStaging::isPro() && $this->isSmartExclusion &&  $this->isExcludingSpamComments;
        }

        return $this->isExcludingSpamComments;
    }





    public function setIsExcludingSpamComments($isExcludingSpamComments)
    {
        $this->isExcludingSpamComments = $isExcludingSpamComments;
    }






    public function getIsExcludingPostRevision($checkSmartExclusion = true): bool
    {
        if ($checkSmartExclusion) {
            return WPStaging::isPro() && $this->isSmartExclusion &&  $this->isExcludingPostRevision;
        }

        return $this->isExcludingPostRevision;
    }





    public function setIsExcludingPostRevision($isExcludingPostRevision)
    {
        $this->isExcludingPostRevision = $isExcludingPostRevision;
    }






    public function getIsExcludingDeactivatedPlugins($checkSmartExclusion = true): bool
    {
        if ($checkSmartExclusion) {
            return WPStaging::isPro() && $this->isSmartExclusion &&  $this->isExcludingDeactivatedPlugins;
        }

        return $this->isExcludingDeactivatedPlugins;
    }





    public function setIsExcludingDeactivatedPlugins($isExcludingDeactivatedPlugins)
    {
        $this->isExcludingDeactivatedPlugins = $isExcludingDeactivatedPlugins;
    }






    public function getIsExcludingUnusedThemes($checkSmartExclusion = true): bool
    {
        if ($checkSmartExclusion) {
            return WPStaging::isPro() && $this->isSmartExclusion &&  $this->isExcludingUnusedThemes;
        }

        return $this->isExcludingUnusedThemes;
    }





    public function setIsExcludingUnusedThemes($isExcludingUnusedThemes)
    {
        $this->isExcludingUnusedThemes = $isExcludingUnusedThemes;
    }






    public function getIsExcludingLogs($checkSmartExclusion = true): bool
    {
        if ($checkSmartExclusion) {
            return WPStaging::isPro() && $this->isSmartExclusion &&  $this->isExcludingLogs;
        }

        return $this->isExcludingLogs;
    }





    public function setIsExcludingLogs($isExcludingLogs)
    {
        $this->isExcludingLogs = $isExcludingLogs;
    }






    public function getIsExcludingCaches($checkSmartExclusion = true): bool
    {
        if ($checkSmartExclusion) {
            return WPStaging::isPro() && $this->isSmartExclusion &&  $this->isExcludingCaches;
        }

        return $this->isExcludingCaches;
    }





    public function setIsExcludingCaches($isExcludingCaches)
    {
        $this->isExcludingCaches = $isExcludingCaches;
    }






    public function getIsSmartExclusion($checkLicense = true): bool
    {
        if ($checkLicense) {
            return WPStaging::isPro() && $this->isSmartExclusion;
        }

        return $this->isSmartExclusion;
    }





    public function setIsSmartExclusion($isSmartExclusion)
    {
        $this->isSmartExclusion = $isSmartExclusion;
    }
}
