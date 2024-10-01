<?php

namespace WPStaging\Basic\Language;

use WPStaging\Framework\Language\Language as FrameworkLanguage;

class Language
{
    /**
     * @param string $locale
     * @param string $moFileLocal
     * @param string[] $moFilesGlobal
     * @return void
     */
    public function loadLanguage(string $locale, string $moFileLocal, array $moFilesGlobal)
    {
        $isGlobalLoaded = false;
        foreach ($moFilesGlobal as $moFileGlobal) {
            if (file_exists($moFileGlobal) && load_textdomain(FrameworkLanguage::TEXT_DOMAIN, $moFileGlobal)) {
                $isGlobalLoaded = true;
            }
        }

        if (!$isGlobalLoaded) {
            load_plugin_textdomain(FrameworkLanguage::TEXT_DOMAIN, false, WPSTG_PLUGIN_SLUG . '/languages');
        }

        if (file_exists($moFileLocal)) {
            load_textdomain(FrameworkLanguage::TEXT_DOMAIN, $moFileLocal);
        }
    }
}
