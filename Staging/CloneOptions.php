<?php

namespace WPStaging\Staging;

use stdClass;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\SiteInfo;








class CloneOptions
{




    const WPSTG_CLONE_SETTINGS_KEY = 'wpstg_clone_settings';

 
    const FILTER_CLONING_TARGET_HOSTNAME = 'wpstg_cloning_target_hostname';

 
    const FILTER_CLONING_TARGET_DIR = 'wpstg_cloning_target_dir';









    public function get($option = null, $default = null)
    {
 
        if (!WPStaging::make(SiteInfo::class)->isStagingSite()) {
            return $default;
        }

        $settings = get_option(self::WPSTG_CLONE_SETTINGS_KEY, null);

 
        if ($option === null) {
            return $settings;
        }

 
        if ($settings === null || !is_object($settings)) {
            return $default;
        }

 
        if (!property_exists($settings, $option)) {
            return $default;
        }

        return $settings->{$option};
    }









    public function set(string $option, $value): bool
    {
 
        if (!WPStaging::make(SiteInfo::class)->isStagingSite()) {
            return false;
        }

        $settings = get_option(self::WPSTG_CLONE_SETTINGS_KEY, null);

 
        if ($settings === null || !is_object($settings)) {
            $settings = new stdClass();
        }

        $settings->{$option} = $value;

        return update_option(self::WPSTG_CLONE_SETTINGS_KEY, $settings);
    }








    public function delete(string $option): bool
    {
 
        if (!WPStaging::make(SiteInfo::class)->isStagingSite()) {
            return false;
        }

        $settings = get_option(self::WPSTG_CLONE_SETTINGS_KEY, null);

 
        if ($settings === null || !is_object($settings)) {
            return false;
        }

 
        if (!property_exists($settings, $option)) {
            return true;
        }

        unset($settings->{$option});

        return update_option(self::WPSTG_CLONE_SETTINGS_KEY, $settings);
    }
}
