<?php

namespace WPStaging\Framework\Traits;







trait I18nTrait
{





    protected function translate(string $message, string $domain)
    {
        if (function_exists('__')) {
            return __($message, $domain); // phpcs:ignore
        }

        return $message;
    }






    protected function escapeHtmlAndTranslate(string $message, string $domain)
    {
        if (function_exists('esc_html__')) {
            return esc_html__($message, $domain); // phpcs:ignore
        }

        return $message;
    }
}
