<?php

namespace WPStaging\Framework\Database;

class DbInfo extends WpDbInfo
{
    /*
     * @var string
     */
    protected $server;

    /*
     * @var string
     */
    protected $user;

    /*
     * @var string
     */
    protected $password;

    /*
     * @var string
     */
    protected $database;

    /*
     * @var string|null
     */
    protected $error;

    /*
     * @var bool
     */
    protected $connected;

    /*
     * @param string $hostServer
     * @param string $user
     * @param string $password
     * @param string $database
     */
    public function __construct($hostServer, $user, $password, $database)
    {
        $this->server = $hostServer;
        $this->user = $user;
        $this->password = $password;
        $this->database = $database;

        parent::__construct($this->connect());
    }

    public function connect()
    {
        $db = new \mysqli($this->server, $this->user, $this->password, $this->database);
        $this->error = null;
        $this->connected = true;
        if ($db->connect_error) {
            $this->error = 'Connect Error (' . $db->connect_errno . ') '
                . $db->connect_error;
            $this->connected = false;
            $db->close();
            return null;
        }
        $db->close();

        $wpdb = new \wpdb($this->user, $this->password, $this->database, $this->server);
        return $wpdb;
    }

    /*
     * @return string|null
     */
    public function getError()
    {
        return $this->error;
    }
}