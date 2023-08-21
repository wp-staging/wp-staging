<?php

namespace WPStaging\Framework\Notices;

use WPStaging\Framework\SiteInfo;

/**
 * Class WarningsNotice
 *
 * Single Dismissible Notice for showing all warnings on staging sites
 *
 * This notice is disabled for the moment as it was annoying to so many wp staging notices.
 * @see Notices
 */
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

    /**
     * This warning notice should be shown only on the staging site
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        // Early bail if not staging site
        if (!$this->siteInfo->isStagingSite()) {
            return false;
        }

        return parent::isEnabled();
    }
}
