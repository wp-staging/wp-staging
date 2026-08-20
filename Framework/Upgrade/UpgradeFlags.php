<?php

namespace WPStaging\Framework\Upgrade;













class UpgradeFlags
{



    const OPTION_KEY = 'wpstg_completed_upgrades';





    public function has($flag)
    {
        $flags = get_option(self::OPTION_KEY, []);
        return is_array($flags) && isset($flags[$flag]);
    }





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
