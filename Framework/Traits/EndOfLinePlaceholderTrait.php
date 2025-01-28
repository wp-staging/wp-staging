<?php

namespace WPStaging\Framework\Traits;

/**
 * Trait EndOfLinePlaceholderTrait
 * @package WPStaging\Framework\Traits
 */
trait EndOfLinePlaceholderTrait
{
    use WindowsOsTrait;

    /**
     * @param  array|string $subject
     *
     * @see https://github.com/wp-staging/wp-staging-pro/issues/3402
     *
     * @return array|string
     */
    public function replaceEOLsWithPlaceholders($subject)
    {
        if ($subject === null) {
            return $subject;
        }

        //Early bail: newline (\n) in file name will not happen on windows.
        if ($this->isWindowsOs()) {
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
        if ($subject === null) {
            return $subject;
        }

        if (strpos($subject, '{WPSTG_EOL}') === false) {
            return $subject;
        }

        if ($this->isWindowsOs()) {
            if (!empty($this->logger)) {
                $this->logger->warning(sprintf('Filename %s contains EOL character, but Windows doesn\'t support EOL in file name, plugin/theme using that file might not work.', $subject));
            }

            return $subject;
        }

        return empty($subject) ? $subject : str_replace(['{WPSTG_EOL}'], [PHP_EOL], $subject);
    }
}
