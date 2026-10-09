<?php

namespace WPStaging\Backup\Transfer;

use WPStaging\Framework\Exceptions\WPStagingException;




class TransferSessionException extends WPStagingException
{
 
    const CODE_ALREADY_PREPARING = 409;

    public static function invalidRequest(): self
    {
        return new self(__('Invalid request.', 'wp-staging'));
    }

    public static function sourceFileNotFound(): self
    {
        return new self(__('The backup file no longer exists.', 'wp-staging'));
    }

    public static function sourceFileNotReadable(): self
    {
        return new self(__('The backup file could not be read. Please check the file permissions.', 'wp-staging'));
    }

    public static function backupStillBeingWritten(): self
    {
        return new self(__('The backup is still being created and cannot be downloaded yet.', 'wp-staging'));
    }

    public static function transferDisabled(): self
    {
        return new self(__('Secure download links are disabled on this site.', 'wp-staging'));
    }

    public static function cannotCreateTransferDirectory(): self
    {
        return new self(__('Could not create the temporary transfer directory. Please check the file permissions and the available disk space.', 'wp-staging'));
    }

    public static function transferDirectoryNotPublic(): self
    {
        return new self(__('The temporary transfer folder is not reachable over HTTP, so no download link can be created. Move it inside the site directory or set its URL with the wpstg.transfer_session.base_url filter.', 'wp-staging'));
    }

    public static function notEnoughDiskSpace(): self
    {
        return new self(__('There is not enough free disk space to create a temporary copy of this backup.', 'wp-staging'));
    }

    public static function backupTooLargeToCopy(string $formattedSize, string $formattedLimit): self
    {
        return new self(sprintf(
            /* translators: 1: size of the backup, 2: largest size that can still be copied. */
            __('This server cannot create hardlinks, so a temporary copy of the backup would have to be made, and at %1$s this backup is larger than the %2$s a single request can copy. Move the backup directory to the same filesystem as wp-content, or raise the limit with the wpstg.transfer_session.max_copy_size filter.', 'wp-staging'),
            $formattedSize,
            $formattedLimit
        ));
    }

    public static function artifactCreationFailed(): self
    {
        return new self(__('The temporary download link could not be prepared.', 'wp-staging'));
    }

    public static function sessionNotFound(): self
    {
        return new self(__('The transfer link expired. Please create a new one.', 'wp-staging'));
    }

    public static function alreadyPreparing(): self
    {
        return new self(__('A secure download link for this backup is already being prepared.', 'wp-staging'), self::CODE_ALREADY_PREPARING);
    }

    public static function unreadableLinkFolderNotCleared(): self
    {
        return new self(__('An older temporary download link for this backup was created with WordPress security keys this site no longer has, and a folder in wp-content/wp-staging/transfers could not be cleared. Empty wp-content/wp-staging/transfers and revoke again.', 'wp-staging'));
    }

 
    public function isAlreadyPreparing(): bool
    {
        return $this->getCode() === self::CODE_ALREADY_PREPARING;
    }

    public static function cannotDeleteArtifact(): self
    {
        return new self(__('Could not delete the temporary transfer file. Please check the file permissions.', 'wp-staging'));
    }

    public static function storageUnavailable(): self
    {
        return new self(__('The transfer session could not be stored. Please check the database permissions.', 'wp-staging'));
    }

    public static function encryptionUnavailable(): self
    {
        return new self(__('The secure download link could not be protected, because this site has no security keys in its wp-config.php. Add AUTH_KEY and AUTH_SALT, then try again.', 'wp-staging'));
    }
}
