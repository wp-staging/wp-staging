<?php
namespace WPStaging\Backup\Service\Database\Importer;
use WPStaging\Backup\Dto\Service\DatabaseImporterDto;
interface SubsiteManagerInterface
{
    public function initialize(DatabaseImporterDto $databaseImporterDto);

    public function updateSubsiteId();

    public function isTableFromDifferentSubsite(string $query): bool;
}
