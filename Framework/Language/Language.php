<?php

namespace WPStaging\Framework\Language;

use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Utils\Env;

class Language
{
 
    const HOOK_LOAD_MO_FILES = 'wpstg.language.load_mo_files';

 
    const TEXT_DOMAIN = 'wp-staging';

    const FILTER_PLUGIN_LOCALE = 'plugin_locale';

 
    const CLIENT_CLI = 'cli';

 
    const CLIENT_DESKTOP = 'desktop';

 
    const DEFAULT_CAMPAIGN = 'pro_upgrade';







    const CLIENT_CAMPAIGNS = [
        self::CLIENT_CLI     => 'wp-staging-cli',
        self::CLIENT_DESKTOP => 'wp-staging-desktop',
    ];




    public function load()
    {
 
        $pluginLangDirectory = WPSTG_PLUGIN_DIR . 'languages/';
        $wpLangDirectory     = $this->getLangDirectory();

        if (function_exists('get_user_locale')) {
            $locale = get_user_locale();
        } else {
            $locale = get_locale();
        }

 
        $locale       = apply_filters(self::FILTER_PLUGIN_LOCALE, $locale, self::TEXT_DOMAIN);
        $localMoFile  = $this->getLocalMoFile($locale);
        $globalMoFile = $this->getGlobalMoFile($locale);
 
        $actualMoFile = sprintf('%1$s-%2$s.mo', self::TEXT_DOMAIN, $locale);

 
        $moFileLocal   = $pluginLangDirectory . $localMoFile;
        $moFilesGlobal = [];
        if ($globalMoFile !== $actualMoFile) {
            $moFilesGlobal[] = sprintf('%s/%s/%s', $wpLangDirectory, 'plugins', $actualMoFile);
        }

        $moFilesGlobal[] = sprintf('%s/%s/%s', $wpLangDirectory, 'plugins', $globalMoFile);

 
        Hooks::callInternalHook(self::HOOK_LOAD_MO_FILES, [$locale, $moFileLocal, $moFilesGlobal]);
    }





    public function getLocaleLanguageCode(): string
    {
        if (function_exists('get_user_locale')) {
            $locale = get_user_locale();
        } else {
            $locale = get_locale();
        }
        return substr($locale, 0, 2);
    }






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





    public static function localizeCheckoutUrl(string $url): string
    {
        $locale = function_exists('get_user_locale') ? get_user_locale() : get_locale();
        if (strpos($locale, 'de_') === 0) {
            return str_replace('/checkout/', '/de/kaufen/', $url);
        }

        return $url;
    }





    public static function localizePricingUrl(string $url): string
    {
        $locale = function_exists('get_user_locale') ? get_user_locale() : get_locale();
        if (strpos($locale, 'de_') === 0) {
            return str_replace('wp-staging.com/#', 'wp-staging.com/de/#', $url);
        }

        return $url;
    }














    public static function getUpgradeUrl(string $context = '', string $source = 'wp-staging-free'): string
    {
 
 
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

 
 
        return $base . '?' . self::buildUtmQuery($context, $source) . '#pricing';
    }






    private static function buildUtmQuery(string $context, string $source): string
    {
        return http_build_query([
            'utm_source'   => $source,
            'utm_medium'   => 'plugin',
            'utm_campaign' => self::getUpgradeCampaign(),
            'utm_content'  => $context,
        ]);
    }




    public static function getDesktopUrl(string $context = ''): string
    {
        $url     = self::localizeUrl('https://wp-staging.com/desktop/');
        $context = preg_replace('/[^a-z0-9_]/', '', strtolower($context));

        if ($context === '') {
            return $url;
        }

        return $url . '?' . self::buildUtmQuery($context, 'wp-staging-free');
    }






    public static function getInstallClient(): string
    {
        $client = Env::get('WPSTG_CLIENT');
        if (!is_string($client)) {
            return '';
        }

        $client = strtolower(trim($client));

        return array_key_exists($client, self::CLIENT_CAMPAIGNS) ? $client : '';
    }





    public static function getUpgradeCampaign(): string
    {
        $client = self::getInstallClient();

        return $client === '' ? self::DEFAULT_CAMPAIGN : self::CLIENT_CAMPAIGNS[$client];
    }







    public static function addClientAttribution(string $url): string
    {
        $client = self::getInstallClient();
        if ($client === '' || strpos($url, 'wp-staging.com') === false) {
            return $url;
        }

 
 
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





    public static function localizeSupportUrl(string $url): string
    {
        $locale = function_exists('get_user_locale') ? get_user_locale() : get_locale();
        if (strpos($locale, 'de_') === 0) {
            return str_replace('/support/', '/de/support/', $url);
        }

        return $url;
    }





    public static function localizeHomepageUrl(string $url): string
    {
        $locale = function_exists('get_user_locale') ? get_user_locale() : get_locale();
        if (strpos($locale, 'de_') === 0) {
            return str_replace('wp-staging.com/', 'wp-staging.com/de/', $url);
        }

        return $url;
    }






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




    protected function getLangDirectory(): string
    {
        return WP_LANG_DIR;
    }
}
