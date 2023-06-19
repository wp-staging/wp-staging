<?php

namespace WPStaging\Backup\Ajax\Backup;

use wpdb;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Analytics\Actions\AnalyticsBackupCreate;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Utils\Urls;
use WPStaging\Backup\Ajax\PrepareJob;
use WPStaging\Backup\BackupProcessLock;
use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Exceptions\ProcessLockedException;
use WPStaging\Backup\Job\JobBackupProvider;
use WPStaging\Backup\Job\Jobs\JobBackup;

class PrepareBackup extends PrepareJob
{
    /** @var JobBackupDataDto */
    private $jobDataDto;

    /** @var JobBackup */
    private $jobBackup;

    /** @var Urls */
    private $urls;

    /** @var AnalyticsBackupCreate */
    private $analyticsBackupCreate;

    /** @var wpdb */
    private $wpdb;

    public function __construct(Filesystem $filesystem, Directory $directory, Auth $auth, BackupProcessLock $processLock, Urls $urls, AnalyticsBackupCreate $analyticsBackupCreate)
    {
        parent::__construct($filesystem, $directory, $auth, $processLock);

        global $wpdb;

        $this->wpdb = $wpdb;
        $this->urls = $urls;
        $this->analyticsBackupCreate = $analyticsBackupCreate;
    }

    public function ajaxPrepare($data)
    {
        if (!$this->auth->isAuthenticatedRequest()) {
            wp_send_json_error(null, 401);
        }

        try {
            $this->processLock->checkProcessLocked();
        } catch (ProcessLockedException $e) {
            wp_send_json_error($e->getMessage(), $e->getCode());
        }

        $response = $this->prepare($data);

        if ($response instanceof \WP_Error) {
            wp_send_json_error($response->get_error_message(), $response->get_error_code());
        } else {
            $this->analyticsBackupCreate->enqueueStartEvent($this->jobDataDto->getId(), $this->jobDataDto);
            wp_send_json_success();
        }
    }

    public function prepare($data = null)
    {
        if (empty($data) && array_key_exists('wpstgBackupData', $_POST)) {
            $data = Sanitize::sanitizeArray($_POST['wpstgBackupData'], [
                'isExportingPlugins' => 'bool',
                'isExportingMuPlugins' => 'bool',
                'isExportingThemes' => 'bool',
                'isExportingUploads' => 'bool',
                'isExportingOtherWpContentFiles' => 'bool',
                'isExportingDatabase' => 'bool',
                'isAutomatedBackup' => 'bool',
                'repeatBackupOnSchedule' => 'bool',
                'scheduleRotation' => 'int',
                'isCreateScheduleBackupNow' => 'bool',
            ]);
            $data['name'] = isset($_POST['wpstgBackupData']['name']) ? sanitize_text_field($_POST['wpstgBackupData']['name']) : '';
        }

        try {
            $sanitizedData = $this->setupInitialData($data);
        } catch (\Exception $e) {
            return new \WP_Error(400, $e->getMessage());
        }

        return $sanitizedData;
    }

    private function setupInitialData($sanitizedData)
    {
        $sanitizedData = $this->validateAndSanitizeData($sanitizedData);
        $this->clearCacheFolder();

        // Lazy-instantiation to avoid process-lock checks conflicting with running processes.
        $services = WPStaging::getInstance()->getContainer();
        $this->jobDataDto = $services->get(JobBackupDataDto::class);
        $this->jobBackup  = $services->get(JobBackupProvider::class)->getJob();

        $this->jobDataDto->hydrate($sanitizedData);
        $this->jobDataDto->setInit(true);
        $this->jobDataDto->setFinished(false);
        $this->jobDataDto->setStartTime(time());

        $this->jobDataDto->setId(substr(md5(mt_rand() . time()), 0, 12));

        $this->jobBackup->setJobDataDto($this->jobDataDto);

        return $sanitizedData;
    }

