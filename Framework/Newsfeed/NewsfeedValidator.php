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

        $required = ['version', 'date'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                debug_log(sprintf('Newsfeed validation failed: missing required field "%s"', $field));
                return false;
            }
        }

        $arrayFields = ['highlights', 'fixes', 'tips', 'intro'];
        foreach ($arrayFields as $field) {
            if (isset($data[$field]) && !is_array($data[$field])) {
                debug_log(sprintf('Newsfeed validation failed: %s must be an array', $field));
                return false;
            }
        }

        // Validate video object if present and populated
        if (isset($data['video']) && is_array($data['video']) && !empty($data['video']['vimeo_id'])) {
            // Video is populated — no further validation needed, vimeo_id exists
        } elseif (isset($data['video']) && !is_array($data['video'])) {
            debug_log('Newsfeed validation failed: video must be an array');
            return false;
        }

        return true;
    }
}
