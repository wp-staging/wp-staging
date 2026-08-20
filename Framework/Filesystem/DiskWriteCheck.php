<?php

namespace WPStaging\Framework\Filesystem;

use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;

class DiskWriteCheck
{
 
    const OPTION_DISK_WRITABLE_FAILED = 'wpstg_disk_writable_check_failed';

 
    const FILTER_FILESYSTEM_DISABLED_DISK_FREE_SPACE_CHECK = 'wpstg.filesystem.disableDiskFreeSpaceCheck';

    protected $directory;

    protected $filesystem;

    protected $reservedMemory;

    public function __construct(Filesystem $filesystem, Directory $directory)
    {
        $this->directory = $directory;
        $this->filesystem = $filesystem;
 
        $this->reservedMemory = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    }








    public function checkPathCanStoreEnoughBytes($path, $bytesToStore)
    {
 
        if (Hooks::applyFilters(self::FILTER_FILESYSTEM_DISABLED_DISK_FREE_SPACE_CHECK, false)) {
            throw new \RuntimeException();
        }

 
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




    public function hasDiskWriteTestFailed()
    {
        if (get_option(self::OPTION_DISK_WRITABLE_FAILED) === 'fail') {
            throw DiskNotWritableException::diskNotWritable();
        }
    }






    public function testDiskIsWriteable()
    {
        $destination = $this->directory->getPluginUploadsDirectory() . '.wpstgDiskWriteCheck';

        if (file_exists($destination)) {
            unlink($destination);
        }

 
        if (@file_put_contents($destination, $this->reservedMemory)) {
            unlink($destination);

            delete_option(self::OPTION_DISK_WRITABLE_FAILED);

            return true;
        }

 
        $result = $this->setLowLevelDiskFullFlag();

        $this->filesystem->delete($this->directory->getCacheDirectory());

 
        if (!$result) {
            $result = $this->setLowLevelDiskFullFlag();
        }

        $this->filesystem->delete($this->directory->getTmpDirectory());

 
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
