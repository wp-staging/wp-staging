<?php

namespace WPStaging\Framework\Traits;





trait EndOfLinePlaceholderTrait
{
    use WindowsOsTrait;








    public function replaceEOLsWithPlaceholders($subject)
    {
        if ($subject === null) {
            return $subject;
        }

 
        if ($this->isWindowsOs()) {
            return $subject;
        }

        return empty($subject) ? $subject : str_replace([PHP_EOL], ['{WPSTG_EOL}'], $subject);
    }








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
