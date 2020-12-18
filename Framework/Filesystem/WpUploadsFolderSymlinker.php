<?php

namespace WPStaging\Framework\Filesystem;

use WPStaging\Backend\Modules\Jobs\Exceptions\FatalException;
use WPStaging\Framework\Utils\WpDefaultDirectories;

/*
 * This is a service class to symlink the upload folder of production site
 * to staging site
 * Symlink will only work if staging site is on same hosting as production site
 */
class WpUploadsFolderSymlinker 
{
    /**
     * @var string
     */
    protected $stagingWpPath;

    /**
     * @var string
     */
    protected $stagingUploadPath;

    /**
     * @var WpDefaultDirectories
     */
    protected $wpDirectories;

    /**
     * @var string
     */
    protected $error;

    /**
     * @param string $stagingWpPath
     */
    public function __construct($stagingWpPath) 
    {
        $this->stagingWpPath = $stagingWpPath;
        // todo inject using dependency injection if possible
        $this->wpDirectories = new WpDefaultDirectories();
        $this->stagingUploadPath = rtrim($this->stagingWpPath . $this->wpDirectories->getRelativeUploadPath(), '/');
    }

    /**
     * @return bool
     */
    public function trySymlink()
    {
        if (is_link($this->stagingUploadPath)) {
            $this->error = "Link already exists";
            return false;
        }

        if (file_exists($this->stagingUploadPath)) {
            $this->error = "Directory already exists";
            return false;
        }

        $uploadPath = $this->wpDirectories->getUploadPath();

        $this->createDirectory($this->stagingUploadPath);

        if (false === symlink($uploadPath, $this->stagingUploadPath)) {
            $this->error = "Can not symlink  " . $uploadPath . "to " . $this->stagingUploadPath;
            return false;
        }

        $this->error = "";
        return true;
    }

    /**
     * Return error
     */
    public function getError() 
    {
        return $this->error;
    }

    /**
     * Create staging sites upload directory ready to be connected via symlink to the production site
     * Directory must exist before i
     */
    protected function createDirectory($path)
    {
        $dirname = dirname($path);
        $directories = explode(DIRECTORY_SEPARATOR, $dirname);
        $currentDirectory = '';
        foreach ($directories as $directory) {
            $currentDirectory .= $directory . DIRECTORY_SEPARATOR;
            if (!file_exists($currentDirectory)) {
                mkdir($currentDirectory, 0755);
            }
        }
    }
}