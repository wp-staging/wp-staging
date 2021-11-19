<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; return types && type-hints
// TODO PHP7.1; constant visibility

namespace WPStaging\Framework\Utils\Cache;

use WPStaging\Framework\Filesystem\FileObject;

class Cache extends AbstractCache
{
    /**
     * @inheritDoc
     */
    public function get($default = null)
    {
        if (!$this->isValid()) {
            return $default;
        }

        return json_decode(file_get_contents($this->filePath), true);
    }

    /**
     * @inheritDoc
     */
    public function save($value, $pretty = false)
    {
        if ($pretty) {
            return (new FileObject($this->filePath, FileObject::MODE_WRITE))->fwriteSafe(json_encode($value, JSON_PRETTY_PRINT));
        } else {
            return (new FileObject($this->filePath, FileObject::MODE_WRITE))->fwriteSafe(json_encode($value));
        }
    }
}
