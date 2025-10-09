<?php

namespace WPStaging\Staging\Ajax\Create;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Filesystem\Scanning\ScanConst;
use WPStaging\Framework\Job\Ajax\PrepareJob;
use WPStaging\Framework\Job\Exception\ProcessLockedException;
use WPStaging\Framework\Job\JobTransientCache;
use WPStaging\Staging\Dto\Job\StagingSiteCreateDataDto;
use WPStaging\Staging\Dto\StagingSiteDto;
use WPStaging\Staging\Jobs\StagingSiteCreate;
use WPStaging\Staging\Service\StagingSetup;

class PrepareCreate extends PrepareJob
{
    /** @var StagingSiteCreateDataDto */
    protected $jobDataDto;

    /** @var StagingSiteCreate */
    protected $jobCreate;

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
        if (empty($data) && array_key_exists('wpstgCreateData', $_POST)) {
            $data = Sanitize::sanitizeArray($_POST['wpstgCreateData'], [
                'cloneId'                => 'string',
                'allTablesExcluded'      => 'bool',
                'excludeSizeGreaterThan' => 'string',
            ]);

            $data['name']                = isset($_POST['wpstgCreateData']['name']) ? Sanitize::sanitizeString($_POST['wpstgCreateData']['name']) : '';
            $data['excludedTables']      = isset($_POST['wpstgCreateData']['excludedTables']) ? $this->parseAndSanitizeTables($_POST['wpstgCreateData']['excludedTables']) : []; // phpcs:ignore
            $data['includedTables']      = isset($_POST['wpstgCreateData']['includedTables']) ? $this->parseAndSanitizeTables($_POST['wpstgCreateData']['includedTables']) : []; // phpcs:ignore
            $data['nonSiteTables']       = isset($_POST['wpstgCreateData']['nonSiteTables']) ? $this->parseAndSanitizeTables($_POST['wpstgCreateData']['nonSiteTables']) : []; // phpcs:ignore
            $data['excludedDirectories'] = isset($_POST['wpstgCreateData']['excludedDirectories']) ? $this->parseAndSanitizeDirectories($_POST['wpstgCreateData']['excludedDirectories']) : []; // phpcs:ignore
            $data['extraDirectories']    = isset($_POST['wpstgCreateData']['extraDirectories']) ? $this->parseAndSanitizeDirectories($_POST['wpstgCreateData']['extraDirectories']) : []; // phpcs:ignore
            // Exclude rules
            $data['excludeFileRules']      = isset($_POST['wpstgCreateData']['excludeFileRules']) ? $this->parseAndSanitizeDirectories($_POST['wpstgCreateData']['excludeFileRules']) : []; // phpcs:ignore
            $data['excludeFolderRules']    = isset($_POST['wpstgCreateData']['excludeFolderRules']) ? $this->parseAndSanitizeDirectories($_POST['wpstgCreateData']['excludeFolderRules']) : []; // phpcs:ignore
            $data['excludeExtensionRules'] = isset($_POST['wpstgCreateData']['excludeExtensionRules']) ? $this->parseAndSanitizeDirectories($_POST['wpstgCreateData']['excludeExtensionRules']) : []; // phpcs:ignore
            $data = array_merge($data, $this->getAdvanceSettings());
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

        $data['name'] = empty($data['name']) ? $this->generateStagingSiteName($data['cloneId']) : sanitize_text_field($data['name']);

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

        $data = $this->validateAndSanitizeAdvanceSettingsData($data);

        $data['stagingSitePath'] = $this->getDestinationPath($data);
        $data['stagingSiteUrl']  = $this->getDestinationUrl($data);

        return $data;
    }

