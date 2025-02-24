<?php

namespace WPStaging\Staging\Ajax\Delete;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Job\Ajax\PrepareJob;
use WPStaging\Framework\Job\Exception\ProcessLockedException;
use WPStaging\Staging\Dto\Job\StagingSiteDeleteDataDto;
use WPStaging\Staging\Jobs\StagingSiteDelete;

class PrepareDelete extends PrepareJob
{
    /** @var StagingSiteDeleteDataDto */
    private $jobDataDto;

    /** @var StagingSiteDelete */
    private $jobDelete;

    /**
     * @param array|null $data
     * @return void
     */
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
        }

        wp_send_json_success();
    }

    /**
     * @param array|null $data
     * @return array|\WP_Error
     */
    public function prepare($data = null)
    {
        if (empty($data) && array_key_exists('wpstgDeleteData', $_POST)) {
            $data = Sanitize::sanitizeArray($_POST['wpstgDeleteData'], [
                'isDeletingTables' => 'bool',
                'isDeletingFiles'  => 'bool',
                'cloneId'          => 'string',
            ]);
            $data['excludedTables'] = isset($_POST['wpstgDeleteData']['excludedTables']) ? Sanitize::sanitizeString($_POST['wpstgDeleteData']['excludedTables']) : [];
        }

        try {
            $sanitizedData = $this->setupInitialData($data);
        } catch (\Exception $e) {
            return new \WP_Error(400, $e->getMessage());
        }

        return $sanitizedData;
    }

    /**
     * @param $sanitizedData
     * @return array
     */
    private function setupInitialData($sanitizedData): array
    {
        $sanitizedData = $this->validateAndSanitizeData($sanitizedData);
        $this->clearCacheFolder();

        // Lazy-instantiation to avoid process-lock checks conflicting with running processes.
        $services = WPStaging::getInstance()->getContainer();
        /** @var StagingSiteDeleteDataDto */
        $this->jobDataDto = $services->get(StagingSiteDeleteDataDto::class);
        /** @var StagingSiteDelete */
        $this->jobDelete = $services->get(StagingSiteDelete::class);

        $this->jobDataDto->hydrate($sanitizedData);
        $this->jobDataDto->setInit(true);
        $this->jobDataDto->setFinished(false);
        $this->jobDataDto->setStartTime(time());

        $this->jobDataDto->setId(substr(md5(mt_rand() . time()), 0, 12));

        $this->jobDelete->setJobDataDto($this->jobDataDto);

        return $sanitizedData;
    }

    /**
     * @param $data
     * @return array
     */
    public function validateAndSanitizeData($data): array
    {
        // Unset any empty value so that we replace them with the defaults.
        foreach ($data as $key => $value) {
            if (empty($value)) {
                unset($data[$key]);
            }
        }

        $defaults = [
            'cloneId'          => '',
            'isDeletingFiles'  => false,
            'isDeletingTables' => false,
            'excludedTables'   => [],
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

        // Clone ID
        $data['cloneId'] = sanitize_text_field($data['cloneId']);

        // What to delete
        $data['isDeletingFiles']  = $this->jsBoolean($data['isDeletingFiles']);
        $data['isDeletingTables'] = $this->jsBoolean($data['isDeletingTables']);

        // Excluded tables
        $data['excludedTables'] = array_map('sanitize_text_field', $data['excludedTables']);

        if (empty($data['cloneId'])) {
            throw new \UnexpectedValueException("Invalid request. Missing 'cloneId'.");
        }

        return $data;
    }

    /**
     * Returns the reference to the current Job, if any.
     *
     * @return StagingSiteDelete|null The current reference to the Backup Job, if any.
     */
    public function getJob()
    {
        return $this->jobDelete;
    }

    /**
     * Persists the current Job status.
     *
     * @return bool Whether the current Job status was persisted or not.
     */
    public function persist(): bool
    {
        if (!$this->jobDelete instanceof StagingSiteDelete) {
            return false;
        }

        $this->jobDelete->persist();

        return true;
    }
}
