<?php

namespace WPStaging\Backup\Service;

/**
 * Class AbstractServiceProvider
 *
 * @package WPStaging\Service\Backup
 */
abstract class AbstractServiceProvider
{
    /** @var ServiceInterface */
    private $service;

    public function __construct(ServiceInterface $service)
    {
        $this->service = $service;
    }

    public function getService(): ServiceInterface
    {
        return $this->service;
    }
}
