<?php

namespace WPStaging\Framework\Filesystem;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Utils\ServerVars;
use WPStaging\Framework\Facades\Hooks;

use function WPStaging\functions\debug_log;

class OPcache
{



    const FILTER_OPCACHE_MAYBE_INVALIDATE = 'wpstg.opcache.maybe_invalidate';




    private $serverVars;

    public function __construct()
    {
        $this->serverVars = WPStaging::make(ServerVars::class);
    }





    private function isOpCacheApiAccessible(): bool
    {
        $restrictApi = ini_get('opcache.restrict_api');
        if (empty($restrictApi)) {
            return true;
        }

        if (empty($_SERVER['SCRIPT_FILENAME'])) {
            return false;
        }

        $scriptPath = realpath($_SERVER['SCRIPT_FILENAME']); // phpcs:ignore
        if ($scriptPath === false) {
            return false;
        }

        return stripos($scriptPath, $restrictApi) === 0;
    }

    public function reset(): bool
    {
        if (!function_exists('opcache_reset') || $this->serverVars->isFunctionDisabled('opcache_reset')) {
            return false;
        }

        if (!$this->isOpCacheApiAccessible()) {
            return false;
        }

        return @opcache_reset();
    }







    public function invalidateFile(string $filePath, bool $force = false): bool
    {
        static $canInvalidate = null;
        if (
            $canInvalidate === null
            && function_exists('opcache_invalidate')
            && (
                !ini_get('opcache.restrict_api')
                || !empty($_SERVER['SCRIPT_FILENAME']) && stripos(realpath($_SERVER['SCRIPT_FILENAME']), ini_get('opcache.restrict_api')) === 0 // phpcs:ignore
            )
        ) {
            $canInvalidate = true;
        }

        if (!$canInvalidate || strtolower(substr($filePath, -4)) !== '.php') {
            return false;
        }

        if (!$this->isOpCacheApiAccessible()) {
            return false;
        }

        return @opcache_invalidate($filePath, $force);
    }





    public function invalidateDirectory(string $dirPath)
    {
        if (!is_dir($dirPath)) {
            return;
        }

        $dirIterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dirPath));
        foreach ($dirIterator as $fileInfo) {
            if (!$fileInfo->isFile() || $fileInfo->isLink() || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            $this->invalidateFile($fileInfo->getRealPath(), true);
        }
    }

 
    public function maybeInvalidate()
    {
        if (!Hooks::applyFilters(self::FILTER_OPCACHE_MAYBE_INVALIDATE, true)) {
            debug_log('opcache invalidate disabled.', 'info', false);
            return;
        }

 
        if ($this->reset()) {
            debug_log('opcache_reset executed.', 'info', false);
            return;
        }

 
        if (!function_exists('opcache_invalidate') || $this->serverVars->isFunctionDisabled('opcache_invalidate')) {
            return;
        }

        debug_log('Trigger opcache invalidate.', 'info', false);

 
        if (function_exists('opcache_get_status') && !$this->serverVars->isFunctionDisabled('opcache_get_status') && $this->isOpCacheApiAccessible()) {
            $opcacheStatus = @opcache_get_status();
            if (!empty($opcacheStatus['scripts'])) {
                foreach ($opcacheStatus['scripts'] as $file => $data) {
                    $this->invalidateFile($file, true);
                }
            }

            return;
        }

 
        $wpCoreFiles = [
            'index.php',
            'wp-activate.php',
            'wp-blog-header.php',
            'wp-comments-post.php',
            'wp-config-sample.php',
            'wp-config.php',
            'wp-cron.php',
            'wp-links-opml.php',
            'wp-load.php',
            'wp-login.php',
            'wp-mail.php',
            'wp-settings.php',
            'wp-signup.php',
            'wp-trackback.php',
            'xmlrpc.php',
        ];

        foreach ($wpCoreFiles as $file) {
            $this->invalidateFile(ABSPATH . $file);

            $parentFile = dirname(ABSPATH) . '/' . $file;
            if (file_exists($parentFile)) {
                $this->invalidateFile($parentFile);
            }
        }

 
        $wpCoreDirs = [
            'wp-admin/',
            'wp-includes/',
            'wp-content/plugins/',
            'wp-content/mu-plugins/',
            'wp-content/themes/',
        ];

        foreach ($wpCoreDirs as $dir) {
            $this->invalidateDirectory(ABSPATH . $dir);

            $parentDir = dirname(ABSPATH) . '/' . $dir;
            if (is_dir($parentDir)) {
                $this->invalidateDirectory($parentDir);
            }
        }
    }
}
