<?php

namespace WPStaging\Vendor\Psr\Log;

/**
 * Basic Implementation of LoggerAwareInterface.
 */
trait LoggerAwareTrait
{
    /**
     * The logger instance.
     *
     * @var LoggerInterface|null
     */
    protected $logger;
    /**
     * Sets a logger.
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(\WPStaging\Vendor\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
