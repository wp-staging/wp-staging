<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; return types && type-hints
// TODO PHP7.1; constant visibility

namespace WPStaging\Framework\Utils\Cache;

use LimitIterator;
use WPStaging\Framework\Exceptions\IOException;
use WPStaging\Framework\Traits\ResourceTrait;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Backup\Exceptions\DiskNotWritableException;
use WPStaging\Backup\Exceptions\ThresholdException;

use function WPStaging\functions\debug_log;

// TODO DRY; re-use \WPStaging\Framework\Filesystem\FileObject
// Buffered cache reads the file partially
class BufferedCache extends AbstractCache
{
    use ResourceTrait;

    const POSITION_TOP = 'top';
    const POSITION_BOTTOM = 'bottom';

    const AVERAGE_LINE_LENGTH = 4096;

    protected $chunkReadingSizeForAppendingFile = 500 * 1024; // 500KB

    /**
     * @throws IOException
     */
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

        $first = '';
        $offset = 0;
        clearstatcache();
        $len = filesize($this->filePath);
        while (($buffer = fgets($handle, self::AVERAGE_LINE_LENGTH)) !== false) {
            if (!$first) {
                $first  = $buffer;
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

    /**
     * @param $value
     * @return int
     * @throws DiskNotWritableException
     */
    public function append($value)
    {
        if (is_array($value)) {
            $value = implode(PHP_EOL, $value);
        }

        /** @noinspection UnnecessaryCastingInspection */
        $file = new FileObject($this->filePath, FileObject::MODE_APPEND);

        $writtenData = $file->fwriteSafe($value . PHP_EOL);

        if ($writtenData === false) {
            debug_log("Could not write to file {$this->filePath} Data: {$value}");
            throw DiskNotWritableException::fileNotWritable($this->filePath);
        }

        if (!file_exists($this->filePath)) {
            debug_log("Could not write to file {$this->filePath} Data: {$value}. File not created!");
            throw DiskNotWritableException::fileNotWritable($this->filePath);
        }

        return $writtenData;
    }

    /**
     * Like array_reverse(), but for files.
     *
     * @throws ThresholdException|DiskNotWritableException When threshold limit hits.
     */
    public function reverse()
    {
        if (!file_exists($this->filePath . 'tmp')) {
            copy($this->filePath, $this->filePath . 'tmp');
        }

        $existingFile = new FileObject($this->filePath, 'rb+');
        $existingFile->flock(LOCK_EX);

        $tempFile = new FileObject($this->filePath . 'tmp', 'rb+');
        $existingFile->flock(LOCK_EX);

        $lastLine    = null;
        $currentLine = null;

        try {
            $i = 0;
            while (true) {
                $i++;
                // Only check for thresholds every 25 lines
                if ($i >= 25) {
                    $i = 0;
                    if ($this->isThreshold()) {
                        throw ThresholdException::thresholdHit();
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
                    $lastLine    = $existingFile->key();
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
            debug_log("Threshold hit while reversing file {$this->filePath}");
            throw $e;
        } catch (\Exception $e) {
            debug_log("Could not reverse file {$this->filePath}. {$e->getMessage()}");
        }

        unlink($this->filePath);
        rename($this->filePath . 'tmp', $this->filePath);
    }

    /**
     * @throws DiskNotWritableException
     */
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

        $existingFile = new FileObject($this->filePath, 'rb');
        $existingFile->flock(LOCK_EX);

        $tempFile = new FileObject($this->filePath . 'tmp', 'wb');
        $existingFile->flock(LOCK_EX);
        $tempFile->fwrite($data);

        while (!empty($nextLine = $existingFile->readAndMoveNext())) {
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

    /**
     * @throws IOException|DiskNotWritableException
     */
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
     * @return bool
     * @noinspection PhpUnused
     * @throws IOException
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
        $size       = filesize($this->filePath);
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
     * @throws IOException
     */
    public function deleteBottomBytes($bytes)
    {
        $handle = fopen($this->filePath, 'rb+');
        if (!$handle) {
            debug_log('Failed to open file: ' . $this->filePath, 'file');
            throw new IOException('Failed to open file: ' . $this->filePath);
        }

        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            debug_log('Failed to lock file: ' . $this->filePath, 'file');
            throw new IOException('Failed to lock file: ' . $this->filePath);
        }

        $stats = fstat($handle);
        ftruncate($handle, $stats['size'] - $bytes);
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    /**
     * @param $default
     * @return array|false|mixed|object|string|null
     * @throws IOException
     */
    public function get($default = null)
    {
        if (!$this->isValid()) {
            return $default;
        }

        return file_get_contents($this->filePath);
    }

    /**
     * @param $value
     * @return int
     * @throws DiskNotWritableException
     */
    public function save($value)
    {
        $file = new FileObject($this->filePath, FileObject::MODE_WRITE);

        $writtenData = $file->fwriteSafe($value);

        if ($writtenData === false) {
            debug_log("Could not save() and write to file {$this->filePath} Data: {$value}");
            throw DiskNotWritableException::fileNotWritable($this->filePath);
        }

        return $writtenData;
    }

    /**
     * This provides total line count of cache file, depending on the server / environment,
     * 1GB file can be read as low as .5s/ 500ms or less.
     * @return int
     */
    public function countLines()
    {
        $handle = fopen($this->filePath, 'rb+');
        $total  = 0;

        while (!feof($handle)) {
            $total += substr_count(fread($handle, self::AVERAGE_LINE_LENGTH), PHP_EOL);
        }
        fclose($handle);
        return $total;
    }

    // TODO DRY \WPStaging\Framework\Filesystem\FileObject::readBottomLines

    /**
     * @param int $lines
     * @return array
     * @throws DiskNotWritableException
     * @throws \Exception
     */
    private function readBottomLine($lines)
    {
        $file = new FileObject($this->filePath, 'rb');
        $file->seek(PHP_INT_MAX);
        $lastLine = $file->key();
        $offset   = max($lastLine - $lines, 0);

        $allLines = new LimitIterator($file, $offset, $lastLine);
        return array_reverse(array_values(iterator_to_array($allLines)));
    }

    /**
     * @throws DiskNotWritableException
     */
    public function readLastLine()
    {
        $file           = new FileObject($this->filePath, 'rb');
        $negativeOffset = 16 * KB_IN_BYTES;

        // Set the pointer to the end of the file, minus the negative offset for which to start looking for the last line.
        $file->fseek(max($file->getSize() - $negativeOffset, 0), SEEK_SET);

        do {
            $lastLine = $file->readAndMoveNext();
        } while (!$file->eof());

        return $lastLine;
    }

    /**
     * @param int $lines
     * @return array|null
     * @throws IOException
     */
    private function readTopLine($lines)
    {
        $handle = fopen($this->filePath, 'rb');
        if (!$handle) {
            throw new IOException('Failed to open file: ' . $this->filePath);
        }

        $data = [];
        $i    = 0;
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
     * @throws \RuntimeException If you can't read chunk from file
     */
    private function stoppableAppendFile($source, $target, $offset)
    {
        $stats             = fstat($source);
        $bytesWrittenTotal = $offset;
        fseek($source, $offset);
        while (!$this->isThreshold() && !feof($source)) {
            $chunk = fread($source, $this->chunkReadingSizeForAppendingFile);

            if ($chunk === false) {
                debug_log('stoppableAppendFile(): Could not read chunk from file');
                throw new \RuntimeException('Could not read chunk from file');
            }

            $bytesWrittenInThisRequest = fwrite($target, $chunk);

            // Failed to write
            if ($bytesWrittenInThisRequest === false || ($bytesWrittenInThisRequest <= 0 && strlen($chunk) > 0)) {
                debug_log('stoppableAppendFile(): Could not write chunk to file');
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
