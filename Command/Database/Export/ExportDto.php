<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; return types & type-hints

namespace WPStaging\Command\Database\Export;

use DateTime;
use Exception;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Traits\HydrateTrait;

class ExportDto
{
    use HydrateTrait;

    const DEFAULT_FORMAT = ExportCommand::FORMAT_GZIP;
    const DEFAULT_PORT = 3306;

    /** @var string */
    private $host;

    /** @var int */
    private $port;

    /** @var string */
    private $name;

    /** @var string */
    private $username;

    /** @var string */
    private $password;

    /** @var string */
    private $prefix;

    /** @var string */
    private $format;

    /** @var string|null */
    private $directory;

    /** @var string|null */
    private $fullPath;

    /** @var string */
    private $version;

    /**
     * @return string
     */
    public function getHost()
    {
        if (!$this->host) {
            return DB_HOST;
        }
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        if ($this->port) {
            return $this->port;
        }

        $parts = explode(':', DB_HOST);
        if (isset($parts[1]) && (int) $parts[1] > 0) {
            return (int) $parts[1];
        }

        return self::DEFAULT_PORT;
    }

    /**
     * @param int $port
     */
    public function setPort($port)
    {
        $this->port = (int) $port;
    }

    /**
     * @return string
     */
    public function getName()
    {
        if (!$this->name) {
            return DB_NAME;
        }
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
    public function getUsername()
    {
        if (!$this->username) {
            return DB_USER;
        }
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        if (!$this->password) {
            return DB_PASSWORD;
        }
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        if (!$this->prefix) {
            return (new Database)->getPrefix();
        }
        return $this->prefix;
    }

    /**
     * @param string $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format ?: self::DEFAULT_FORMAT;
    }

    /**
     * @param string $format
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }

    public function provideFileFormat()
    {
        switch($this->getFormat()) {
            case ExportCommand::FORMAT_GZIP:
                return 'gz';
            case ExportCommand::FORMAT_SQL:
                return 'sql';
            case ExportCommand::FORMAT_BZIP2:
                return 'bz2';
        }

        return $this->format;
    }

    /**
     * @return string|null
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * @param string|null $directory
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getFullPath()
    {
        if ($this->fullPath) {
            return $this->fullPath;
        }

        $fileName = sprintf(
            '%s_%s_%s.%s',
            rtrim($this->prefix, '_-'),
            (new DateTime)->format('Y-m-d'),
            md5(mt_rand()),
            $this->provideFileFormat()
        );

        $this->setFullPath($this->getDirectory() . $fileName);
        return $this->fullPath;
    }

    /**
     * @param string|null $fullPath
     */
    public function setFullPath($fullPath)
    {
        $this->fullPath = $fullPath;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param string $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }
}
