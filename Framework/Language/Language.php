<?php

namespace WPStaging\Framework\Language;

use WPStaging\Framework\Facades\Hooks;

class Language
{
    /** @var string */
    const HOOK_LOAD_MO_FILES = 'wpstg.language.load_mo_files';

    /** @var string */
    const TEXT_DOMAIN = 'wp-staging';

    const FILTER_PLUGIN_LOCALE = 'plugin_locale';

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
        $locale = apply_filters(self::FILTER_PLUGIN_LOCALE, $locale, self::TEXT_DOMAIN);
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

    /**
     * Map of locale prefixes/codes to the short code used in our .mo file names.
     * Order matters: longer prefixes must come before shorter ones so that
     * e.g. 'zh_CN' matches before a hypothetical 'zh_' entry, and 'pt_BR'
     * matches before a hypothetical 'pt_' entry.
     */
    const LOCALE_TO_FILE_CODE = [
        'de_'   => 'de',
        'es_'   => 'es',
        'fr_'   => 'fr',
        'it_'   => 'it',
        'nl_'   => 'nl',
        'pl_'   => 'pl',
        'ru_'   => 'ru',
        'tr_'   => 'tr',
        'pt_BR' => 'pt_BR',
        'zh_CN' => 'zh_CN',
        'ja'    => 'ja',
    ];

    /**
     * Map of short file codes to their full WordPress locale form used by global .mo files.
     */
    const FILE_CODE_TO_GLOBAL_LOCALE = [
        'de'    => 'de_DE',
        'es'    => 'es_ES',
        'fr'    => 'fr_FR',
        'it'    => 'it_IT',
        'nl'    => 'nl_NL',
        'pl'    => 'pl_PL',
        'ru'    => 'ru_RU',
        'tr'    => 'tr_TR',
        'pt_BR' => 'pt_BR',
        'zh_CN' => 'zh_CN',
        'ja'    => 'ja',
    ];

    /**
     * Resolve a WordPress locale to the language code used in our bundled .mo files.
     *
     * @param string $locale
     * @return string|null The resolved code, or null when no bundled translation exists.
     */
    private function resolveFileCode(string $locale)
    {
        foreach (self::LOCALE_TO_FILE_CODE as $prefix => $code) {
            if (strpos($locale, $prefix) === 0 || $locale === $code) {
                return $code;
            }
        }

        return null;
    }

    protected function getLocalMoFile(string $locale): string
    {
        $code = $this->resolveFileCode($locale);
        if ($code !== null) {
            $locale = $code;
        }

        return sprintf('%1$s-%2$s.mo', self::TEXT_DOMAIN, $locale);
    }

    protected function getGlobalMoFile(string $locale): string
    {
        $code = $this->resolveFileCode($locale);
        if ($code !== null && isset(self::FILE_CODE_TO_GLOBAL_LOCALE[$code])) {
            $locale = self::FILE_CODE_TO_GLOBAL_LOCALE[$code];
        }

        return sprintf('%1$s-%2$s.mo', self::TEXT_DOMAIN, $locale);
    }

    /**
     * Rewrite a checkout URL for the current locale.
     * German locales (de_DE, de_AT, de_CH, de_DE_formal, …) use /de/kaufen/ instead of /checkout/.
     */
    public static function localizeCheckoutUrl(string $url): string
    {
        $locale = function_exists('get_user_locale') ? get_user_locale() : get_locale();
        if (strpos($locale, 'de_') === 0) {
            return str_replace('/checkout/', '/de/kaufen/', $url);
        }

        return $url;
    }

    /**
     * Rewrite a pricing URL for the current locale.
     * German locales use /de/#pricing instead of /#pricing.
     */
    public static function localizePricingUrl(string $url): string
    {
        $locale = function_exists('get_user_locale') ? get_user_locale() : get_locale();
        if (strpos($locale, 'de_') === 0) {
            return str_replace('wp-staging.com/#', 'wp-staging.com/de/#', $url);
        }

        return $url;
    }

    /**
     * Rewrite the support URL for the current locale.
     * German locales use /de/support/ instead of /support/.
     */
    public static function localizeSupportUrl(string $url): string
    {
        $locale = function_exists('get_user_locale') ? get_user_locale() : get_locale();
        if (strpos($locale, 'de_') === 0) {
            return str_replace('/support/', '/de/support/', $url);
        }

        return $url;
    }

    /**
     * Rewrite a wp-staging.com homepage URL for the current locale.
     * German locales insert /de/ after the domain.
     */
    public static function localizeHomepageUrl(string $url): string
    {
        $locale = function_exists('get_user_locale') ? get_user_locale() : get_locale();
        if (strpos($locale, 'de_') === 0) {
            return str_replace('wp-staging.com/', 'wp-staging.com/de/', $url);
        }

        return $url;
    }

    /**
     * Rewrite any wp-staging.com URL for the current locale.
     * Inserts /de/ after the domain for German locales.
     * Works with bare URLs, URLs with paths, and fragment-only URLs like /#pricing.
     */
    public static function localizeUrl(string $url): string
    {
        $locale = function_exists('get_user_locale') ? get_user_locale() : get_locale();
        if (strpos($locale, 'de_') !== 0) {
            return $url;
        }

        if (strpos($url, 'wp-staging.com/de/') !== false) {
            return $url;
        }

        return preg_replace(
            '#(https?://wp-staging\.com)/?#',
            '$1/de/',
            $url,
            1
        );
    }

    /**
     * Rewrite a wp-staging.com docs URL for the current locale.
     * Handles articles where the German slug differs from the English one.
     */
    public static function localizeDocsUrl(string $url): string
    {
        $locale = function_exists('get_user_locale') ? get_user_locale() : get_locale();
        if (strpos($locale, 'de_') !== 0) {
            return $url;
        }

        $germanDocsMap = [
            'https://wp-staging.com/docs/how-to-migrate-your-wordpress-site-to-a-new-host/' => 'https://wp-staging.com/de/docs/wordpress-seite-zu-anderem-host-migrieren/',
            'https://wp-staging.com/docs/documentation/'                                    => 'https://wp-staging.com/de/docs/dokumentation/',
            'https://wp-staging.com/docs/set-up-wp-staging-cli/'                            => 'https://wp-staging.com/de/docs/lokale-kopie-deiner-wordpress-seite-erstellen/',
            'https://wp-staging.com/docs/pull-a-wordpress-site-from-one-server-to-another/' => 'https://wp-staging.com/de/docs/wordpress-seite-von-einem-server-auf-einen-anderen-ziehen/',
        ];

        // Strip fragment for lookup, re-append after
        $fragment = '';
        $hashPos  = strpos($url, '#');
        if ($hashPos !== false) {
            $fragment = substr($url, $hashPos);
            $baseUrl  = substr($url, 0, $hashPos);
        } else {
            $baseUrl = $url;
        }

        if (isset($germanDocsMap[$baseUrl])) {
            return $germanDocsMap[$baseUrl] . $fragment;
        }

        return self::localizeUrl($url);
    }

    /**
     * @return string
     */
    protected function getLangDirectory(): string
    {
        return WP_LANG_DIR;
    }
}
