<?php

namespace WPStaging\Component\Job\Dto;

use DateTime;
use Exception;

class CloneDto
{
    // TODO; [directoryName]
    /** @var string */
    private $name;

    /** @var string */
    private $path;

    /** @var string */
    private $url;

    /** @var JobDto */
    private $jobDto;

    /** @var DatabaseDto */
    private $databaseProd;

    /** @var DatabaseDto */
    private $databaseClone;

    /** @var ExclusionDto */
    private $exclusions;

    /**
     * @param array $data
     *
     * @return self
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function hydrate(array $data = [])
    {
        $this->setName($data['directoryName']);
        $this->setPath($data['path']);
        $this->setUrl($data['url']);

        // TODO RPoC; Hydrate? $this->setBuild((new JobDto)->hydrate($data));
        $build = new JobDto;
        $build->setNumber($data['number']);
        $build->setVersion($data['version']);
        $build->setStatus($data['status']);
        $build->setStatus(JobDto::STATUS_FINISHED);
        $build->setCreatedAt((new DateTime)->setTimestamp($data['datetime']));

        $this->setJobDto($build);

        // TODO RPoC; Hydrate?
        $dbMaster = new DatabaseDto;
        $dbMaster->setHost($data['databaseServer']?: null);
        $dbMaster->setName($data['databaseDatabase']?: null);
        $dbMaster->setPrefix($data['databasePrefix']?: null);
        $dbMaster->setUsername($data['databaseUser']?: null);
        $dbMaster->setPassword($data['databasePassword']?: null);
        $this->setDatabaseProd($dbMaster);

        // TODO RPoC; Hydrate?
        $dbClone = new DatabaseDto;
        $dbClone->setPrefix($data['prefix']?: null);
        $this->setDatabaseClone($dbClone);

        $exclusions = new ExclusionDto;
        $exclusions->setDirs($data['excludedDirs']);
        $exclusions->setTables($data['excludedTables']);
        $this->setExclusions($exclusions);

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return JobDto
     * @noinspection PhpUnused
     */
    public function getJobDto()
    {
        return $this->jobDto;
    }

    /**
     * @param JobDto $jobDto
     */
    public function setJobDto(JobDto $jobDto)
    {
        $this->jobDto = $jobDto;
    }

    /**
     * @return DatabaseDto
     * @noinspection PhpUnused
     */
    public function getDatabaseProd()
    {
        return $this->databaseProd;
    }

    /**
     * @param DatabaseDto $databaseProd
     */
    public function setDatabaseProd(DatabaseDto $databaseProd)
    {
        $this->databaseProd = $databaseProd;
    }

    /**
     * @return DatabaseDto
     * @noinspection PhpUnused
     */
    public function getDatabaseClone()
    {
        return $this->databaseClone;
    }

    /**
     * @param DatabaseDto $databaseClone
     * @noinspection PhpUnused
     */
    public function setDatabaseClone(DatabaseDto $databaseClone)
    {
        $this->databaseClone = $databaseClone;
    }

    /**
     * @return ExclusionDto
     * @noinspection PhpUnused
     */
    public function getExclusions()
    {
        return $this->exclusions;
    }

    /**
     * @param ExclusionDto $exclusions
     * @noinspection PhpUnused
     */
    public function setExclusions(ExclusionDto $exclusions)
    {
        $this->exclusions = $exclusions;
    }

}
