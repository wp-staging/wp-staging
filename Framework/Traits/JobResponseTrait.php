<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Framework\Job\AbstractJob;
use WPStaging\Framework\Job\Exception\ProcessLockedException;

/**
 * Answers an HTTP request with the outcome of one job step, including the case where another
 * request already owns the job.
 */
trait JobResponseTrait
{
    /**
     * Run one step of a job for the current HTTP request and answer it with JSON.
     *
     * Turning process lock contention into an HTTP status belongs at the HTTP boundary and nowhere
     * else: a background worker has no client to answer and must see the ProcessLockedException
     * itself, so it can re-queue its action and let the worker that won the lock carry the job on.
     *
     * @param AbstractJob $job
     * @return void
     */
    protected function sendJobResponse(AbstractJob $job)
    {
        try {
            $response = $job->prepareAndExecute();
        } catch (ProcessLockedException $e) {
            wp_send_json_error($e->getMessage(), $e->getCode());

            return;
        }

        wp_send_json($response);
    }
}
