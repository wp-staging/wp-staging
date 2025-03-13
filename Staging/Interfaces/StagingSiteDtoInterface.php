<?php

namespace WPStaging\Staging\Interfaces;

use WPStaging\Staging\Dto\StagingSiteDto;

interface StagingSiteDtoInterface
{
    public function setCloneId(string $cloneId);

    public function getCloneId(): string;

    /**
     * @param StagingSiteDto|null $stagingSite
     * @return void
     */
    public function setStagingSite($stagingSite);

    /**
     * @return StagingSiteDto|null
     */
    public function getStagingSite();
}
