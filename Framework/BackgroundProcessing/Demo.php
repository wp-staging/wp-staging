<?php













namespace WPStaging\Framework\BackgroundProcessing;

use WPStaging\Core\WPStaging;

class Demo
{
    public function run($count)
    {
        $queue = WPStaging::getInstance()->getContainer()->make(Queue::class);

        foreach (range(1, $count) as $k) {
            $queue->enqueueAction(self::class . '::' . 'writeToLog', [$k]);
            \WPStaging\functions\debug_log("Enqueued Action {$k}");
        }
    }

    public function writeToLog($k)
    {
        $interval = mt_rand(0, 2);
        sleep($interval);
        $pid = getmypid();
        \WPStaging\functions\debug_log("Action {$k} done [PID {$pid}]!");
    }
}
