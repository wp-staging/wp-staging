<?php

namespace WPStaging\Framework\Traits;

/**
 * Provide a wrapper method for i18n wordpress functions, to return the string as it is when the functions are not available
 * Useful in standalone tool
 * Trait I18nTrait
 * @package WPStaging\Framework\Traits
 */
trait I18nTrait
{
    /**
     * @param string $message
     * @param string $domain
     * @return string
     */
    protected function translate(string $message, string $domain)
    {
        if (function_exists('__')) {
            return __($message, $domain); // phpcs:ignore
        }

        return $message;
    }

    /**
     * @param string $message
     * @param string $domain
     * @return string
     */
    protected function escapeHtmlAndTranslate(string $message, string $domain)
    {
        if (function_exists('esc_html__')) {
            return esc_html__($message, $domain); // phpcs:ignore
        }

        return $message;
    }
}
