<?php

namespace WPStaging\Backup\Security;

use Throwable;
use WPStaging\Backup\Service\BackupsDirectoryResolver;
use WPStaging\Backup\Transfer\TransferSessionService;
use WPStaging\Core\Utils\Htaccess;
use WPStaging\Core\Utils\IISWebConfig;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Filesystem\Filesystem;

use function WPStaging\functions\debug_log;





class BackupDirectoryProtectionService
{
 
    const BACKUP_VAULT_MARKER = 'WP STAGING backup vault';

 
    const WEB_CONFIG_LEGACY_MARKER = '<mimeMap fileExtension=".wpstg"';

 
    const HTACCESS_LEGACY_MARKER = 'AddType application/octet-stream .wpstg';

 
    const HTACCESS_MIME_MARKER = 'WP STAGING backup mime types';

 
    const FILTER_PROTECT = 'wpstg.backup_directory.protect';

 
    const TRANSIENT_LAST_CHECKED_PROTECTION_STATE = 'wpstg.backup_directory_protection.last_checked';

 
    const CHECK_INTERVAL = HOUR_IN_SECONDS;

 
    private $backupsDirectoryResolver;

 
    private $filesystem;

    public function __construct(BackupsDirectoryResolver $backupsDirectoryResolver, Filesystem $filesystem)
    {
        $this->backupsDirectoryResolver = $backupsDirectoryResolver;
        $this->filesystem               = $filesystem;
    }

 
    public function isEnabled(): bool
    {
        $transferSessionsEnabled = (bool)Hooks::applyFilters(TransferSessionService::FILTER_ENABLED, true);

        return (bool)Hooks::applyFilters(self::FILTER_PROTECT, $transferSessionsEnabled);
    }

 
    public function isPermanentBackupUrlOffered(): bool
    {
        return !(bool)Hooks::applyFilters(TransferSessionService::FILTER_ENABLED, true) && !$this->isEnabled();
    }

 
    public function maybeProtect()
    {
        $wantedProtectionState = $this->describeWantedProtectionState();
        if (get_transient(self::TRANSIENT_LAST_CHECKED_PROTECTION_STATE) === $wantedProtectionState) {
            return;
        }

        set_transient(self::TRANSIENT_LAST_CHECKED_PROTECTION_STATE, $wantedProtectionState, self::CHECK_INTERVAL);

        $this->protect();
    }





    public function protect(): bool
    {
        try {
            $backupsDirectory = $this->resolveBackupsDirectory();
        } catch (Throwable $e) {
            debug_log('WP STAGING: Could not protect the backup directory. ' . $e->getMessage());

            return false;
        }

        if (!is_dir($backupsDirectory) || !is_writable($backupsDirectory)) {
            return false;
        }

        if (!$this->isEnabled()) {
            return $this->removeDenyRules($backupsDirectory);
        }

        $written = $this->writeHtaccess($backupsDirectory);
        $written = $this->writeWebConfig($backupsDirectory) && $written;

        $this->writeIndexFiles($backupsDirectory);

        return $written;
    }

    private function describeWantedProtectionState(): string
    {
        try {
            $backupsDirectory = $this->resolveBackupsDirectory();
        } catch (Throwable $e) {
            $backupsDirectory = '';
        }

        return ($this->isEnabled() ? 'protected:' : 'open:') . $backupsDirectory;
    }

