<?php

namespace WPStaging\Staging\Traits;





trait StagingNetworkDtoTrait
{
 
    private $isStagingNetwork = false;




    private $stagingNetworkDomain = '';




    private $stagingNetworkPath = '';






    private $sourceBlogId = 0;





    public function setIsStagingNetwork(bool $isStagingNetwork)
    {
        $this->isStagingNetwork = $isStagingNetwork;
    }




    public function getIsStagingNetwork(): bool
    {
        return $this->isStagingNetwork;
    }





    public function setStagingNetworkDomain(string $stagingNetworkDomain)
    {
        $this->stagingNetworkDomain = $stagingNetworkDomain;
    }




    public function getStagingNetworkDomain(): string
    {
        return $this->stagingNetworkDomain;
    }





    public function setStagingNetworkPath(string $stagingNetworkPath)
    {
        $this->stagingNetworkPath = $stagingNetworkPath;
    }




    public function getStagingNetworkPath(): string
    {
        return $this->stagingNetworkPath;
    }





    public function setSourceBlogId(int $sourceBlogId)
    {
        $this->sourceBlogId = $sourceBlogId;
    }




    public function getSourceBlogId(): int
    {
        return $this->sourceBlogId;
    }
}
