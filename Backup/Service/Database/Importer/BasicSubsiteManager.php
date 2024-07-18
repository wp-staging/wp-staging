<?php
namespace WPStaging\Backup\Service\Database\Importer;
use WPStaging\Backup\Dto\Job\JobRestoreDataDto;
class BasicSubsiteManager implements SubsiteManagerInterface
{
    public function initialize(JobRestoreDataDto $jobRestoreDataDto)
    {
        }
    public function updateSubsiteId()
    {
        }
    public function isTableFromDifferentSubsite(string $query): bool
    {
        return false;
    }
}
