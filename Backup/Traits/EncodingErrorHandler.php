<?php

namespace WPStaging\Backup\Traits;




trait EncodingErrorHandler
{







    protected function logEncodingErrorWithContext(string $logMessage, array $context)
    {
        if (class_exists('\WPStaging\Core\WPStaging')) {
            try {
                $logger = \WPStaging\Core\WPStaging::make(\WPStaging\Vendor\Psr\Log\LoggerInterface::class);

                $logger->warning($logMessage);
                $logger->info('Context properties: ' . json_encode($context));
            } catch (\Throwable $e) {
 
            }
        }
    }
}
