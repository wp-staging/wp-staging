<?php

namespace WPStaging\Backup\Ajax\Restore;

use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Security\Auth;

class ReadBackupMetadata
{
    private $auth;
    private $backupsFinder;

    public function __construct(Auth $auth, BackupsFinder $backupsFinder)
    {
        $this->auth = $auth;
        $this->backupsFinder = $backupsFinder;
    }

    public function ajaxPrepare($data)
    {
        if (!$this->auth->isAuthenticatedRequest()) {
            wp_send_json_error(null, 401);
        }

        $response = $this->prepare($data);

        if ($response instanceof \WP_Error) {
            wp_send_json_error($response->get_error_message(), $response->get_error_code());
        } else {
            wp_send_json_success($response);
        }
    }

    public function prepare($data = null)
    {
        if (empty($data) && array_key_exists('wpstg', $_POST)) {
            $data = Sanitize::sanitizeArray($_POST['wpstg'], [
                'file' => 'path'
            ]);
        }

        if (!is_array($data)) {
            throw new \UnexpectedValueException('Data is not an array. Type: ' . gettype($data));
        }

        try {
            if (!array_key_exists('file', $data)) {
                throw new \UnexpectedValueException('Missing file', 400);
            }

            // Find the given backup in the filesystem
            $matchingBackup = array_filter($this->backupsFinder->findBackups(), function (\SplFileInfo $fileInfo) use ($data) {
                return basename($data['file']) === $fileInfo->getBasename();
            });

            if (empty($matchingBackup)) {
                throw new \UnexpectedValueException('Could not find backup file', 400);
            }

            $matchingBackup = array_shift($matchingBackup);

            if (!$matchingBackup instanceof \SplFileInfo) {
                throw new \UnexpectedValueException('Could not find backup file', 400);
            }

            $metadata = (new BackupMetadata())->hydrateByFilePath($matchingBackup->getPathname());

            return json_encode($metadata);
        } catch (\Exception $e) {
            return new \WP_Error($e->getCode(), $e->getMessage());
        }
    }
}
