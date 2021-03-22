<?php

namespace WPStaging\Framework\Database;

interface iDbInfo
{
    public function getDbCollation();
    public function getDbEngine();
    public function getMySqlServerVersion();
    public function getMySqlClientVersion();
    public function toArray();
}
