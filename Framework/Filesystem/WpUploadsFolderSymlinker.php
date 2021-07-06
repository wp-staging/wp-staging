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
            $this->error = __("Link already exists", 'wp-staging');
            return false;
        }

        if (file_exists($this->stagingUploadPath)) {
            $this->error = __("Path exists at link path", 'wp-staging');
            return false;
        }

        $uploadPath = rtrim($this->wpDirectories->getUploadsPath(), '/\\');

        (new Filesystem())->mkdir(dirname($this->stagingUploadPath));

        // try symlink with exec(ln) if exec is enabled and user is on windows
        if ((stripos(PHP_OS, 'WIN') === 0) && $this->isExecEnabled()) {
            return $this->linkWithExec($uploadPath, $this->stagingUploadPath);
        }

        return $this->link($uploadPath, $this->stagingUploadPath);
    }

    /**
     * Return error
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Try symlinking with exec
     *
     * @param string $source
     * @param string $destination
     * @return boolean
     */
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

    /**
     * Try symlinking with php function
     *
     * @param string $source
     * @param string $destination
     * @return boolean
     */
    private function link($source, $destination)
    {
        try {
            symlink($source, $destination);
            return true;
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
