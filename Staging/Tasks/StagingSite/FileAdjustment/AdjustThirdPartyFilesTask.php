<?php

namespace WPStaging\Staging\Tasks\StagingSite\FileAdjustment;

use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Staging\Tasks\FileAdjustmentTask;

/**
 * Replacement for WPStaging\Framework\CloningProcess\Data\CleanThirdPartyConfigs
 */
class AdjustThirdPartyFilesTask extends FileAdjustmentTask
{
    /**
     * @return string
     */
    public static function getTaskName()
    {
        return 'staging_adjust_third_party_files';
    }

    /**
     * @return string
     */
    public static function getTaskTitle()
    {
        return 'Adjusting third party files';
    }

    /**
     * @return TaskResponseDto
     */
    public function execute()
    {
        $replaceWithDummyFile = [];
        if ($this->siteInfo->isFlywheel()) {
            $replaceWithDummyFile[] = '.fw-config.php';
        }

        if (empty($replaceWithDummyFile)) {
            $this->logger->info("Found no third party file to adjust.");
            return $this->generateResponse();
        }

        foreach ($replaceWithDummyFile as $file) {
            $this->createDummyFile($file);
        }

        return $this->generateResponse();
    }

    /**
     * @param string $file
     * @return void
     */
    private function createDummyFile(string $file)
    {
        $this->logger->info("Creating dummy file for `$file`");
        $this->writeFile($file, "<?php // WP Staging dummy file");
    }
}
