<?php

namespace WPStaging\Framework\Utils;

class Escape
{
    /**
     * Escape html with allowed html tags
     *
     * @param string $content
     * @param string $domain
     *
     * @return string
     */
    public function escapeHtml($content)
    {
        return wp_kses($content, $this->htmlAllowedDuringEscape([]));
    }

    /**
     * Html decode and then wp_kses_post
     *
     * @param string $text
     * @return string
     */
    public function decodeKsesPost($text)
    {
        return wp_kses_post(html_entity_decode($text));
    }

    /**
     * @param array $array
     * @return array
     */
    public function htmlAllowedDuringEscape($array)
    {
        return [
            'a'      => [
                'id'     => [],
                'href'   => [],
                'title'  => [],
                'target' => [],
                'rel'    => [],
            ],
            'span'   => [
                'class'  => [],
                'title'  => [],
            ],
            'p'      => [],
            'br'     => [],
            'code'   => [],
            'em'     => [],
            'strong' => [
                'class'  => [],
            ],
        ];
    }

    /**
     * Mimics the mysql_real_escape_string function. Adapted from a post by 'feedr' on php.net.
     * @link   http://php.net/manual/en/function.mysql-real-escape-string.php#101248
     * @access public
     * @param string | array $input The string to escape.
     * @return string|array
     */
    public function mysqlRealEscapeString($input)
    {
        if (is_array($input)) {
            return array_map(__METHOD__, $input);
        }
        if (!empty($input) && is_string($input)) {
            return str_replace(['\\', "\0", "\n", "\r", "'", '"', "\x1a"], ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'], $input);
        }

        return $input;
    }
}
