<?php

namespace WPStaging\Framework\CloningProcess\Data;

use WPStaging\Framework\SiteInfo;

class CleanThirdPartyConfigs extends FileCloningService
{
    /**
     * @inheritDoc
     */
    protected function internalExecute()
    {
        $filesForWhichToCreateDummy = [];

        /** @var SiteInfo $siteInfo */
        $siteInfo = new SiteInfo();
        if ($siteInfo->isFlywheel()) {
            $filesForWhichToCreateDummy[] = '.fw-config.php'; // Flywheel config to clean
        }

        foreach ($filesForWhichToCreateDummy as $file) {
            $this->createDummyFile($file);
        }

        return true;
    }

    /**
     * @param string $file
     */
    private function createDummyFile($file)
    {
        $this->log("Creating dummy file for $file");
        $this->writeFile($file, "<?php // WP Staging dummy file");
    }
}
