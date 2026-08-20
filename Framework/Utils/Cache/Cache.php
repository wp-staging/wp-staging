<?php

 
 
 

namespace WPStaging\Framework\Utils\Cache;

use WPStaging\Framework\Filesystem\FileObject;

class Cache extends AbstractCache
{
 
    const PHP_HEADER = "<?php exit(); ?>\n";

 
    const FILE_EXTENSION = 'cache.php';




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




    public function save($value, $pretty = false)
    {
        $file    = new FileObject($this->filePath, FileObject::MODE_WRITE);
        $written = false;
        if ($pretty) {
            $written = $file->fwriteSafe(self::PHP_HEADER . json_encode($value, JSON_PRETTY_PRINT));
        } else {
            $written = $file->fwriteSafe(self::PHP_HEADER . json_encode($value));
        }

        $file = null;

        return $written;
    }





    public function initWithPhpHeader()
    {
        if (is_file($this->filePath)) {
            return;
        }

        file_put_contents($this->filePath, self::PHP_HEADER);
    }




    protected function getFileExtension(): string
    {
        return self::FILE_EXTENSION;
    }
}
