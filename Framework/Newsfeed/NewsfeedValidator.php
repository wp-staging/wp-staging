<?php

namespace WPStaging\Framework\Newsfeed;

use WPStaging\Core\WPStaging;

use function WPStaging\functions\debug_log;

class NewsfeedValidator
{
    /**
     * @param string
     * @return bool
     */
    public function validate($content): bool
    {
        if (!$this->validateContentStructure($content)) {
            return false;
        }

        // If not exists, abort and return true
        if (!class_exists('DOMDocument') || !function_exists('libxml_get_last_error') || !function_exists('libxml_use_internal_errors')) {
            return true;
        }

        try {
            // To detect invalid tag
            libxml_use_internal_errors(true);

            // Validate using DOMDocument
            $dom = new \DOMDocument();
            @$dom->loadHTML($content);

            $errors = libxml_get_errors();

            // Return true if no errors
            if (empty($errors)) {
                return true;
            }

            foreach ($errors as $errObject) {
                if (empty($errObject->message)) {
                    continue;
                }

                // Return true if html5 tags, false otherwise
                if (preg_match('@Tag\s(.*?)\sinvalid@i', $errObject->message, $matches)) {
                    return $this->isHtml5Tags($matches[1]);

                // Return true if tag in exclude list
                } elseif (preg_match('@Unexpected end tag :\s([a-z]+)@i', $errObject->message, $matches)) {
                    return $this->isAllowInvalidEndTag($matches[1]);
                } else {
                    debug_log(sprintf('%s: %s', __METHOD__, $errObject->message));
                    return false;
                }
            }
        } catch (\Throwable $e) {
            debug_log(sprintf('%s: failed to validate using DOMDocument: %s', __METHOD__, $e->getMessage()));
        }

        // Return true if DOMDocument failed
        return true;
    }

    /**
     * @param string
     * @return bool
     */
    private function validateContentStructure($content): bool
    {
        $content = trim($content);

        if (empty($content)) {
            return false;
        }

        // Regex pattern to match the content structure:
        // <div class="wpstg-block--header">
        //  <strong class="wpstg-block--title">What's new in WP Staging 5.8.7 Pro?</strong>
        //  <span class="wpstg-block--date">October 1, 2024</span>
        // </div>
        // content
        $pattern = '@<div class="wpstg-block--header">\s*<strong class="wpstg-block--title">[^<]+<\/strong>\s*<span class="wpstg-block--date">[^<]+<\/span>\s*<\/div>([^<]+|[a-zA-Z0-9]+)@';

        return @preg_match($pattern, $content) ? true : false;
    }

    /**
     * @param string
     * @return bool
     */
    private function isHtml5Tags(string $tag): bool
    {
        $tag = trim($tag);

        if (empty($tag)) {
            return false;
        }

        $tags = [
            'article',
            'aside',
            'audio',
            'bdi',
            'canvas',
            'data',
            'datalist',
            'details',
            'dialog',
            'figcaption',
            'figure',
            'footer',
            'header',
            'main',
            'mark',
            'menuitem',
            'meter',
            'nav',
            'progress',
            'rp',
            'rt',
            'ruby',
            'section',
            'summary',
            'svg',
            'time',
            'video',
            'wbr',
        ];

        return in_array($tag, $tags);
    }

    /**
     * @param string
     * @return bool
     */
    private function isAllowInvalidEndTag(string $tag): bool
    {
        $tags = [
            'br', /* Invalid end tag '</br>', no effect on html content -> <br>content</br> */
        ];

        return in_array($tag, $tags);
    }
}
