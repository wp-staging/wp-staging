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
        // Load the bundled translation first. WordPress merges translations with
        // first-loaded-wins semantics, so the local file overrides any conflicting
        // WordPress.org language pack while the global files below only fill gaps.
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
