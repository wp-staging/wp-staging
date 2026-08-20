<?php

namespace WPStaging\Backup\Service;






abstract class AbstractServiceProvider
{
 
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