    private function resolveBackupsDirectory(): string
    {
        $uploads = wp_upload_dir(null, false);

        return trailingslashit($this->backupsDirectoryResolver->resolveFromUploadsDirectory($uploads['basedir']));
    }

 
    private function getWebConfigContent(): string
    {
        return implode(PHP_EOL, array_merge([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<!-- ' . self::BACKUP_VAULT_MARKER . ': direct HTTP access forbidden -->',
            '<configuration>',
            '<system.webServer>',
        ], IISWebConfig::STATIC_CONTENT_RULES, [
            '<security>',
            '<requestFiltering>',
            '<fileExtensions allowUnlisted="false">',
            '<add fileExtension=".wpstg" allowed="false" />',
            '<add fileExtension=".wpstgtmp" allowed="false" />',
            '<add fileExtension=".sql" allowed="false" />',
            '<add fileExtension=".log" allowed="false" />',
            '</fileExtensions>',
            '</requestFiltering>',
            '</security>',
            '<directoryBrowse enabled="false" />',
            '</system.webServer>',
            '</configuration>',
        ]));
    }

 
    private function getHtaccessMimeLines(): array
    {
        return array_merge(Htaccess::MOD_MIME_RULES, [
            '<IfModule mod_dir.c>',
            'DirectoryIndex index.php',
            '</IfModule>',
            '<IfModule mod_autoindex.c>',
            'Options -Indexes',
            '</IfModule>',
        ]);
    }

 
    private function getHtaccessLines(): array
    {
        return [
            'Options -Indexes',
            '',
            '<IfModule mod_authz_core.c>',
            '    Require all denied',
            '</IfModule>',
            '',
            '<IfModule !mod_authz_core.c>',
            '    Order allow,deny',
            '    Deny from all',
            '</IfModule>',
            '',
            '<IfModule mod_mime.c>',
            'AddType application/octet-stream .log',
            self::HTACCESS_LEGACY_MARKER,
            '</IfModule>',
        ];
    }

 
    private function writeHtaccess(string $backupsDirectory): bool
    {
        $path = $backupsDirectory . '.htaccess';

        if ($this->hasMarker($path, self::BACKUP_VAULT_MARKER)) {
            return true;
        }

        return $this->filesystem->createWithMarkers($path, self::BACKUP_VAULT_MARKER, $this->getHtaccessLines());
    }

 
    private function writeWebConfig(string $backupsDirectory): bool
    {
        $path = $backupsDirectory . 'web.config';

        if ($this->hasMarker($path, self::BACKUP_VAULT_MARKER)) {
            return true;
        }

        if (file_exists($path) && !$this->hasMarker($path, self::WEB_CONFIG_LEGACY_MARKER)) {
            debug_log('WP STAGING: Left an existing web.config in the backup directory untouched, it was not written by WP STAGING. Add the deny rules to it manually to block direct downloads.');

            return false;
        }

        return $this->filesystem->create($path, $this->getWebConfigContent());
    }

 
    private function removeDenyRules(string $backupsDirectory): bool
    {
        $htaccessPath = $backupsDirectory . '.htaccess';
        $hadDenyRules = $this->hasMarker($htaccessPath, self::BACKUP_VAULT_MARKER);
        $removed      = $this->removeMarkedBlock($htaccessPath);

        if ($hadDenyRules && $removed && file_exists($htaccessPath)) {
            $removed = $this->filesystem->createWithMarkers($htaccessPath, self::HTACCESS_MIME_MARKER, $this->getHtaccessMimeLines()) && $removed;
        }

        $webConfigPath = $backupsDirectory . 'web.config';
        if ($this->hasMarker($webConfigPath, self::BACKUP_VAULT_MARKER)) {
            $removed = $this->filesystem->delete($webConfigPath) && $removed;
        }

        $this->writeIndexFiles($backupsDirectory);

        return $removed;
    }





    private function removeMarkedBlock(string $path): bool
    {
        if (!$this->hasMarker($path, self::BACKUP_VAULT_MARKER)) {
            return true;
        }

        $contents = file_get_contents($path);
        if (!is_string($contents)) {
            return false;
        }

        $marker = preg_quote(self::BACKUP_VAULT_MARKER, '/');

        $withoutBlock = preg_replace('/\R*# BEGIN ' . $marker . '.*?# END ' . $marker . '[^\n]*/s', '', $contents);

        if (!is_string($withoutBlock) || strpos($withoutBlock, self::BACKUP_VAULT_MARKER) !== false) {
            debug_log('WP STAGING: Could not remove the deny rules from the backup directory .htaccess. Remove the WP STAGING backup vault block by hand to make the backup URL resolve again.');

            return false;
        }

        if (trim($withoutBlock) === '') {
            return $this->filesystem->delete($path);
        }

        return $this->filesystem->create($path, rtrim($withoutBlock) . PHP_EOL);
    }

 
    private function writeIndexFiles(string $backupsDirectory)
    {
        if (!file_exists($backupsDirectory . 'index.html')) {
            $this->filesystem->create($backupsDirectory . 'index.html', '');
        }

        if (file_exists($backupsDirectory . 'index.php')) {
            return;
        }

        $this->filesystem->create($backupsDirectory . 'index.php', "<?php\n// Silence is golden.\n");
    }

    private function hasMarker(string $path, string $marker): bool
    {
        if (!file_exists($path)) {
            return false;
        }

        $contents = file_get_contents($path);

        return is_string($contents) && strpos($contents, $marker) !== false;
    }
}
