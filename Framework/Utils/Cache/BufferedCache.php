<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; return types && type-hints
// TODO PHP7.1; constant visibility

namespace WPStaging\Framework\Utils\Cache;

use SplFileObject;
use LimitIterator;
use WPStaging\Framework\Exceptions\IOException;
use WPStaging\Framework\Traits\ResourceTrait;
use WPStaging\Framework\Filesystem\File;
use WPStaging\Pro\Backup\Exceptions\DiskNotWritableException;
use WPStaging\Pro\Backup\Exceptions\ThresholdException;

// TODO DRY; re-use \WPStaging\Framework\Filesystem\File
// Buffered cache reads the file partially
class BufferedCache extends AbstractCache
{
    use ResourceTrait;

    const POSITION_TOP = 'top';
    const POSITION_BOTTOM = 'bottom';

    const AVERAGE_LINE_LENGTH = 4096;

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
            fseek($handle, $pos - strlen($buffer) - $offset);
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
        return (new File($this->filePath, File::MODE_APPEND))->fwriteSafe((string)$value . PHP_EOL);
    }

    /**
     * Like array_reverse(), but for files.
     *
     * @throws ThresholdException When threshold limit hits.
     */
    public function reverse()
    {
        if (!file_exists($this->filePath . 'tmp')) {
            copy($this->filePath, $this->filePath . 'tmp');
        }

        $existingFile = new SplFileObject($this->filePath, 'rb+');
        $existingFile->flock(LOCK_EX);

        $tempFile = new SplFileObject($this->filePath . 'tmp', 'rb+');
        $existingFile->flock(LOCK_EX);

        $lastLine = null;
        $currentLine = null;

        try {
            $i = 0;
            while (true) {
                $i++;
                // Only check for thresholds every 25 lines
                if ($i >= 25) {
                    $i = 0;
                    if ($this->isThreshold()) {
                        throw ThresholdException::thresholdHit('');
                    }
                }

                $existingFile->seek(PHP_INT_MAX);

                if (!is_null($currentLine)) {
                    $currentLine--;

                    if ($currentLine < 0) {
                        throw new \OutOfBoundsException();
                    }

                    $existingFile->seek($currentLine);
                }

                if (is_null($lastLine)) {
                    $lastLine = $existingFile->key();
                    $currentLine = $lastLine;
                    $existingFile->seek($lastLine);
                }

                $line = $existingFile->current();
                $tempFile->fwrite($line);
                $existingFile->ftruncate($existingFile->ftell());
            }
        } catch (\OutOfBoundsException $e) {
            // End of file
        } catch (ThresholdException $e) {
            // This exception must be handled by the caller.
            throw $e;
        }

        unlink($this->filePath);
        rename($this->filePath . 'tmp', $this->filePath);
    }

    public function prepend($data)
    {
        if (is_array($data)) {
            $data = implode(PHP_EOL, $data);
        }

        $data = trim($data) . PHP_EOL;

        // Early bail: First addition
        if (!file_exists($this->filePath)) {
            file_put_contents($this->filePath, $data);
            return;
        }

        /*
         * To prepend to a large file, we have to re-write it from scratch,
         * so let's make a copy of the file, add our data to the beginning of a new file,
         * and add the data from the existing file into it.
         */

        copy($this->filePath, $this->filePath . 'tmp');

        $existingFile = new SplFileObject($this->filePath, 'rb');
        $existingFile->flock(LOCK_EX);

        $tempFile = new SplFileObject($this->filePath . 'tmp', 'wb');
        $existingFile->flock(LOCK_EX);
        $tempFile->fwrite($data);

        while (!empty($nextLine = $existingFile->fgets())) {
            $tempFile->fwrite($nextLine);
        }

        unlink($this->filePath);
        copy($this->filePath . 'tmp', $this->filePath);
    }

    /**
     * @param resource $source
     * @param int $offset
     * @throws DiskNotWritableException
     * @throws \RuntimeException
     * @return int
     */
    public function appendFile($source, $offset = 0)
    {
        $target = fopen($this->filePath, 'ab');

        return $this->stoppableAppendFile($source, $target, $offset);
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
            throw new IOException('Failed to lock file: ' . $this->filePath);
        }

        $offset = 0;
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
            throw new IOException('Failed to lock file: ' . $this->filePath);
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
        $offset = max($lastLine - $lines, 0);

        $allLines = new LimitIterator($file, $offset, $lastLine);
        return array_reverse(array_values(iterator_to_array($allLines)));
    }

    public function readLastLine()
    {
        $file = new SplFileObject($this->filePath, 'rb');
        $negativeOffset = 16 * KB_IN_BYTES;

        // Set the pointer to the end of the file, minus the negative offset for which to start looking for the last line.
        $file->fseek(max($file->getSize() - $negativeOffset, 0), SEEK_SET);

        do {
            $lastLine = $file->fgets();
        } while (!$file->eof());

        return $lastLine;
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
            $data[] = trim($buffer);
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
     * @throws DiskNotWritableException
     * @throws \RuntimeException If can't read chunk from file
     */
    private function stoppableAppendFile($source, $target, $offset)
    {
        $stats = fstat($source);
        $bytesWrittenTotal = $offset;
        fseek($source, $offset);
        while (!$this->isThreshold() && !feof($source)) {
            $chunk = fread($source, 512 * KB_IN_BYTES);

            if ($chunk === false) {
                throw new \RuntimeException('Could not read chunk from file');
            }

            $bytesWrittenInThisRequest = fwrite($target, $chunk);

            // Failed to write
            if ($bytesWrittenInThisRequest === false || ($bytesWrittenInThisRequest <= 0 && strlen($chunk) > 0)) {
                throw DiskNotWritableException::fileNotWritable($this->filePath);
            }

            // Finished writing, nothing more to write!
            $bytesWrittenTotal += $bytesWrittenInThisRequest;
            if ($bytesWrittenInThisRequest === 0 || $stats['size'] <= $bytesWrittenTotal) {
                break;
            }
        }
        return $bytesWrittenTotal;
    }
}
