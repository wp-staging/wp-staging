<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Framework\ErrorHandler;

trait MemoryExhaustTrait
{
    /**
     * @var string
     */
    protected $memoryExhaustErrorTmpFile = '';

    /**
     * @param  string $requestType
     * @return string
     */
    public function getMemoryExhaustErrorTmpFile(string $requestType): string
    {
        if (empty($this->memoryExhaustErrorTmpFile)) {
            $this->memoryExhaustErrorTmpFile = $this->setupTmpErrorFile($requestType);
        }

        return $this->memoryExhaustErrorTmpFile;
    }

    /**
     * @param  string $requestType
     * @return string
     */
    protected function setupTmpErrorFile(string $requestType): string
    {
        if (!defined('WPSTG_UPLOADS_DIR')) {
            return '';
        }

        if (!defined('WPSTG_REQUEST')) {
            define('WPSTG_REQUEST', $requestType);
        }

        return trailingslashit(WPSTG_UPLOADS_DIR) . $requestType . ErrorHandler::ERROR_FILE_EXTENSION;
    }

    /**
     * @return void
     */
    protected function removeMemoryExhaustErrorTmpFile()
    {
        if ($this->memoryExhaustErrorTmpFile === '') {
            return;
        }

        if (file_exists($this->memoryExhaustErrorTmpFile)) {
            unlink($this->memoryExhaustErrorTmpFile);
        }
    }
}
