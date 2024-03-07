<?php

namespace WPStaging\Backup\Service\Database\Importer;

use WPStaging\Framework\Database\SearchReplace;

interface DatabaseSearchReplacerInterface
{
    /**
     * @param string $homeURL
     * @param string $siteURL
     * @param string $absPath
     * @param string|null $destinationSiteUploadURL
     * @return SearchReplace
     */
    public function getSearchAndReplace(string $homeURL, string $siteURL, string $absPath = ABSPATH, $destinationSiteUploadURL = null): SearchReplace;
}
