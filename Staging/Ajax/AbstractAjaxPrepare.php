<?php

namespace WPStaging\Staging\Ajax;

use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Filesystem\Scanning\ScanConst;
use WPStaging\Framework\Job\Ajax\PrepareJob;
use WPStaging\Framework\Job\Exception\ProcessLockedException;

abstract class AbstractAjaxPrepare extends PrepareJob
{
    /** @var string */
    protected $postDataKey = '';

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
        if (empty($this->postDataKey)) {
            return new \WP_Error(400, "Invalid request. Missing 'postDataKey' in Ajax Prepare class.");
        }

        if (empty($data) && array_key_exists($this->postDataKey, $_POST)) {
            $data = $this->postDataSanitization();
        }

        try {
            $sanitizedData = $this->setupInitialJob($data);
        } catch (\Exception $e) {
            return new \WP_Error(400, $e->getMessage());
        }

        $this->deleteSseCacheFiles();

        return $sanitizedData;
    }

    /**
     * @param array|null $data
     * @return array
     */
    public function validateAndSanitizeData($data): array
    {
        if (empty($data)) {
            $data = [];
        }

        // Unset any empty value so that we replace them with the defaults.
        foreach ($data as $key => $value) {
            if ($value === '' || $value === null) {
                unset($data[$key]);
            }
        }

        $defaults = $this->getDefaults();

        $data = wp_parse_args($data, $defaults);

        // Make sure data has no keys other than the expected ones.
        $data = array_intersect_key($data, $defaults);

        // Make sure data has all expected keys.
        foreach ($defaults as $expectedKey => $value) {
            if (!array_key_exists($expectedKey, $data)) {
                throw new \UnexpectedValueException("Invalid request. Missing '$expectedKey'.");
            }
        }

        $data = $this->additionalSanitization($data);

        return $data;
    }

    abstract protected function getDefaults(): array;

    abstract protected function postDataSanitization(): array;

    abstract protected function additionalSanitization(array $data): array;

    /**
     * @param array|null $sanitizedData
     * @return array
     */
    abstract protected function setupInitialData($sanitizedData): array;

    protected function parseAndSanitizeTables(string $tables): array
    {
        $tables = $tables === '' ? [] : explode(ScanConst::DIRECTORIES_SEPARATOR, $tables);

        return array_map('sanitize_text_field', $tables);
    }

    protected function parseAndSanitizeDirectories(string $directories): array
    {
        $directories = $directories === '' ? [] : explode(ScanConst::DIRECTORIES_SEPARATOR, Sanitize::sanitizeString($directories));

        return array_map('sanitize_text_field', $directories);
    }

    abstract protected function prepareStagingSiteDto();
}
