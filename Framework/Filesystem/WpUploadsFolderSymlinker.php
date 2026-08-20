<?php

namespace WPStaging\Framework\Filesystem;

use WPStaging\Backend\Modules\Jobs\Exceptions\FatalException;
use WPStaging\Framework\Utils\WpDefaultDirectories;






class WpUploadsFolderSymlinker
{



    protected $stagingWpPath;




    protected $stagingUploadPath;




    protected $wpDirectories;




    protected $error;

    public function __construct(WpDefaultDirectories $wpDirectories)
    {
        $this->wpDirectories = $wpDirectories;
    }





    public function setStagingPath(string $stagingWpPath)
    {
        $this->stagingWpPath     = trailingslashit($stagingWpPath);
        $this->stagingUploadPath = rtrim($this->stagingWpPath . $this->wpDirectories->getRelativeUploadPath(), '/');
    }





    public function setStagingSiteUploadPath(string $stagingUploadPath)
    {
        $this->stagingUploadPath = rtrim($stagingUploadPath, '/');
    }




    public function trySymlink()
    {
        if (is_link($this->stagingUploadPath)) {
            $this->error = __("Link already exists", 'wp-staging');
            return false;
        }

        if (file_exists($this->stagingUploadPath)) {
            $this->error = __("Path exists at link path", 'wp-staging');
            return false;
        }

        $uploadPath = rtrim($this->wpDirectories->getUploadsPath(), '/\\');

        (new Filesystem())->mkdir(dirname($this->stagingUploadPath));

 
        if ((stripos(PHP_OS, 'WIN') === 0) && $this->isExecEnabled()) {
            return $this->linkWithExec($uploadPath, $this->stagingUploadPath);
        }

        return $this->link($uploadPath, $this->stagingUploadPath);
    }




    public function getError()
    {
        return $this->error;
    }








    private function linkWithExec($source, $destination)
    {
        try {
            exec('mklink /D "' . $destination . '" "' . $source . '"');
            return true;
        } catch (FatalException $ex) {
            $this->error = sprintf(__("Can not symlink %s. Error: ", 'wp-staging'), $destination, $ex->getMessage());
            return false;
        }
    }








    private function link($source, $destination)
    {
        try {
            return symlink($source, $destination);
        } catch (FatalException $ex) {
            $this->error = sprintf(__("Can not symlink %s. Error: ", 'wp-staging'), $destination, $ex->getMessage());
            return false;
        }
    }

    private function isExecEnabled()
    {
        if (!function_exists('exec')) {
            return false;
        }

        $disabled = explode(',', ini_get('disable_functions'));
        return !in_array('exec', $disabled);
    }
}
