<?php

namespace WPStaging\Framework\Newsfeed;

use function WPStaging\functions\debug_log;

/**
 * Validates newsfeed JSON data structure.
 *
 * Ensures that newsfeed data contains required fields and proper array structures
 * for highlights, fixes, and tips sections.
 */
class NewsfeedValidator
{
    /**
     * Validate newsfeed data structure
     *
     * @param array|mixed $data The parsed JSON data to validate
     * @return bool True if valid, false otherwise
     */
    public function validate($data): bool
    {
        if (!is_array($data)) {
            debug_log('Newsfeed validation failed: data is not an array');
            return false;
        }

        // Required fields
        $required = ['version', 'date'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                debug_log(sprintf('Newsfeed validation failed: missing required field "%s"', $field));
                return false;
            }
        }

        // Validate highlights array if present
        if (isset($data['highlights']) && !is_array($data['highlights'])) {
            debug_log('Newsfeed validation failed: highlights must be an array');
            return false;
        }

        // Validate fixes array if present
        if (isset($data['fixes']) && !is_array($data['fixes'])) {
            debug_log('Newsfeed validation failed: fixes must be an array');
            return false;
        }

        // Validate tips array if present
        if (isset($data['tips']) && !is_array($data['tips'])) {
            debug_log('Newsfeed validation failed: tips must be an array');
            return false;
        }

        // Validate intro object if present
        if (isset($data['intro']) && !is_array($data['intro'])) {
            debug_log('Newsfeed validation failed: intro must be an object');
            return false;
        }

        return true;
    }
}
