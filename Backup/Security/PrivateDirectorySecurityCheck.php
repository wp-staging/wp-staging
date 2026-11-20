<?php

namespace WPStaging\Backup\Security;

use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Utils\ServerVars;





class PrivateDirectorySecurityCheck
{
 
    const OPTION_STATUS = 'wpstg_private_directory_security_check';

 
    private $directory;

 
    private $serverVars;

 
    private $isLoopbackUnavailable = false;

    public function __construct(Directory $directory, ServerVars $serverVars)
    {
        $this->directory  = $directory;
        $this->serverVars = $serverVars;
    }

 
    public function getStatus(): string
    {
        if (!Hooks::applyFilters(BackupDirectorySecurityCheck::FILTER_ENABLED, true) || !$this->serverVars->isNginx()) {
            return BackupDirectorySecurityCheck::STATUS_NOT_TESTED;
        }

        return (string)get_option(self::OPTION_STATUS, BackupDirectorySecurityCheck::STATUS_NOT_TESTED);
    }

 
    public function run(): string
    {
        if (!Hooks::applyFilters(BackupDirectorySecurityCheck::FILTER_ENABLED, true) || ($this->serverVars->getServerSoftware() !== '' && !$this->serverVars->isNginx())) {
            return BackupDirectorySecurityCheck::STATUS_NOT_TESTED;
        }

        $this->isLoopbackUnavailable = false;
        $directories = $this->getDirectories();
        $this->deleteStaleProbes(array_keys($directories));

        $status = BackupDirectorySecurityCheck::STATUS_PROTECTED;
        foreach ($directories as $directory => $extension) {
            $folderStatus = $this->probe($directory, $extension);
            if ($folderStatus === BackupDirectorySecurityCheck::STATUS_PUBLICLY_REACHABLE) {
                $status = $folderStatus;
                break;
            }

            if ($folderStatus === BackupDirectorySecurityCheck::STATUS_INCONCLUSIVE) {
                $status = BackupDirectorySecurityCheck::STATUS_INCONCLUSIVE;
            }
        }

        update_option(self::OPTION_STATUS, $status, false);

        return $status;
    }







    private function deleteStaleProbes(array $directories)
    {
        foreach ($directories as $directory) {
            foreach ((array)glob($directory . BackupDirectorySecurityCheck::PROBE_PREFIX . '*') as $staleProbe) {
                if (is_file($staleProbe)) {
                    unlink($staleProbe);
                }
            }
        }
    }

 
    public function getUrlPaths(): array
    {
        return array_values(array_filter(array_map(function ($directory) {
            return (string)wp_parse_url($this->getDirectoryUrl($directory), PHP_URL_PATH);
        }, array_keys($this->getDirectories()))));
    }

 
    private function getDirectories(): array
    {
        $pluginContentDirectory = $this->directory->getPluginWpContentDirectory();

        return [
            $this->directory->getLogDirectory()                    => '.log',
            $this->directory->getCacheDirectory()                  => '.sql',
            $this->directory->getPluginUploadsDirectory() . 'tmp/' => '.wpstg',
            $pluginContentDirectory . 'tmp/'                       => '.wpstgtmp',
            $pluginContentDirectory . 'extract/'                   => '.txt',
        ];
    }

    private function probe(string $directory, string $extension): string
    {
        $directoryUrl = $this->getDirectoryUrl($directory);
        if ($directoryUrl === '' || $this->isLoopbackUnavailable) {
            return BackupDirectorySecurityCheck::STATUS_INCONCLUSIVE;
        }

        if (!is_dir($directory)) {
            return BackupDirectorySecurityCheck::STATUS_PROTECTED;
        }

        $probeName = BackupDirectorySecurityCheck::PROBE_PREFIX . wp_generate_password(12, false) . $extension;
        if (file_put_contents($directory . $probeName, $probeName) === false) {
            return BackupDirectorySecurityCheck::STATUS_INCONCLUSIVE;
        }

        try {
            $response = wp_remote_get($directoryUrl . $probeName, ['timeout' => BackupDirectorySecurityCheck::REQUEST_TIMEOUT, 'redirection' => 0, 'sslverify' => false]);
        } finally {
            if (file_exists($directory . $probeName)) {
                unlink($directory . $probeName);
            }
        }

        $this->isLoopbackUnavailable = is_wp_error($response);
        $httpCode = is_wp_error($response) ? 0 : (int)wp_remote_retrieve_response_code($response);
        if ($httpCode === 200 && strpos((string)wp_remote_retrieve_body($response), $probeName) !== false) {
            return BackupDirectorySecurityCheck::STATUS_PUBLICLY_REACHABLE;
        }

        return in_array($httpCode, [401, 403, 404], true) ? BackupDirectorySecurityCheck::STATUS_PROTECTED : BackupDirectorySecurityCheck::STATUS_INCONCLUSIVE;
    }





    private function getDirectoryUrl(string $directory): string
    {
        $directory = trailingslashit(wp_normalize_path($directory));
        $siteRoot  = trailingslashit(wp_normalize_path(ABSPATH));
        if (strpos($directory, $siteRoot) !== 0) {
            return '';
        }

        $blogId  = is_multisite() ? (int)get_current_site()->blog_id : null;
        $siteUrl = is_multisite() ? get_blog_option($blogId, 'siteurl') : get_option('siteurl');

        return get_site_url($blogId, substr($directory, strlen($siteRoot)), is_ssl() ? 'https' : (string)wp_parse_url((string)$siteUrl, PHP_URL_SCHEME));
    }
}
