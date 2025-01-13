<?php
namespace WPStaging\Backup\Service\Database\Exporter;
abstract class AbstractExporterProvider
{
    private $exporter;

    public function __construct(AbstractExporter $exporter)
    {
        $this->exporter = $exporter;
    }

    public function getExporter()
    {
        return $this->exporter;
    }
}
