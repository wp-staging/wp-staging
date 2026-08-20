<?php

namespace WPStaging\Framework\Newsfeed;

use function WPStaging\functions\debug_log;







class NewsfeedValidator
{






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

 
        if (isset($data['video']) && is_array($data['video']) && !empty($data['video']['vimeo_id'])) {
 
        } elseif (isset($data['video']) && !is_array($data['video'])) {
            debug_log('Newsfeed validation failed: video must be an array');
            return false;
        }

        return true;
    }
}
