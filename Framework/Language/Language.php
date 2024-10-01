<?php

namespace WPStaging\Framework\Language;

use WPStaging\Framework\Facades\Hooks;

class Language
{
    /** @var string */
    const HOOK_LOAD_MO_FILES = 'wpstg.language.load_mo_files';

    /** @var string */
    const TEXT_DOMAIN = 'wp-staging';

    /**
     * @return void
     */
    public function load()
    {
        /** @noinspection NullPointerExceptionInspection */
        $pluginLangDirectory = WPSTG_PLUGIN_DIR . 'languages/';
        $wpLangDirectory     = $this->getLangDirectory();

        if (function_exists('get_user_locale')) {
            $locale = get_user_locale();
        } else {
            $locale = get_locale();
        }

        // Traditional WP plugin locale filter
        $locale = apply_filters('plugin_locale', $locale, self::TEXT_DOMAIN);
        $localMoFile  = $this->getLocalMoFile($locale);
        $globalMoFile = $this->getGlobalMoFile($locale);
        // Unfiltered mo file name
        $actualMoFile = sprintf('%1$s-%2$s.mo', self::TEXT_DOMAIN, $locale);

        // Setup paths to current locale file
        $moFileLocal   = $pluginLangDirectory . $localMoFile;
        $moFilesGlobal = [];
        if ($globalMoFile !== $actualMoFile) {
            $moFilesGlobal[] = sprintf('%s/%s/%s', $wpLangDirectory, 'plugins', $actualMoFile);
        }

        $moFilesGlobal[] = sprintf('%s/%s/%s', $wpLangDirectory, 'plugins', $globalMoFile);

        // Internal Use Only. Use for loading languages files
        Hooks::callInternalHook(self::HOOK_LOAD_MO_FILES, [$locale, $moFileLocal, $moFilesGlobal]);
    }

    /**
     * Get the language code of the current locale, e.g. de, en, it, etc.
     * @return string
     */
    public function getLocaleLanguageCode(): string
    {
        if (function_exists('get_user_locale')) {
            $locale = get_user_locale();
        } else {
            $locale = get_locale();
        }
        return substr($locale, 0, 2);
    }

    protected function getLocalMoFile(string $locale): string
    {
        // Let us assume that the locale is `de` for all `de_` dilects
        if (strpos($locale, 'de_') === 0) {
            $locale = 'de';
        }

        return sprintf('%1$s-%2$s.mo', self::TEXT_DOMAIN, $locale);
    }

    protected function getGlobalMoFile(string $locale): string
    {
        // Let us assume that the locale is `de` for all `de_` dilects
        if (strpos($locale, 'de_') === 0) {
            $locale = 'de_DE';
        }

        return sprintf('%1$s-%2$s.mo', self::TEXT_DOMAIN, $locale);
    }

    /**
     * @return string
     */
    protected function getLangDirectory(): string
    {
        return WP_LANG_DIR;
    }
}
