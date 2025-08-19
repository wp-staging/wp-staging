<?php

namespace WPStaging\Framework\Utils;

class Escape
{
    /**
     * Escape html with allowed html tags
     *
     * @param string $content
     * @return string
     */
    public function escapeHtml(string $content): string
    {
        return wp_kses($content, $this->htmlAllowedDuringEscape([]));
    }

    /**
     * Html decode and then wp_kses_post
     *
     * @param string $text
     * @return string
     */
    public function decodeKsesPost(string $text): string
    {
        return wp_kses_post(html_entity_decode($text));
    }

    /**
     * @param array $array
     * @return array
     */
    public function htmlAllowedDuringEscape(array $array): array
    {
        return [
            'a'      => [
                'id'     => [],
                'href'   => [],
                'title'  => [],
                'target' => [],
                'rel'    => [],
                'class'  => [],
            ],
            'span'   => [
                'class'  => [],
                'title'  => [],
            ],
            'p'      => [],
            'br'     => [],
            'b'      => [],
            'code'   => [],
            'em'     => [],
            'strong' => [
                'class' => [],
            ],
            'svg' => [
                'xmlns'           => [],
                'width'           => [],
                'height'          => [],
                'viewbox'         => [],
                'fill'            => [],
                'stroke'          => [],
                'stroke-width'    => [],
                'stroke-linecap'  => [],
                'stroke-linejoin' => [],
                'aria-hidden'     => [],
                'focusable'       => [],
                'role'            => [],
                'class'           => [],
            ],
            'path' => [
                'd'               => [],
                'fill'            => [],
                'stroke'          => [],
                'stroke-width'    => [],
                'stroke-linecap'  => [],
                'stroke-linejoin' => [],
            ],
            'g' => [
                'fill' => [],
            ],
            'polyline' => [
                'points' => [],
                'fill'   => [],
                'stroke' => [],
            ],
            'circle' => [
                'cx'     => [],
                'cy'     => [],
                'r'      => [],
                'fill'   => [],
                'stroke' => [],
            ],
            'rect' => [
                'x'      => [],
                'y'      => [],
                'width'  => [],
                'height' => [],
                'fill'   => [],
                'stroke' => [],
            ],
            'line' => [
                'x1'     => [],
                'y1'     => [],
                'x2'     => [],
                'y2'     => [],
                'fill'   => [],
                'stroke' => [],
            ],
            'defs' => [
                'clipPath' => []
            ],
            'ellipse' => [
                'cx'     => [],
                'cy'     => [],
                'rx'     => [],
                'ry'     => [],
            ]
        ];
    }

    /**
     * Mimics the mysql_real_escape_string function. Adapted from a post by 'feedr' on php.net.
     * @link   http://php.net/manual/en/function.mysql-real-escape-string.php#101248
     * @access public
     * @param string|array $input The string to escape.
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
