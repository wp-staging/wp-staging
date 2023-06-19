<?php

namespace WPStaging\Backup\Ajax\Restore;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Database\TableService;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Security\Auth;
use WPStaging\Backup\Ajax\PrepareJob;
use WPStaging\Backup\BackupProcessLock;
use WPStaging\Backup\Dto\Job\JobRestoreDataDto;
use WPStaging\Backup\Exceptions\ProcessLockedException;
use WPStaging\Backup\Job\JobRestoreProvider;
use WPStaging\Backup\Job\Jobs\JobRestore;

class PrepareRestore extends PrepareJob
{
    /** @var JobRestoreDataDto*/
    private $jobDataDto;

    /** @var JobRestore */
    private $jobRestore;

    /** @var TableService */
    private $tableService;

    /** @var string */
    const CUSTOM_TMP_PREFIX_FILTER = 'wpstg.restore.tmp_database_prefix';

    /** @var string */
    const TMP_DATABASE_PREFIX = 'wpstgtmp_';

    /**
     * The prefix used when dropping a table. Same length as TMP_DATABASE_PREFIX
     * to avoid extrapolating the limit of 64 characters for a table name.
     *
     * @var string
     */
    const TMP_DATABASE_PREFIX_TO_DROP = 'wpstgbak_';

    public function __construct(Filesystem $filesystem, Directory $directory, Auth $auth, BackupProcessLock $processLock, TableService $tableService)
    {
        parent::__construct($filesystem, $directory, $auth, $processLock);
        $this->tableService = $tableService;
    }

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

        // Lazy-instantiation to avoid process-lock checks conflicting with running processes.
        $container        = WPStaging::getInstance()->getContainer();
        $this->jobDataDto = $container->get(JobRestoreDataDto::class);
        $this->jobRestore = $container->get(JobRestoreProvider::class)->getJob();

        $response = $this->prepare($data);

        if ($response instanceof \WP_Error) {
            wp_send_json_error($response->get_error_message(), $response->get_error_code());
        } else {
            wp_send_json_success();
        }
    }

    public function prepare($data = null)
    {
        if (empty($data) && array_key_exists('wpstgRestoreData', $_POST)) {
            $data = Sanitize::sanitizeArray($_POST['wpstgRestoreData'], [
                'backupMetadata' => 'array',
                'headerStart' => 'int',
                'headerEnd' => 'int',
                'totalFiles' => 'int',
                'totalDirectories' => 'int',
                'maxTableLength' => 'int',
                'databaseFileSize' => 'int',
                'backupSize' => 'int',
                'blogId' => 'int',
                'networkId' => 'int',
                'dateCreated' => 'int',
                'isAutomatedBackup' => 'bool',
                'phpShortOpenTags' => 'bool',
                'wpBakeryActive' => 'bool',
                'subdomainInstall' => 'bool',
                'isExportingPlugins' => 'bool',
                'isExportingMuPlugins' => 'bool',
                'isExportingThemes' => 'bool',
                'isExportingUploads' => 'bool',
                'isExportingOtherWpContentFiles' => 'bool',
                'isExportingDatabase' => 'bool'
            ]);
        }

        try {
            $sanitizedData = $this->setupInitialData($data);
        } catch (\Exception $e) {
            return new \WP_Error(400, $e->getMessage());
        }

        return $sanitizedData;
    }

    private function setupInitialData($sanitizedData)
    {
        $sanitizedData = $this->validateAndSanitizeData($sanitizedData);
        $this->clearCacheFolder();

        $this->jobDataDto->hydrate($sanitizedData);
        $this->jobDataDto->setInit(true);
        $this->jobDataDto->setFinished(false);
        $this->jobDataDto->setTmpDatabasePrefix($this->getTmpDatabasePrefix());

        $this->jobDataDto->setId(substr(md5(mt_rand() . time()), 0, 12));

        $this->jobRestore->setJobDataDto($this->jobDataDto);

        return $sanitizedData;
    }

    /**
     * @return string
     */
    protected function getTmpDatabasePrefix()
    {
        $tmpDatabasePrefix = apply_filters(self::CUSTOM_TMP_PREFIX_FILTER, self::TMP_DATABASE_PREFIX);
        if ($tmpDatabasePrefix === self::TMP_DATABASE_PREFIX) {
            return self::TMP_DATABASE_PREFIX;
        }

        if ($this->isTmpPrefixAvailable($tmpDatabasePrefix)) {
            return $tmpDatabasePrefix;
        }

        return self::TMP_DATABASE_PREFIX;
    }

    /**
     * @param string $tmpDatabasePrefix
     * @return bool
     */
    protected function isTmpPrefixAvailable($tmpDatabasePrefix)
    {
        if (count($this->tableService->findTableNamesStartWith($tmpDatabasePrefix)) > 0) {
            return false;
        }

        if (count($this->tableService->findViewsNamesStartWith($tmpDatabasePrefix)) > 0) {
            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    private function validateAndSanitizeData($data)
    {
        $expectedKeys = [
            'file',
        ];

        // Make sure data has no keys other than the expected ones.
        $data = array_intersect_key($data, array_flip($expectedKeys));

        // Make sure data has all expected keys.
        foreach ($expectedKeys as $expectedKey) {
            if (!array_key_exists($expectedKey, $data)) {
                throw new \UnexpectedValueException("Invalid request. Missing '$expectedKey'.");
            }
        }

        return $data;
    }
}
