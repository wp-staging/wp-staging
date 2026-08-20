<?php

namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\CloningProcess\Data\CleanThirdPartyConfigs;
use WPStaging\Framework\CloningProcess\Data\CopyWpConfig;
use WPStaging\Framework\CloningProcess\Data\Job as DataJob;
use WPStaging\Framework\CloningProcess\Data\CleanupTemporaryLogins;
use WPStaging\Framework\CloningProcess\Data\ResetIndexPhp;
use WPStaging\Framework\CloningProcess\Data\UpdateSiteUrlAndHome;
use WPStaging\Framework\CloningProcess\Data\UpdateTablePrefix;
use WPStaging\Framework\CloningProcess\Data\UpdateWpConfigConstants;
use WPStaging\Framework\CloningProcess\Data\UpdateWpOptionsTablePrefix;
use WPStaging\Framework\CloningProcess\Data\UpdateStagingOptionsTable;
use WPStaging\Framework\CloningProcess\Data\UpdateWpConfig;
use WPStaging\Framework\Utils\Strings;





class Data extends DataJob
{



    public function initialize()
    {
        parent::initialize();
        $this->getTables();
    }

    protected function initializeSteps()
    {
        $this->steps = [
            CopyWpConfig::class, 
            UpdateSiteUrlAndHome::class,
            UpdateStagingOptionsTable::class,
            UpdateTablePrefix::class,
            UpdateWpConfig::class,
            ResetIndexPhp::class, 
            UpdateWpOptionsTablePrefix::class, 
            UpdateWpConfigConstants::class,
            CleanThirdPartyConfigs::class, 
            CleanupTemporaryLogins::class, 
        ];
    }




    protected function getTables()
    {
        $strings = new Strings();
        $this->tables = [];
        foreach ($this->options->tables as $table) {
            $this->tables[] = $this->options->prefix . $strings->strReplaceFirst(WPStaging::getTablePrefix(), '', $table);
        }
    }





    protected function calculateTotalSteps()
    {
        $this->options->totalSteps = 9;
    }
}
