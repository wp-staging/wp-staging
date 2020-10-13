<?php

namespace WPStaging\Component\Job\Dto;


class DatabaseDto
{
    /** @var string */
    private $host;

    /** @var string */
    private $name;

    /** @var string|null */
    private $prefix;

    /** @var string|null */
    private $username;

    // TODO; Encrypt it for saving it in database
    /** @var string|null */
    private $password;

    /**
     * @return string
     */
    public function getHost()
    {
        // TODO PHP7.0; $this->host ?? DB_HOST
        return $this->host?: DB_HOST;
    }

    /**
     * @param string $host
     * @noinspection PhpUnused
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @return string
     */
    public function getName()
    {
        // TODO PHP7.0; $this->name ?? DB_NAME
        return $this->name?: DB_NAME;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param string|null $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * @return string|null
     * @noinspection PhpUnused
     */
    public function getUsername()
    {
        // TODO PHP7.0; $this->username ?? DB_USER
        return $this->username?: DB_USER;
    }

    /**
     * @param string|null $username
     * @noinspection PhpUnused
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return string|null
     * @noinspection PhpUnused
     */
    public function getPassword()
    {
        // TODO PHP7.0; $this->password ?? DB_PASSWORD
        return $this->password?: DB_PASSWORD;
    }

    /**
     * @param string|null $password
     * @noinspection PhpUnused
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }
}
