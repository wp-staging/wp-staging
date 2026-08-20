<?php

namespace WPStaging\Backup\Service;

use WPStaging\Framework\Assets\Assets;






class BackupAssets
{



    private $assets;

    public function __construct(Assets $assets)
    {
        $this->assets = $assets;
    }




    public function register()
    {
        $asset = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? 'js/vendor/resumable.js' : 'js/vendor/resumable.min.js';
        wp_enqueue_script(
            "wpstg-resumable",
            $this->assets->getAssetsUrl($asset),
            ["wpstg-common"],
            $this->assets->getAssetsVersion($asset),
            $this->assets->getScriptLoadingStrategy()
        );

        $asset = $this->assets->getJsAssetsFileName('backup/wpstg-backup');
        wp_enqueue_script(
            "wpstg-backup",
            $this->assets->getAssetsUrl($asset),
            ["wpstg-resumable"],
            $this->assets->getAssetsVersion($asset),
            $this->assets->getScriptLoadingStrategy()
        );
    }
}
