<?php

namespace WPStaging\Framework\Filesystem;

use WPStaging\Framework\Adapter\Directory;
use WPStaging\Backup\Exceptions\DiskNotWritableException;

class DiskWriteCheck
{
    protected $directory;
    protected $filesystem;
    protected $reservedMemory;

    const OPTION_DISK_WRITABLE_FAILED = 'wpstg_disk_writable_check_failed';

    public function __construct(Filesystem $filesystem, Directory $directory)
    {
        $this->directory = $directory;
        $this->filesystem = $filesystem;
        // 1kb
        $this->reservedMemory = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    }

    /**
     * @param string    $path         An absolute path to check for free disk space.
     * @param int|float $bytesToStore The number of bytes intended to be written.
     *
     * @throws \RuntimeException When something happened that prevented us from checking if there's enough free disk space.
     * @throws DiskNotWritableException When disk_free_space reports there's not enough disk space to store this amount of bytes.
     */
    public function checkPathCanStoreEnoughBytes($path, $bytesToStore)
    {
        // Early bail: Disabled by filter
        if (apply_filters('wpstg.filesystem.disableDiskFreeSpaceCheck', false)) {
            throw new \RuntimeException();
        }

        // Early bail: disk_free_space might have been disabled using php.ini "disable_functions"
        if (!function_exists('disk_free_space')) {
            throw new \RuntimeException('The disk_free_space function is not available.');
        }

        $path = untrailingslashit($path);

        clearstatcache();
        if (!file_exists($path)) {
            throw new \RuntimeException('The given path does not exist.');
        }

        if (is_link($path)) {
            throw new \RuntimeException('The given path must be a directory.');
        }

        if (!is_dir($path)) {
            throw new \RuntimeException('The path must be a directory.');
        }

        $freeSpaceInBytes = @disk_free_space($path);

        if ($freeSpaceInBytes === false) {
            $message = '';
            $error = error_get_last();

            if (is_array($error) && array_key_exists('message', $error)) {
                $message = $error['message'];
            }

            throw new \RuntimeException($message);
        }

        if (!is_numeric($freeSpaceInBytes)) {
            throw new \RuntimeException('disk_free_space returned an unexpected result');
        }

        if ($freeSpaceInBytes - $bytesToStore < 0) {
            throw DiskNotWritableException::willExceedFreeDiskSpace(abs($freeSpaceInBytes - $bytesToStore));
        }
    }

    /**
     * @throws DiskNotWritableException If a previous disk write test has failed.
     */
    public function hasDiskWriteTestFailed()
    {
        if (get_option(self::OPTION_DISK_WRITABLE_FAILED) === 'fail') {
            throw DiskNotWritableException::diskNotWritable();
        }
    }

    /**
     * @return bool
     * @throws DiskNotWritableException
     * @throws FilesystemExceptions
     */
    public function testDiskIsWriteable()
    {
        $destination = $this->directory->getPluginUploadsDirectory() . '.wpstgDiskWriteCheck';

        if (file_exists($destination)) {
            unlink($destination);
        }

        // Early bail: Disk writeable check pass
        if (@file_put_contents($destination, $this->reservedMemory)) {
            unlink($destination);

            delete_option(self::OPTION_DISK_WRITABLE_FAILED);

            return true;
        }

        // First try, this might fail as the disk is full.
        $result = $this->setLowLevelDiskFullFlag();

        $this->filesystem->delete($this->directory->getCacheDirectory());

        // Second try, this might succeed if the first failed as we freed up a few kb of data.
        if (!$result) {
            $result = $this->setLowLevelDiskFullFlag();
        }

        $this->filesystem->delete($this->directory->getTmpDirectory());

        // Third try, this should succeed if the second failed, but it's the tmp directory can be very big and the request might timeout before getting here.
        if (!$result) {
            $result = $this->setLowLevelDiskFullFlag();

            if (!$result) {
                \WPStaging\functions\debug_log('WP STAGING DiskWriteCheck failed and could not update the option in the database.');
            }
        }

        throw DiskNotWritableException::diskNotWritable();
    }

    protected function setLowLevelDiskFullFlag()
    {
        global $wpdb;

        return $wpdb->query($wpdb->prepare("INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`)", self::OPTION_DISK_WRITABLE_FAILED, 'fail', 'no'));
    }
}
