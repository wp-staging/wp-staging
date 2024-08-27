<?php

namespace WPStaging\Framework\Adapter;

use WPStaging\Framework\Adapter\Database\InterfaceDatabaseClient;

interface DatabaseInterface
{
    public function getClient(): InterfaceDatabaseClient;

    public function getPrefix(): string;

    public function getBasePrefix(): string;

    public function getSqlVersion(bool $compact = false, bool $refresh = false): string;
}
