<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; return types && type-hints
// TODO PHP7.1; constant visibility

namespace WPStaging\Framework\Utils\Cache;

use LimitIterator;
use SplFileObject;
use WPStaging\Vendor\Symfony\Component\Filesystem\Exception\IOException;
use WPStaging\Framework\Filesystem\File;
use WPStaging\Framework\Filesystem\Filesystem;

// TODO DRY; re-use \WPStaging\Framework\Filesystem\File
// Buffered cache reads the file partially
class BufferedCache extends AbstractCache
{
    const POSITION_TOP = 'top';
    const POSITION_BOTTOM = 'bottom';

    const AVERAGE_LINE_LENGTH = 4096;
    const MAX_LENGTH_PER_IOP = 512000; // Max Length Per Input Output Operations

    public function first()
    {
        if (!$this->isValid()) {
            return null;
        }

        $handle = fopen($this->filePath, 'cb+');
        if (!$handle) {
            return null;
        }

        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            return null;
        }

        $first = null;
        $offset = 0;
        clearstatcache();
        $len = filesize($this->filePath);
        while (($buffer = fgets($handle, self::AVERAGE_LINE_LENGTH)) !== false) {
            if (!$first) {
                $first = $buffer;
                $offset = strlen($first);
                continue;
            }

            $pos = ftell($handle);
            fseek($handle,$pos - strlen($buffer) - $offset);
            fwrite($handle, $buffer);
            fseek($handle, $pos);
        }

        fflush($handle);
        ftruncate($handle, $len - $offset);
        flock($handle, LOCK_UN);
        fclose($handle);

