<?php

namespace WPStaging\Backup\Traits;

/**
 * Trait for handling DataEncoder errors with logging and fallback mechanisms
 */
trait EncodingErrorHandler
{
    /**
     * Log DataEncoder errors with context and fallback handling
     *
     * @param string $errorMessage The error message from DataEncoder
     * @param array $context Contextual information for logging
     * @param string $logMessageTemplate Template for the log message (should contain %s for error message)
     * @return void
     */
    protected function logEncodingErrorWithContext(string $errorMessage, array $context, string $logMessageTemplate)
    {
        if (class_exists('\WPStaging\Core\WPStaging')) {
            try {
                $logger = \WPStaging\Core\WPStaging::make(\WPStaging\Vendor\Psr\Log\LoggerInterface::class);

                $logMessage = sprintf($logMessageTemplate, $errorMessage);

                $logger->warning($logMessage);
                $logger->info('Context properties: ' . json_encode($context));
            } catch (\Exception $e) {
                // Silently continue if logging fails - don't want logging issues to break backup
            }
        }
    }
}
