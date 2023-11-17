<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; return types && type-hints
// TODO PHP7.1; constant visibility

namespace WPStaging\Framework\Utils\Cache;

use WPStaging\Framework\Filesystem\FileObject;

class Cache extends AbstractCache
{
    /** @var string */
    const PHP_HEADER = "<?php exit(); ?>\n";

    /** @var string */
    const FILE_EXTENSION = 'cache.php';

    /**
     * @inheritDoc
     */
    public function get($default = null)
    {
        if (!$this->isValid()) {
            return $default;
        }

        $content = file_get_contents($this->filePath);
        if (strpos($content, self::PHP_HEADER) !== 0) {
            return $default;
        }

        $content = substr($content, strlen(self::PHP_HEADER));
        return json_decode(trim($content), true);
    }

    /**
     * @inheritDoc
     */
    public function save($value, $pretty = false)
    {
        if ($pretty) {
            return (new FileObject($this->filePath, FileObject::MODE_WRITE))->fwriteSafe(self::PHP_HEADER . json_encode($value, JSON_PRETTY_PRINT));
        }

        return (new FileObject($this->filePath, FileObject::MODE_WRITE))->fwriteSafe(self::PHP_HEADER . json_encode($value));
    }

    /**
     * @return string
     */
    protected function getFileExtension(): string
    {
        return self::FILE_EXTENSION;
    }
}
