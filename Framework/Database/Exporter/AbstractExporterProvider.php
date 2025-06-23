<?php

namespace WPStaging\Framework\Database\Exporter;

abstract class AbstractExporterProvider
{
    /** @var AbstractExporter */
    private $exporter;

    public function __construct(AbstractExporter $exporter)
    {
        $this->exporter = $exporter;
    }

    /**
     * @return AbstractExporter
     */
    public function getExporter()
    {
        return $this->exporter;
    }
}
