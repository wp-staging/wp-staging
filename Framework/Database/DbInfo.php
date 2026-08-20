<?php

namespace WPStaging\Framework\Database;

use wpdb;
use WPStaging\Framework\Adapter\Database\DatabaseException;




class DbInfo extends WpDbInfo
{



    protected $server;




    protected $user;




    protected $password;




    protected $database;




    protected $useSsl;









    public function __construct(string $hostServer, string $user, string $password, string $database, bool $useSsl = false)
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
        (new ExternalDatabaseConfiguration())->validateConnectionTarget([
            'databaseServer'   => $this->server,
            'databaseUser'     => $this->user,
            'databaseDatabase' => $this->database,
        ]);

        $db = mysqli_init();
        if ($db === false) {
            throw new DatabaseException('Unable to initialize MySQL connection.');
        }

        $connected         = false;
        $connectionFailure = null;

        set_error_handler(function () {
            return true;
        });

        try {
            if ($this->useSsl) {
 
                if (!defined('MYSQL_CLIENT_FLAGS')) {
                    // phpcs:disable PHPCompatibility.Constants.NewConstants.mysqli_client_ssl_dont_verify_server_certFound
                    define('MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_SSL | MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT);
                }

                // @phpstan-ignore-next-line - null is valid for port and socket parameters
                $connected = $db->real_connect($this->server, $this->user, $this->password, $this->database, null, null, MYSQL_CLIENT_FLAGS);
            } else {
                $connected = $db->real_connect($this->server, $this->user, $this->password, $this->database);
            }
        } catch (\Throwable $e) {
            $connectionFailure = $e;
        } finally {
            restore_error_handler();
        }

        if ($connectionFailure !== null) {
            throw new DatabaseException('Connect Error (' . $connectionFailure->getCode() . ') ' . $connectionFailure->getMessage());
        }

        if (!$connected || $db->connect_error) {
            throw new DatabaseException('Connect Error (' . $db->connect_errno . ') ' . $db->connect_error);
        }

        $db->close();

        $wpdb = new wpdb($this->user, $this->password, $this->database, $this->server);
        return $wpdb;
    }
}
