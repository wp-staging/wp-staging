<?php

namespace WPStaging\Framework\Traits;

/**
 * Trait EndOfLinePlaceholderTrait
 * @package WPStaging\Framework\Traits
 */
trait EndOfLinePlaceholderTrait
{
    /**
     * @param  array|string $subject
     *
     * @see https://github.com/wp-staging/wp-staging-pro/issues/3402
     *
     * @return array|string
     */
    public function replaceEOLsWithPlaceholders($subject)
    {
        //Early bail: newline (\n) in file name will not happen on windows.
        if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
            return $subject;
        }

        return empty($subject) ? $subject : str_replace([PHP_EOL], ['{WPSTG_EOL}'], $subject);
    }

    /**
     * @param  array|string $subject
     *
     * @see https://github.com/wp-staging/wp-staging-pro/issues/3402
     *
     * @return array|string
     */
    public function replacePlaceholdersWithEOLs($subject)
    {
        if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
            if (!empty($this->logger) && strpos($subject, '{WPSTG_EOL}') !== false) {
                $this->logger->warning(sprintf('Filename %s contains EOL character, plugin using that file might not work.', $subject));
            }

            return $subject;
        }

        return empty($subject) ? $subject : str_replace(['{WPSTG_EOL}'], [PHP_EOL], $subject);
    }
}
