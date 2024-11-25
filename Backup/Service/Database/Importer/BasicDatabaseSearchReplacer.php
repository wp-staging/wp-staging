<?php
namespace WPStaging\Backup\Service\Database\Importer;
use WPStaging\Framework\Database\SearchReplace;
class BasicDatabaseSearchReplacer implements DatabaseSearchReplacerInterface
{
    public function getSearchAndReplace(string $destinationSiteUrl, string $destinationHomeUrl, string $absPath = '', $destinationSiteUploadURL = null): SearchReplace
    {
        return (new SearchReplace())
            ->setSearch([])
            ->setReplace([])
            ->setWpBakeryActive(false);
    }
}
