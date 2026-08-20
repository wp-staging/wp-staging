<?php

namespace WPStaging\Framework\Onboarding;

use WPStaging\Staging\Sites;








class FirstInstall
{
 
    const OPTION_FIRST_INSTALL = 'wpstg_first_install';








    const FOOTPRINT_OPTIONS = [
        'wpstg_installDate',
        'wpstg_free_install_date',
        'wpstgpro_install_date',
        'wpstg_version',
        Sites::STAGING_SITES_OPTION,
    ];








    public static function markIfFirstInstall(): bool
    {
        if (get_option(self::OPTION_FIRST_INSTALL) !== false) {
            return true;
        }

        if (!self::hasNeverSeenWpStaging()) {
            return false;
        }

        add_option(self::OPTION_FIRST_INSTALL, (string)time(), '', false);

        return true;
    }











    public static function hasNeverSeenWpStaging(): bool
    {
        if (get_option(self::OPTION_FIRST_INSTALL) !== false) {
            return false;
        }

        foreach (self::FOOTPRINT_OPTIONS as $option) {
            if (get_option($option) !== false) {
                return false;
            }
        }

        return true;
    }

    public function isFirstInstall(): bool
    {
        return get_option(self::OPTION_FIRST_INSTALL) !== false;
    }
}
