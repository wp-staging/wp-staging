<?php

namespace WPStaging\Backup\Service;

use WPStaging\Framework\Job\JobTransientCache;
use WPStaging\Framework\Logger\SseEventCache;

use function WPStaging\functions\debug_log;




class BackgroundBackupProgress
{
 
    private $jobTransientCache;

 
    private $sseEventCache;





    public function __construct(JobTransientCache $jobTransientCache, SseEventCache $sseEventCache)
    {
        $this->jobTransientCache = $jobTransientCache;
        $this->sseEventCache     = $sseEventCache;
    }





    public function getLastTask(string $logContext = ''): array
    {
        $nothing = ['title' => '', 'percentage' => 0];

        try {
            $jobId = $this->jobTransientCache->getJobId();
            if (empty($jobId)) {
                return $nothing;
            }

            $this->sseEventCache->setJobId($jobId);
            $this->sseEventCache->load();
            $events = $this->sseEventCache->getEvents();
        } catch (\Throwable $e) {
            if ($logContext !== '') {
                debug_log($logContext . ': could not read backup progress. ' . $e->getMessage(), 'debug', false);
            }

            return $nothing;
        }

        foreach (array_reverse((array)$events) as $event) {
            if (is_array($event) && isset($event['type'], $event['data']['title']) && $event['type'] === SseEventCache::EVENT_TYPE_TASK) {
                return [
                    'title'      => (string)$event['data']['title'],
                    'percentage' => isset($event['data']['percentage']) ? (int)$event['data']['percentage'] : 0,
                ];
            }
        }

        return $nothing;
    }
}
