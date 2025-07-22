<?php

namespace WPStaging\Staging\Traits;

/**
 * Trait StagingNetworkDtoTrait
 * This trait is has properties related to network staging site
 */
trait StagingNetworkDtoTrait
{
    /** @var bool */
    private $isStagingNetwork = false;

    /**
     * @var string
     */
    private $stagingNetworkDomain = '';

    /**
     * @var string
     */
    private $stagingNetworkPath = '';

    /**
     * @param bool $isStagingNetwork
     * @return void
     */
    public function setIsStagingNetwork(bool $isStagingNetwork)
    {
        $this->isStagingNetwork = $isStagingNetwork;
    }

    /**
     * @return bool
     */
    public function getIsStagingNetwork(): bool
    {
        return $this->isStagingNetwork;
    }

    /**
     * @param string $stagingNetworkDomain
     * @return void
     */
    public function setStagingNetworkDomain(string $stagingNetworkDomain)
    {
        $this->stagingNetworkDomain = $stagingNetworkDomain;
    }

    /**
     * @return string
     */
    public function getStagingNetworkDomain(): string
    {
        return $this->stagingNetworkDomain;
    }

    /**
     * @param string $stagingNetworkPath
     * @return void
     */
    public function setStagingNetworkPath(string $stagingNetworkPath)
    {
        $this->stagingNetworkPath = $stagingNetworkPath;
    }

    /**
     * @return string
     */
    public function getStagingNetworkPath(): string
    {
        return $this->stagingNetworkPath;
    }
}
