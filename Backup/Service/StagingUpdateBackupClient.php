<?php

namespace WPStaging\Backup\Service;

use Throwable;
use WPStaging\Backup\BackgroundProcessing\Backup\PrepareBackup;
use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Dto\Task\Backup\Response\FinalizeBackupResponseDto;
use WPStaging\Backup\Task\Tasks\JobBackup\FinishBackupTask;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\SourceDatabase;
use WPStaging\Framework\BackgroundProcessing\FeatureDetection;
use WPStaging\Framework\BackgroundProcessing\Queue;
use WPStaging\Framework\BackgroundProcessing\QueueProcessor;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Network\HttpBasicAuth;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Traits\SerializeTrait;
use WPStaging\Staging\Sites;









class StagingUpdateBackupClient
{
    use HttpBasicAuth;
    use SerializeTrait;

 
    const JOB_ID_PREFIX = 'wpstg_staging_update_';

 
    const TRANSIENT_JOB_PREFIX = 'wpstg_staging_update_job_';

 
    const ACTION_MONITOR = 'wpstg_staging_update_backup_monitor';

 
    const JOB_LIFETIME_IN_SECONDS = DAY_IN_SECONDS;

 
    const COMPLETED_JOB_LIFETIME_IN_SECONDS = 5 * MINUTE_IN_SECONDS;

 
    const STALLED_AFTER_SECONDS = 5 * MINUTE_IN_SECONDS;

 
    private $sites;

 
    private $sourceDatabase;





    public function __construct(Sites $sites, SourceDatabase $sourceDatabase)
    {
        $this->sites          = $sites;
        $this->sourceDatabase = $sourceDatabase;
    }






    public function request(string $operation, string $cloneId): array
    {
        if (!in_array($operation, ['reusable', 'start', 'progress'], true) || $cloneId === '') {
            return $this->error(esc_html__('Staging site not found.', 'wp-staging'));
        }

 
 
        if ($operation === 'reusable') {
            return ['success' => true, 'data' => []];
        }

        try {
            $target = $this->getVerifiedTarget($cloneId);
            if ($target === null) {
                return $this->error(esc_html__('Staging site not found.', 'wp-staging'));
            }

            if ($operation === 'start') {
                return $this->start($target, $cloneId);
            }

            return $this->progress($target, $cloneId);
        } catch (Throwable $e) {
            return $this->error(esc_html__('Backup request failed. Please try again.', 'wp-staging'));
        }
    }






    private function start(array $target, string $cloneId): array
    {
        $lockName = $this->getJobLockName($target['scheduleId']);
        if (!$this->acquireStartLock($target['database'], $lockName)) {
            return [
                'success' => false,
                'data'    => [
                    'message' => esc_html__('A backup is already running.', 'wp-staging'),
                    'code'    => 'already_running',
                ],
            ];
        }

        try {
            return $this->startWhileLocked($target, $cloneId);
        } finally {
            $this->releaseStartLock($target['database'], $lockName);
        }
    }






    private function startWhileLocked(array $target, string $cloneId): array
    {
        $existingJobId = $this->getJobId($cloneId);
        if ($existingJobId !== '' && $this->hasActiveJob($target['database'], $target['queueTable'], $existingJobId)) {
            return [
                'success' => false,
                'data'    => [
                    'message' => esc_html__('A backup is already running.', 'wp-staging'),
                    'code'    => 'already_running',
                ],
            ];
        }

        $jobId = self::JOB_ID_PREFIX . wp_generate_password(20, false);
        $args  = $this->getBackupData($jobId, $target['scheduleId']);
        $now   = (new \DateTimeImmutable('now', $target['timezone']))->format('Y-m-d H:i:s');

        $inserted = $target['database']->insert(
            $target['queueTable'],
            [
                'action'     => PrepareBackup::class . '::act',
                'jobId'      => $jobId,
                'status'     => 'ready',
                'priority'   => 0,
                'args'       => maybe_serialize($args),
                'updated_at' => $now,
            ],
            ['%s', '%s', '%s', '%d', '%s', '%s']
        );

        if ($inserted === false) {
            return $this->error(esc_html__('The backup could not be started.', 'wp-staging'), 'start_failed');
        }

        set_transient($this->getTransientName($cloneId), [
            'jobId'     => $jobId,
            'startedAt' => time(),
        ], self::JOB_LIFETIME_IN_SECONDS);
        $this->scheduleMonitor($cloneId);
        $this->triggerQueue($target['url'], $target['httpAuthHeaders']);

        return ['success' => true, 'data' => ['status' => 'running']];
    }









