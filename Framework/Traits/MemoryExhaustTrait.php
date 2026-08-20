<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Framework\ErrorHandler;

trait MemoryExhaustTrait
{



    protected $memoryExhaustErrorTmpFile = '';





    public function getMemoryExhaustErrorTmpFile(string $requestType): string
    {
        if (empty($this->memoryExhaustErrorTmpFile)) {
            $this->memoryExhaustErrorTmpFile = $this->setupTmpErrorFile($requestType);
        }

        return $this->memoryExhaustErrorTmpFile;
    }





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
