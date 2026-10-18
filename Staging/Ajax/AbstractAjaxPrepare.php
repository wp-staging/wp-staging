<?php

namespace WPStaging\Staging\Ajax;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Filesystem\Scanning\ScanConst;
use WPStaging\Framework\Job\Ajax\PrepareJob;
use WPStaging\Staging\Dto\Job\StagingSiteJobsDataDto;
use WPStaging\Staging\Service\StagingEngine;
use WPStaging\Staging\Sites;

abstract class AbstractAjaxPrepare extends PrepareJob
{
 
    protected $postDataKey = '';

 
    protected $jobDataDto;





    public function prepare($data = null)
    {
        if (empty($this->postDataKey)) {
            return new \WP_Error(400, "Invalid request. Missing 'postDataKey' in Ajax Prepare class.");
        }

        if (empty($data) && array_key_exists($this->postDataKey, $_POST)) {
            $data = $this->postDataSanitization();
        }

        try {
            $sanitizedData = $this->setupInitialJob($data);
        } catch (\Exception $e) {
            return new \WP_Error(400, $e->getMessage());
        }

        $this->deleteSseCacheFiles();

        return $sanitizedData;
    }





    public function validateAndSanitizeData($data): array
    {
        if (empty($data)) {
            $data = [];
        }

 
        foreach ($data as $key => $value) {
            if ($value === '' || $value === null) {
                unset($data[$key]);
            }
        }

        $defaults = $this->getDefaults();

        $data = wp_parse_args($data, $defaults);

 
        $data = array_intersect_key($data, $defaults);

 
        foreach ($defaults as $expectedKey => $value) {
            if (!array_key_exists($expectedKey, $data)) {
                throw new \UnexpectedValueException("Invalid request. Missing '$expectedKey'.");
            }
        }

        $data = $this->additionalSanitization($data);

        return $data;
    }




    protected function getJobUnavailableError()
    {
        return WPStaging::make(StagingEngine::class)->getNextGenUnavailableError();
    }




    public static function getTableAndFolderSelectionDefaults(): array
    {
        return [
            'allTablesExcluded'   => false,
            'excludedTables'      => [],
            'includedTables'      => [],
            'nonSiteTables'       => [],
            'excludedDirectories' => [],
            'extraDirectories'    => [],
        ];
    }




    public static function getSiteSelectionDefaults(): array
    {
        return array_merge(self::getTableAndFolderSelectionDefaults(), [
            'excludeSizeGreaterThan' => 8,
            'excludeFileRules'       => [],
            'excludeFolderRules'     => [],
            'excludeExtensionRules'  => [],
        ]);
    }

    abstract protected function getDefaults(): array;

    abstract protected function postDataSanitization(): array;

    abstract protected function additionalSanitization(array $data): array;

    protected function parseAndSanitizeTables(string $tables): array
    {
        $tables = $tables === '' ? [] : explode(ScanConst::DIRECTORIES_SEPARATOR, $tables);

        return array_map('sanitize_text_field', $tables);
    }

    protected function parseAndSanitizeDirectories(string $directories): array
    {
 
 
        $directories = $directories === '' ? [] : explode(ScanConst::DIRECTORIES_SEPARATOR, wpstg_urldecode(Sanitize::sanitizeString($directories)));

        return array_map('sanitize_text_field', $directories);
    }

    protected function populateJobDataDtoByCloneId(string $cloneId)
    {
        $stagingSite = WPStaging::make(Sites::class)->getStagingSiteDtoByCloneId($cloneId);
        $this->jobDataDto->setStagingSite($stagingSite);
        $this->jobDataDto->setCloneId($cloneId);
        $this->jobDataDto->setStagingSiteUrl($stagingSite->getUrl());
        $this->jobDataDto->setStagingSitePath($stagingSite->getPath());
        $this->jobDataDto->setDatabasePrefix($stagingSite->getUsedPrefix());
        $this->jobDataDto->setIsExternalDatabase($stagingSite->getIsExternalDatabase());
        $this->jobDataDto->setUseCustomDatabase($stagingSite->getIsCustomDatabaseConnection());
        $this->jobDataDto->setDatabaseServer($stagingSite->getDatabaseServer());
        $this->jobDataDto->setDatabaseName($stagingSite->getDatabaseDatabase());
        $this->jobDataDto->setDatabaseUser($stagingSite->getDatabaseUser());
        $this->jobDataDto->setDatabasePassword($stagingSite->getDatabasePassword());
        $this->jobDataDto->setDatabaseSsl($stagingSite->getDatabaseSsl());
    }

    abstract protected function prepareStagingSiteDto();
}