    private function acquireStartLock($database, string $lockName): bool
    {
        $query = $database->prepare('SELECT GET_LOCK(%s, 0)', $lockName);

        return (int)$database->get_var($query) === 1;
    }






    private function releaseStartLock($database, string $lockName)
    {
        $query = $database->prepare('SELECT RELEASE_LOCK(%s)', $lockName);
        $database->get_var($query);
    }








    public function monitor(string $cloneId)
    {
        try {
            $target = $this->getVerifiedTarget($cloneId);
            if ($target === null) {
                return;
            }

 
 
            $result = $this->progress($target, $cloneId, false);
        } catch (Throwable $e) {
            return;
        }

        $status = isset($result['data']['status']) ? (string)$result['data']['status'] : '';
        if ($result['success'] && $status === 'running') {
            $this->scheduleMonitor($cloneId);
        }
    }







    private function progress(array $target, string $cloneId, bool $failIfStalled = true): array
    {
        $jobId = $this->getJobId($cloneId);
        if ($jobId === '') {
            return $this->progressError(esc_html__('Backup request failed. Please try again.', 'wp-staging'));
        }

        if ($this->isCompletedJob($cloneId, $jobId)) {
            return $this->completedProgress();
        }

        $rows = $this->getJobRows($target['database'], $target['queueTable'], $jobId);
        if ($rows === []) {
            $this->clearJobHandle($target, $cloneId, $jobId);
            return $this->progressError(esc_html__('Backup request failed. Please try again.', 'wp-staging'));
        }

        foreach ($rows as $row) {
            if ($row['status'] === 'failed') {
                $this->clearJobHandle($target, $cloneId, $jobId);
                return $this->progressError(esc_html__('Backup request failed. Please try again.', 'wp-staging'));
            }
        }

        $latestResponse = $this->getLatestResponse($rows);
        if ($this->hasActiveRows($rows)) {
            if ($failIfStalled && $this->isStalled($rows, $target['timezone'])) {
                $this->clearJobHandle($target, $cloneId, $jobId);
                return $this->progressError(esc_html__('Backup request failed. Please try again.', 'wp-staging'));
            }

            $this->triggerQueue($target['url'], $target['httpAuthHeaders']);

            return ['success' => true, 'data' => $this->runningProgress($latestResponse)];
        }

        if (!$latestResponse instanceof TaskResponseDto || $latestResponse->isRunning() || $this->isFailureResponse($latestResponse)) {
            $this->clearJobHandle($target, $cloneId, $jobId);
            return $this->progressError(esc_html__('Backup request failed. Please try again.', 'wp-staging'));
        }

        $this->pruneCompletedRecoveryPoints($target, $cloneId);
        $this->markJobCompleted($target, $cloneId, $jobId);

        return $this->completedProgress();
    }





    private function getVerifiedTarget(string $cloneId)
    {
        $stagingSites = $this->sites->tryGettingStagingSites(true);
        if (!isset($stagingSites[$cloneId]) || !is_array($stagingSites[$cloneId])) {
            return null;
        }

        $stagingSite = $this->sites->getStagingSiteDtoByCloneId($cloneId);
        $realPath    = realpath($stagingSite->getPath());
        if ($realPath === false || !is_dir($realPath)) {
            return null;
        }

        $this->sourceDatabase->setOptions((object)$stagingSites[$cloneId]);
        $database     = $this->sourceDatabase->getDatabase();
        $prefix       = $this->validatePrefix($stagingSite->getUsedPrefix());
        $optionsTable = $prefix . 'options';
        $queueTable   = $prefix . 'wpstg_queue';
        $url          = $this->getVerifiedUrl($database, $optionsTable, $stagingSite->getUrl());

        if ($url === '' || !$this->queueTableExists($database, $queueTable)) {
            return null;
        }

        return [
            'database'        => $database,
            'queueTable'      => $queueTable,
            'optionsTable'    => $optionsTable,
            'rootPath'        => wp_normalize_path($stagingSite->getPath()),
            'realRootPath'    => wp_normalize_path($realPath),
            'url'             => $url,
            'scheduleId'      => BeforeUpdateBackupsService::getStagingUpdateScheduleId($cloneId, $url, $realPath),
            'timezone'        => $this->getTargetTimezone($database, $optionsTable),
            'httpAuthHeaders' => $this->getTargetHttpAuthHeaders($database, $optionsTable),
        ];
    }









