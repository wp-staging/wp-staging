<?php

namespace WPStaging\Framework\Filesystem;

/**
 * This is an abstract class of WP Staging implementation of SplFileObject class
 * Which doesn't depend upon core plugin, so it can be used with wpstg-restorer standalone tool
 * Don't import core PHP namespaces as it is not required for standalone tool as it will be bundled into single file
 * When building in standalone tool, this class will be renamed to FileObject and changed to final class
 * Also this class should not use any wp core functions or classes.
 */
abstract class AbstractFileObject extends \SplFileObject
{
    const MODE_READ            = 'rb'; // read only, binary
    const MODE_WRITE           = 'wb'; // write only, binary
    const MODE_APPEND          = 'ab'; // append with create, binary
    const MODE_APPEND_AND_READ = 'ab+'; // append with read and create if not exists, binary
    const MODE_WRITE_SAFE      = 'xb'; // write if exists E_WARNING & return false, binary
    const MODE_WRITE_UNSAFE    = 'cb'; // append, if exists cursor to top, binary

    /** @var int */
    protected $totalLines = null;

    /** @var bool */
    protected $fgetsUsedOnKey0 = false;

    /** @var bool */
    protected $fseekUsed = false;

    public function __construct(string $fullPath, string $openMode = self::MODE_READ)
    {
        try {
            parent::__construct($fullPath, $openMode);
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /** @return int */
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

    /**
     * Override SplFileObject::seek()
     * Alternative function for SplFileObject::seek() that behaves identical in all PHP Versions.
     *
     * There was a major change in PHP 8.0.1 where after using `SplFileObject::seek($line)`, the first subsequent
     * call to `SplFileObject::fgets()` does not increase the line pointer anymore as it did in earlier version since PHP 5.x
     * @see https://bugs.php.net/bug.php?id=81551
     *
     * Note: This will remove READ_AHEAD flag while execution to deliver reliable and identical results as READ_AHEAD tells
     * SplFileObject to read on next() and rewind() too which our custom seek() makes use of.
     * This would disturb this seek() implementation and would lead to fatal errors if 'cpu load' setting is 'medium' or 'high'
     *
     *
     * @param int $offset The zero-based line number to seek to.
     * @return void
     * @throws Exception
     */
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

    /**
     * SplFileObject::fgets() is not consistent after SplFileObject::fseek() between php 5.x/7.x and php 8.0.1.
     * We could either make fgets consistent after SplFileObject::seek() or SplFileObject::fseek()
     * This implementation makes it consistent after SplFileObject::seek across all PHP versions up to 8.0.1.
     * Use readAndMoveNext() instead if you want to achieve consistent behavior of SplFileObject::fgets after SplFileObject::fseek.
     *
     * @deprecated 4.2.13 Use readAndMoveNext instead as it is hard to make fgets against multiple php version after seek(0)
     *
     * @return string
     */
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

    /** @return int */
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

    /**
     * Seek to a position
     *
     * @param int $offset The value to start from added to the $whence
     * @param int $whence values are:
     * SEEK_SET - Set position equal to offset bytes.
     * SEEK_CUR - Set position to current location plus offset.
     * SEEK_END - Set position to end-of-file plus offset.
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function fseek($offset, $whence = SEEK_SET): int
    {
        if (version_compare(PHP_VERSION, '8.0.19', '<')) {
            return parent::fseek($offset, $whence);
        }

        if (version_compare(PHP_VERSION, '8.1', '>') && version_compare(PHP_VERSION, '8.1.6', '<')) {
            return parent::fseek($offset, $whence);
        }

        // After calling parent::fseek() and $this->fgets() two or three times it starts to act different on PHP >= 8.0.19, PHP >= 8.1.6 and PHP >= 8.2.
        // Calling it three times helps to write a consistent fseek() for the above mentioned PHP versions.
        for ($i = 0; $i < 3; $i++) {
            parent::fseek(0);
            $this->fgets();
        }

        $this->fseekUsed = true;
        return parent::fseek((int)$offset, $whence);
    }

    /**
     * SplFileObject::fgets() is not consistent after SplFileObject::fseek() between php 5.x/7.x and php 8.0.1.
     * Use this method instead if you want to achieve consistent behavior of SplFileObject::fgets after SplFileObject::fseek across all PHP versions up to PHP 8.0.1.
     * READ_AHEAD flag will not have any affect on this method. It's disabled.
     *
     * @var bool $useFgets default false. Setting this to true will use fgets on PHP < 8.0.1
     *
     * @return string
     */
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

    /** @return bool */
    public function isSqlFile(): bool
    {
        return $this->getExtension() === 'sql';
    }

    public function fgetsUseParent(): string
    {
        return parent::fgets();
    }

    /** @return int */
    public function keyUseParent(): int
    {
        return parent::key();
    }

    /** @return void */
    public function seekUseParent(int $offset)
    {
        parent::seek($offset);
    }

    /**
     * Manage file locking.
     * On WinOS a file is always locked when using SplFileObject. So, do nothing here and just return true on WinOS.
     * @param int $operation
     * @param int|null $wouldBlock
     * @return bool
     *
     * Note: Adding type for $operation throw error in IDE
     */
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

    /**
     * @return bool
     */
    protected function isWindowsOs(): bool
    {
        if (function_exists('wpstgIsWindowsOs')) {
            return wpstgIsWindowsOs();
        }

        return false;
    }
}
