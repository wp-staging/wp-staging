<?php






namespace WPStaging\Staging\Dto\Task\Response;

use WPStaging\Framework\Job\Dto\TaskResponseDto;

class FinishStagingSiteResponseDto extends TaskResponseDto
{



    private $cloneId = '';

 
    private $stagingSiteUrl = '';





    public function setCloneId(string $cloneId)
    {
        $this->cloneId = $cloneId;
    }

    public function getCloneId(): string
    {
        return $this->cloneId;
    }

    public function setStagingSiteUrl(string $stagingSiteUrl)
    {
        $this->stagingSiteUrl = $stagingSiteUrl;
    }

    public function getStagingSiteUrl(): string
    {
        return $this->stagingSiteUrl;
    }
}