    /**
     * @return array
     */
    public function validateAndSanitizeData($data)
    {
        // Unset any empty value so that we replace them with the defaults.
        foreach ($data as $key => $value) {
            if (empty($value)) {
                unset($data[$key]);
            }
        }

        $sites = [];

        if (is_multisite()) {
            // @todo remove once we give user options to select sites from ui
            $sites = $this->wpdb->get_results("SELECT blog_id, site_id, domain, path FROM {$this->wpdb->base_prefix}blogs");
        }

        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);
            $site->site_url = site_url();
            $site->home_url = home_url();
            restore_current_blog();
        }

        $defaults = [
            'name' => $this->urls->getBaseUrlWithoutScheme(),
            'isExportingPlugins' => false,
            'isExportingMuPlugins' => false,
            'isExportingThemes' => false,
            'isExportingUploads' => false,
            'isExportingOtherWpContentFiles' => false,
            'isExportingDatabase' => false,
            'isAutomatedBackup' => false,
            'repeatBackupOnSchedule' => false,
            'scheduleRecurrence' => '',
            'scheduleTime' => [0, 0],
            'scheduleRotation' => 1,
            // scheduleId will only be set for backups created automatically on a schedule.
            'scheduleId' => null,
            'storages' => [],
            'isCreateScheduleBackupNow' => false,
            'sitesToBackup' => $sites
        ];

        $data = wp_parse_args($data, $defaults);

        // Make sure data has no keys other than the expected ones.
        $data = array_intersect_key($data, $defaults);

        // Make sure data has all expected keys.
        foreach ($defaults as $expectedKey => $value) {
            if (!array_key_exists($expectedKey, $data)) {
                throw new \UnexpectedValueException("Invalid request. Missing '$expectedKey'.");
            }
        }

        // Sanitize data
        $data['name'] = substr(sanitize_text_field(html_entity_decode($data['name'])), 0, 100);

        // Foo\'s Backup => Foo's Backup
        $data['name'] = str_replace('\\\'', '\'', $data['name']);

        $data['isExportingPlugins'] = $this->jsBoolean($data['isExportingPlugins']);
        $data['isExportingMuPlugins'] = $this->jsBoolean($data['isExportingMuPlugins']);
        $data['isExportingThemes'] = $this->jsBoolean($data['isExportingThemes']);
        $data['isExportingUploads'] = $this->jsBoolean($data['isExportingUploads']);
        $data['isExportingOtherWpContentFiles'] = $this->jsBoolean($data['isExportingOtherWpContentFiles']);
        $data['isExportingDatabase'] = $this->jsBoolean($data['isExportingDatabase']);

        $data['repeatBackupOnSchedule'] = $this->jsBoolean($data['repeatBackupOnSchedule']);
        $data['scheduleRecurrence'] = sanitize_text_field(html_entity_decode($data['scheduleRecurrence']));
        $data['scheduleRotation'] = absint($data['scheduleRotation']);

        $data['scheduleTime'] = $this->createScheduleTimeArray($data['scheduleTime']);

        $data['isCreateScheduleBackupNow'] = $this->jsBoolean($data['isCreateScheduleBackupNow']);

        return $data;
    }

    /**
     * Depending on whether the scheduleTime is coming from JavaScript or being hydrated, this can be an array [0,0] or a string 00:00
     * @param $scheduleTime
     * @return array containing hour and minute ["18", "32"]
     */
    private function createScheduleTimeArray($scheduleTime)
    {
        if (empty($scheduleTime)) {
            return [0,0];
        }

        // It's already an array. Convert it into a string like "18:28" to process it further
        if (is_array($scheduleTime)) {
            $scheduleTime = implode(':', $scheduleTime);
        }

        // It's a string and matches, e.g "18:32". Convert it into an array ["18":"32"]
        if (preg_match('#\d+:\d+#', $scheduleTime)) {
            $scheduleTime = explode(':', $scheduleTime);
        } else {
            $scheduleTime = [0, 0];
        }
        return $scheduleTime;
    }

    /**
     * Returns the reference to the current Backup Job, if any.
     *
     * @return JobBackup|null The current reference to the Backup Job, if any.
     */
    public function getJobBackup()
    {
        return $this->jobBackup;
    }

    /**
     * Persists the current Job Backup status.
     *
     * @return bool Whether the current Job status was persisted or not.
     */
    public function persist()
    {
        if (!$this->jobBackup instanceof JobBackup) {
            return false;
        }

        $this->jobBackup->persist();

        return true;
    }
}