    protected function getDefaults(): array
    {
        return [
            'cloneId'                => time(),
            'name'                   => '',
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

    protected function getAdvanceSettings(): array
    {
        return [];
    }

    protected function validateAndSanitizeAdvanceSettingsData(array $data): array
    {
        // New admin user
        $data['useNewAdminAccount'] = false;
        $data['adminEmail']         = '';
        $data['adminPassword']      = '';

        // Database
        $data['useCustomDatabase'] = false;
        $data['databaseServer']    = '';
        $data['databaseName']      = '';
        $data['databaseUser']      = '';
        $data['databasePassword']  = '';
        $data['databasePrefix']    = '';
        $data['databaseSsl']       = false;

        // Path
        $data['customPath'] = '';
        $data['customUrl']  = '';

        // Other settings
        $data['emailsAllowed']         = true;
        $data['cronDisabled']          = false;
        $data['wooSchedulerDisabled']  = false;
        $data['emailsReminderAllowed'] = false;

        return $data;
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
        /** @var StagingSiteCreateDataDto */
        $this->jobDataDto = $services->get(StagingSiteCreateDataDto::class);
        /** @var StagingSiteCreate */
        $this->jobCreate  = $services->get($this->getJobClass());

        $this->jobDataDto->hydrate($sanitizedData);
        $this->jobDataDto->setInit(true);
        $this->jobDataDto->setFinished(false);
        $this->jobDataDto->setStartTime(time());
        $this->jobDataDto->setStagingSiteUploads($this->directory->getRelativeUploadsDirectory());
        $this->jobDataDto->setJobType(StagingSetup::JOB_NEW_STAGING_SITE);

        $this->prepareStagingSiteDto();

        $this->jobDataDto->setId(substr(md5(mt_rand() . time()), 0, 12));

        $this->jobCreate->getTransientCache()->startJob($this->jobDataDto->getId(), esc_html__('Cloning in Progress', 'wp-staging'), JobTransientCache::JOB_TYPE_STAGING_CREATE, $this->queueId);

        $this->jobCreate->setJobDataDto($this->jobDataDto);

        return $sanitizedData;
    }

    /**
     * Returns the reference to the current Job, if any.
     *
     * @return StagingSiteCreate|null The current reference to the Staging Site Create Job, if any.
     */
    public function getJob()
    {
        return $this->jobCreate;
    }

    /**
     * @return string
     */
    protected function getJobClass(): string
    {
        return StagingSiteCreate::class;
    }

    /**
     * Persists the current Job status.
     *
     * @return bool Whether the current Job status was persisted or not.
     */
    public function persist(): bool
    {
        if (!$this->jobCreate instanceof StagingSiteCreate) {
            return false;
        }

        $this->jobCreate->persist();

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

    protected function findDatabasePrefix()
    {
        global $wpdb;

        // Find a new prefix that does not already exist in database.
        // Loop through up to 1000 different possible prefixes should be enough here;)
        for ($i = 0; $i <= 10000; $i++) {
            $prefix = 'wpstg' . $i . '_';

            $sql    = "SHOW TABLE STATUS LIKE '{$prefix}%'";
            $tables = $wpdb->get_results($sql);

            // Prefix does not exist. We can use it
            if (!$tables) {
                return $prefix;
            }
        }
    }

    protected function generateStagingSiteName(string $cloneId): string
    {
        // List of predefined names to choose from
        $nameList = [
            "enterprise",
            "voyager",
            "defiant",
            "discovery",
            "excelsior",
            "intrepid",
            "constitution",
            "reliant",
            "grissom",
            "yamato",
            "excelsior",
            "venture",
            "cerritos",
            "prometheus",
            "bellerophon",
            "sanpablo",
            "sutherland",
            "shenzhou",
            "titan",
            "reliant",
            "stargazer",
            "franklin",
            "protostar",
        ];

        // Randomly shuffle the list of names
        shuffle($nameList);

        foreach ($nameList as $name) {
            // Sanitize the name to ensure it is safe for use
            $name    = sanitize_text_field($name);
            $dirPath = ABSPATH . $name;

            // If it doesn't exist, return this name as the friendly name
            if (!file_exists($dirPath)) {
                return $name;
            }
        }

        // If all predefined names are taken, return a clone Id
        return $cloneId;
    }

    protected function getDestinationPath(array $data): string
    {
        $absPath = trailingslashit($this->filesystem->normalizePath($this->directory->getAbsPath()));

        return $absPath . $data['name'];
    }

    protected function getDestinationUrl(array $data): string
    {
        return trailingslashit(home_url()) . $data['name'];
    }

    protected function prepareStagingSiteDto()
    {
        $this->jobDataDto->setIsExternalDatabase(false);
        $this->jobDataDto->setDatabasePrefix($this->findDatabasePrefix());

        $stagingSite = new StagingSiteDto();
        $stagingSite->setCloneId($this->jobDataDto->getCloneId());
        $stagingSite->setCloneName($this->jobDataDto->getName());
        $stagingSite->setPath($this->jobDataDto->getStagingSitePath());
        $stagingSite->setUrl($this->jobDataDto->getStagingSiteUrl());
        $stagingSite->setStatus(StagingSiteDto::STATUS_UNFINISHED_BROKEN);
        $stagingSite->setPrefix($this->jobDataDto->getDatabasePrefix());
        $stagingSite->setDatetime(time());
        $stagingSite->setVersion(WPStaging::getVersion());
        $stagingSite->setOwnerId(get_current_user_id());

        $this->jobDataDto->setStagingSite($stagingSite);
    }
}
