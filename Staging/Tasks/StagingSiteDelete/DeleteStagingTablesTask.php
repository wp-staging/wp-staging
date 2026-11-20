<?php

namespace WPStaging\Staging\Tasks\StagingSiteDelete;

use RuntimeException;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Traits\WithUnfinishedCloneWpConfigGuard;
use WPStaging\Staging\Dto\Job\StagingSiteDeleteDataDto;
use WPStaging\Staging\FolderOwnership;
use WPStaging\Staging\Tasks\StagingSite\CleanupStagingTablesTask;




class DeleteStagingTablesTask extends CleanupStagingTablesTask
{
    use WithUnfinishedCloneWpConfigGuard;

 
    protected $jobDataDto;









    public function prepareCleanupTask(): string
    {
        $stagingPrefix = parent::prepareCleanupTask();
        if ($stagingPrefix === '') {
            return '';
        }

        $stagingSitePath = $this->jobDataDto->getStagingSite()->getPath();
        $wpConfigPath    = trailingslashit($stagingSitePath) . 'wp-config.php';
        $isStagingFolder = WPStaging::make(FolderOwnership::class)->isStagingSiteFolder($stagingSitePath);
        if (!$isStagingFolder && $this->wpConfigMayNameStagingPrefix($wpConfigPath, $stagingPrefix)) {
            throw new RuntimeException('Cannot delete staging tables because WP STAGING cannot confirm that the folder of this wp-config.php holds the staging site, and the file may name their prefix: ' . $wpConfigPath . '. If the folder holds this staging site, delete it by hand, then delete the staging site again.');
        }

        if ($isStagingFolder && !$this->closeWordPressInstallerBeforeDroppingStagingTables(WPStaging::make(Filesystem::class), $stagingSitePath)) {
            throw new RuntimeException('Cannot delete the staging tables: make wp-config.php writable and delete wp-admin/install.php and wp-admin/setup-config.php in ' . $stagingSitePath . ', then delete the staging site again.');
        }

        return $stagingPrefix;
    }










    private function wpConfigMayNameStagingPrefix(string $wpConfigPath, string $stagingPrefix): bool
    {
        if (!is_dir(dirname($wpConfigPath)) || !is_file($wpConfigPath)) {
            return false;
        }

        $wpConfig = is_readable($wpConfigPath) ? file_get_contents($wpConfigPath) : false;
        return $wpConfig === false || stripos($wpConfig, $stagingPrefix) !== false;
    }
}
