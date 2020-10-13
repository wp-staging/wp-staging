<?php

namespace WPStaging\Component\Job\Dto;


class ExclusionDto
{
    /** @var array */
    private $dirs = [];

    /** @var array */
    private $tables = [];

    /**
     * @noinspection PhpUnused
     * @return array
     */
    public function getDirs()
    {
        return $this->dirs;
    }

    /** @noinspection PhpUnused */
    public function setDirs(array $dirs)
    {
        $this->dirs = $dirs;
    }

    /**
     * @noinspection PhpUnused
     * @return array
     */
    public function getTables()
    {
        return $this->tables;
    }

    /** @noinspection PhpUnused */
    public function setTables(array $tables)
    {
        $this->tables = $tables;
    }
}
