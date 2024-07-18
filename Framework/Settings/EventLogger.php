<?php

namespace WPStaging\Framework\Settings;

use WPStaging\Framework\Traits\EventLoggerTrait;
use WPStaging\Framework\Utils\Sanitize;

class EventLogger
{
    use EventLoggerTrait;

    /**
     * @var Sanitize
     */
    private $sanitize;

    /**
     * @var string
     */
    private $process;

    /**
     * @var array
     */
    private $processPrefixes;

    /**
     * @param Sanitize $sanitize
     */
    public function __construct(Sanitize $sanitize)
    {
        $this->sanitize = $sanitize;
        $this->processPrefixes = [
            'backup' => $this->backupProcessPrefix,
            'restore' => $this->restoreProcessPrefix,
            'clone' => $this->cloneProcessPrefix,
            'push' => $this->pushProcessPrefix,
        ];
    }

    /**
     * @return void
     */
    public function ajaxLogEventFailure()
    {
        $process = isset($_POST['process']) ? $this->sanitize->sanitizeString($_POST['process']) : '';
        if (empty($process)) {
            wp_send_json_error();
        }

        $this->process = $this->getProcessPrefix($process);
        if (empty($this->process)) {
            wp_send_json_error();
        }

        $response = $this->updateFailedProcess($this->process);
        if ($response) {
            wp_send_json_success();
        }

        wp_send_json_error();
    }

    /**
     * @param string $processName
     * @return string
     */
    protected function getProcessPrefix(string $processName): string
    {
        return empty($this->processPrefixes[$processName]) ? '' : $this->processPrefixes[$processName];
    }
}
