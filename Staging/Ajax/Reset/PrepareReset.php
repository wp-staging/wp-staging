<?php

namespace WPStaging\Staging\Ajax\Reset;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Filesystem\Scanning\ScanConst;
use WPStaging\Framework\Job\Ajax\PrepareJob;
use WPStaging\Framework\Job\Exception\ProcessLockedException;
use WPStaging\Framework\Job\JobTransientCache;
use WPStaging\Staging\Dto\Job\StagingSiteJobsDataDto;
use WPStaging\Staging\Dto\StagingSiteDto;
use WPStaging\Staging\Jobs\StagingSiteReset;
use WPStaging\Staging\Service\StagingSetup;
use WPStaging\Staging\Sites;

class PrepareReset extends PrepareJob
{
    /** @var StagingSiteJobsDataDto */
    protected $jobDataDto;

    /** @var StagingSiteReset */
    protected $jobReset;

    /**
     * @param array|null $data
     * @return void
     */
    public function ajaxPrepare($data)
    {
        if (!$this->auth->isAuthenticatedRequest()) {
            wp_send_json_error(null, 401);
        }

        try {
            $this->processLock->checkProcessLocked();
        } catch (ProcessLockedException $e) {
            wp_send_json_error($e->getMessage(), $e->getCode());
        }

        $response = $this->prepare($data);

        if ($response instanceof \WP_Error) {
            wp_send_json_error($response->get_error_message(), $response->get_error_code());
        }

        wp_send_json_success();
    }

    /**
     * @param array|null $data
     * @return array|\WP_Error
     */
    public function prepare($data = null)
    {
        if (empty($data) && array_key_exists('wpstgResetData', $_POST)) {
            $data = Sanitize::sanitizeArray($_POST['wpstgResetData'], [
                'cloneId'                => 'string',
                'allTablesExcluded'      => 'bool',
                'excludeSizeGreaterThan' => 'string',
            ]);

            $data['excludedTables']      = isset($_POST['wpstgResetData']['excludedTables']) ? $this->parseAndSanitizeTables($_POST['wpstgResetData']['excludedTables']) : []; // phpcs:ignore
            $data['includedTables']      = isset($_POST['wpstgResetData']['includedTables']) ? $this->parseAndSanitizeTables($_POST['wpstgResetData']['includedTables']) : []; // phpcs:ignore
            $data['nonSiteTables']       = isset($_POST['wpstgResetData']['nonSiteTables']) ? $this->parseAndSanitizeTables($_POST['wpstgResetData']['nonSiteTables']) : []; // phpcs:ignore
            $data['excludedDirectories'] = isset($_POST['wpstgResetData']['excludedDirectories']) ? $this->parseAndSanitizeDirectories($_POST['wpstgResetData']['excludedDirectories']) : []; // phpcs:ignore
            $data['extraDirectories']    = isset($_POST['wpstgResetData']['extraDirectories']) ? $this->parseAndSanitizeDirectories($_POST['wpstgResetData']['extraDirectories']) : []; // phpcs:ignore
            // Exclude rules
            $data['excludeFileRules']      = isset($_POST['wpstgResetData']['excludeFileRules']) ? $this->parseAndSanitizeDirectories($_POST['wpstgResetData']['excludeFileRules']) : []; // phpcs:ignore
            $data['excludeFolderRules']    = isset($_POST['wpstgResetData']['excludeFolderRules']) ? $this->parseAndSanitizeDirectories($_POST['wpstgResetData']['excludeFolderRules']) : []; // phpcs:ignore
            $data['excludeExtensionRules'] = isset($_POST['wpstgResetData']['excludeExtensionRules']) ? $this->parseAndSanitizeDirectories($_POST['wpstgResetData']['excludeExtensionRules']) : []; // phpcs:ignore
        }

        try {
            $sanitizedData = $this->setupInitialData($data);
        } catch (\Exception $e) {
            return new \WP_Error(400, $e->getMessage());
        }

        $this->deleteSseCacheFiles();

        return $sanitizedData;
    }

