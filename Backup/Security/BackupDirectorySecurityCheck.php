<?php

namespace WPStaging\Backup\Security;

use Throwable;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Utils\ServerVars;
use WPStaging\Framework\Utils\Urls;

use function WPStaging\functions\debug_log;





class BackupDirectorySecurityCheck
{
 
    const FILTER_ENABLED = 'wpstg.backup_directory_security_check.enabled';

 
    const FILTER_CACHE_TTL = 'wpstg.backup_directory_security_check.cache_ttl';

 
    const OPTION_RESULT = 'wpstg_backup_directory_security_check';

 
    const DEFAULT_CACHE_TTL = DAY_IN_SECONDS;

 
    const REQUEST_TIMEOUT = 10;

 
    const STATUS_PROTECTED = 'protected';

 
    const STATUS_PUBLICLY_REACHABLE = 'publicly_reachable';

 
    const STATUS_INCONCLUSIVE = 'inconclusive';

 
    const STATUS_NOT_TESTED = 'not_tested';

 
    const PROBE_PREFIX = 'wpstg-security-check-';

 
    private $backupsFinder;

 
    private $urls;

 
    private $serverVars;

    public function __construct(BackupsFinder $backupsFinder, Urls $urls, ServerVars $serverVars)
    {
        $this->backupsFinder = $backupsFinder;
        $this->urls          = $urls;
        $this->serverVars    = $serverVars;
    }

    public function isEnabled(): bool
    {
        return (bool)Hooks::applyFilters(self::FILTER_ENABLED, true);
    }

 
    public function getResult(): array
    {
        $notTested = $this->buildResult(self::STATUS_NOT_TESTED, 0, '');
        $result    = get_option(self::OPTION_RESULT, []);

        if (!is_array($result) || empty($result['status'])) {
            return $notTested;
        }

        return array_merge($notTested, $result);
    }

 
    public function isDue(): bool
    {
        $result = $this->getResult();

        if ($result['status'] === self::STATUS_NOT_TESTED) {
            return true;
        }

        $cacheTtl = (int)Hooks::applyFilters(self::FILTER_CACHE_TTL, self::DEFAULT_CACHE_TTL);

        return (time() - (int)$result['checkedAt']) >= max($cacheTtl, HOUR_IN_SECONDS);
    }

 
    public function maybeRun(): array
    {
        if (!$this->isEnabled() || !$this->isDue()) {
            return $this->getResult();
        }

        return $this->run();
    }

 
    public function run(): array
    {
        $probePath = '';

        try {
            $backupsDirectory = trailingslashit($this->backupsFinder->getBackupsDirectory());
            $probeName        = self::PROBE_PREFIX . wp_generate_password(12, false) . '.txt';
            $probePath        = $backupsDirectory . $probeName;

            if (file_put_contents($probePath, 'WP STAGING security check. This file is deleted automatically.') === false) {
                return $this->storeResult(self::STATUS_INCONCLUSIVE, 0, 'The security check file could not be created.');
            }

            return $this->fetchProbe($probeName);
        } catch (Throwable $e) {
            debug_log('WP STAGING: The backup folder security check could not be completed. ' . $e->getMessage());

            return $this->storeResult(self::STATUS_INCONCLUSIVE, 0, $e->getMessage());
        } finally {
            if ($probePath !== '' && file_exists($probePath)) {
                unlink($probePath);
            }
        }
    }

    private function fetchProbe(string $probeName): array
    {
        $probeUrl = $this->urls->getBackupUrl() . $probeName;

        if (strpos($probeUrl, '//') === 0) {
            $probeUrl = (is_ssl() ? 'https:' : 'http:') . $probeUrl;
        }

        $response = wp_remote_get($probeUrl, [
            'timeout'     => self::REQUEST_TIMEOUT,
            'redirection' => 0,
            'sslverify'   => false,
            'headers'     => ['Range' => 'bytes=0-63'],
        ]);

        if (is_wp_error($response)) {
            return $this->storeResult(self::STATUS_INCONCLUSIVE, 0, $response->get_error_message());
        }

        $httpCode = (int)wp_remote_retrieve_response_code($response);

        if (in_array($httpCode, [200, 206], true)) {
            return $this->storeResult(self::STATUS_PUBLICLY_REACHABLE, $httpCode, '', $this->hasDirectoryListing($probeName));
        }

        if (in_array($httpCode, [401, 403, 404], true)) {
            return $this->storeResult(self::STATUS_PROTECTED, $httpCode, '');
        }

        return $this->storeResult(self::STATUS_INCONCLUSIVE, $httpCode, '');
    }

 
    private function hasDirectoryListing(string $probeName): bool
    {
        $response = wp_remote_get($this->urls->getBackupUrl(), [
            'timeout'     => self::REQUEST_TIMEOUT,
            'redirection' => 0,
            'sslverify'   => false,
        ]);

        if (is_wp_error($response) || (int)wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }

        return strpos((string)wp_remote_retrieve_body($response), $probeName) !== false;
    }

    private function storeResult(string $status, int $httpCode, string $message, bool $directoryListing = false): array
    {
        $result = $this->buildResult($status, $httpCode, $message, $directoryListing);

        update_option(self::OPTION_RESULT, $result, false);

        debug_log(sprintf('WP STAGING: Backup folder security check result: %s (HTTP %d, nginx %s).', $status, $httpCode, $result['isNginx'] ? 'yes' : 'no'));

        return $result;
    }

    private function buildResult(string $status, int $httpCode, string $message, bool $directoryListing = false): array
    {
        return [
            'status'           => $status,
            'checkedAt'        => $status === self::STATUS_NOT_TESTED ? 0 : time(),
            'isNginx'          => $this->serverVars->isNginx(),
            'httpCode'         => $httpCode,
            'message'          => $message,
            'directoryListing' => $directoryListing,
        ];
    }
}
