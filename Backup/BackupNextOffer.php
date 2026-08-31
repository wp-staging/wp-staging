<?php

namespace WPStaging\Backup;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\SiteInfo;




class BackupNextOffer
{
 
    private $siteInfo;

 
    private $isEligible = null;

    public function __construct(SiteInfo $siteInfo)
    {
        $this->siteInfo = $siteInfo;
    }







    public static function resolve()
    {
        try {
            return WPStaging::make(self::class);
        } catch (\Throwable $e) {
            return null;
        }
    }

 
    public function isEligible(): bool
    {
        if ($this->isEligible === null) {
            $this->isEligible = current_user_can('manage_options') && !$this->siteInfo->isStagingSite();
        }

        return $this->isEligible;
    }
}
