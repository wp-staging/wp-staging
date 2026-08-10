<?php

namespace WPStaging\Framework\Onboarding;

use WPStaging\Staging\Sites;

/**
 * Records whether an activation is the first WP STAGING has ever seen on this site.
 *
 * The marker is written only when the activation finds no WP STAGING footprint
 * at all, which is what keeps first-run experiences away from a user who merely
 * reactivates or upgrades: their activation ran long before the marker existed.
 */
class FirstInstall
{
    /** @var string UNIX timestamp of the first ever activation on this site. */
    const OPTION_FIRST_INSTALL = 'wpstg_first_install';

    /**
     * Options only a previous run can have left behind; any one means this is not
     * a first install. `wpstg_settings` is deliberately absent — booting the
     * plugin writes it before this check runs, so it is present on fresh installs.
     *
     * @var string[]
     */
    const FOOTPRINT_OPTIONS = [
        'wpstg_installDate',
        'wpstg_free_install_date',
        'wpstgpro_install_date',
        'wpstg_version',
        Sites::STAGING_SITES_OPTION,
    ];

    /**
     * Must run at the very top of the activation routine, before WP STAGING
     * writes any of its own options.
     *
     * @return bool Whether this activation was a first install.
     */
    public static function markIfFirstInstall(): bool
    {
        if (get_option(self::OPTION_FIRST_INSTALL) !== false) {
            return true;
        }

        foreach (self::FOOTPRINT_OPTIONS as $option) {
            if (get_option($option) !== false) {
                return false;
            }
        }

        add_option(self::OPTION_FIRST_INSTALL, (string)time(), '', false);

        return true;
    }

    public function isFirstInstall(): bool
    {
        return get_option(self::OPTION_FIRST_INSTALL) !== false;
    }
}
