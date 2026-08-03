<?php

namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Database\DatabaseException;

/**
 * Initializes database handles used by legacy staging clone, update, reset, and push jobs.
 */
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

    /**
     * @return void
     */
    protected function setupMemoryExhaustFile()
    {
        $this->memoryExhaustErrorTmpFile = $this->getMemoryExhaustErrorTmpFile(Cloning::WPSTG_REQUEST);
    }

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
        if (!$this->validateExternalDatabaseConnectionData()) {
            return false;
        }

        if ($this->options->databaseSsl && !defined('MYSQL_CLIENT_FLAGS')) {
            // phpcs:disable PHPCompatibility.Constants.NewConstants.mysqli_client_ssl_dont_verify_server_certFound
            define('MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_SSL | MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT);
        }

        $this->stagingDb = new \wpdb($this->options->databaseUser, str_replace("\\\\", "\\", $this->options->databasePassword), $this->options->databaseDatabase, $this->options->databaseServer);

        if (!is_object($this->stagingDb)) {
            $this->returnException('Can not create database object.');
            return false;
        }

        // Check if there were any error when connecting
        if (
            property_exists($this->stagingDb, 'error') &&
            $this->stagingDb->error instanceof \WP_Error
        ) {
            /** @var \WP_Error $wp_error */
            $wp_error = $this->stagingDb->error;
            if ($wp_error->get_error_code() === 'db_connect_fail') {
                $this->returnException(sprintf(
                    __('Can not connect to external database: %1$s. %2$s', 'wp-staging'),
                    $this->getExternalDatabaseLabel(),
                    $this->getExternalDatabaseConnectionFailureMessage($wp_error)
                ));

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
                    $message = $this->normalizeDatabaseErrorMessage($wp_error->get_error_message());
                    $this->returnException($message !== '' ? $message : sprintf('Error: Can\'t select database %s. Either it does not exist or you don\'t have privileges to access it.', $this->options->databaseDatabase));
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

    /**
     * @return bool
     */
    protected function validateExternalDatabaseConnectionData(): bool
    {
        try {
            $this->externalDatabaseConfiguration->validateConnectionTarget($this->options);
        } catch (DatabaseException $exception) {
            $this->returnException($exception->getMessage());
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    private function getExternalDatabaseLabel(): string
    {
        $databaseName = isset($this->options->databaseDatabase) ? trim((string)$this->options->databaseDatabase) : '[Not Set]';

        return $databaseName;
    }

    /**
     * @param \WP_Error $wpError
     * @return string
     */
    private function getExternalDatabaseConnectionFailureMessage(\WP_Error $wpError): string
    {
        $message = $this->normalizeDatabaseErrorMessage($wpError->get_error_message());
        if ($message === '' || stripos($message, 'Error establishing a database connection') !== false) {
            return __('Verify the database host, database name, database user, and password, and make sure the database server is reachable.', 'wp-staging');
        }

        return sprintf(__('Reason: %s', 'wp-staging'), $message);
    }

    /**
     * @param string $message
     * @return string
     */
    private function normalizeDatabaseErrorMessage(string $message): string
    {
        $message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');
        $message = wp_strip_all_tags($message, true);
        $message = preg_replace('/\s+/', ' ', $message);

        return is_string($message) ? trim($message) : '';
    }
}
