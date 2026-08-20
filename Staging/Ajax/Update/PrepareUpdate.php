<?php

namespace WPStaging\Staging\Ajax\Update;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Job\JobTransientCache;
use WPStaging\Staging\Ajax\AbstractAjaxPrepare;
use WPStaging\Staging\Dto\Job\StagingSiteJobsDataDto;
use WPStaging\Staging\Dto\StagingSiteDto;
use WPStaging\Staging\Jobs\StagingSiteUpdate;
use WPStaging\Staging\Service\StagingEngine;
use WPStaging\Staging\Service\StagingSetup;
use WPStaging\Staging\Sites;

class PrepareUpdate extends AbstractAjaxPrepare
{
 
    protected $postDataKey = 'wpstgUpdateData';

 
    protected $jobDataDto;

 
    protected $jobUpdate;

    protected function postDataSanitization(): array
    {
        if (empty($_POST['wpstgUpdateData'])) {
            throw new \UnexpectedValueException("Invalid request. Missing 'wpstgUpdateData'. Should never happen.");
        }

        $data = Sanitize::sanitizeArray($_POST['wpstgUpdateData'], [
            'cloneId'                => 'string',
            'stagingEngine'          => 'string',
            'allTablesExcluded'      => 'bool',
            'excludeSizeGreaterThan' => 'string',
            'isCleanPluginsThemes'   => 'bool',
            'isCleanUploads'         => 'bool',
        ]);

        $data['excludedTables']      = isset($_POST['wpstgUpdateData']['excludedTables']) ? $this->parseAndSanitizeTables($_POST['wpstgUpdateData']['excludedTables']) : []; // phpcs:ignore
        $data['includedTables']      = isset($_POST['wpstgUpdateData']['includedTables']) ? $this->parseAndSanitizeTables($_POST['wpstgUpdateData']['includedTables']) : []; // phpcs:ignore
        $data['nonSiteTables']       = isset($_POST['wpstgUpdateData']['nonSiteTables']) ? $this->parseAndSanitizeTables($_POST['wpstgUpdateData']['nonSiteTables']) : []; // phpcs:ignore
        $data['excludedDirectories'] = isset($_POST['wpstgUpdateData']['excludedDirectories']) ? $this->parseAndSanitizeDirectories($_POST['wpstgUpdateData']['excludedDirectories']) : []; // phpcs:ignore
        $data['extraDirectories']    = isset($_POST['wpstgUpdateData']['extraDirectories']) ? $this->parseAndSanitizeDirectories($_POST['wpstgUpdateData']['extraDirectories']) : []; // phpcs:ignore
 
        $data['excludeFileRules']      = isset($_POST['wpstgUpdateData']['excludeFileRules']) ? $this->parseAndSanitizeDirectories($_POST['wpstgUpdateData']['excludeFileRules']) : []; // phpcs:ignore
        $data['excludeFolderRules']    = isset($_POST['wpstgUpdateData']['excludeFolderRules']) ? $this->parseAndSanitizeDirectories($_POST['wpstgUpdateData']['excludeFolderRules']) : []; // phpcs:ignore
        $data['excludeExtensionRules'] = isset($_POST['wpstgUpdateData']['excludeExtensionRules']) ? $this->parseAndSanitizeDirectories($_POST['wpstgUpdateData']['excludeExtensionRules']) : []; // phpcs:ignore
        $data = array_merge($data, $this->getAdvanceSettings());

        return $data;
    }

    protected function additionalSanitization(array $data): array
    {
 
        $data['cloneId'] = sanitize_text_field($data['cloneId']);
        $data['stagingEngine'] = StagingEngine::ENGINE_NEXT_GEN;

        if (empty($data['cloneId'])) {
            throw new \UnexpectedValueException("Invalid request. Missing 'cloneId'.");
        }

 
        $data['excludedTables'] = array_map('sanitize_text_field', $data['excludedTables']);
        $data['includedTables'] = array_map('sanitize_text_field', $data['includedTables']);
        $data['nonSiteTables']  = array_map('sanitize_text_field', $data['nonSiteTables']);

 
        $data['extraDirectories']    = array_map('sanitize_text_field', $data['extraDirectories']);
        $data['excludedDirectories'] = array_map('sanitize_text_field', $data['excludedDirectories']);

 
        $data['excludeSizeGreaterThan'] = sanitize_text_field($data['excludeSizeGreaterThan']);
        $data['excludeFileRules']       = array_map('sanitize_text_field', $data['excludeFileRules']);
        $data['excludeFolderRules']     = array_map('sanitize_text_field', $data['excludeFolderRules']);
        $data['excludeExtensionRules']  = array_map('sanitize_text_field', $data['excludeExtensionRules']);

 
        $data['isCleanPluginsThemes'] = $this->jsBoolean($data['isCleanPluginsThemes']);
        $data['isCleanUploads']       = $this->jsBoolean($data['isCleanUploads']);

        $data = $this->validateAndSanitizeAdvanceSettingsData($data);

        return $data;
    }