    private function getTargetHttpAuthHeaders($database, string $table): array
    {
        $query       = $database->prepare("SELECT option_value FROM `{$table}` WHERE option_name = %s LIMIT 1", Queue::OPTION_HTTP_AUTH_CREDENTIALS);
        $credentials = $this->safeMaybeUnserialize((string)$database->get_var($query));

        return $this->buildHttpAuthHeaders($credentials);
    }









    private function getTargetTimezone($database, string $table): \DateTimeZone
    {
        $query          = $database->prepare("SELECT option_value FROM `{$table}` WHERE option_name = %s LIMIT 1", 'timezone_string');
        $timezoneString = (string)$database->get_var($query);

        if ($timezoneString !== '') {
            try {
                return new \DateTimeZone($timezoneString);
            } catch (\Exception $e) {
 
            }
        }

        $query  = $database->prepare("SELECT option_value FROM `{$table}` WHERE option_name = %s LIMIT 1", 'gmt_offset');
        $offset = (float)$database->get_var($query);
        $hours  = (int)$offset;
        $minutes = (int)round(abs($offset - $hours) * 60);
        $timezoneString = sprintf('%s%02d:%02d', $offset < 0 ? '-' : '+', abs($hours), $minutes);

        try {
            return new \DateTimeZone($timezoneString);
        } catch (\Exception $e) {
            return new \DateTimeZone('UTC');
        }
    }





