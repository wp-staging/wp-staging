<?php

namespace WPStaging\Backup\Interfaces;

use WPStaging\Backup\Dto\File\ExtractorDto;

/**
 * Interface for tasks that need to persist extractor state during backup restoration
 */
interface ExtractorTaskInterface
{
    public function persistDto(ExtractorDto $dto);
}
