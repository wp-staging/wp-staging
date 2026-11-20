<?php

namespace WPStaging\Backup\Ajax\Restore;

use WPStaging\Backup\Dto\Job\JobRestoreDataDto;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Job\JobRestoreProvider;
use WPStaging\Backup\Job\Jobs\JobRestore;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Database\TableService;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Job\Ajax\PrepareJob;
use WPStaging\Framework\Job\JobTransientCache;
use WPStaging\Framework\Job\ProcessLock;
use WPStaging\Framework\Job\Traits\WithTmpDatabasePrefix;
use WPStaging\Framework\Security\Auth;
use WPStaging\Backup\Service\Encryption\BackupEncryptionService;

class PrepareRestore extends PrepareJob
{
    use WithTmpDatabasePrefix;

 
    private $jobDataDto;

 
    private $jobRestore;

 
    private $tableService;

    public function __construct(Filesystem $filesystem, Directory $directory, Auth $auth, ProcessLock $processLock, TableService $tableService)
    {
        parent::__construct($filesystem, $directory, $auth, $processLock);
        $this->tableService = $tableService;
    }

    public function prepare($data = null)
    {
 
        $container        = WPStaging::getInstance()->getContainer();
        $this->jobDataDto = $container->get(JobRestoreDataDto::class);
        $this->jobRestore = $container->get(JobRestoreProvider::class)->getJob();

        if (empty($data) && array_key_exists('wpstgRestoreData', $_POST)) {
            $data = Sanitize::sanitizeArray($_POST['wpstgRestoreData'], [
                'backupMetadata'                 => 'array',
                'headerStart'                    => 'int',
                'headerEnd'                      => 'int',
                'totalFiles'                     => 'int',
                'totalDirectories'               => 'int',
                'maxTableLength'                 => 'int',
                'databaseFileSize'               => 'int',
                'backupSize'                     => 'int',
                'blogId'                         => 'int',
                'networkId'                      => 'int',
                'dateCreated'                    => 'int',
                'isAutomatedBackup'              => 'bool',
                'phpShortOpenTags'               => 'bool',
                'wpBakeryActive'                 => 'bool',
                'subdomainInstall'               => 'bool',
                'isExportingPlugins'             => 'bool',
                'isExportingMuPlugins'           => 'bool',
                'isExportingThemes'              => 'bool',
                'isExportingUploads'             => 'bool',
                'isExportingOtherWpContentFiles' => 'bool',
                'isExportingOtherWpRootFiles'    => 'bool',
                'isExportingDatabase'            => 'bool',
                'encryptionPassword'             => 'password',
            ]);
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
        $defaults = [
            'file'               => '',
            'encryptionPassword' => '',
            'decryptionKey'      => '',
            'hmacKey'            => '',
        ];

        $data = wp_parse_args($data, $defaults);

 
        $data = array_intersect_key($data, $defaults);

        if ($data['file'] === '') {
            throw new \UnexpectedValueException("Invalid request. Missing 'file'.");
        }

        $this->prepareEncryptionData($data);

        return $data;
    }





    private function prepareEncryptionData(array &$data)
    {
        $metadata = (new BackupMetadata())->hydrateByFilePath($data['file']);
        if (!$metadata->getIsEncrypted()) {
            return;
        }

        if ($data['encryptionPassword'] === '') {
            throw new \UnexpectedValueException(__('Please enter the password for this encrypted backup.', 'wp-staging'));
        }

        $salt = base64_decode($metadata->getEncryptionSalt(), true);
        if ($salt === false || $metadata->getEncryptionVerificationToken() === '') {
            throw new \UnexpectedValueException(__('This encrypted backup is missing password verification metadata.', 'wp-staging'));
        }

 
        $encryptionService = WPStaging::make(BackupEncryptionService::class);
        list($encKey, $macKey) = $encryptionService->deriveKeys($data['encryptionPassword'], $salt);
        if (!$encryptionService->verifyKey($encKey, $metadata->getEncryptionVerificationToken())) {
            throw new \UnexpectedValueException(__('The backup encryption password is incorrect.', 'wp-staging'));
        }

        $data['decryptionKey']      = base64_encode($encKey);
        $data['hmacKey']            = base64_encode($macKey);
        $data['encryptionPassword'] = '';
    }

    public function getJob()
    {
        return $this->jobRestore;
    }

    public function persist(): bool
    {
        if (!$this->jobRestore instanceof JobRestore) {
            return false;
        }

        $this->jobRestore->persist();

        return true;
    }

    protected function setupInitialData($sanitizedData): array
    {
        $sanitizedData = $this->validateAndSanitizeData($sanitizedData);
        $this->clearCacheFolder();

        $this->jobDataDto->hydrate($sanitizedData);
        $this->jobDataDto->setInit(true);
        $this->jobDataDto->setFinished(false);
        $this->jobDataDto->setTmpDatabasePrefix($this->getTmpDatabasePrefix());

        $this->jobDataDto->generateId();

        $this->jobRestore->setJobDataDto($this->jobDataDto);

        if (!$this->jobDataDto->getIsSyncRequest()) {
            $this->jobRestore->getTransientCache()->startJob($this->jobDataDto->getId(), esc_html__('Restore in Progress', 'wp-staging'), JobTransientCache::JOB_TYPE_RESTORE, $this->queueId, basename($this->jobDataDto->getFile()));
        }

        return $sanitizedData;
    }
}
