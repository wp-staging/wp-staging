<?php

namespace WPStaging\Backup\Service\DirectoryExplorer;

use DirectoryIterator;
use Throwable;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Staging\Sites;
use WPStaging\Framework\TemplateEngine\TemplateEngine;

use function WPStaging\functions\debug_log;




class Scan
{



    private $directories = [];

 
    private $disabledDirs = [];




    protected $dirAdapter;




    private $templateEngine;




    private $stagingSitesHelper;

    public function __construct(Sites $sites, Directory $directory, TemplateEngine $templateEngine)
    {
        $this->dirAdapter   = $directory;
        $this->disabledDirs = array_merge($this->dirAdapter->getWpDefaultRootDirectories(), $this->dirAdapter->getWpStagingRestoreDirs());

 
        $this->stagingSitesHelper = $sites;
        $this->templateEngine     = $templateEngine;

        $this->buildDirectories();
    }




    public function addStagingSitesDirsToDisabledDirs()
    {
        $this->disabledDirs = array_merge($this->disabledDirs, $this->stagingSitesHelper->getStagingDirectories());
    }






    public function listDirectoryForBackup($directories = null, bool $forceDisabled = false): string
    {
        if (empty($directories)) {
            $directories = $this->directories;
        }

        if (empty($directories)) {
            return '<li class="wpstg-no-custom-dirs">' . esc_html__('No available custom folders!', 'wp-staging') . '</li>';
        }

        $output = '';
        foreach ($directories as $dirBaseName => $directory) {
            if (!is_array($directory)) {
                continue;
            }

 
            $data = reset($directory);
            unset($directory[key($directory)]);

            if (!isset($data["path"])) {
                continue;
            }

            $disabledDirsWithoutSlashit = array_map('untrailingslashit', $this->disabledDirs);
            $isDisabled                 = $this->isPathInList($data["path"], $disabledDirsWithoutSlashit);

            $idName = uniqid('wpstg');
            $output .= $this->templateEngine->render('backup/directory-navigation.php', [
                'scan'          => $this,
                'idName'        => $idName,
                'isDisabled'    => $isDisabled,
                'forceDisabled' => $forceDisabled,
                'data'          => $data,
                'dirBaseName'   => $dirBaseName,
            ]);
        }

        return $output;
    }




    protected function buildDirectories()
    {
        try {
            $directories = new DirectoryIterator(ABSPATH);

            foreach ($directories as $directory) {
                $path = false;
                try {
                    $path = $this->getPath($directory);
                } catch (Throwable $e) {
                    continue;
                }

                if ($path === false) {
                    continue;
                }

                if ($this->isPathInList(trailingslashit($path), $this->dirAdapter->getWpDefaultRootDirectories())) {
                    continue;
                }

                $this->directories[basename($path)]["metaData"] = [
                    "path" => $path,
                ];
            }
        } catch (Throwable $ex) {
            debug_log('Cannot scan root path. Error: ' . $ex->getMessage());
        }
    }





    protected function getPath(DirectoryIterator $directory)
    {
        if (!$directory->isDir() || $directory->isDot()) {
            return false;
        }

        $path = $directory->getRealPath();
        if ($path === false) {
            return false;
        }

        return $this->dirAdapter->getFileSystem()->normalizePath($path);
    }









    protected function isPathInList(string $path, array $list): bool
    {
        $isUncPath = strpos($path, '//') === 0;
        foreach ($list as $item) {
            if ($isUncPath ? strcasecmp($path, $item) === 0 : $path === $item) {
                return true;
            }
        }

        return false;
    }
}
