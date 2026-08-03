<?php

namespace WPStaging\Framework\Language;

use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Utils\Env;

class Language
{
    /** @var string */
    const HOOK_LOAD_MO_FILES = 'wpstg.language.load_mo_files';

    /** @var string */
    const TEXT_DOMAIN = 'wp-staging';

    const FILTER_PLUGIN_LOCALE = 'plugin_locale';

    /** @var string */
    const CLIENT_CLI = 'cli';

    /** @var string */
    const CLIENT_DESKTOP = 'desktop';

    /** @var string */
    const DEFAULT_CAMPAIGN = 'pro_upgrade';

    /**
     * Campaign rather than utm_source or utm_term: campaign name is a top-level
     * Matomo dimension that archiving never collapses.
     *
     * @var array
     */
    const CLIENT_CAMPAIGNS = [
        self::CLIENT_CLI     => 'wp-staging-cli',
        self::CLIENT_DESKTOP => 'wp-staging-desktop',
    ];

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
        $locale       = apply_filters(self::FILTER_PLUGIN_LOCALE, $locale, self::TEXT_DOMAIN);
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

        // Internal use only: loads the .mo files
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
     * Locale prefix/code to the short code used in our .mo file names.
     * Order matters: a longer prefix must precede any shorter one it overlaps,
     * so a future 'zh_' entry would have to sit after 'zh_CN'.
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

    /** Short file code to the full WordPress locale used by global .mo files. */
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
     * @return string|null Null when no bundled translation exists.
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
     * Build the localized wp-staging.com pricing-table URL for an in-plugin
     * "upgrade to Pro" CTA.
     *
     * The language path follows the admin user's locale, falling back to the site
     * locale, so users land on the pricing table in their own language.
     *
     * @param string $context Optional utm_content slug identifying the link.
     *                        Sanitized to [a-z0-9_].
     * @param string $source  utm_source for the click; pass a Pro/licensing source
     *                        for CTAs shown to licensed users. Sanitized to
     *                        [a-z0-9_-]; empty input falls back to the default.
     */
    public static function getUpgradeUrl(string $context = '', string $source = 'wp-staging-free'): string
    {
        // wp-staging.com ships only these languages; every other locale falls back
        // to the English pricing table at "/".
        $localePaths = [
            'de' => '/de/',
            'it' => '/it/',
            'es' => '/es/',
            'fr' => '/fr/',
            'pt' => '/pt/',
            'pl' => '/pl/',
            'ja' => '/ja/',
        ];

        $locale = function_exists('get_user_locale') ? get_user_locale() : get_locale();
        $prefix = strtolower(substr($locale, 0, 2));
        $path   = isset($localePaths[$prefix]) ? $localePaths[$prefix] : '/';

        $base = 'https://wp-staging.com' . $path;

        $context = preg_replace('/[^a-z0-9_]/', '', strtolower($context));
        if ($context === '') {
            return $base . '#pricing';
        }

        $source = preg_replace('/[^a-z0-9_-]/', '', strtolower($source));
        if ($source === '') {
            $source = 'wp-staging-free';
        }

        // Query must precede the #pricing anchor, or the link stops both tracking
        // and scrolling to the pricing table.
        $query = http_build_query([
            'utm_source'   => $source,
            'utm_medium'   => 'plugin',
            'utm_campaign' => self::getUpgradeCampaign(),
            'utm_content'  => $context,
        ]);

        return $base . '?' . $query . '#pricing';
    }

    /**
     * Which WP STAGING environment serves this install: CLIENT_CLI, CLIENT_DESKTOP,
     * or '' for an ordinary host. The CLI writes WPSTG_CLIENT into the php service
     * of the site's docker-compose.yml.
     */
    public static function getInstallClient(): string
    {
        $client = Env::get('WPSTG_CLIENT');
        if (!is_string($client)) {
            return '';
        }

        $client = strtolower(trim($client));

        return array_key_exists($client, self::CLIENT_CAMPAIGNS) ? $client : '';
    }

    /**
     * utm_campaign for this install: the environment's own campaign when the
     * site runs on the CLI or Desktop stack, the generic one everywhere else.
     */
    public static function getUpgradeCampaign(): string
    {
        $client = self::getInstallClient();

        return $client === '' ? self::DEFAULT_CAMPAIGN : self::CLIENT_CAMPAIGNS[$client];
    }

    /**
     * Re-tag an already-campaigned wp-staging.com URL with this install's environment,
     * for CTAs that build their URL by hand instead of going through getUpgradeUrl().
     * A URL without a utm_campaign is left untouched, so a plain docs link never
     * becomes a campaign one.
     */
    public static function addClientAttribution(string $url): string
    {
        $client = self::getInstallClient();
        if ($client === '' || strpos($url, 'wp-staging.com') === false) {
            return $url;
        }

        // Fragment must trail the query string, or the link stops both tracking
        // and scrolling.
        $fragment = '';
        $hashPos  = strpos($url, '#');
        if ($hashPos !== false) {
            $fragment = substr($url, $hashPos);
            $url      = substr($url, 0, $hashPos);
        }

        $queryPos = strpos($url, '?');
        if ($queryPos === false) {
            return $url . $fragment;
        }

        $args = [];
        parse_str(substr($url, $queryPos + 1), $args);
        if (empty($args['utm_campaign'])) {
            return $url . $fragment;
        }

        if (empty($args['utm_content'])) {
            $args['utm_content'] = $args['utm_campaign'];
        }

        $args['utm_campaign'] = self::CLIENT_CAMPAIGNS[$client];

        return substr($url, 0, $queryPos) . '?' . http_build_query($args) . $fragment;
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
