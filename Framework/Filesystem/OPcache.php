<?php

namespace WPStaging\Framework\Filesystem;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Utils\ServerVars;
use WPStaging\Framework\Facades\Hooks;

use function WPStaging\functions\debug_log;

class OPcache
{
    /**
     * @var string
     */
    const FILTER_OPCACHE_MAYBE_INVALIDATE = 'wpstg.opcache.maybe_invalidate';

    /**
     * @var ServerVars
     */
    private $serverVars;

    public function __construct()
    {
        $this->serverVars = WPStaging::make(ServerVars::class);
    }

    public function reset(): bool
    {
        if (!function_exists('opcache_reset') || $this->serverVars->isFunctionDisabled('opcache_reset')) {
            return false;
        }

        return opcache_reset();
    }

    /**
     * @see https://developer.wordpress.org/reference/functions/wp_opcache_invalidate/
     * @param string $filePath
     * @param bool $force
     * @return bool
     */
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

        return opcache_invalidate($filePath, $force);
    }

    /**
     * @param string $dirPath
     * @return void
     */
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

    /** @return void */
    public function maybeInvalidate()
    {
        if (!Hooks::applyFilters(self::FILTER_OPCACHE_MAYBE_INVALIDATE, true)) {
            debug_log('opcache invalidate disabled.', 'info', false);
            return;
        }

        // If can use opcache_reset
        if ($this->reset()) {
            debug_log('opcache_reset executed.', 'info', false);
            return;
        }

        // Abort if opcache_invalidate not available
        if (!function_exists('opcache_invalidate') || $this->serverVars->isFunctionDisabled('opcache_invalidate')) {
            return;
        }

        debug_log('Trigger opcache invalidate.', 'info', false);

        // If can use opcache_get_status
        if (function_exists('opcache_get_status') && !$this->serverVars->isFunctionDisabled('opcache_get_status')) {
            $opcacheStatus = opcache_get_status();
            if (!empty($opcacheStatus['scripts'])) {
                foreach ($opcacheStatus['scripts'] as $file => $data) {
                    $this->invalidateFile($file, true);
                }
            }

            return;
        }

        // Invalidate wp core files
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
            'xmlrpc.php'
        ];

        foreach ($wpCoreFiles as $file) {
            $this->invalidateFile(ABSPATH . $file);

            $parentFile = dirname(ABSPATH) . '/' . $file;
            if (file_exists($parentFile)) {
                $this->invalidateFile($parentFile);
            }
        }

        // Invalidate directory
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
