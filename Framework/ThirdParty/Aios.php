<?php

namespace WPStaging\Framework\ThirdParty;



















class Aios
{

 
    const AIOS_OPTIONS = 'aio_wp_security_configs';

    const AIOS_SALT_OPTION = 'aiowps_enable_salt_postfix';

 
    const WHITELISTED_PLUGINS_OPTION = 'wpstg_optimizer_excluded';

 
    const AIOS_PLUGIN_SLUG = 'all-in-one-wp-security-and-firewall';

 
    private $whitelistedPlugins;

    public function __construct()
    {
        $this->whitelistedPlugins = get_option(self::WHITELISTED_PLUGINS_OPTION, []);
    }




    public function isSaltPostfixOptionEnabled(): bool
    {
 
        if (!$this->isAiosActive()) {
            return false;
        }

        $aiosOptions = get_option(self::AIOS_OPTIONS);
        if (empty($aiosOptions) || empty($aiosOptions[self::AIOS_SALT_OPTION])) {
            return false;
        }

        return $aiosOptions[self::AIOS_SALT_OPTION] === '1';
    }




    public function optimizerWhitelistUpdater()
    {
        $this->maybeAddAiosToWhitelist();
        $this->maybeRemoveAiosFromWhitelist();
    }





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






    public function doesClassExist(string $class): bool
    {
        return class_exists($class);
    }




    protected function isAiosActive(): bool
    {
        return $this->doesClassExist('AIO_WP_Security');
    }




    private function aiosIsWhitelisted(): bool
    {
        if (in_array(self::AIOS_PLUGIN_SLUG, $this->whitelistedPlugins)) {
            return true;
        }

        return false;
    }
}
