<?php

namespace WPStaging\Framework\Logger;




class LogFileReader
{
 
    const MAX_READ_BYTES = 102400;







    public function findLogFileOfJob(string $logsDir, string $jobId, string $logNamePrefix = ''): string
    {
        return $this->newestLogFileOfJob($this->listLogFileNames($logsDir), $jobId, $logNamePrefix);
    }







    public function listLogFileNames(string $logsDir): array
    {
        if (!is_dir($logsDir)) {
            return [];
        }

        return array_values(preg_grep('/\.log$/', (array)scandir($logsDir)));
    }










    public function newestLogFileOfJob(array $logFileNames, string $jobId, string $logNamePrefix = ''): string
    {
        if ($jobId === '') {
            return '';
        }

        $suffix  = '__' . $jobId . '.log';
        $matches = array_filter($logFileNames, function ($fileName) use ($suffix, $logNamePrefix) {
            return substr($fileName, -strlen($suffix)) === $suffix && ($logNamePrefix === '' || strpos($fileName, $logNamePrefix) === 0);
        });

        sort($matches);

        return (string)end($matches);
    }







    public function parseLogContent(string $logPath): array
    {
        $fileSize  = (int)filesize($logPath);
        $truncated = $fileSize > self::MAX_READ_BYTES;

        if (!$truncated) {
            $content = file_get_contents($logPath);

            return $this->parseLogLines($content !== false ? $content : '', false);
        }

        $handle = fopen($logPath, 'rb');
        if ($handle === false) {
            return [];
        }

        fseek($handle, -self::MAX_READ_BYTES, SEEK_END);
        $content = fread($handle, self::MAX_READ_BYTES);
        fclose($handle);

        return $this->parseLogLines($content !== false ? $content : '', true);
    }








    public function parseLogLines(string $content, bool $truncated): array
    {
        $entries = [];

        if ($truncated) {
            $entries[] = [
                'type'    => 'notice',
                'date'    => '',
                'message' => esc_html__('[Truncated — showing last 100 KB]', 'wp-staging'),
            ];
        }

        foreach (explode(PHP_EOL, $content) as $line) {
            $line = rtrim($line);
            if (empty($line)) {
                continue;
            }

            if (preg_match('/^\[([A-Za-z_]+)\]-\[([^\]]*)\]\s?(.*)$/', $line, $matches)) {
                $entries[] = [
                    'type'    => strtolower($matches[1]),
                    'date'    => $matches[2],
                    'message' => $matches[3],
                ];
            }
        }

        return $entries;
    }
}
