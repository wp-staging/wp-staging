<?php

namespace WPStaging\Staging\Ajax\Create;

use RuntimeException;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Filesystem\Scanning\ScanConst;
use WPStaging\Framework\Job\Ajax\PrepareJob;
use WPStaging\Framework\Job\Exception\ProcessLockedException;
use WPStaging\Staging\Dto\Job\StagingSiteCreateDataDto;
use WPStaging\Staging\Dto\StagingSiteDto;
use WPStaging\Staging\Jobs\StagingSiteCreate;

class PrepareCreate extends PrepareJob
{
    /** @var StagingSiteCreateDataDto */
    private $jobDataDto;

    /** @var StagingSiteCreate */
    private $jobCreate;

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
                'cloneId'           => 'string',
                'allTablesExcluded' => 'bool',
            ]);
            $data['name']                = isset($_POST['wpstgCreateData']['name']) ? Sanitize::sanitizeString($_POST['wpstgCreateData']['name']) : '';
            $data['excludedTables']      = isset($_POST['wpstgCreateData']['excludedTables']) ? $this->parseAndSanitizeTables($_POST['wpstgCreateData']['excludedTables']) : []; // phpcs:ignore
            $data['includedTables']      = isset($_POST['wpstgCreateData']['includedTables']) ? $this->parseAndSanitizeTables($_POST['wpstgCreateData']['includedTables']) : []; // phpcs:ignore
            $data['nonSiteTables']       = isset($_POST['wpstgCreateData']['nonSiteTables']) ? $this->parseAndSanitizeTables($_POST['wpstgCreateData']['nonSiteTables']) : []; // phpcs:ignore
            $data['excludedDirectories'] = isset($_POST['wpstgCreateData']['excludedDirectories']) ? Sanitize::sanitizeString($_POST['wpstgCreateData']['excludedDirectories']) : [];
            $data['extraDirectories']    = isset($_POST['wpstgCreateData']['extraDirectories']) ? Sanitize::sanitizeString($_POST['wpstgCreateData']['extraDirectories']) : [];
            $data['excludeGlobRules']    = isset($_POST['wpstgCreateData']['excludeGlobRules']) ? Sanitize::sanitizeString($_POST['wpstgCreateData']['excludeGlobRules']) : [];
            $data = array_merge($data, $this->getAdvanceSettings());
        }

        try {
            $sanitizedData = $this->setupInitialData($data);
        } catch (\Exception $e) {
            return new \WP_Error(400, $e->getMessage());
        }

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

        $defaults = [
            'cloneId'             => time(),
            'name'                => '',
            'allTablesExcluded'   => false,
            'excludedTables'      => [],
            'includedTables'      => [],
            'nonSiteTables'       => [],
            'excludedDirectories' => [],
            'extraDirectories'    => [],
            'excludeGlobRules'    => [],
        ];

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
        $data['excludeGlobRules']    = array_map('sanitize_text_field', $data['excludeGlobRules']);

        $data = $this->validateAndSanitizeAdvanceSettingsData($data);

        $data['stagingSitePath'] = $this->getDestinationPath($data);
        $data['stagingSiteUrl']  = $this->getDestinationUrl($data);

        // Prepare staging site dto with `unfinished` status
        $data['stagingSite']     = $this->prepareStagingSiteDto($data);

        return $data;
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
        $this->jobCreate = $services->get(StagingSiteCreate::class);

        $this->jobDataDto->hydrate($sanitizedData);
        $this->jobDataDto->setInit(true);
        $this->jobDataDto->setFinished(false);
        $this->jobDataDto->setStartTime(time());

        $this->setDatabasePrefix();

        $this->jobDataDto->setId(substr(md5(mt_rand() . time()), 0, 12));

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

    protected function setDatabasePrefix()
    {
        $this->jobDataDto->setDatabasePrefix($this->findDatabasePrefix());
    }

    protected function parseAndSanitizeTables(string $tables)
    {
        $tables = $tables === '' ? [] : explode(ScanConst::DIRECTORIES_SEPARATOR, $tables);

        return array_map('sanitize_text_field', $tables);
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

        if (empty($data['customPath'])) {
            return $absPath . $data['name'];
        }

        $customPath = trailingslashit($this->filesystem->normalizePath($data['customPath']));

        // Throw fatal error
        if ($customPath === $absPath) {
            throw new RuntimeException('Error: Target path must be different from the root of the current website.');
        }

        return $customPath;
    }

    protected function getDestinationUrl(array $data): string
    {
        if (!empty($data['customUrl'])) {
            return $this->getHostnameWithoutScheme($data['customUrl']);
        }

        return trailingslashit($this->getHostnameWithoutScheme(home_url())) . $data['name'];
    }

    /**
     * Return Hostname without scheme
     * @param string $string
     * @return string
     */
    protected function getHostnameWithoutScheme(string $string): string
    {
        return preg_replace('#^https?://#', '', rtrim($string, '/'));
    }

    protected function prepareStagingSiteDto(array $data): StagingSiteDto
    {
        $stagingSite = new StagingSiteDto();
        $stagingSite->setCloneId($data['cloneId']);
        $stagingSite->setCloneName($data['name']);
        $stagingSite->setPath($data['stagingSitePath']);
        $stagingSite->setUrl($data['stagingSiteUrl']);
        $stagingSite->setStatus(StagingSiteDto::STATUS_UNFINISHED_BROKEN);
        $stagingSite->setDatabasePrefix($data['databasePrefix']);
        if ($data['useCustomDatabase']) {
            $stagingSite->setDatabaseServer($data['databaseServer']);
            $stagingSite->setDatabaseDatabase($data['databaseName']);
            $stagingSite->setDatabaseUser($data['databaseUser']);
            $stagingSite->setDatabasePassword($data['databasePassword']);
            $stagingSite->setDatabaseSsl($data['databaseSsl']);
        }

        return $stagingSite;
    }
}
