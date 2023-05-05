<?php

namespace WPStaging\Framework\Filesystem;

use RuntimeException;
use WPStaging\Framework\Notices\Notices;
use WPStaging\Core\Utils\Htaccess;
use WPStaging\Core\Utils\IISWebConfig;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;

/**
 * Class DirectoryListing
 *
 * Protect sensitive folders from directory listings.
 *
 * @package WPStaging\Framework\Filesystem
 */
class DirectoryListing
{
    /**  @var Directory */
    private $directory;

    /**  @var Htaccess */
    private $htaccess;

    /**  @var IISWebConfig */
    private $webConfig;

    public function __construct(Directory $directory, Htaccess $htaccess, IISWebConfig $webConfig)
    {
        $this->directory = $directory;
        $this->htaccess  = $htaccess;
        $this->webConfig = $webConfig;
    }

    /**
     * Protect the WPStaging upload folder from directory listing.
     */
    public function protectPluginUploadDirectory()
    {
        $lastChecked = get_transient('wpstg.directory_listing.last_checked');
        $now         = current_time('timestamp');

        if (!empty($lastChecked)) {
            if (($now - $lastChecked) < $this->getInterval()) {
                // Early bail: Last check happened not long ago...
                return;
            }
        }

        set_transient('wpstg.directory_listing.last_checked', $now);

        try {
            $it = new \RecursiveDirectoryIterator($this->directory->getPluginUploadsDirectory());
            $it = new \RecursiveIteratorIterator($it);

            $dirsToProtect = [];

            /** @var \SplFileInfo $item */
            foreach ($it as $item) {
                if ($item->isDir() && $item->getBasename() !== '..') {
                    $dirsToProtect[] = $item->getRealPath();
                }
            }

            $dirsToProtect = array_unique($dirsToProtect);

            foreach ($dirsToProtect as $dir) {
                try {
                    $this->preventDirectoryListing($dir);
                } catch (\Exception $e) {
                    /**
                     * Enqueue this error. All enqueued errors will be shown as a single notice.
                     *
                     * @see \WPStaging\Framework\Notices\Notices::showDirectoryListingWarningNotice
                     */
                    WPStaging::getInstance()->getContainer()->pushToArray(Notices::$directoryListingErrors, $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            \WPStaging\functions\debug_log('WP STAGING: Could not open plugin upload directory to protect from directory listing. ' . $e->getMessage());
        }
    }

    /**
     * Directory listing protection is very fast. In a high-end computer
     * with a NVMe hard-drive running Linux, it completes in 0.0057 seconds.
     *
     * However, with slow HDDs and more folders this could take longer.
     *
     * Since this runs on every request, and disks are the slowest thing in computing,
     * we micro-optimize this code for performance, avoiding hitting the disk while we can.
     *
     * Thus we only run these checks after an interval, which is by default, once every 15 minutes.
     *
     * @return int How many seconds to wait between each check for directory listing protection.
     */
    private function getInterval()
    {
        return (int)apply_filters('wpstg.directory_listing.interval_check', 15 * 60);
    }

    /**
     * @param string $path The path to prevent directory listing.
     *
     * @throws RuntimeException When could not prevent directory listing on the given path.
     *
     * @return void
     */
    public function preventDirectoryListing($path)
    {
        $path = trailingslashit(wp_normalize_path($path));

        // Earliest bail: Directory listing is already prevented.
        if (file_exists($path . 'index.php')) {
            return;
        }

        // Early bail: Not a directory.
        if (!is_dir($path)) {
            return;
        }

        // If it's not writable, check if directory listing is prevented. If both fail, bail.
        if (!is_writable($path) && !file_exists($path . 'index.php')) {
            throw new RuntimeException(__(sprintf("Could not prevent directory listing on %s (Reason: Directory is not writable and does not contain an index file)", untrailingslashit($path)), 'wp-staging'));
        }

        // index.php
        if (!file_exists($path . 'index.php')) {
            $indexPhpCreated = file_put_contents($path . 'index.php', <<<PHP
<?php
/** 
 * WPSTAGING automatically places this index file on all folders it creates to prevent
 * directory listing on servers that might have directory listing enabled.
 * 
 * You might have Directory Listing disabled already. If you do, feel free to ignore this file.
 * 
 * @link https://www.google.com/search?q=directory+listing+vulnerability
 *       Read more about why Directory Listing can be a security risk.
 *       
 * @link https://www.google.com/search?q=disable+directory+listing+apache
 *       How to disable Directory Listing on Apache.
 *       
 * @link https://www.google.com/search?q=disable+directory+listing+nginx
 *       How to disable Directory Listing on Nginx.
 */
PHP
            );

            if ($indexPhpCreated === false) {
                throw new RuntimeException(__(sprintf('Could not prevent directory listing on %s (Reason: Failed to create index.php)', untrailingslashit($path)), 'wp-staging'));
            }
        }

        // index.html
        if (!file_exists($path . 'index.html')) {
            file_put_contents($path . 'index.html', '');
            // We'll not throw if index.html fails to write, as this is just an additional protection layer.
        }

        // .htaccess
        if (!file_exists($path . '.htaccess')) {
            $this->htaccess->create($path . '.htaccess');
            // We'll not throw if .htaccess fails to write, as this is just an additional protection layer.
        }

        // web.config
        if (!file_exists($path . 'web.config')) {
            $this->webConfig->create($path . 'web.config');
            // We'll not throw if web.config fails to write, as this is just an additional protection layer.
        }
    }

    /**
     * Previous versions of WP STAGING generated .htaccess and web.config files without the headers
     * to force browser download when using Apache or IIS. This method converts old .htaccess and web.config
     * to the new version, with the headers.
     *
     * If the .htaccess/web.config is already the new version, does nothing.
     *
     * @deprecated This affects only folders protected by Directory Listing before WP STAGING Pro 4.0.2.
     *             Remove after a reasonable amount of time has passed. (eg: Jan 2023)
     *
     * @param $backupDirectory
     */
    public function maybeUpdateOldHtaccessWebConfig($backupDirectory)
    {
        $backupDirectory = trailingslashit($backupDirectory);

        // Htaccess
        if (file_exists($backupDirectory . '.htaccess')) {
            if ($contents = file_get_contents($backupDirectory . '.htaccess')) {
                if (strpos($contents, 'AddType application/octet-stream .wpstg') === false) {
                    unlink($backupDirectory . '.htaccess');
                    $this->htaccess->create($backupDirectory . '.htaccess');
                }
            }
        }

        // IIS Web Config
        if (file_exists($backupDirectory . 'web.config')) {
            if ($contents = file_get_contents($backupDirectory . 'web.config')) {
                if (strpos($contents, '<mimeMap fileExtension=".wpstg" mimeType="application/octet-stream"') === false) {
                    unlink($backupDirectory . 'web.config');
                    $this->webConfig->create($backupDirectory . 'web.config');
                }
            }
        }
    }
}
