<?php

namespace WPStaging\Backup\Service;

use WPStaging\Framework\Assets\Assets;

/**
 * Class BackupAssets
 *
 * @package WPStaging\Service\Backup
 */
class BackupAssets
{
    /**
     * @var Assets
     */
    private $assets;

    public function __construct(Assets $assets)
    {
        $this->assets = $assets;
    }

    /**
     * @return void
     */
    public function register()
    {
        $asset = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? 'js/vendor/resumable.js' : 'js/vendor/resumable.min.js';
        wp_enqueue_script(
            "wpstg-resumable",
            $this->assets->getAssetsUrl($asset),
            ["wpstg-common"],
            $this->assets->getAssetsVersion($asset),
            false
        );

        $asset = $this->assets->getJsAssetsFileName('backup/wpstg-backup');
        wp_enqueue_script(
            "wpstg-backup",
            $this->assets->getAssetsUrl($asset),
            ["wpstg-resumable"],
            $this->assets->getAssetsVersion($asset),
            false
        );
    }
}
