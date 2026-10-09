<?php

namespace WPStaging\Backup\Transfer;

use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Utils\Urls;

use function WPStaging\functions\debug_log;




class TransferDirectory
{
 
    const FILTER_BASE_DIR = 'wpstg.transfer_session.base_dir';

 
    const FILTER_BASE_URL = 'wpstg.transfer_session.base_url';

 
    const DIRECTORY_NAME = 'transfers';

 
    private $directory;

 
    private $filesystem;

 
    private $urls;

 
    private $baseDirectory = '';

    public function __construct(Directory $directory, Filesystem $filesystem, Urls $urls)
    {
        $this->directory  = $directory;
        $this->filesystem = $filesystem;
        $this->urls       = $urls;
    }

 
    public function getBaseDirectory(): string
    {
        if ($this->baseDirectory !== '') {
            return $this->baseDirectory;
        }

        $default       = $this->directory->getPluginWpContentDirectory() . self::DIRECTORY_NAME;
        $baseDirectory = Hooks::applyFilters(self::FILTER_BASE_DIR, $default);

        $this->baseDirectory = trailingslashit(wp_normalize_path($baseDirectory));

        return $this->baseDirectory;
    }






    public function prepareBaseDirectory(): string
    {
        $baseDirectory = $this->getBaseDirectory();

        if (!$this->filesystem->mkdir($baseDirectory, true) || !is_writable($baseDirectory)) {
            debug_log('WP STAGING: Could not create or write to the transfer directory ' . $baseDirectory);

            throw TransferSessionException::cannotCreateTransferDirectory();
        }

        $this->addAntiListingFiles($baseDirectory);

        return $baseDirectory;
    }

 
    public function getSessionDirectory(string $token): string
    {
        return $this->getBaseDirectory() . $token . '/';
    }

 
    public function getBaseUrl(): string
    {
        $baseUrl = (string)Hooks::applyFilters(self::FILTER_BASE_URL, $this->deriveBaseUrl());

        return $baseUrl === '' ? '' : trailingslashit($baseUrl);
    }

 
    public function isPubliclyAddressable(): bool
    {
        return $this->getBaseUrl() !== '';
    }

 
    public function getArtifactUrl(string $token, string $fileName): string
    {
        return $this->getBaseUrl() . $token . '/' . $fileName;
    }






    public function addAntiListingFiles(string $path)
    {
        $path = trailingslashit(wp_normalize_path($path));

        if (!file_exists($path . 'index.html')) {
            $this->filesystem->create($path . 'index.html', '');
        }

        if (file_exists($path . 'index.php')) {
            return;
        }

        $this->filesystem->create($path . 'index.php', "<?php\n// Silence is golden. WP STAGING stores short-lived download links here.\n");
    }





    private function deriveBaseUrl(): string
    {
        $baseDirectory = $this->getBaseDirectory();

        $wpContentDirectory = trailingslashit(wp_normalize_path(WP_CONTENT_DIR));
        if (strpos($baseDirectory, $wpContentDirectory) === 0) {
            return trailingslashit($this->urls->maybeUseProtocolRelative(content_url())) . substr($baseDirectory, strlen($wpContentDirectory));
        }

        $absPath = trailingslashit(wp_normalize_path(ABSPATH));
        if (strpos($baseDirectory, $absPath) === 0) {
            return trailingslashit($this->urls->maybeUseProtocolRelative(get_option('siteurl'))) . substr($baseDirectory, strlen($absPath));
        }

        return '';
    }
}
