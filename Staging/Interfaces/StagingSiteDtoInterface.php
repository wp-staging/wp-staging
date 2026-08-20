<?php

namespace WPStaging\Staging\Interfaces;

use WPStaging\Staging\Dto\StagingSiteDto;

interface StagingSiteDtoInterface
{
    public function setCloneId(string $cloneId);

    public function getCloneId(): string;





    public function setStagingSite($stagingSite);




    public function getStagingSite();
}
