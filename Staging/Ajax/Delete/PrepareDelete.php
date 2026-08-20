<?php

namespace WPStaging\Staging\Ajax\Delete;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Job\Ajax\PrepareJob;
use WPStaging\Framework\Job\Exception\ProcessLockedException;
use WPStaging\Framework\Job\JobTransientCache;
use WPStaging\Staging\Dto\Job\StagingSiteDeleteDataDto;
use WPStaging\Staging\Jobs\StagingSiteDelete;

class PrepareDelete extends PrepareJob
{
 
    private $jobDataDto;

 
    private $jobDelete;





    public function ajaxPrepare($data)
    {
        if (!$this->auth->isAuthenticatedRequest()) {
            wp_send_json_error(null, 401);
        }

        try {
            $this->processLock->lockProcess();
        } catch (ProcessLockedException $e) {
            wp_send_json_error($e->getMessage(), $e->getCode());
        }

        $response = $this->prepare($data);

        if ($response instanceof \WP_Error) {
            wp_send_json_error($response->get_error_message(), $response->get_error_code());
        }

        wp_send_json_success();
    }





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
            $sanitizedData = $this->setupInitialJob($data);
        } catch (\Exception $e) {
            return new \WP_Error(400, $e->getMessage());
        }

        $this->deleteSseCacheFiles();

        return $sanitizedData;
    }





    protected function setupInitialData($sanitizedData): array
    {
        $sanitizedData = $this->validateAndSanitizeData($sanitizedData);
        $this->clearCacheFolder();

 
        $services = WPStaging::getInstance()->getContainer();
 
        $this->jobDataDto = $services->get(StagingSiteDeleteDataDto::class);
 
        $this->jobDelete = $services->get(StagingSiteDelete::class);

        $this->jobDataDto->hydrate($sanitizedData);
        $this->jobDataDto->setInit(true);
        $this->jobDataDto->setFinished(false);
        $this->jobDataDto->setStartTime(time());

        $this->jobDataDto->setId(substr(md5(mt_rand() . time()), 0, 12));

        $this->jobDelete->getTransientCache()->startJob($this->jobDataDto->getId(), esc_html__('Staging Site Delete in Progress', 'wp-staging'), JobTransientCache::JOB_TYPE_STAGING_DELETE, $this->queueId);

        $this->jobDelete->setJobDataDto($this->jobDataDto);

        return $sanitizedData;
    }





    public function validateAndSanitizeData($data): array
    {
 
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

 
        $data = array_intersect_key($data, $defaults);

 
        foreach ($defaults as $expectedKey => $value) {
            if (!array_key_exists($expectedKey, $data)) {
                throw new \UnexpectedValueException("Invalid request. Missing '$expectedKey'.");
            }
        }

 
        $data['cloneId'] = sanitize_text_field($data['cloneId']);

 
        $data['isDeletingFiles']  = $this->jsBoolean($data['isDeletingFiles']);
        $data['isDeletingTables'] = $this->jsBoolean($data['isDeletingTables']);

        if (!$data['isDeletingFiles'] && !$data['isDeletingTables']) {
            throw new \UnexpectedValueException('Invalid request. Select at least one item to delete.');
        }

 
        $data['excludedTables'] = array_map('sanitize_text_field', $data['excludedTables']);

        if (empty($data['cloneId'])) {
            throw new \UnexpectedValueException("Invalid request. Missing 'cloneId'.");
        }

        return $data;
    }






    public function getJob()
    {
        return $this->jobDelete;
    }






    public function persist(): bool
    {
        if (!$this->jobDelete instanceof StagingSiteDelete) {
            return false;
        }

        $this->jobDelete->persist();

        return true;
    }
}
