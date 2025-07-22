<?php

namespace WPStaging\Staging\Interfaces;

interface StagingNetworkDtoInterface
{
    public function setIsStagingNetwork(bool $isStagingNetwork);

    public function getIsStagingNetwork(): bool;

    public function setStagingNetworkDomain(string $stagingNetworkDomain);

    public function getStagingNetworkDomain(): string;

    public function setStagingNetworkPath(string $stagingNetworkPath);

    public function getStagingNetworkPath(): string;
}
