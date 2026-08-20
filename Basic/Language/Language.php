<?php

namespace WPStaging\Basic\Language;

use WPStaging\Framework\Language\Language as FrameworkLanguage;

class Language
{






    public function loadLanguage(string $locale, string $moFileLocal, array $moFilesGlobal)
    {
 
 
 
        $isLocalLoaded = false;
        if (file_exists($moFileLocal)) {
            $isLocalLoaded = load_textdomain(FrameworkLanguage::TEXT_DOMAIN, $moFileLocal);
        }

        if (!$isLocalLoaded) {
            load_plugin_textdomain(FrameworkLanguage::TEXT_DOMAIN, false, WPSTG_PLUGIN_SLUG . '/languages');
        }

        foreach ($moFilesGlobal as $moFileGlobal) {
            if (file_exists($moFileGlobal)) {
                load_textdomain(FrameworkLanguage::TEXT_DOMAIN, $moFileGlobal);
            }
        }
    }
}
