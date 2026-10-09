<?php

namespace WPStaging\Backup\Security;

use Throwable;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Backup\Transfer\TransferDirectory;
use WPStaging\Backup\Transfer\TransferSessionService;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Utils\Urls;





class BackupDirectoryNoticeService
{
 
    const OPTION_DISMISSED = 'wpstg_backup_directory_notice_dismissed';

 
    const LEVEL_INFO = 'info';

 
    const LEVEL_WARNING = 'warning';

 
    const LEVEL_NONE = 'none';

 
    private $securityCheck;

 
    private $backupsFinder;

 
    private $urls;

 
    private $transferDirectory;

 
    private $protectionService;

 
    private $transferSessionService;

    public function __construct(BackupDirectorySecurityCheck $securityCheck, BackupsFinder $backupsFinder, Urls $urls, TransferDirectory $transferDirectory, BackupDirectoryProtectionService $protectionService, TransferSessionService $transferSessionService)
    {
        $this->securityCheck          = $securityCheck;
        $this->backupsFinder          = $backupsFinder;
        $this->urls                   = $urls;
        $this->transferDirectory      = $transferDirectory;
        $this->protectionService      = $protectionService;
        $this->transferSessionService = $transferSessionService;
    }





    public function getLevel(): string
    {
        if (!$this->protectionService->isEnabled()) {
            return self::LEVEL_NONE;
        }

        $result = $this->securityCheck->getResult();

        if ($result['status'] !== BackupDirectorySecurityCheck::STATUS_PUBLICLY_REACHABLE) {
            return self::LEVEL_NONE;
        }

        return !empty($result['directoryListing']) ? self::LEVEL_WARNING : self::LEVEL_INFO;
    }

 
    public function shouldShow(): bool
    {
        if ($this->getLevel() === self::LEVEL_NONE) {
            return false;
        }

        return get_option(self::OPTION_DISMISSED, '') !== $this->getStateSignature();
    }

 
    public function dismiss()
    {
        update_option(self::OPTION_DISMISSED, $this->getStateSignature(), false);
    }

 
    public function reset()
    {
        delete_option(self::OPTION_DISMISSED);
    }

 
    public function getNoticeData(): array
    {
        $result = $this->securityCheck->getResult();

        return [
            'level'                    => $this->getLevel(),
            'directoryListing'         => !empty($result['directoryListing']),
            'checkedAt'                => (int)$result['checkedAt'],
            'isNginx'                  => !empty($result['isNginx']),
            'isTransferSessionEnabled' => $this->transferSessionService->isEnabled(),
            'backupUrlPath'            => $this->getBackupUrlPath(),
            'transferUrlPath'          => $this->getTransferUrlPath(),
        ];
    }

 
    public function getTransferUrlPath(): string
    {
        return $this->getUrlPath($this->transferDirectory->getBaseUrl(), $this->transferDirectory->getBaseDirectory());
    }

 
    public function getBackupUrlPath(): string
    {
        return $this->getUrlPath($this->urls->getBackupUrl(), $this->getBackupsDirectory());
    }

    private function getUrlPath(string $url, string $fallbackDirectory): string
    {
        $path = wp_parse_url($url, PHP_URL_PATH);

        return is_string($path) && $path !== '' ? $path : $this->getPathRelativeToSiteRoot($fallbackDirectory);
    }





    private function getPathRelativeToSiteRoot(string $absolutePath): string
    {
        if ($absolutePath === '') {
            return '';
        }

        $absolutePath = trailingslashit(wp_normalize_path($absolutePath));
        $siteRoot     = trailingslashit(wp_normalize_path(ABSPATH));

        if (strpos($absolutePath, $siteRoot) !== 0) {
            return '';
        }

        $siteUrlPath = wp_parse_url(network_site_url(), PHP_URL_PATH);
        $siteUrlPath = is_string($siteUrlPath) && $siteUrlPath !== '' ? trailingslashit($siteUrlPath) : '/';

        return $siteUrlPath . substr($absolutePath, strlen($siteRoot));
    }

 
    private function getBackupsDirectory(): string
    {
        try {
            return $this->backupsFinder->getBackupsDirectory();
        } catch (Throwable $e) {
            return '';
        }
    }

 
    private function getStateSignature(): string
    {
        $result = $this->securityCheck->getResult();

        return md5(implode('|', [
            $result['status'],
            empty($result['isNginx']) ? '0' : '1',
            empty($result['directoryListing']) ? '0' : '1',
            $this->getBackupsDirectory(),
            WPStaging::getVersion(),
        ]));
    }
}
