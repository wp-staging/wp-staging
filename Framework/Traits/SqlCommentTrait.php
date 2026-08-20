<?php

namespace WPStaging\Framework\Traits;







trait SqlCommentTrait
{



    protected function isSqlCommentOrEmpty(string $line): bool
    {
        $trimmed = trim($line);
        if ($trimmed === '' || $this->isLineBreakOnly($trimmed)) {
            return true;
        }

        $first2Chars = substr($trimmed, 0, 2);
        return $first2Chars === '--' || strpos($trimmed, '#') === 0 || $first2Chars === '/*';
    }




    protected function isSqlStatement(string $line): bool
    {
        $trimmed = trim($line);
        return $trimmed !== '' && substr($trimmed, -1) === ';';
    }





    protected function isSqlContent(string $line): bool
    {
        return $this->isSqlCommentOrEmpty($line) || $this->isSqlStatement($line);
    }

    private function isLineBreakOnly(string $string): bool
    {
        return empty($string) || in_array($string, ["\r", "\n", "\r\n", "\n\r", chr(13), chr(10), PHP_EOL]);
    }
}
