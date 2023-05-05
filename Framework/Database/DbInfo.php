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
     * @var bool
     */
    protected $useSsl;

    /*
     * @param string $hostServer
     * @param string $user
     * @param string $password
     * @param string $database
     * @param bool $useSsl
     */
    public function __construct($hostServer, $user, $password, $database, $useSsl = false)
    {
        $this->server   = $hostServer;
        $this->user     = $user;
        $this->password = $password;
        $this->database = $database;
        $this->useSsl   = $useSsl;

        parent::__construct($this->connect());
    }

    public function connect()
    {
        if ($this->useSsl) {
            // wpdb requires this constant for SSL use
            if (!defined('MYSQL_CLIENT_FLAGS')) {
                // phpcs:disable PHPCompatibility.Constants.NewConstants.mysqli_client_ssl_dont_verify_server_certFound
                define('MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_SSL | MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT);
            }

            $db = mysqli_init();
            $db->real_connect($this->server, $this->user, $this->password, $this->database, null, null, MYSQL_CLIENT_FLAGS);
        } else {
            $db = new \mysqli($this->server, $this->user, $this->password, $this->database);
        }

        $this->error     = null;
        $this->connected = true;
        if ($db->connect_error) {
            $this->error     = 'Connect Error (' . $db->connect_errno . ') ' . $db->connect_error;
            $this->connected = false;
            return;
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
