<?php

namespace WPStaging\Framework\Logger;

use WP_REST_Request;
use WPStaging\Framework\Job\JobTransientCache;
use WPStaging\Framework\Rest\Rest;

/**
 * This class is used to push the stored sse events for the background jobs.
 * Providing a feel of realtime logger for the background jobs in the UI.
 */
class BackgroundLogger
{
    /**
     * @var SseEventCache
     */
    private $sseEventCache;

    /**
     * @var JobTransientCache
     */
    private $jobTransientCache;

    /**
     * @var int
     */
    private $lastPercentage = 0;

    /**
     * @var string
     */
    private $lastTaskTitle = '';

    public function __construct(SseEventCache $sseEventCache, JobTransientCache $jobTransientCache)
    {
        $this->sseEventCache     = $sseEventCache;
        $this->jobTransientCache = $jobTransientCache;
    }

    /**
     * Let set headers for the sse stream for sse route only, this is done to make sure that wordpress itself does not
     * send any headers before we do.
     * @param mixed $result
     * @param \WP_REST_Server $server
     * @param \WP_REST_Request $request
     *
     * @return mixed
     */
    public function maybePrepareSseStream($result, \WP_REST_Server $server, WP_REST_Request $request)
    {
        // Get the route being requested
        $route = trim($request->get_route(), '/');
        if ($route !== Rest::WPSTG_ROUTE_NAMESPACE_V1 . '/sse-logs') {
            return $result;
        }

        $this->setHeaders();

        return $result;
    }

    public function verifyRestRequest()
    {
        $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
        if ($token !== $this->jobTransientCache->getJobId()) {
            return new \WP_Error('rest_forbidden', __('You are not allowed to access this resource.', 'wp-staging'), ['status' => 403]);
        }

        return true;
    }

    /**
     * Don't use return, use exit/die to end the script, otherwise wordpress will try to change the header and generate warnings
     * @param \WP_REST_Request $request
     */
    public function restEventStream(WP_REST_Request $request)
    {
        @ini_set('zlib.output_compression', 0);
        @ini_set('output_buffering', 'off');
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        @ob_implicit_flush(1);
        flush();

        $this->setHeaders();

        echo ": connected\n\n"; // phpcs:ignore
        flush();

        if (!$this->isJobRunning()) {
            $this->closeStream();
        }

        $end   = microtime(true) + 5;
        $jobId = $this->jobTransientCache->getJobId();
        if (empty($jobId)) {
            $data = [
                'retry'   => true,
                'message' => esc_html__('No job ID found', 'wp-staging'),
            ];

            $this->output($jobId, 'error', json_encode($data));
            $this->closeStream();
        }

        $offset = intval($request->get_param('offset') ?? 0);
        $exists = $this->sseEventCache->setJobId($jobId, true);
        if (!$exists) {
            $data = [
                'retry' => false,
                'error' => esc_html__('Log file not found', 'wp-staging'),
            ];

            $this->output($jobId, 'error', json_encode($data));
            $this->closeStream();
        }

        while (microtime(true) < $end) {
            if (!$this->isJobRunning()) {
                $this->closeStream();
            }

            $this->sseEventCache->load();
            $total  = $this->sseEventCache->getCount();
            $events = $this->sseEventCache->getEvents($offset);

            foreach ($events as $event) {
                if ($event['type'] === 'task') {
                    $this->pushTaskProgress($jobId, $event['data']);
                    continue;
                }

                $this->output($jobId, '', json_encode($event));
            }

            $offset = $total;
            if (!$this->isJobRunning()) {
                $this->closeStream();
            }

            usleep(200000); // Sleep for 0.2 seconds
        }

        $this->output($jobId, 'offset', $offset);
        $this->closeStream();
    }

    protected function output(string $id, string $name, string $data)
    {
        echo "id: $id" . "\n"; // phpcs:ignore
        if (!empty($name)) {
            echo "event: $name" . "\n"; // phpcs:ignore
        }

        //use \n instead of PHP_EOL for add another line data: if is the same data object https://www.html5rocks.com/en/tutorials/eventsource/basics/#toc-js-api
        echo "data: $data" . "\n"; // phpcs:ignore
        echo "\n";

        // Flush all active output buffers
        while (ob_get_level() > 0) {
            @ob_end_flush(); // Use @ only to suppress harmless warnings
        }

        flush();
    }

    protected function isJobRunning(): bool
    {
        $status  = $this->jobTransientCache->getJobStatus();
        $jobData = $this->jobTransientCache->getJob();
        if ($status === JobTransientCache::STATUS_RUNNING) {
            return true;
        }

        $data = [];

        if ($status === JobTransientCache::STATUS_CANCELLED) {
            $this->output($jobData['jobId'], 'task', json_encode([
                'percentage' => 80,
                'title'      => esc_html__('Processing...', 'wp-staging'),
            ]));
            $data['title'] = $jobData['title'];
        } elseif ($status === JobTransientCache::STATUS_FAILED) {
            $data['message'] = esc_html__('Job failed', 'wp-staging');
        } elseif ($status === JobTransientCache::STATUS_SUCCESS) {
            $data['message'] = esc_html__('Job completed successfully', 'wp-staging');
        }

        $this->output('', $status, json_encode($data));
        return false;
    }

    protected function pushTaskProgress(string $jobId, array $taskData)
    {
        if ($taskData['percentage'] === $this->lastPercentage && $taskData['title'] === $this->lastTaskTitle) {
            return;
        }

        $this->lastPercentage = $taskData['percentage'];
        $this->lastTaskTitle  = $taskData['title'];

        $this->output($jobId, 'task', json_encode($taskData));
    }

    /**
     * Close the stream and exit the script
     * @return never
     */
    protected function closeStream()
    {
        echo ": stream closed\n\n"; // phpcs:ignore
        flush();
        exit();
    }

    protected function setHeaders()
    {
        if (headers_sent()) {
            return;
        }

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // For nginx
    }
}
