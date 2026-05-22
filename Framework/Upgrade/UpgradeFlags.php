<?php

namespace WPStaging\Framework\Upgrade;

/**
 * Persistent flags that mark completion of one-shot upgrade routines.
 *
 * Flags are stored together in a single autoloaded wp_options row as a
 * keyed map — ['flag_id' => true] — so has() is a single isset() with
 * no scan, regardless of how many flags exist. Shared between Free and
 * Pro (they run on the same WordPress install).
 *
 * Replaces version_compare gating for upgrade routines, which can
 * silently skip migrations when the stored version is missing, corrupt,
 * or a legacy dev-version string.
 */
class UpgradeFlags
{
    /**
     * @var string
     */
    const OPTION_KEY = 'wpstg_completed_upgrades';

    /**
     * @param string $flag
     * @return bool
     */
    public function has($flag)
    {
        $flags = get_option(self::OPTION_KEY, []);
        return is_array($flags) && isset($flags[$flag]);
    }

    /**
     * @param string $flag
     * @return void
     */
    public function mark($flag)
    {
        $flags = get_option(self::OPTION_KEY, []);
        if (!is_array($flags)) {
            $flags = [];
        }

        if (isset($flags[$flag])) {
            return;
        }

        $flags[$flag] = true;
        update_option(self::OPTION_KEY, $flags);
    }
}
