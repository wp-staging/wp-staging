<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Core\Utils\Logger;
use WPStaging\Core\WPStaging;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

trait BenchmarkTrait
{
    /** @var LoggerInterface To store the benchmark results. */
    private $benchmarkLogger;

    /** @var int Timestamp when the benchmark started. */
    private $benchmarkStart = 0;

    protected function startBenchmark()
    {
        if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
            /** @var Logger $logger */
            $this->benchmarkLogger = WPStaging::make(Logger::class);

            // Eg: JobSiteBackup_Benchmark
            $filename = sanitize_file_name(sprintf(
                '%s_benchmark',
                (new \ReflectionClass($this))->getShortName()
            ));

            $this->benchmarkLogger->setFileName($filename);
            $this->benchmarkStart = microtime(true);
        }
    }

    /**
     * @param $context string Write a meaningful context of the benchmark, like a function name that is measured.
     * @return void
     */
    protected function finishBenchmark($context)
    {
        if (defined('WPSTG_DEBUG') && WPSTG_DEBUG && $this->benchmarkLogger instanceof Logger) {
            $message = sprintf(
                'Finished %s in %s seconds',
                $context,
                microtime(true) - $this->benchmarkStart
            );

            $this->benchmarkLogger->info($message);
        }
    }
}
