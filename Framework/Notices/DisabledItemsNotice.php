<?php

namespace WPStaging\Framework\Notices;

use WPStaging\Framework\SiteInfo;

/**
 * Class DisabledItemsNotice
 *
 * This class is used to show notice about what WP Staging has disabled on the staging site
 *
 * @see \WPStaging\Framework\Notices\Notices;
 */
class DisabledItemsNotice extends BooleanNotice
{
    /**
     * The option name to store the visibility of disabled cache notice
     */
    const OPTION_NAME = 'wpstg_disabled_notice';

    private $siteInfo;

    public function __construct(SiteInfo $siteInfo)
    {
        $this->siteInfo = $siteInfo;
    }

    public function getOptionName()
    {
        return self::OPTION_NAME;
    }

    /**
     * Show this notice only on staging site
     *
     * @return bool
     */
    public function isEnabled()
    {
        // Early bail if not staging site
        if (!$this->siteInfo->isStagingSite()) {
            return false;
        }

        return parent::isEnabled();
    }
}
