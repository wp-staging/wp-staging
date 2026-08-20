<?php

namespace WPStaging\Framework\Filesystem;








abstract class AbstractFileObject extends \SplFileObject
{
    const MODE_READ            = 'rb'; 
    const MODE_WRITE           = 'wb'; 
    const MODE_APPEND          = 'ab'; 
    const MODE_APPEND_AND_READ = 'ab+'; 
    const MODE_WRITE_SAFE      = 'xb'; 
    const MODE_WRITE_UNSAFE    = 'cb'; 

 
    protected $totalLines = null;

 
    protected $fgetsUsedOnKey0 = false;

 
    protected $fseekUsed = false;

    public function __construct(string $fullPath, string $openMode = self::MODE_READ)
    {
        try {
            parent::__construct($fullPath, $openMode);
        } catch (\Throwable $e) {
            throw $e;
        }
    }









    public function setTotalLines(int $totalLines)
    {
        $this->totalLines = $totalLines;
    }

 
    public function totalLines(bool $useParent = false): int
    {
        if ($this->totalLines !== null) {
            return $this->totalLines;
        }

        if ($useParent) {
            $currentKey = $this->keyUseParent();
            $this->seekUseParent(PHP_INT_MAX);
            $this->totalLines = $this->keyUseParent();

            if ($currentKey < 0) {
                $currentKey = 0;
            }

            $this->seekUseParent($currentKey);
        } else {
            $currentKey = $this->key();
            if ($currentKey < 0) {
                $currentKey = 0;
            }

            $this->seek(PHP_INT_MAX);
            $this->totalLines = $this->key();
            $this->seek($currentKey);
        }

 
 
 
 
 
 
 
 
 
 
        if ($this->totalLines > 0) {
            if (PHP_VERSION === '8.2.0RC3' || version_compare(PHP_VERSION, '8.2.0', '>=')) {
                $this->totalLines += 1;
            }

            if (version_compare(PHP_VERSION, '8.1', '>') && version_compare(PHP_VERSION, '8.1.11', '<=')) {
                $this->totalLines += 1;
            }
        }

        return $this->totalLines;
    }


















    #[\ReturnTypeWillChange]
    public function seek($offset)
    {
        if ($offset < 0) {
            throw new \Exception("Can't seek file: " . $this->getPathname() . " to negative offset: $offset");
        }

        $this->fseekUsed       = false;
        $this->fgetsUsedOnKey0 = false;
        if ($offset === 0 || version_compare(PHP_VERSION, '8.0.1', '<')) {
            parent::seek($offset);
            return;
        }

        $offset -= 1;

        if ($this->totalLines !== null && $offset >= $this->totalLines) {
            $offset += 1;
        }

        $originalFlags = $this->getFlags();
        $newFlags      = $originalFlags & ~self::READ_AHEAD;
        $this->setFlags($newFlags);

        parent::seek($offset);

        if ($this->eof()) {
            $this->current();
            $this->totalLines = $this->key();
            return;
        }

        $this->current();
        $this->next();
        $this->current();

        $this->setFlags($originalFlags);
    }











    public function fgets(): string
    {
        if ($this->key() === 0 || version_compare(PHP_VERSION, '8.0.1', '<')) {
            $this->fgetsUsedOnKey0 = true;
            return parent::fgets();
        }

        $originalFlags = $this->getFlags();
        $newFlags      = $originalFlags & ~self::READ_AHEAD;
        $this->setFlags($newFlags);

        $line = $this->current();
        $this->next();

        if (version_compare(PHP_VERSION, '8.0.19', '<')) {
            $line = $this->current();
        }

        if (version_compare(PHP_VERSION, '8.1', '>') && version_compare(PHP_VERSION, '8.1.6', '<')) {
            $line = $this->current();
        }

        if (!$this->fseekUsed) {
            $line = $this->current();
        }

        $this->setFlags($originalFlags);
        return $line;
    }

 
    #[\ReturnTypeWillChange]
    public function key(): int
    {
        if (!$this->fgetsUsedOnKey0 || version_compare(PHP_VERSION, '8.0.19', '<')) {
            return parent::key();
        }

        if (version_compare(PHP_VERSION, '8.1', '>') && version_compare(PHP_VERSION, '8.1.6', '<')) {
            return parent::key();
        }

        return parent::key() - 1;
    }











    #[\ReturnTypeWillChange]
    public function fseek($offset, $whence = SEEK_SET): int
    {
        if (version_compare(PHP_VERSION, '8.0.19', '<')) {
            return parent::fseek($offset, $whence);
        }

        if (version_compare(PHP_VERSION, '8.1', '>') && version_compare(PHP_VERSION, '8.1.6', '<')) {
            return parent::fseek($offset, $whence);
        }

 
 
        for ($i = 0; $i < 3; $i++) {
            parent::fseek(0);
            $this->fgets();
        }

        $this->fseekUsed = true;
        return parent::fseek((int)$offset, $whence);
    }










    public function readAndMoveNext(bool $useFgets = false): string
    {
        if ($useFgets && version_compare(PHP_VERSION, '8.0.1', '<')) {
            return parent::fgets();
        }

        $originalFlags = $this->getFlags();
        $newFlags      = $originalFlags & ~self::READ_AHEAD;
        $this->setFlags($newFlags);

        $line = $this->current();
        $this->next();

        $this->setFlags($originalFlags);
        return $line;
    }

 
    public function isSqlFile(): bool
    {
        return $this->getExtension() === 'sql';
    }

    public function fgetsUseParent(): string
    {
        return parent::fgets();
    }

 
    public function keyUseParent(): int
    {
        return parent::key();
    }

 
    public function seekUseParent(int $offset)
    {
        parent::seek($offset);
    }










    #[\ReturnTypeWillChange]
    public function flock($operation, &$wouldBlock = null): bool
    {
        if ($this->isWindowsOs()) {
            return true;
        }

        $parentMethodFlock = 'parent::flock';
        if (version_compare(PHP_VERSION, '8.2', '>=')) {
            // phpcs:ignore SlevomatCodingStandard.PHP.ForbiddenClasses.ForbiddenClass
            $parentMethodFlock = \SplFileObject::class . '::flock';
        }

        if (!is_callable($parentMethodFlock)) {
            return false;
        }

        return parent::flock($operation, $wouldBlock);
    }




    protected function isWindowsOs(): bool
    {
        if (function_exists('wpstgIsWindowsOs')) {
            return wpstgIsWindowsOs();
        }

        return false;
    }
}
