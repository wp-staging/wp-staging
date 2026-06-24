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
            $this->logger->info("No third-party hosting files needed adjusting on this site.");
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
        $this->logger->info("Replacing the third-party hosting file `$file` with an empty placeholder so it does not affect the staging site.");
        $this->writeFile($file, "<?php // WP Staging dummy file");
    }
}
