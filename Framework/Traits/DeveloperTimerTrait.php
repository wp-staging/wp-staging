<?php

/**
 * Provides methods to fetch potentially unlimited rows from a database table
 * with resource-usage awareness using raw MySQL(i) queries.
 *
 * @package WPStaging\Framework\Traits
 */

namespace WPStaging\Framework\Traits;

/**
 * Trait DeveloperTimerTrait
 *
 * This trait should be used ONLY FOR DEBUGGING DURING DEVELOPMENT.
 *
 * This is NOT a substitute to real Profiling, but it proved to be
 * useful to gather data in a way that the developer would like to profile.
 *
 * No production code should call methods from this trait.
 * Feel free to commit code that uses this while the PR is under progress,
 * but it should be removed before the PR is approved.
 *
 * @todo    Add a PHPCS rule that looks for these methods and fail CI.
 *
 * @package WPStaging\Framework\Traits
 */
trait DeveloperTimerTrait
{
    use ResourceTrait;

    protected $debugTimingIteration = 0;

    protected $eventsStart = [];

    protected $timings = [];

    protected $eventStart;

    protected function startEventTimer($event)
    {
        if (!defined('WPSTG_DEBUG') || !WPSTG_DEBUG) {
            return;
        }

        $this->eventsStart[$event] = microtime(true);
    }

    public function __destruct()
    {
        if (!defined('WPSTG_DEBUG') || !WPSTG_DEBUG) {
            return;
        }

        \WPStaging\functions\debug_log(json_encode($this->timings, JSON_PRETTY_PRINT));
    }

    protected function finishEventTimer($event, $context = [])
    {
        if (!defined('WPSTG_DEBUG') || !WPSTG_DEBUG) {
            return;
        }

        if (!array_key_exists($event, $this->eventsStart)) {
            throw new \BadMethodCallException('You should initiate the event timer with startEventTimer("Name of the event")');
        }

        if (!array_key_exists($this->debugTimingIteration, $this->timings)) {
            $this->timings[$this->debugTimingIteration] = [];
        }

        if (!array_key_exists($event, $this->timings[$this->debugTimingIteration])) {
            $this->timings[$this->debugTimingIteration][$event] = [];
            $this->timings[$this->debugTimingIteration][$event]['accumulatedTime'] = 0;
        }

        if (count($this->timings[$this->debugTimingIteration][$event]) > 100) {
            return;
        }

        $this->timings[$this->debugTimingIteration][$event]['accumulatedTime'] += microtime(true) - $this->eventsStart[$event];

        $this->timings[$this->debugTimingIteration][$event][] = [
            'completedIn' => number_format(microtime(true) - $this->eventsStart[$event]) . ' seconds',
            'memoryUsage' => size_format($this->getMemoryUsage()),
            'runningTime' => $this->getRunningTime(),
            'context' => $context,
        ];
    }

    protected function incrementTimerIteration()
    {
        if (!defined('WPSTG_DEBUG') || !WPSTG_DEBUG) {
            return;
        }

        $this->debugTimingIteration++;
    }
}