    protected function getDefaults(): array
    {
        return [
            'cloneId'                => '',
            'stagingEngine'          => StagingEngine::ENGINE_NEXT_GEN,
            'allTablesExcluded'      => false,
            'excludedTables'         => [],
            'includedTables'         => [],
            'nonSiteTables'          => [],
            'excludedDirectories'    => [],
            'extraDirectories'       => [],
 
            'excludeSizeGreaterThan' => 8,
            'excludeFileRules'       => [],
            'excludeFolderRules'     => [],
            'excludeExtensionRules'  => [],
 
            'isCleanPluginsThemes'   => false,
            'isCleanUploads'         => false,
        ];
    }

    protected function getAdvanceSettings(): array
    {
        return [];
    }

    protected function validateAndSanitizeAdvanceSettingsData(array $data): array
    {
 
        $data['isEmailsAllowed']         = true;
        $data['isEmailsReminderEnabled'] = false;
        $data['isAutoUpdatePlugins']     = false;

        return $data;
    }





    protected function setupInitialData($sanitizedData): array
    {
        $sanitizedData = $this->validateAndSanitizeData($sanitizedData);
        $this->clearCacheFolder();

 
        $services = WPStaging::getInstance()->getContainer();
 
        $this->jobDataDto = $services->get(StagingSiteJobsDataDto::class);
 
        $this->jobUpdate  = $services->get($this->getJobClass());

        $this->populateJobDataDtoByCloneId($sanitizedData['cloneId']);

        $this->jobDataDto->hydrate($sanitizedData);
        $this->jobDataDto->setInit(true);
        $this->jobDataDto->setFinished(false);
        $this->jobDataDto->setStartTime(time());
        $this->jobDataDto->setStagingSiteUploads($this->directory->getRelativeUploadsDirectory());
        $this->jobDataDto->setJobType(StagingSetup::JOB_UPDATE);

        $this->prepareStagingSiteDto();

        $this->jobDataDto->setId(substr(md5(mt_rand() . time()), 0, 12));

        $this->jobUpdate->getTransientCache()->startJob($this->jobDataDto->getId(), esc_html__('Staging Site Update in Progress', 'wp-staging'), JobTransientCache::JOB_TYPE_STAGING_UPDATE, $this->queueId);

        $this->jobUpdate->setJobDataDto($this->jobDataDto);

        return $sanitizedData;
    }






    public function getJob()
    {
        return $this->jobUpdate;
    }




    protected function getJobClass(): string
    {
        return StagingSiteUpdate::class;
    }






    public function persist(): bool
    {
        if (!$this->jobUpdate instanceof StagingSiteUpdate) {
            return false;
        }

        $this->jobUpdate->persist();

        return true;
    }

    protected function prepareStagingSiteDto()
    {
        $stagingSite = $this->jobDataDto->getStagingSite();
        $stagingSite->setStatus(StagingSiteDto::STATUS_UNFINISHED_BROKEN);
        $stagingSite->setDatetime(time());
        $stagingSite->setVersion(WPStaging::getVersion());
        $stagingSite->setOwnerId(get_current_user_id());

        $this->jobDataDto->setStagingSite($stagingSite);
    }

    protected function populateJobDataDtoByCloneId(string $cloneId)
    {



        $stagingSites = WPStaging::make(Sites::class); // @phpstan-ignore-line
        $stagingSite  = $stagingSites->getStagingSiteDtoByCloneId($cloneId);
        $this->jobDataDto->setStagingSite($stagingSite);
        $this->jobDataDto->setCloneId($cloneId);
        $this->jobDataDto->setStagingSiteUrl($stagingSite->getUrl());
        $this->jobDataDto->setStagingSitePath($stagingSite->getPath());
        $this->jobDataDto->setDatabasePrefix($stagingSite->getUsedPrefix());
        $this->jobDataDto->setIsExternalDatabase($stagingSite->getIsExternalDatabase());
    }
}
