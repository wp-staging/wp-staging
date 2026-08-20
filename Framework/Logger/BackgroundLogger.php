<?php

namespace WPStaging\Framework\Logger;

use WP_REST_Request;
use WPStaging\Core\Utils\Logger;
use WPStaging\Framework\Job\JobTransientCache;
use WPStaging\Framework\Rest\Rest;
use WPStaging\Framework\Traits\SetTimeLimitTrait;





class BackgroundLogger
{
    use SetTimeLimitTrait;




    private $sseEventCache;




    private $jobTransientCache;





    const STALE_JOB_THRESHOLD_SECONDS = 60;




    private $lastPercentage = 0;




    private $lastTaskTitle = '';

    public function __construct(SseEventCache $sseEventCache, JobTransientCache $jobTransientCache)
    {
        $this->sseEventCache     = $sseEventCache;
        $this->jobTransientCache = $jobTransientCache;
    }










    public function maybePrepareSseStream($result, \WP_REST_Server $server, WP_REST_Request $request)
    {
 
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
        $jobId = $this->jobTransientCache->getJobId();

 
 
 
 
        if ($token === '' || $jobId === '' || !hash_equals((string)$jobId, $token)) {
            return new \WP_Error('rest_forbidden', __('You are not allowed to access this resource.', 'wp-staging'), ['status' => 403]);
        }

        return true;
    }





    public function restEventStream(WP_REST_Request $request)
    {
 
 
        @ini_set('zlib.output_compression', '0');
        @ini_set('output_buffering', 'off');
        @ini_set('implicit_flush', '1');
        $this->setTimeLimit(0);
        @ignore_user_abort(true);
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
            @apache_setenv('dont-vary', '1');
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

 
        if (PHP_VERSION_ID >= 80000) {
            // @phpstan-ignore-next-line - PHPStan stubs may expect int for compatibility
            @ob_implicit_flush(true);
        } else {
            // @phpstan-ignore-next-line - PHP < 8.0 expects int, not bool
            @ob_implicit_flush(1);
        }
        flush();

        $this->setHeaders();

 
        echo ":" . str_repeat(' ', 2048) . "\n\n"; // phpcs:ignore
        echo "retry: 3000\n\n"; // phpcs:ignore
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

        $lastHeartbeat = microtime(true);
        while (microtime(true) < $end) {
            if (connection_aborted()) {
                $this->closeStream();
            }

            if (!$this->isJobRunning()) {
                $this->closeStream();
            }

            $this->sseEventCache->load();
            $total  = $this->sseEventCache->getCount();
            $events = $this->sseEventCache->getEvents($offset);

            foreach ($events as $event) {
                if ($event['type'] === SseEventCache::EVENT_TYPE_TASK) {
                    $this->pushTaskProgress($jobId, $event['data']);
                    continue;
                }

                if ($event['type'] === SseEventCache::EVENT_TYPE_COMPLETE) {
                    $this->output($jobId, $event['data']['status'], json_encode($event['data']['data']));
                    continue;
                }

                if ($event['type'] === SseEventCache::EVENT_TYPE_MEMORY_EXHAUST) {
                    $this->output($jobId, SseEventCache::EVENT_TYPE_MEMORY_EXHAUST, json_encode($event['data']));
                    $this->output($jobId, '', json_encode([
                        'type'    => Logger::TYPE_ERROR,
                        'date'    => $event['data']['time'],
                        'message' => "Memory exceed allowed size! Allowed memory: {$event['data']['allowedMemoryLimit']} bytes. Exceeded memory: {$event['data']['exhaustedMemorySize']} bytes",
                    ]));
                    continue;
                }

                if ($event['type'] === SseEventCache::EVENT_TYPE_FATAL_ERROR) {
                    $this->output($jobId, SseEventCache::EVENT_TYPE_FATAL_ERROR, json_encode($event['data']));
                    $this->output($jobId, '', json_encode([
                        'type'    => Logger::TYPE_ERROR,
                        'date'    => $event['data']['time'],
                        'message' => "Job failed due to a fatal error! Error data: " . print_r($event['data'], true),
                    ]));
                    continue;
                }

                $this->output($jobId, '', json_encode($event));
            }

 
            $now = microtime(true);
            if ($now - $lastHeartbeat >= 1.0) {
                echo ": ping " . $now . "\n\n"; // phpcs:ignore
                flush();
                $lastHeartbeat = $now;
            }

            $offset = $total;
            if (!$this->isJobRunning()) {
                $this->closeStream();
            }

            usleep(200000); 
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

 
        echo "data: $data" . "\n"; // phpcs:ignore
        echo "\n";

 
        while (ob_get_level() > 0) {
            @ob_end_flush(); 
        }

        flush();
    }

    protected function isJobRunning(): bool
    {
        $status  = $this->jobTransientCache->getJobStatus();
        $jobData = $this->jobTransientCache->getJob();
        if ($status === JobTransientCache::STATUS_RUNNING) {
 
 
            if (!empty($jobData['preInitAt']) && (time() - $jobData['preInitAt']) > self::STALE_JOB_THRESHOLD_SECONDS) {
                $message = esc_html__('The background process could not start. This usually means the server cannot send HTTP requests to itself (loopback). Please check your server configuration, firewall rules, and DNS settings.', 'wp-staging');
                $this->jobTransientCache->failJob(
                    esc_html__('Background process failed to start', 'wp-staging'),
                    $message
                );
                $this->output($jobData['jobId'], SseEventCache::EVENT_TYPE_FATAL_ERROR, json_encode(['message' => $message]));
                return false;
            }

            return true;
        }

        $data = [];

        if ($status === JobTransientCache::STATUS_CANCELLED) {
            $this->output($jobData['jobId'], SseEventCache::EVENT_TYPE_TASK, json_encode([
                'percentage' => 60, 
                'title'      => esc_html__('Processing...', 'wp-staging'),
            ]));
            $data['title'] = $jobData['title'];
        } elseif ($status === JobTransientCache::STATUS_FAILED) {
            $data['message'] = !empty($jobData['message']) ? esc_html((string) $jobData['message']) : esc_html__('Job failed', 'wp-staging');
 
            if (!empty($jobData['severity'])) {
                $data['severity'] = $jobData['severity'];
            }
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

        $this->output($jobId, SseEventCache::EVENT_TYPE_TASK, json_encode($taskData));
    }





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

        header('Content-Type: text/event-stream; charset=UTF-8');

 
 
        header('Cache-Control: private, no-cache, no-store, no-transform, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('CDN-Cache-Control: no-store');
        header('Cloudflare-CDN-Cache-Control: no-store');
        header('Surrogate-Control: no-store');
        header('X-LiteSpeed-Cache-Control: no-cache');
        header('X-Accel-Buffering: no');
        header('Connection: keep-alive');
        header('Keep-Alive: timeout=300');
    }
}
