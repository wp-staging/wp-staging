<?php

namespace WPStaging\Framework\Traits;

use RuntimeException;
use SplFileInfo;

use function WPStaging\functions\debug_log;

/**
 * Wraps SplFileInfo stat calls that can throw a RuntimeException when
 * open_basedir restrictions block access to the path being inspected.
 */
trait SafeFileInfoTrait
{
    /**
     * @param SplFileInfo $item
     * @return bool|null Null when open_basedir blocks the check.
     */
    protected function isLinkSafely(SplFileInfo $item)
    {
        try {
            return $item->isLink();
        } catch (RuntimeException $openBaseDirException) {
            $this->logInaccessiblePath($item, $openBaseDirException);
            return null;
        }
    }

    /**
     * @param SplFileInfo $item
     * @return bool|null Null when open_basedir blocks the check.
     */
    protected function isFileSafely(SplFileInfo $item)
    {
        try {
            return $item->isFile();
        } catch (RuntimeException $openBaseDirException) {
            $this->logInaccessiblePath($item, $openBaseDirException);
            return null;
        }
    }

    /**
     * @param SplFileInfo $item
     * @return bool|null Null when open_basedir blocks the check.
     */
    protected function isDirSafely(SplFileInfo $item)
    {
        try {
            return $item->isDir();
        } catch (RuntimeException $openBaseDirException) {
            $this->logInaccessiblePath($item, $openBaseDirException);
            return null;
        }
    }

    /**
     * @param SplFileInfo $item
     * @return string|false False when open_basedir blocks the check or the target is unresolvable.
     */
    protected function getRealPathSafely(SplFileInfo $item)
    {
        try {
            return $item->getRealPath();
        } catch (RuntimeException $openBaseDirException) {
            $this->logInaccessiblePath($item, $openBaseDirException);
            return false;
        }
    }

    /**
     * @param SplFileInfo $item
     * @param RuntimeException $exception
     * @return void
     */
    protected function logInaccessiblePath(SplFileInfo $item, RuntimeException $exception)
    {
        debug_log('WP STAGING: Skipping inaccessible path during scan: ' . $item->getPathname() . ' - ' . $exception->getMessage());
    }
}