    private function validatePrefix(string $prefix): string
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $prefix)) {
            throw new \UnexpectedValueException('Invalid staging database prefix.');
        }

        return $prefix;
    }







    private function getVerifiedUrl($database, string $table, string $registeredUrl): string
    {
        $query       = $database->prepare("SELECT option_value FROM `{$table}` WHERE option_name = %s LIMIT 1", 'home');
        $databaseUrl = (string)$database->get_var($query);

        $query         = $database->prepare("SELECT option_value FROM `{$table}` WHERE option_name = %s LIMIT 1", SiteInfo::IS_STAGING_KEY);
        $isStagingSite = (string)$database->get_var($query);

        if ($isStagingSite !== 'true' || $databaseUrl === '' || untrailingslashit($databaseUrl) !== untrailingslashit($registeredUrl)) {
            return '';
        }

        return $databaseUrl;
    }






    private function queueTableExists($database, string $table): bool
    {
        $query = $database->prepare('SHOW TABLES LIKE %s', $database->esc_like($table));

        return (string)$database->get_var($query) === $table;
    }






    private function getBackupData(string $jobId, string $scheduleId): array
    {
        return [
            'jobId'                          => $jobId,
            'name'                           => esc_html__('Before staging site update', 'wp-staging'),
            'scheduleId'                     => $scheduleId,
            'isInit'                         => true,
            'isExportingPlugins'             => true,
            'isExportingMuPlugins'           => true,
            'isExportingThemes'              => true,
            'isExportingUploads'             => true,
            'isExportingOtherWpContentFiles' => true,
            'isExportingOtherWpRootFiles'    => true,
            'isExportingDatabase'            => true,
            'isBeforeUpdateBackup'           => true,
            'isAutomatedBackup'              => true,
            'repeatBackupOnSchedule'         => false,
            'storages'                       => ['localStorage'],
            'isSmartExclusion'               => true,
            'isExcludingCaches'              => true,
            'isExcludingLogs'                => true,
            'isWpCliRequest'                 => true,
        ];
    }







    private function getJobRows($database, string $table, string $jobId): array
    {
        $query = $database->prepare(
            "SELECT status, response, updated_at FROM `{$table}` WHERE jobId = %s ORDER BY id DESC",
            $jobId
        );
        $rows = $database->get_results($query, ARRAY_A);

        return is_array($rows) ? $rows : [];
    }







    private function hasActiveJob($database, string $table, string $jobId): bool
    {
        return $this->hasActiveRows($this->getJobRows($database, $table, $jobId));
    }





    private function hasActiveRows(array $rows): bool
    {
        foreach ($rows as $row) {
            if (in_array($row['status'], ['ready', 'processing'], true)) {
                return true;
            }
        }

        return false;
    }






    private function isStalled(array $rows, \DateTimeZone $timezone): bool
    {
        foreach ($rows as $row) {
            if (!in_array($row['status'], ['ready', 'processing'], true)) {
                continue;
            }

            try {
                $updatedAt = new \DateTimeImmutable($row['updated_at'], $timezone);
            } catch (\Exception $e) {
                return false;
            }

            return $updatedAt->getTimestamp() < time() - self::STALLED_AFTER_SECONDS;
        }

        return false;
    }





    private function getLatestResponse(array $rows)
    {
        foreach ($rows as $row) {
            if ((string)$row['response'] === '') {
                continue;
            }

            $response = $this->safeMaybeUnserialize((string)$row['response'], [
                TaskResponseDto::class,
                FinalizeBackupResponseDto::class,
            ]);
            if ($response instanceof TaskResponseDto) {
                return $response;
            }
        }

        return null;
    }





    private function runningProgress($response): array
    {
        return [
            'status'         => 'running',
            'title'          => $response instanceof TaskResponseDto ? (string)$response->getStatusTitle() : esc_html__('Backup in Progress', 'wp-staging'),
            'percentage'     => $response instanceof TaskResponseDto ? (int)$response->getPercentage() : 0,
            'failureReason'  => '',
            'failureDetails' => '',
            'isPaused'       => false,
        ];
    }




    private function completedProgress(): array
    {
        return [
            'success' => true,
            'data'    => [
                'status'         => 'completed',
                'title'          => esc_html__('Backup Complete', 'wp-staging'),
                'percentage'     => 100,
                'failureReason'  => '',
                'failureDetails' => '',
                'isPaused'       => false,
            ],
        ];
    }





    private function isFailureResponse(TaskResponseDto $response): bool
    {
        return in_array($response->getJobStatus(), ['JOB_FAIL', 'JOB_CANCEL', 'JOB_NOTHING_TO_BACKUP'], true);
    }






    private function triggerQueue(string $url, array $headers = [])
    {
 
 
        $sslVerify = empty($headers) ? apply_filters(FeatureDetection::FILTER_HTTPS_LOCAL_SSL_VERIFY, false) : true;

        wp_remote_post(trailingslashit($url) . 'wp-admin/admin-ajax.php', [
            'timeout'   => 0.01,
            'blocking'  => false,
            'sslverify' => $sslVerify,
            'headers'   => $headers,
            'body'      => ['action' => QueueProcessor::ACTION_QUEUE_PROCESS],
        ]);
    }





    private function getJobId(string $cloneId): string
    {
        $job = get_transient($this->getTransientName($cloneId));
        $jobId = is_array($job) && isset($job['jobId']) ? $job['jobId'] : $job;

        return is_string($jobId) && strpos($jobId, self::JOB_ID_PREFIX) === 0 ? $jobId : '';
    }





    private function getJobLockName(string $scheduleId): string
    {
        return self::JOB_ID_PREFIX . substr(hash('sha256', $scheduleId), 0, 32);
    }






    private function isCompletedJob(string $cloneId, string $jobId): bool
    {
        $job = get_transient($this->getTransientName($cloneId));

        return is_array($job)
            && isset($job['jobId'], $job['completedAt'])
            && $job['jobId'] === $jobId
            && (int)$job['completedAt'] > 0;
    }










    private function markJobCompleted(array $target, string $cloneId, string $jobId)
    {
        $lockName = $this->getJobLockName($target['scheduleId']);
        if (!$this->acquireStartLock($target['database'], $lockName)) {
            return;
        }

        try {
            $job = get_transient($this->getTransientName($cloneId));
            if (!is_array($job) || !isset($job['jobId']) || $job['jobId'] !== $jobId) {
                return;
            }

            set_transient($this->getTransientName($cloneId), [
                'jobId'       => $jobId,
                'startedAt'   => isset($job['startedAt']) ? (int)$job['startedAt'] : 0,
                'completedAt' => time(),
            ], self::COMPLETED_JOB_LIFETIME_IN_SECONDS);
        } finally {
            $this->releaseStartLock($target['database'], $lockName);
        }
    }










    private function clearJobHandle(array $target, string $cloneId, string $jobId)
    {
        $lockName = $this->getJobLockName($target['scheduleId']);
        if (!$this->acquireStartLock($target['database'], $lockName)) {
            return;
        }

        try {
            if ($this->getJobId($cloneId) === $jobId) {
                delete_transient($this->getTransientName($cloneId));
            }
        } finally {
            $this->releaseStartLock($target['database'], $lockName);
        }
    }









    private function pruneCompletedRecoveryPoints(array $target, string $cloneId)
    {
        $job = get_transient($this->getTransientName($cloneId));
        $startedAt = is_array($job) && isset($job['startedAt']) ? (int)$job['startedAt'] : 0;
        $query = $target['database']->prepare(
            "SELECT option_value FROM `{$target['optionsTable']}` WHERE option_name = %s LIMIT 1",
            FinishBackupTask::OPTION_LAST_BACKUP
        );
        $serialized = (string)$target['database']->get_var($query);
        $lastBackup = $this->safeMaybeUnserialize($serialized, [JobBackupDataDto::class, 'stdClass']);

        if (!is_array($lastBackup) || !isset($lastBackup['JobBackupDataDto']) || !$lastBackup['JobBackupDataDto'] instanceof JobBackupDataDto) {
            return;
        }

        if ($startedAt > 0 && (!isset($lastBackup['endTime']) || (int)$lastBackup['endTime'] < $startedAt)) {
            return;
        }

        $jobDataDto = $lastBackup['JobBackupDataDto'];
        if ((string)$jobDataDto->getScheduleId() !== $target['scheduleId']) {
            return;
        }

        $backupPath     = wp_normalize_path((string)$jobDataDto->getBackupFilePath());
        $realBackupPath = realpath($backupPath);
        if ($backupPath === '' || strpos($backupPath, '/../') !== false || $realBackupPath === false || !is_file($backupPath)) {
            return;
        }

        $isInsideRegisteredRoot = $this->isPathPrefixedByRoot($backupPath, $target['rootPath']);
        $isInsideRealRoot       = $this->isPathPrefixedByRoot(wp_normalize_path($realBackupPath), $target['realRootPath']);
        if (!$isInsideRegisteredRoot && !$isInsideRealRoot) {
            return;
        }

        WPStaging::make(BeforeUpdateBackupsService::class)->pruneForScheduleInDirectory($target['scheduleId'], dirname($backupPath));
    }






    private function isPathPrefixedByRoot(string $path, string $root): bool
    {
        return strpos(wp_normalize_path($path), trailingslashit(wp_normalize_path($root))) === 0;
    }





    private function getTransientName(string $cloneId): string
    {
        return self::TRANSIENT_JOB_PREFIX . md5($cloneId);
    }





    private function scheduleMonitor(string $cloneId)
    {
        $args = [$cloneId];
        if (wp_next_scheduled(self::ACTION_MONITOR, $args) === false) {
            wp_schedule_single_event(time() + MINUTE_IN_SECONDS, self::ACTION_MONITOR, $args);
        }
    }





    private function progressError(string $message): array
    {
        return [
            'success' => true,
            'data'    => [
                'status'         => 'failed',
                'failureReason'  => '',
                'failureDetails' => $message,
                'isPaused'       => false,
            ],
        ];
    }






    private function error(string $message, string $code = ''): array
    {
        return [
            'success' => false,
            'data'    => [
                'message'        => $message,
                'code'           => $code,
                'failureDetails' => $message,
            ],
        ];
    }
}
