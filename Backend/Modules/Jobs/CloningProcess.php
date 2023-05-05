<?php

namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Core\WPStaging;

abstract class CloningProcess extends JobExecutable
{
    /**
     * Can be local or external \wpdb object
     * @var \wpdb
     */
    protected $stagingDb;

    /**
     * Always be the local \wpdb object
     * @var \wpdb
     */
    protected $productionDb;

    protected function initializeDbObjects()
    {
        $this->productionDb = WPStaging::getInstance()->get("wpdb");

        if ($this->isExternalDatabase()) {
            $this->setExternalDatabase();
        } else {
            $this->setLocalDatabase();
        }
    }

    protected function setLocalDatabase()
    {
        $this->stagingDb = WPStaging::getInstance()->get("wpdb");
    }

    /**
     * @return bool
     */
    protected function setExternalDatabase()
    {
        if ($this->options->databaseSsl && !defined('MYSQL_CLIENT_FLAGS')) {
            // phpcs:disable PHPCompatibility.Constants.NewConstants.mysqli_client_ssl_dont_verify_server_certFound
            define('MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_SSL | MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT);
        }

        $this->stagingDb = new \wpdb($this->options->databaseUser, str_replace("\\\\", "\\", $this->options->databasePassword), $this->options->databaseDatabase, $this->options->databaseServer);

        // Check if there were any error when connecting
        if (
            property_exists($this->stagingDb, 'error') &&
            $this->stagingDb->error instanceof \WP_Error
        ) {
            /** @var \WP_Error $wp_error */
            $wp_error = $this->stagingDb->error;
            if ($wp_error->get_error_code() === 'db_connect_fail') {
                $this->returnException(sprintf('Can not connect to external database %s. Reason: %s', $this->options->databaseDatabase, $wp_error->get_error_message()));
                return false;
            }
        }

        $this->stagingDb->select($this->options->databaseDatabase);
        if (!$this->stagingDb->ready) {
            if (
                property_exists($this->stagingDb, 'error') &&
                $this->stagingDb->error instanceof \WP_Error
            ) {
                /** @var \WP_Error $wp_error */
                $wp_error = $this->stagingDb->error;
                if ($wp_error->get_error_code() === 'db_select_fail') {
                    $this->returnException($wp_error->get_error_message());
                    exit;
                }

                // Generic error
                $this->returnException(sprintf('Error: Can\'t select database %s. Either it does not exist or you don\'t have privileges to access it.', $this->options->databaseDatabase));
                exit;
            }

            // Generic error
            $this->returnException(sprintf('Error: Can\'t select database %s. Either it does not exist or you don\'t have privileges to access it.', $this->options->databaseDatabase));
            exit;
        }
        return true;
    }
}
