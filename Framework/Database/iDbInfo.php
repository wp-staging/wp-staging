<?php

namespace WPStaging\Framework\Database;

interface iDbInfo
{
    public function getDbCollation(): string;
    public function getDbEngine(): string;
    public function getMySqlServerVersion(): int;
    public function getMySqlClientVersion(): int;
    public function toArray(): array;
}
