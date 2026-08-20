<?php

namespace WPStaging\Staging\Ajax\Reset;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Job\JobTransientCache;
use WPStaging\Staging\Ajax\AbstractAjaxPrepare;
use WPStaging\Staging\Dto\Job\StagingSiteJobsDataDto;
use WPStaging\Staging\Dto\StagingSiteDto;
use WPStaging\Staging\Jobs\StagingSiteReset;
use WPStaging\Staging\Service\StagingEngine;
use WPStaging\Staging\Service\StagingSetup;
use WPStaging\Staging\Sites;

class PrepareReset extends AbstractAjaxPrepare
{
 
    protected $postDataKey = 'wpstgResetData';

 
    protected $jobDataDto;

 
    protected $jobReset;

    protected function postDataSanitization(): array
    {
        if (empty($_POST['wpstgResetData'])) {
            throw new \UnexpectedValueException("Invalid request. Missing 'wpstgResetData'. Should never happen.");
        }

        $data = Sanitize::sanitizeArray($_POST['wpstgResetData'], [
            'cloneId'                => 'string',
            'stagingEngine'          => 'string',
            'allTablesExcluded'      => 'bool',
            'excludeSizeGreaterThan' => 'string',
        ]);

        $data['excludedTables']      = isset($_POST['wpstgResetData']['excludedTables']) ? $this->parseAndSanitizeTables($_POST['wpstgResetData']['excludedTables']) : []; // phpcs:ignore
        $data['includedTables']      = isset($_POST['wpstgResetData']['includedTables']) ? $this->parseAndSanitizeTables($_POST['wpstgResetData']['includedTables']) : []; // phpcs:ignore
        $data['nonSiteTables']       = isset($_POST['wpstgResetData']['nonSiteTables']) ? $this->parseAndSanitizeTables($_POST['wpstgResetData']['nonSiteTables']) : []; // phpcs:ignore
        $data['excludedDirectories'] = isset($_POST['wpstgResetData']['excludedDirectories']) ? $this->parseAndSanitizeDirectories($_POST['wpstgResetData']['excludedDirectories']) : []; // phpcs:ignore
        $data['extraDirectories']    = isset($_POST['wpstgResetData']['extraDirectories']) ? $this->parseAndSanitizeDirectories($_POST['wpstgResetData']['extraDirectories']) : []; // phpcs:ignore
 
        $data['excludeFileRules']      = isset($_POST['wpstgResetData']['excludeFileRules']) ? $this->parseAndSanitizeDirectories($_POST['wpstgResetData']['excludeFileRules']) : []; // phpcs:ignore
        $data['excludeFolderRules']    = isset($_POST['wpstgResetData']['excludeFolderRules']) ? $this->parseAndSanitizeDirectories($_POST['wpstgResetData']['excludeFolderRules']) : []; // phpcs:ignore
        $data['excludeExtensionRules'] = isset($_POST['wpstgResetData']['excludeExtensionRules']) ? $this->parseAndSanitizeDirectories($_POST['wpstgResetData']['excludeExtensionRules']) : []; // phpcs:ignore

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
        ];
    }





    protected function setupInitialData($sanitizedData): array
    {
        $sanitizedData = $this->validateAndSanitizeData($sanitizedData);
        $this->clearCacheFolder();

 
        $services = WPStaging::getInstance()->getContainer();
 
        $this->jobDataDto = $services->get(StagingSiteJobsDataDto::class);
 
        $this->jobReset  = $services->get($this->getJobClass());

        $this->populateJobDataDtoByCloneId($sanitizedData['cloneId']);

        $this->jobDataDto->hydrate($sanitizedData);
        $this->jobDataDto->setInit(true);
        $this->jobDataDto->setFinished(false);
        $this->jobDataDto->setStartTime(time());
        $this->jobDataDto->setStagingSiteUploads($this->directory->getRelativeUploadsDirectory());
        $this->jobDataDto->setJobType(StagingSetup::JOB_RESET);

        $this->prepareStagingSiteDto();

        $this->jobDataDto->setId(substr(md5(mt_rand() . time()), 0, 12));

        $this->jobReset->getTransientCache()->startJob($this->jobDataDto->getId(), esc_html__('Staging Site Reset in Progress', 'wp-staging'), JobTransientCache::JOB_TYPE_STAGING_RESET, $this->queueId);

        $this->jobReset->setJobDataDto($this->jobDataDto);

        return $sanitizedData;
    }






    public function getJob()
    {
        return $this->jobReset;
    }




    protected function getJobClass(): string
    {
        return StagingSiteReset::class;
    }






    public function persist(): bool
    {
        if (!$this->jobReset instanceof StagingSiteReset) {
            return false;
        }

        $this->jobReset->persist();

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
