<?php

namespace WPStaging\Backup\Traits;




trait EncodingErrorHandler
{








    protected function logEncodingErrorWithContext(string $errorMessage, array $context, string $logMessageTemplate)
    {
        if (class_exists('\WPStaging\Core\WPStaging')) {
            try {
                $logger = \WPStaging\Core\WPStaging::make(\WPStaging\Vendor\Psr\Log\LoggerInterface::class);

                $logMessage = sprintf($logMessageTemplate, $errorMessage);

                $logger->warning($logMessage);
                $logger->info('Context properties: ' . json_encode($context));
            } catch (\Exception $e) {
 
            }
        }
    }
}
