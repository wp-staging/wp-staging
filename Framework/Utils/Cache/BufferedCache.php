<?php

 
 
 

namespace WPStaging\Framework\Utils\Cache;

use LimitIterator;
use WPStaging\Backup\Service\ZlibCompressor;
use WPStaging\Framework\Exceptions\IOException;
use WPStaging\Framework\Traits\ResourceTrait;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;
use WPStaging\Framework\Job\Exception\ThresholdException;

use function WPStaging\functions\debug_log;















 
 
class BufferedCache extends AbstractCache
{
    use ResourceTrait;

 
    const POSITION_TOP = 'top';

 
    const POSITION_BOTTOM = 'bottom';

 
    const AVERAGE_LINE_LENGTH = 4096;

 
    const FILE_EXTENSION = 'cache.php';

 
    protected $chunkReadingSizeForAppendingFile = 512 * 1024; 




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

        $first  = '';
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

        return trim(rtrim($first, "\n"));
    }






    public function append($value)
    {
        if (is_array($value)) {
            $value = implode("\n", $value);
        }

 
        $file = new FileObject($this->filePath, FileObject::MODE_APPEND);

        $writtenData = $file->fwriteSafe($value . "\n");

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






    public function appendUnsafe(string $content): int
    {
 
        $file = new FileObject($this->filePath, FileObject::MODE_APPEND_AND_READ);

        $writtenData = $file->fwrite($content);

        if ($writtenData === false) {
            debug_log("Could not write to file {$this->filePath} Data: {$content}");
            throw DiskNotWritableException::fileNotWritable($this->filePath);
        }

        if (!file_exists($this->filePath)) {
            debug_log("Could not write to file {$this->filePath} Data: {$content}. File not created!");
            throw DiskNotWritableException::fileNotWritable($this->filePath);
        }

        $file = null;

        return $writtenData;
    }






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
 
        } catch (ThresholdException $e) {
 
            debug_log("Threshold hit while reversing file {$this->filePath}");
            throw $e;
        } catch (\Exception $e) {
            debug_log("Could not reverse file {$this->filePath}. {$e->getMessage()}");
        }

        unlink($this->filePath);
        rename($this->filePath . 'tmp', $this->filePath);
    }




    public function prepend($data)
    {
        if (is_array($data)) {
            $data = implode("\n", $data);
        }

        $data = trim($data) . "\n";

 
        if (!file_exists($this->filePath)) {
            file_put_contents($this->filePath, $data);
            return;
        }







        copy($this->filePath, $this->filePath . 'tmp');

        $existingFile = new FileObject($this->filePath, 'rb');
        $existingFile->flock(LOCK_EX);

        $tempFile = new FileObject($this->filePath . 'tmp', 'wb');
        $existingFile->flock(LOCK_EX);
        $tempFile->fwrite($data);

        while (!empty($nextLine = $existingFile->readAndMoveNext())) {
            $tempFile->fwrite($nextLine);
        }

        $existingFile = null;
        $tempFile = null;
        unlink($this->filePath);
        copy($this->filePath . 'tmp', $this->filePath);
    }









    public function appendFile($source, $offset = 0)
    {
        $target = fopen($this->filePath, 'ab');

        try {
            $bytesWritten = $this->stoppableAppendFile($source, $target, $offset);
        } catch (ThresholdException $e) {
 
            fclose($target);
            $target = null;
            throw $e;
        }

        fclose($target);
        $target = null;

        return $bytesWritten;
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






    public function get($default = null)
    {
        if (!$this->isValid()) {
            return $default;
        }

        return file_get_contents($this->filePath);
    }






    public function save($value)
    {
        $file = new FileObject($this->filePath, FileObject::MODE_WRITE);

        $writtenData = $file->fwriteSafe($value);

        $file = null;

        if ($writtenData === false) {
            debug_log("Could not save() and write to file {$this->filePath} Data: {$value}");
            throw DiskNotWritableException::fileNotWritable($this->filePath);
        }

        return $writtenData;
    }






    public function countLines(): int
    {
        if (!file_exists($this->filePath)) {
            return 0;
        }

        $handle = fopen($this->filePath, 'rb+');
        $total  = 0;

        while (!feof($handle)) {
            $total += substr_count(fread($handle, self::AVERAGE_LINE_LENGTH), "\n");
        }

        fclose($handle);
        return $total;
    }

 







    private function readBottomLine($lines)
    {
        $file = new FileObject($this->filePath, 'rb');
        $file->seek(PHP_INT_MAX);
        $lastLine = $file->key();
        $offset   = max($lastLine - $lines, 0);

        $allLines = new LimitIterator($file, $offset, $lastLine);
        $file     = null;
        return array_reverse(array_values(iterator_to_array($allLines)));
    }




    public function readLastLine()
    {
        $file           = new FileObject($this->filePath, 'rb');
        $negativeOffset = 16 * KB_IN_BYTES;

 
        $file->fseek(max($file->getSize() - $negativeOffset, 0), SEEK_SET);

        do {
            $lastLine = $file->readAndMoveNext();
        } while (!$file->eof());

        $file = null;

        return $lastLine;
    }





    public function setFileAppendTimeLimit(int $timeLimt)
    {
        self::$fileAppendMaxExecutionTimeInSeconds = $timeLimt;
    }






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










    private function stoppableAppendFile($source, $target, $offset)
    {
        $stats             = fstat($source);
        $bytesWrittenTotal = $offset;
        fseek($source, $offset);

 
        if ($this->isFileAppendThreshold()) {
            throw ThresholdException::thresholdHit();
        }

        while (!$this->isFileAppendThreshold() && !feof($source)) {
            $chunk = fread($source, $this->chunkReadingSizeForAppendingFile);

            if ($chunk === false) {
                debug_log('stoppableAppendFile(): Could not read chunk from file');
                throw new \RuntimeException('Could not read chunk from file');
            }

            $bytesWrittenInThisRequest = fwrite($target, $chunk);

 
            if ($bytesWrittenInThisRequest === false || ($bytesWrittenInThisRequest <= 0 && strlen($chunk) > 0)) {
                debug_log('stoppableAppendFile(): Could not write chunk to file');
                throw DiskNotWritableException::fileNotWritable($this->filePath);
            }

 
            $bytesWrittenTotal += $bytesWrittenInThisRequest;
            if ($bytesWrittenInThisRequest === 0 || $stats['size'] <= $bytesWrittenTotal) {
                break;
            }
        }

        return $bytesWrittenTotal;
    }
}
