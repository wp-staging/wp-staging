<?php

namespace WPStaging\Framework\Traits;

/**
 * Provides helpers to identify SQL lines during database file extraction.
 * Used to filter out non-SQL content such as backup index data from extracted SQL files.
 *
 * Also used in wpstg-restore standalone tool (src/wpstg-restore/src/classes/Extractor.php).
 */
trait SqlCommentTrait
{
    /**
     * Check if a line is a SQL comment or empty/whitespace-only.
     */
    protected function isSqlCommentOrEmpty(string $line): bool
    {
        $trimmed = trim($line);
        if ($trimmed === '' || $this->isLineBreakOnly($trimmed)) {
            return true;
        }

        $first2Chars = substr($trimmed, 0, 2);
        return $first2Chars === '--' || strpos($trimmed, '#') === 0 || $first2Chars === '/*';
    }

    /**
     * Check if a line is a valid SQL statement (ends with semicolon).
     */
    protected function isSqlStatement(string $line): bool
    {
        $trimmed = trim($line);
        return $trimmed !== '' && substr($trimmed, -1) === ';';
    }

    /**
     * Check if a line is valid SQL content (statement, comment, or empty).
     * Used to filter out backup index data from extracted database files.
     */
    protected function isSqlContent(string $line): bool
    {
        return $this->isSqlCommentOrEmpty($line) || $this->isSqlStatement($line);
    }

    private function isLineBreakOnly(string $string): bool
    {
        return empty($string) || in_array($string, ["\r", "\n", "\r\n", "\n\r", chr(13), chr(10), PHP_EOL]);
    }
}
