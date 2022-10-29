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
}
