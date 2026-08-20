<?php

namespace WPStaging\Framework\Database;

use WPStaging\Framework\Adapter\Database\DatabaseException;




final class ExternalDatabaseConfiguration
{




    public function isEnabled($options): bool
    {
        $options = $this->normalizeOptions($options);

        if (array_key_exists('useCustomDatabase', $options)) {
            return filter_var($options['useCustomDatabase'], FILTER_VALIDATE_BOOLEAN);
        }

        return $this->hasConnectionTarget($options);
    }





    public function hasConnectionTarget($options): bool
    {
        $options = $this->normalizeOptions($options);

        return $this->getValue($options, 'databaseServer') !== ''
            && $this->getValue($options, 'databaseUser') !== ''
            && $this->getValue($options, 'databaseDatabase') !== '';
    }






    public function validateConnectionTarget($options)
    {
        $options = $this->normalizeOptions($options);

        if (!$this->hasConnectionTarget($options)) {
            throw new DatabaseException(__('External database credentials are incomplete. Enter the database host, database name, and database user.', 'wp-staging'));
        }

        if (strpos($this->getValue($options, 'databaseServer'), '://') !== false) {
            throw new DatabaseException(__('The external database server must be a hostname, IP address, socket, or host:port value without a URL scheme such as http:// or https://.', 'wp-staging'));
        }
    }





    private function normalizeOptions($options): array
    {
        if (is_array($options)) {
            return $options;
        }

        if (is_object($options)) {
            return get_object_vars($options);
        }

        return [];
    }






    private function getValue(array $options, string $key): string
    {
        return isset($options[$key]) ? trim((string)$options[$key]) : '';
    }
}
