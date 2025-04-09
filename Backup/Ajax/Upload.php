<?php

namespace WPStaging\Backup\Ajax;

use Exception;
use WPStaging\Backup\BackupRepairer;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Backup\Task\Tasks\JobRestore\RestoreRequirementsCheckTask;
use WPStaging\Backup\WithBackupIdentifier;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;
use WPStaging\Framework\Filesystem\DiskWriteCheck;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Security\Otp\Otp;
use WPStaging\Framework\Security\Otp\OtpDisabledException;
use WPStaging\Framework\Security\Otp\OtpException;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Framework\Utils\Sanitize;

use function WPStaging\functions\debug_log;

class Upload extends AbstractTemplateComponent
{
    use WithBackupIdentifier;

    /**
     * @var string
     */
    const OPTION_UPLOAD_PREPARED = 'wpstg.backups.upload_prepared';

    /** @var BackupsFinder */
    private $backupsFinder;

    /** @var BackupRepairer */
    private $backupRepairer;

    /** @var Sanitize */
    private $sanitize;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Otp
     */
    private $otpService;

    public function __construct(BackupsFinder $backupsFinder, TemplateEngine $templateEngine, BackupRepairer $backupRepairer, Sanitize $sanitize, Filesystem $filesystem, Otp $otpService)
    {
        parent::__construct($templateEngine);
        $this->backupsFinder  = $backupsFinder;
        $this->backupRepairer = $backupRepairer;
        $this->sanitize       = $sanitize;
        $this->filesystem     = $filesystem;
        $this->otpService     = $otpService;
    }

    public function ajaxPrepareUpload()
    {
        if (!$this->canRenderAjax()) {
            wp_send_json_error([
                'message' => esc_html__('Invalid Request!', 'wp-staging')
            ], 401);
        }

        try {
            $this->otpService->validateOtpRequest();
        } catch (OtpDisabledException $ex) {
            debug_log($ex->getMessage());
        } catch (OtpException $ex) {
            wp_send_json_error([
                'message' => esc_html($ex->getMessage()),
            ], $ex->getCode());
        }

        delete_option(self::OPTION_UPLOAD_PREPARED);
        if (!update_option(self::OPTION_UPLOAD_PREPARED, 'true')) {
            wp_send_json_error([
                'message' => __('Could not prepare backup upload', 'wp-staging'),
            ], 500);
        }

        wp_send_json_success();
    }

    public function render()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        /**
         * Example:
         *
         * name = "8xx.290.myftpupload.com_20210521-193355_967950a65d39 (2).wpstg"
         * type = "application/octet-stream"
         * tmp_name = "/tmp/phpYFrjBk"
         * error = {int} 0
         * size = {int} 1048576
         */
        $file = isset($_FILES['file']) ? $this->sanitize->sanitizeFileUpload($_FILES['file']) : null;

