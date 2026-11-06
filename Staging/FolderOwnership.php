<?php

namespace WPStaging\Staging;

use RuntimeException;
use WPStaging\Framework\Filesystem\Filesystem;





class FolderOwnership
{






    const CLONE_WRITTEN_WP_CONFIG_PATTERN = '/WPSTAGING_UNFINISHED_CLONE_GUARD_START|Changed by WP[ -]?STAGING|defined\(\s*[\'"]WPSTAGING_DEV_SITE[\'"]\s*\)|\$table_prefix\s*=\s*[\'"][^\'"]*[\'"][ \t]*;[ \t]*\/\/[ \t]*=[ \t]*[\'"]/i';

 
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }








    public function isStagingSiteFolder(string $folder): bool
    {
        $folder = $this->filesystem->normalizePath($this->stripTrailingSeparators($folder));

        if (($folder !== '' && !$this->isAbsolutePath($folder)) || is_file($folder)) {
            return false;
        }

        if (!is_dir($folder)) {
            return !file_exists($folder);
        }

        $folder = $this->filesystem->normalizePath((string)realpath($folder), true);
        if (stripos($this->filesystem->normalizePath((string)realpath(ABSPATH), true), $folder) === 0) {
            return false;
        }

        if ($this->filesystem->isEmptyDir($folder)) {
            return true;
        }

        $wpConfig = $folder . 'wp-config.php';
        $content  = is_file($wpConfig) && is_readable($wpConfig) ? file_get_contents($wpConfig) : false;

        return is_string($content) && preg_match(self::CLONE_WRITTEN_WP_CONFIG_PATTERN, $content) === 1;
    }





    public function assertIsStagingSiteFolder(string $folder)
    {
        if ($this->isStagingSiteFolder($folder)) {
            return;
        }

        throw new RuntimeException(esc_html__('Cannot reset or update this staging site because WP STAGING cannot confirm that its folder belongs to it. Delete the entry, which keeps that folder, and create a new staging site, or contact support@wp-staging.com.', 'wp-staging'));
    }

 
    private function isAbsolutePath(string $folder): bool
    {
        return path_is_absolute($folder) || preg_match('#^[a-zA-Z]:/#', $folder) === 1;
    }

 
    private function stripTrailingSeparators(string $folder): string
    {
        $strippedFolder = untrailingslashit($folder);

        return $strippedFolder === '' && $folder !== '' ? '/' : $strippedFolder;
    }
}
