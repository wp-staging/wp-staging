<?php

/**
 * Runs a Queue Background Processing Demo from the browser.
 *
 * Go to https:<site>/?wpstg_q_demo to run the demo queueing 100 actions and close all browser windows on the site.
 *
 * Go to https:<site>/?wpstg_q_demo=<n> to run the demo queueing <n> actions and close all browser windows on the site.
 *
 * @since   TBD
 *
 * @package WPStaging\Framework\BackgroundProcessing
 */

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