        try {
            $this->validateRequestData($file);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => !empty($e->getMessage()) ? $e->getMessage() : 'Invalid request data',
            ], 400);
        }

        $resumableChunkNumber      = isset($_GET['resumableChunkNumber']) ? $this->sanitize->sanitizeInt($_GET['resumableChunkNumber'], true) : 0;
        $resumableChunkSize        = isset($_GET['resumableChunkSize']) ? $this->sanitize->sanitizeInt($_GET['resumableChunkSize'], true) : 0;
        $resumableCurrentChunkSize = isset($_GET['resumableCurrentChunkSize']) ? $this->sanitize->sanitizeInt($_GET['resumableCurrentChunkSize'], true) : 0;
        $resumableTotalSize        = isset($_GET['resumableTotalSize']) ? $this->sanitize->sanitizeInt($_GET['resumableTotalSize'], true) : 0;
        $resumableTotalChunks      = isset($_GET['resumableTotalChunks']) ? $this->sanitize->sanitizeInt($_GET['resumableTotalChunks'], true) : 0;
        $uniqueIdentifierSuffix    = isset($_GET['uniqueIdentifierSuffix']) ? $this->sanitize->sanitizeString($_GET['uniqueIdentifierSuffix']) : '';
        $resumableIdentifier       = isset($_GET['resumableIdentifier']) ? sanitize_file_name($_GET['resumableIdentifier']) : '';
        $resumableFilename         = isset($_GET['resumableFilename']) ? sanitize_file_name($_GET['resumableFilename']) : '';
        $resumableRelativePath     = isset($_GET['resumableRelativePath']) ? sanitize_file_name($_GET['resumableRelativePath']) : '';

        $originalPath = $this->backupsFinder->getBackupsDirectory() . $resumableFilename;
        if ($this->isBackupPart($originalPath) && file_exists($originalPath)) {
            wp_send_json_error([
                'message' => __('This backup part exists already', 'wp-staging'),
            ], 409); // 409 status code for conflict
        }

        $fullPath = $this->backupsFinder->getBackupsDirectory() . $uniqueIdentifierSuffix . $resumableFilename . '.uploading';
        // If neither uploading file or the upload prepared option that mean that the upload is not prepared and didn't pass through the OTP process!
        if (!file_exists($fullPath) && get_option(self::OPTION_UPLOAD_PREPARED) !== 'true') {
            wp_send_json_error([
                'message' => __('The backup file is missing or the upload process is not ready. Try again to upload the backup file or open a support request.', 'wp-staging'),
            ], 500);
        }

        delete_option(self::OPTION_UPLOAD_PREPARED);
        $resumableInternalIdentifier = md5($fullPath);

        // Check free disk space on the first request
        if ($resumableChunkNumber <= 1) {
            try {
                WPStaging::make(DiskWriteCheck::class)->checkPathCanStoreEnoughBytes($this->backupsFinder->getBackupsDirectory(), $resumableTotalSize);
            } catch (DiskNotWritableException $e) {
                wp_send_json_error([
                    'message'    => $e->getMessage(),
                    'isDiskFull' => true,
                ], 507);
            } catch (\RuntimeException $e) {
                // no-op
            }
        }

        // Assert chunks are in sequential order
        if ($resumableChunkNumber > 1 && $resumableTotalChunks > 1) {
            $nextExpectedChunk = (int)get_transient("wpstg.upload.nextExpectedChunk.$resumableInternalIdentifier");

            if ($nextExpectedChunk !== $resumableChunkNumber) {
                // 409 would make more sense, but let's throw a 418 in tribute to the only person in the world capable to laugh at this joke.
                wp_send_json_error('', 418);
            }
        }

        update_option('wpstg.backups.doing_upload', true);

        try {
            $result = file_put_contents($fullPath, file_get_contents($file['tmp_name']), FILE_APPEND);

            if (!$result) {
                // Do a disk_free_space() check
                try {
                    WPStaging::make(DiskWriteCheck::class)->checkPathCanStoreEnoughBytes($this->backupsFinder->getBackupsDirectory());
                } catch (\RuntimeException $e) {
                    // no-op
                }

                // If that succeeds or could not be determined, also do a real write check.
                WPStaging::make(DiskWriteCheck::class)->testDiskIsWriteable();
            }
        } catch (DiskNotWritableException $e) {
            delete_option('wpstg.backups.doing_upload');

            wp_send_json_error([
                'message'    => $e->getMessage(),
                'isDiskFull' => true,
            ], 507);
        } catch (\Exception $e) {
            delete_option('wpstg.backups.doing_upload');

            wp_send_json_error([
                'message' => $e->getMessage(),
            ], 500);
        }

        // Last chunk?
        if ($resumableChunkNumber === $resumableTotalChunks) {
            try {
                delete_transient("wpstg.upload.nextExpectedChunk.$resumableInternalIdentifier");
                if ($this->isBackupPart($originalPath)) {
                    rename($fullPath, $originalPath);
                    return;
                }

                $this->validateBackupFile($fullPath);
                rename($fullPath, $this->backupsFinder->getBackupsDirectory() . $uniqueIdentifierSuffix . $resumableFilename);
            } catch (\Exception $e) {
                if (file_exists($fullPath) && is_file($fullPath)) {
                    unlink($fullPath);
                }

                wp_send_json_error([
                    'message'                => $e->getMessage(),
                    'backupFailedValidation' => true,
                ], 500);
            }
        } else {
            // Set the next expected chunk, to avoid scenarios where an erratic network connection could skip chunks or send them in unexpected order, eg:
            // chunk.part.1
            // chunk.part.2
            // chunk.part.4 <-- not what we want!
            // chunk.part.3
            set_transient("wpstg.upload.nextExpectedChunk.$resumableInternalIdentifier", $resumableChunkNumber + 1, 1 * DAY_IN_SECONDS);
        }

        delete_option('wpstg.backups.doing_upload');

        wp_send_json_success();
    }

    public function ajaxDeleteIncompleteUploads()
    {
        if (!$this->canRenderAjax()) {
            wp_send_json_error(esc_html__('You do not have sufficient permissions to access this page.', 'wp-staging'));
        }

        try {
            /** @var \SplFileInfo $splFileInfo */
            foreach (new \DirectoryIterator($this->backupsFinder->getBackupsDirectory()) as $splFileInfo) {
                if ($splFileInfo->isFile() && !$splFileInfo->isLink() && $splFileInfo->getExtension() === 'uploading') {
                    unlink($splFileInfo->getPathname());
                }
            }

            wp_send_json_success();
        } catch (\Exception $e) {
            wp_send_json_error();
        }
    }

    protected function validateBackupFile($fullPath)
    {
        clearstatcache();
        $backupMetadata = new BackupMetadata();
        $metadata       = $backupMetadata->hydrateByFilePath($fullPath);

        $isCreatedOnPro = $metadata->getCreatedOnPro();
        $version        = $metadata->getWpstgVersion();

        if ($isCreatedOnPro && version_compare($version, RestoreRequirementsCheckTask::BETA_VERSION_LIMIT_PRO, '<')) {
            throw new Exception(__('This backup was generated on a beta version of WP STAGING and can not be used with this version. Please create a new Backup or get in touch with our support if you need assistance.', 'wp-staging'));
        }

        $estimatedSize = $metadata->getBackupSize();
        $isSplitBackup = $metadata->getIsMultipartBackup();

        // Repairing the backup size in metadata
        if ($estimatedSize === 0 && !$isSplitBackup) {
            $this->backupRepairer->repairMetadataSize($fullPath);
            return;
        }

        $realSize         = filesize($fullPath);
        $allowedDifferece = 1 * KB_IN_BYTES;

        $smallerThanExpected = $realSize + $allowedDifferece - $estimatedSize < 0;
        $biggerThanExpected  = $realSize - $allowedDifferece > $estimatedSize;

        if ($smallerThanExpected || $biggerThanExpected) {
            throw new Exception(sprintf(__('The backup size (%s) is different than expected (%s). If this issue persists, upload the file directly to this folder using FTP: <strong>wp-content/uploads/wp-staging/backups</strong>', 'wp-staging'), size_format($realSize, 2), size_format($estimatedSize)));
        }
    }

    protected function validateRequestData($file)
    {
        if (empty($_FILES) || !isset($_FILES['file']) || !is_array($file)) {
            throw new Exception();
        }

        switch ((int)$file['error']) {
            case UPLOAD_ERR_OK:
                // Ok, no-op
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
            case UPLOAD_ERR_PARTIAL:
            case UPLOAD_ERR_NO_FILE:
            case UPLOAD_ERR_NO_TMP_DIR:
            case UPLOAD_ERR_CANT_WRITE:
            case UPLOAD_ERR_EXTENSION:
                throw new Exception();
        }

        if (empty($file['tmp_name']) || !file_exists($file['tmp_name'])) {
            throw new Exception();
        }

        if (!empty($file['name']) && !$this->filesystem->isWpstgBackupFile($file['name'])) {
            throw new Exception(sprintf(__('Invalid backup file extension: %s', 'wp-staging'), $file['name']));
        }

        /**
         * Example:
         *
         * resumableChunkNumber = "1"
         * resumableChunkSize = "1048576"
         * resumableCurrentChunkSize = "1048576"
         * resumableTotalSize = "14209912"
         * resumableType = ""
         * resumableIdentifier = "14209912-multitestswp-staginglocal_fcd2fae486dcwpstg"
         * resumableFilename = "multi.tests.wp-staging.local_fcd2fae486dc.wpstg"
         * resumableRelativePath = "multi.tests.wp-staging.local_fcd2fae486dc.wpstg"
         * resumableTotalChunks = "13"
         */
        $requiredValues = [
            'resumableChunkNumber',
            'resumableChunkSize',
            'resumableCurrentChunkSize',
            'resumableTotalSize',
            'resumableIdentifier',
            'resumableFilename',
            'resumableRelativePath',
            'resumableTotalChunks',
        ];

        foreach ($requiredValues as $requiredValue) {
            if (!isset($_GET[$requiredValue])) {
                throw new Exception();
            }
        }

        $numericValues = [
            'resumableChunkNumber',
            'resumableChunkSize',
            'resumableCurrentChunkSize',
            'resumableTotalSize',
            'resumableTotalChunks',
        ];

        foreach ($numericValues as $numericValue) {
            if (!isset($_GET[$numericValue])) {
                throw new Exception();
            }

            if (!filter_var($_GET[$numericValue], FILTER_VALIDATE_INT)) {
                throw new Exception();
            }
        }

        $nonEmptyValues = [
            'resumableIdentifier',
            'resumableFilename',
            'resumableRelativePath',
        ];

        foreach ($nonEmptyValues as $nonEmptyValue) {
            if (empty($_GET[$nonEmptyValue])) {
                throw new Exception();
            }
        }
    }
}
