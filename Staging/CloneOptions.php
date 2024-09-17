<?php

namespace WPStaging\Staging;

use stdClass;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\SiteInfo;

/**
 * Class CloneOptions
 *
 * This is used to manage settings on the staging site
 *
 * @package WPStaging\Staging
 */
class CloneOptions
{
    /**
     * The option_name that is stored in the database to check first run is executed or not
     * @var string
     */
    const WPSTG_CLONE_SETTINGS_KEY = 'wpstg_clone_settings';

    /**
     * Get the value of the given option,
     * If no option given return all settings
     *
     * @param string|null $option
     *
     * @return mixed
     */
    public function get($option = null, $default = null)
    {
        // Early bail if not a staging site
        if (!WPStaging::make(SiteInfo::class)->isStagingSite()) {
            return $default;
        }

        $settings = get_option(self::WPSTG_CLONE_SETTINGS_KEY, null);

        // Return settings if no options given
        if ($option === null) {
            return $settings;
        }

        // Early Bail: if settings is null or if settings isn't object
        if ($settings === null || !is_object($settings)) {
            return $default;
        }

        // Early bail if given option not exists
        if (!property_exists($settings, $option)) {
            return $default;
        }

        return $settings->{$option};
    }

    /**
     * Set the value of given option
     *
     * @param string $option
     * @param mixed $value
     *
     * @return bool
     */
    public function set(string $option, $value): bool
    {
        // Early bail if not a staging site
        if (!WPStaging::make(SiteInfo::class)->isStagingSite()) {
            return false;
        }

        $settings = get_option(self::WPSTG_CLONE_SETTINGS_KEY, null);

        // If settings is null or if settings isn't object make settings a object
        if ($settings === null || !is_object($settings)) {
            $settings = new stdClass();
        }

        $settings->{$option} = $value;

        return update_option(self::WPSTG_CLONE_SETTINGS_KEY, $settings);
    }

    /**
     * Delete the given option
     *
     * @param string $option
     *
     * @return bool
     */
    public function delete(string $option): bool
    {
        // Early bail if not a staging site
        if (!WPStaging::make(SiteInfo::class)->isStagingSite()) {
            return false;
        }

        $settings = get_option(self::WPSTG_CLONE_SETTINGS_KEY, null);

        // Early Bail: if settings is null or if settings isn't object
        if ($settings === null || !is_object($settings)) {
            return false;
        }

        // Early bail if given option not exists
        if (!property_exists($settings, $option)) {
            return true;
        }

        unset($settings->{$option});

        return update_option(self::WPSTG_CLONE_SETTINGS_KEY, $settings);
    }
}
