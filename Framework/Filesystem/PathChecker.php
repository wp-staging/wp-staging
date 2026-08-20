<?php

namespace WPStaging\Framework\Filesystem;

use UnexpectedValueException;
use WPStaging\Framework\Adapter\Directory;






class PathChecker
{
 
    private $filesystem;

 
    private $directory;

 
    private $pathIdentifier;








    public function __construct(Filesystem $filesystem, Directory $directory, PathIdentifier $pathIdentifier)
    {
        $this->filesystem     = $filesystem;
        $this->directory      = $directory;
        $this->pathIdentifier = $pathIdentifier;
    }













    public function isPathInPathsList(string $path, array $list, bool $isRelative = false, $basePath = null): bool
    {
        if (empty($basePath)) {
            $basePath = $this->directory->getAbsPath();
        }

        $basePath = $this->filesystem->normalizePath($basePath);
        $path     = $this->filesystem->normalizePath($path);
 
        if ($isRelative) {
            $path = '/' . ltrim(str_replace($basePath, '', $path), '/');
        }

        foreach ($list as $pathItem) {
            $pathItem = $this->filesystem->normalizePath($pathItem);
            try {
                $pathItem = $this->pathIdentifier->transformIdentifiableToPath($pathItem);
            } catch (UnexpectedValueException $ex) {
            }

 
            if ($isRelative) {
                $pathItem = '/' . ltrim(str_replace($basePath, '', $pathItem), '/');
            }

            if ($path === $pathItem) {
                return true;
            }

 
            if (strpos($path, $pathItem . '/') === 0) {
                return true;
            }
        }

        return false;
    }
}
