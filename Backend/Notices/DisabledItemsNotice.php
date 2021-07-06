<?php

namespace WPStaging\Backend\Notices;

use WPStaging\Framework\SiteInfo;

/**
 * Class DisabledItemsNotice
 *
 * This class is used to show notice about what WP Staging has disabled on the staging site
 *
 * @see \WPStaging\Backend\Notices\Notices;
 */
class DisabledItemsNotice extends BooleanNotice
{
    /**
     * The option name to store the visibility of disabled cache notice
     */
    const OPTION_NAME = 'wpstg_disabled_notice';

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
        // TODO: inject using DI
        // Early bail if not staging site
        if (!(new SiteInfo())->isStaging()) {
            return false;
        }

        return parent::isEnabled();
    }
}
