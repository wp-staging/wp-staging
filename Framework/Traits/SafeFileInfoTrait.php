<?php

namespace WPStaging\Framework\Traits;

use RuntimeException;
use SplFileInfo;

use function WPStaging\functions\debug_log;





trait SafeFileInfoTrait
{




    protected function isLinkSafely(SplFileInfo $item)
    {
        try {
            return $item->isLink();
        } catch (RuntimeException $openBaseDirException) {
            $this->logInaccessiblePath($item, $openBaseDirException);
            return null;
        }
    }





    protected function isFileSafely(SplFileInfo $item)
    {
        try {
            return $item->isFile();
        } catch (RuntimeException $openBaseDirException) {
            $this->logInaccessiblePath($item, $openBaseDirException);
            return null;
        }
    }





    protected function isDirSafely(SplFileInfo $item)
    {
        try {
            return $item->isDir();
        } catch (RuntimeException $openBaseDirException) {
            $this->logInaccessiblePath($item, $openBaseDirException);
            return null;
        }
    }





    protected function getRealPathSafely(SplFileInfo $item)
    {
        try {
            return $item->getRealPath();
        } catch (RuntimeException $openBaseDirException) {
            $this->logInaccessiblePath($item, $openBaseDirException);
            return false;
        }
    }






    protected function logInaccessiblePath(SplFileInfo $item, RuntimeException $exception)
    {
        debug_log('WP STAGING: Skipping inaccessible path during scan: ' . $item->getPathname() . ' - ' . $exception->getMessage());
    }
}