        return trim(rtrim($first, PHP_EOL));
    }

    public function append($value)
    {
        if (is_array($value)) {
            $value = implode(PHP_EOL, $value);
        }
        /** @noinspection UnnecessaryCastingInspection */
        return (new File($this->filePath, File::MODE_APPEND))->fwriteSafe((string) $value . PHP_EOL);
    }

    public function prepend($value)
    {
        if (is_array($value)) {
            $value = implode(PHP_EOL, $value) . PHP_EOL;
        }

        $handle = fopen($this->filePath, 'wb+');
        $length = strlen($value);

        $i = 0;
        $data = $value;
        while (($buffer = fread($handle, self::AVERAGE_LINE_LENGTH)) !== false) {
            fseek($handle, $i * $length);
            fwrite($handle, $data);
            $data = $buffer;
            $i++;
        }
        fclose($handle);
    }

    /**
     * @param resource $source
     * @param int $offset
     * @param callable|null $shouldStop
     * @return int
     */
    public function appendFile($source, $offset = 0, callable $shouldStop = null)
    {
        $target = fopen($this->filePath, 'ab');

        if (!$shouldStop) {
            return $this->appendAllFile($source, $target, $offset);
        }
        return $this->stoppableAppendFile($source, $target, $offset, $shouldStop);
    }

    public function readLines($lines = 1, $default = null, $position = self::POSITION_TOP)
    {
        if (!$this->isValid()) {
            return $default;
        }

        if ($position === self::POSITION_BOTTOM) {
            return $this->readBottomLine($lines);
        }
        return $this->readTopLine($lines);
    }

    /**
     * @param int $lines
     * @return array|bool
     * @noinspection PhpUnused
     */
    public function deleteLines($lines = 1)
    {
        if (!$this->isValid()) {
            return false;
        }

        $handle = fopen($this->filePath, 'cb+');
        if (!$handle) {
            throw new IOException('Failed to open file: ' . $this->filePath);
        }

        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            throw new IOException('Failed to lock file: '. $this->filePath);
        }

        $offset= 0;
        clearstatcache();
        $size = filesize($this->filePath);
        $totalLines = 0;
        while (($buffer = fgets($handle, self::AVERAGE_LINE_LENGTH)) !== false) {
            $bufferSize = strlen($buffer);
            if ($totalLines < $lines) {
                $offset += $bufferSize;
                $totalLines++;
                continue;
            }

            $pos = ftell($handle);
            fseek($handle, $pos - $bufferSize - $offset);
            fwrite($handle, $buffer);
            fseek($handle, $pos);
        }
        fflush($handle);
        ftruncate($handle, $size - $offset);
        flock($handle, LOCK_UN);
        fclose($handle);

        return $offset > 0;
    }

    /**
     * @param int $bytes
     */
    public function deleteBottomBytes($bytes)
    {
        $handle = fopen($this->filePath, 'rb+');
        if (!$handle) {
            throw new IOException('Failed to open file: ' . $this->filePath);
        }

        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            throw new IOException('Failed to lock file: '. $this->filePath);
        }

        $stats = fstat($handle);
        ftruncate($handle, $stats['size'] - $bytes);
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    /**
     * @inheritDoc
     */
    public function get($default = null)
    {
        if (!$this->isValid()) {
            return $default;
        }

        return file_get_contents($this->filePath);
    }

    /**
     * @inheritDoc
     */
    public function save($value)
    {
        return (new File($this->filePath, File::MODE_WRITE))->fwriteSafe($value);
    }

    /**
     * This provides total line count of cache file, depending on the server / environment,
     * 1GB file can be read as low as .5s/ 500ms or less.
     * @return int
     */
    public function countLines()
    {
        $handle = fopen($this->filePath, 'rb+');
        $total = 0;

        while (!feof($handle)) {
            $total += substr_count(fread($handle, self::AVERAGE_LINE_LENGTH), PHP_EOL);
        }
        fclose($handle);
        return $total;
    }

    // TODO DRY \WPStaging\Framework\Filesystem\File::readBottomLines
    /**
     * @param int $lines
     * @return array
     */
    private function readBottomLine($lines)
    {
        $file = new SplFileObject($this->filePath, 'rb');
        $file->seek(PHP_INT_MAX);
        $lastLine = $file->key();
        $offset = $lastLine - $lines;
        if ($offset < 0) {
            $offset = 0;
        }

        $allLines = new LimitIterator($file, $offset, $lastLine);
        return array_reverse(array_values(iterator_to_array($allLines)));
    }

    /**
     * @param int $lines
     * @return array|null
     */
    private function readTopLine($lines)
    {
        $handle = fopen($this->filePath, 'rb');
        if (!$handle) {
            throw new IOException('Failed to open file: ' . $this->filePath);
        }

        $data = [];
        $i = 0;
        while (($buffer = fgets($handle, self::AVERAGE_LINE_LENGTH)) !== false) {
            $data[]= trim($buffer);
            $i++;
            if ($i >= $lines) {
                break;
            }
        }

        if (!$data) {
            return null;
        }
        return $data;
    }

    /**
     * @param resource $source
     * @param resource $target
     * @param int $offset
     * @return int
     * @throws IOException
     */
    private function appendAllFile($source, $target, $offset)
    {
        $bytesWritten = 0;
        while (!feof($source)) {
            $chunk = fread($source, self::MAX_LENGTH_PER_IOP);
            $_bytesWritten = fwrite($target, $chunk);

            // Failed to write
            if ($_bytesWritten === false) {
                // TODO Custom Exception
                throw new IOException('Failed to append stoppable file');
            }
            $bytesWritten += $_bytesWritten;
        }
        return $bytesWritten;
    }

    /**
     * @param resource $source
     * @param resource $target
     * @param int $offset
     * @param callable $shouldStop
     * @return int
     * @throws IOException
     */
    private function stoppableAppendFile($source, $target, $offset, callable $shouldStop)
    {
        $stats = fstat($source);
        $bytesWritten = 0;
        while (!$shouldStop() && !feof($source)) {
            $chunk = fread($source, self::MAX_LENGTH_PER_IOP);
            $_bytesWritten = fwrite($target, $chunk);

            // Failed to write
            if ($_bytesWritten === false) {
                // TODO Custom Exception
                throw new IOException('Failed to append stoppable file');
            }

            // Finished writing, nothing more to write!
            $bytesWritten += $_bytesWritten;
            if ($_bytesWritten === 0 || $stats['size'] <= $bytesWritten) {
                break;
            }
        }
        return $bytesWritten;
    }
}
