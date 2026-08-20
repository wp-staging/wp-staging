<?php

namespace WPStaging\Backup\Interfaces;

use WPStaging\Backup\Dto\File\ExtractorDto;




interface ExtractorTaskInterface
{
    public function persistDto(ExtractorDto $dto);
}
