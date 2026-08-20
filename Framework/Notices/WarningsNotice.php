<?php

namespace WPStaging\Framework\Notices;

use WPStaging\Framework\SiteInfo;









class WarningsNotice extends BooleanNotice
{
    const OPTION_NAME = 'wpstg_warnings_notice';

    private $siteInfo;

    public function __construct(SiteInfo $siteInfo)
    {
        $this->siteInfo = $siteInfo;
    }

    public function getOptionName(): string
    {
        return self::OPTION_NAME;
    }






    public function isEnabled(): bool
    {
 
        if (!$this->siteInfo->isStagingSite()) {
            return false;
        }

        return parent::isEnabled();
    }
}
