<?php

namespace WPStaging\Staging\Traits;

use WPStaging\Staging\Dto\StagingSiteDto;

trait WithStagingSiteDto
{
 
    private $cloneId = '';

 
    private $stagingSite;





    public function setCloneId(string $cloneId)
    {
        $this->cloneId = $cloneId;
    }




    public function getCloneId(): string
    {
        return $this->cloneId;
    }





    public function setStagingSite($stagingSite)
    {
        $this->stagingSite = $stagingSite;
    }




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
