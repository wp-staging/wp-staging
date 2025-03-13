<?php

namespace WPStaging\Staging\Traits;

use WPStaging\Staging\Dto\StagingSiteDto;

trait WithStagingSiteDto
{
    /** @var string */
    private $cloneId = '';

    /** @var StagingSiteDto */
    private $stagingSite;

    /**
     * @param string $cloneId
     * @return void
     */
    public function setCloneId(string $cloneId)
    {
        $this->cloneId = $cloneId;
    }

    /**
     * @return string
     */
    public function getCloneId(): string
    {
        return $this->cloneId;
    }

    /**
     * @param StagingSiteDto|array|null $stagingSite
     * @return void
     */
    public function setStagingSite($stagingSite)
    {
        $this->stagingSite = $stagingSite;
    }

    /**
     * @return StagingSiteDto|null
     */
    public function getStagingSite()
    {
        if (is_array($this->stagingSite)) {
            $stagingSiteArr = (array) $this->stagingSite;
            $this->stagingSite = new StagingSiteDto();
            $this->stagingSite->hydrate($stagingSiteArr);
        }

        return $this->stagingSite;
    }
}
