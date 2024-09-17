<?php

namespace WPStaging\Framework\Language;

class Language
{
    /** @var string */
    const TEXT_DOMAIN = 'wp-staging';

    /**
     * @return void
     */
    public function load()
    {
        /** @noinspection NullPointerExceptionInspection */
        $languagesDirectory = WPSTG_PLUGIN_DIR . 'languages/';

        if (function_exists('get_user_locale')) {
            $locale = get_user_locale();
        } else {
            $locale = get_locale();
        }

        // Traditional WP plugin locale filter
        $locale = apply_filters('plugin_locale', $locale, self::TEXT_DOMAIN);
        $moFile = $this->getMoFile($locale);

        // Setup paths to current locale file
        $moFileLocal          = $languagesDirectory . $moFile;
        $moFileGlobalAtPlugin = sprintf('%s/%s/%s/%s', WP_LANG_DIR, 'plugins', self::TEXT_DOMAIN, $moFile);
        $moFileGlobalAtRoot   = sprintf('%s/%s/%s', WP_LANG_DIR, 'plugins', $moFile);

        // Let load the local mo file first if it exists for PRO version
        // Note: Pro and Basic Service are loaded after this method call, so no access to WPStaging::isPro()
        $loaded = false;
        if (defined('WPSTGPRO_VERSION') && file_exists($moFileLocal)) {
            $loaded = load_textdomain(self::TEXT_DOMAIN, $moFileLocal);
        }

        // If languages loaded let return early
        if ($loaded) {
            return;
        }

        if (file_exists($moFileGlobalAtPlugin)) {
            load_textdomain(self::TEXT_DOMAIN, $moFileGlobalAtPlugin);
        } elseif (file_exists($moFileGlobalAtRoot)) {
            load_textdomain(self::TEXT_DOMAIN, $moFileGlobalAtRoot);
        } elseif (file_exists($moFileLocal)) {
            load_textdomain(self::TEXT_DOMAIN, $moFileLocal);
        } else {
            load_plugin_textdomain(self::TEXT_DOMAIN, false, WPSTG_PLUGIN_SLUG . '/languages');
        }
    }

    protected function getMoFile(string $locale): string
    {
        // Let us assume that the locale is `de` for all `de_` dilects
        if (strpos($locale, 'de_') === 0) {
            $locale = 'de';
        }

        return sprintf('%1$s-%2$s.mo', self::TEXT_DOMAIN, $locale);
    }
}
