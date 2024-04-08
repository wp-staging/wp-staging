<?php

namespace WPStaging\Framework\ThirdParty;

/**
 * Class All in One WP Security (AIOWPS)
 *
 * Special treatment for the wpstg must-use plugin wp-staging-optimizer plugin if AIOWPS is installed
 * and salt prefix option is enabled. This is to prevent making wp staging unusable due to ajax error 400
 * Github: https://github.com/wp-staging/wp-staging-pro/pull/2762/
 *
 * Class created with performance in mind!
 * It ensures that db calls are only made if actually a status change of the AIOS salt option happens.
 *
 * This class will do:
 *
 * - Add AIOWPS to optimizer whitelist and keep it active during wp staging processing.
 * - Remove AIOWPS from optimizer whitelist and disables it during wp staging processing once AIOS does not have salt prefix option enabled.
 * - Delivers the status if salt prefix option is enabled to allow showing an admin notice to recommend disabling the salt prefix option to increase reliability of wp staging requests.
 *
 * @package WPStaging\Framework\ThirdParty
 */
class Aios
{

    /** The option_name that contains all AIOWPS options in the db. */
    const AIOS_OPTIONS = 'aio_wp_security_configs';

    const AIOS_SALT_OPTION = 'aiowps_enable_salt_postfix';

    /** The option_name that contains all optimizer whitelisted plugins in the db. */
    const WHITELISTED_PLUGINS_OPTION = 'wpstg_optimizer_excluded';

    // Plugin slug
    const AIOS_PLUGIN_SLUG = 'all-in-one-wp-security-and-firewall';

    /** @var array */
    private $whitelistedPlugins;

    public function __construct()
    {
        $this->whitelistedPlugins = get_option(self::WHITELISTED_PLUGINS_OPTION, []);
    }

    /**
     * @return bool
     */
    public function isSaltPostfixOptionEnabled(): bool
    {
        // Extra check to reduce number of db calls for performance reasons
        if (!$this->isAiosActive()) {
            return false;
        }

        $aiosOptions = get_option(self::AIOS_OPTIONS);
        if (empty($aiosOptions) || empty($aiosOptions[self::AIOS_SALT_OPTION])) {
            return false;
        }

        return $aiosOptions[self::AIOS_SALT_OPTION] === '1';
    }

    /**
     * @return void
     */
    public function optimizerWhitelistUpdater()
    {
        $this->maybeAddAiosToWhitelist();
        $this->maybeRemoveAiosFromWhitelist();
    }

    /**
     * Add AIOS plugin from being excluded in optimizer plugin if AIOS salt postfix option is enabled
     * @return void
     */
    private function maybeAddAiosToWhitelist()
    {
        if (!$this->isSaltPostfixOptionEnabled()) {
            return;
        }

        if (in_array(self::AIOS_PLUGIN_SLUG, $this->whitelistedPlugins)) {
            return;
        }

        $this->whitelistedPlugins[] = self::AIOS_PLUGIN_SLUG;

        update_option(self::WHITELISTED_PLUGINS_OPTION, $this->whitelistedPlugins);
    }

    /**
     * Remove AIOS plugin from being excluded in optimizer plugin if AIOS salt postfix option is disabled
     * @return void
     */
    private function maybeRemoveAiosFromWhitelist()
    {
        if (!$this->aiosIsWhitelisted()) {
            return;
        }

        if ($this->isSaltPostfixOptionEnabled()) {
            return;
        }

        $key = array_search(self::AIOS_PLUGIN_SLUG, $this->whitelistedPlugins);
        if ($key !== false) {
            unset($this->whitelistedPlugins[$key]);
        }

        update_option(self::WHITELISTED_PLUGINS_OPTION, $this->whitelistedPlugins);
    }

    /**
     * Used to mock up this method in unit tests. Thus, it needs to be public.
     * @param string $class
     * @return bool
     */
    public function doesClassExist(string $class): bool
    {
        return class_exists($class);
    }

    /**
     * @return bool
     */
    protected function isAiosActive(): bool
    {
        return $this->doesClassExist('AIO_WP_Security');
    }

    /**
     * @return bool
     */
    private function aiosIsWhitelisted(): bool
    {
        if (in_array(self::AIOS_PLUGIN_SLUG, $this->whitelistedPlugins)) {
            return true;
        }

        return false;
    }
}
