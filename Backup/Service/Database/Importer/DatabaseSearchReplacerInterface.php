<?php
namespace WPStaging\Backup\Service\Database\Importer;
use WPStaging\Framework\Database\SearchReplace;
interface DatabaseSearchReplacerInterface
{
    public function getSearchAndReplace(string $homeURL, string $siteURL, string $absPath = ABSPATH, $destinationSiteUploadURL = null): SearchReplace;
}