    /**
     * @param array|null $data
     * @return array
     */
    public function validateAndSanitizeData($data): array
    {
        if (empty($data)) {
            $data = [];
        }

        // Unset any empty value so that we replace them with the defaults.
        foreach ($data as $key => $value) {
            if (empty($value)) {
                unset($data[$key]);
            }
        }

        $defaults = $this->getDefaults();

        $data = wp_parse_args($data, $defaults);

        // Make sure data has no keys other than the expected ones.
        $data = array_intersect_key($data, $defaults);

        // Make sure data has all expected keys.
        foreach ($defaults as $expectedKey => $value) {
            if (!array_key_exists($expectedKey, $data)) {
                throw new \UnexpectedValueException("Invalid request. Missing '$expectedKey'.");
            }
        }

        // Clone ID
        $data['cloneId'] = sanitize_text_field($data['cloneId']);

        if (empty($data['cloneId'])) {
            throw new \UnexpectedValueException("Invalid request. Missing 'cloneId'.");
        }

        // Included/Excluded tables
        $data['excludedTables'] = array_map('sanitize_text_field', $data['excludedTables']);
        $data['includedTables'] = array_map('sanitize_text_field', $data['includedTables']);
        $data['nonSiteTables']  = array_map('sanitize_text_field', $data['nonSiteTables']);

        // Extra directories and directories exclusion and rules
        $data['extraDirectories']    = array_map('sanitize_text_field', $data['extraDirectories']);
        $data['excludedDirectories'] = array_map('sanitize_text_field', $data['excludedDirectories']);

        // Exclude rules
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
            'allTablesExcluded'      => false,
            'excludedTables'         => [],
            'includedTables'         => [],
            'nonSiteTables'          => [],
            'excludedDirectories'    => [],
            'extraDirectories'       => [],
            // exclude rules
            'excludeSizeGreaterThan' => 8,
            'excludeFileRules'       => [],
            'excludeFolderRules'     => [],
            'excludeExtensionRules'  => [],
        ];
    }

    /**
     * @param $sanitizedData
     * @return array
     */
    private function setupInitialData($sanitizedData): array
    {
        $sanitizedData = $this->validateAndSanitizeData($sanitizedData);
        $this->clearCacheFolder();

        // Lazy-instantiation to avoid process-lock checks conflicting with running processes.
        $services = WPStaging::getInstance()->getContainer();
        /** @var StagingSiteJobsDataDto */
        $this->jobDataDto = $services->get(StagingSiteJobsDataDto::class);
        /** @var StagingSiteReset */
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

    /**
     * Returns the reference to the current Job, if any.
     *
     * @return StagingSiteReset|null The current reference to the Staging Site Reset Job, if any.
     */
    public function getJob()
    {
        return $this->jobReset;
    }

    /**
     * @return string
     */
    protected function getJobClass(): string
    {
        return StagingSiteReset::class;
    }

    /**
     * Persists the current Job status.
     *
     * @return bool Whether the current Job status was persisted or not.
     */
    public function persist(): bool
    {
        if (!$this->jobReset instanceof StagingSiteReset) {
            return false;
        }

        $this->jobReset->persist();

        return true;
    }

    protected function parseAndSanitizeTables(string $tables): array
    {
        $tables = $tables === '' ? [] : explode(ScanConst::DIRECTORIES_SEPARATOR, $tables);

        return array_map('sanitize_text_field', $tables);
    }

    protected function parseAndSanitizeDirectories(string $directories): array
    {
        $directories = $directories === '' ? [] : explode(ScanConst::DIRECTORIES_SEPARATOR, $directories);

        return array_map('sanitize_text_field', $directories);
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
        /**
         * @var Sites $stagingSites
         */
        $stagingSites = WPStaging::make(Sites::class); // @phpstan-ignore-line
        $stagingSite  = $stagingSites->getStagingSiteDtoByCloneId($cloneId);
        $this->jobDataDto->setStagingSite($stagingSite);
        $this->jobDataDto->setCloneId($cloneId);
        $this->jobDataDto->setStagingSiteUrl($stagingSite->getUrl());
        $this->jobDataDto->setStagingSitePath($stagingSite->getPath());
        $this->jobDataDto->setDatabasePrefix($stagingSite->getPrefix());
        $this->jobDataDto->setIsExternalDatabase($stagingSite->getIsExternalDatabase());
    }
}
