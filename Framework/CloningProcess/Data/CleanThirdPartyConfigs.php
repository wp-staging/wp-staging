<?php

namespace WPStaging\Framework\CloningProcess\Data;

use WPStaging\Framework\SiteInfo;

class CleanThirdPartyConfigs extends FileCloningService
{



    protected function internalExecute()
    {
        $filesForWhichToCreateDummy = [];

 
        $siteInfo = new SiteInfo();
        if ($siteInfo->isFlywheel()) {
            $filesForWhichToCreateDummy[] = '.fw-config.php'; 
        }

        foreach ($filesForWhichToCreateDummy as $file) {
            $this->createDummyFile($file);
        }

        return true;
    }




    private function createDummyFile($file)
    {
        $this->log("Creating dummy file for $file");
        $this->writeFile($file, "<?php // WP Staging dummy file");
    }
}
