<?php

namespace WPStaging\Framework\Filesystem;

use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Adapter\DirectoryInterface;











class PathIdentifier
{
 
    const IDENTIFIER_ABSPATH = 'wpstg_a_';

 
    const IDENTIFIER_WP_CONTENT = 'wpstg_c_';

 
    const IDENTIFIER_PLUGINS = 'wpstg_p_';

 
    const IDENTIFIER_THEMES = 'wpstg_t_';

 
    const IDENTIFIER_MUPLUGINS = 'wpstg_m_';

 
    const IDENTIFIER_UPLOADS = 'wpstg_u_';

 
    const IDENTIFIER_LANG = 'wpstg_l_';






    protected $lastIdentifier;

 
    protected $directory;

    public function __construct(DirectoryInterface $directory)
    {
        $this->directory = $directory;
    }

 
    public function getBackupDirectory()
    {
        return $this->directory->getBackupDirectory();
    }















    public function transformPathToIdentifiable($path)
    {
 
        if (isset($this->lastIdentifier) && $this->lastIdentifier !== self::IDENTIFIER_WP_CONTENT) {
            $basePath = $this->getIdentifierPath($this->lastIdentifier);

 
            if (strpos($path, $basePath) === 0) {
                return $this->lastIdentifier . substr($path, strlen($basePath));
            }
        }

 
        if (strpos($path, $this->directory->getUploadsDirectory()) === 0) {
            $this->lastIdentifier = self::IDENTIFIER_UPLOADS;

            return $this->lastIdentifier . substr($path, strlen($this->directory->getUploadsDirectory()));
        }

        if ($this->directory->getPluginUploadsDirectory() !== $this->directory->getUploadsDirectory()) {
            if (strpos($path, $this->directory->getPluginUploadsDirectory()) === 0) {
                $this->lastIdentifier = self::IDENTIFIER_UPLOADS;

                return $this->lastIdentifier . substr($path, strlen($this->directory->getPluginUploadsDirectory()));
            }
        }

        if (strpos($path, $this->directory->getPluginsDirectory()) === 0) {
            $this->lastIdentifier = self::IDENTIFIER_PLUGINS;

            return $this->lastIdentifier . substr($path, strlen($this->directory->getPluginsDirectory()));
        }

        foreach ($this->directory->getAllThemesDirectories() as $themesDirectory) {
            if (strpos($path, $themesDirectory) === 0) {
                $this->lastIdentifier = self::IDENTIFIER_THEMES;

                return $this->lastIdentifier . substr($path, strlen($themesDirectory));
            }
        }

        if (strpos($path, $this->directory->getMuPluginsDirectory()) === 0) {
            $this->lastIdentifier = self::IDENTIFIER_MUPLUGINS;

            return $this->lastIdentifier . substr($path, strlen($this->directory->getMuPluginsDirectory()));
        }

        if (strpos($path, $this->directory->getLangsDirectory()) === 0) {
            $this->lastIdentifier = self::IDENTIFIER_LANG;

            return $this->lastIdentifier . substr($path, strlen($this->directory->getLangsDirectory()));
        }

        if (strpos($path, $this->directory->getWpContentDirectory()) === 0) {
            $this->lastIdentifier = self::IDENTIFIER_WP_CONTENT;

            return $this->lastIdentifier . substr($path, strlen($this->directory->getWpContentDirectory()));
        }

        if (strpos($path, $this->directory->getAbspath()) === 0) {
            $this->lastIdentifier = self::IDENTIFIER_ABSPATH;

            return $this->lastIdentifier . substr($path, strlen($this->directory->getAbspath()));
        }

 
        throw new \RuntimeException(sprintf(
            'Could not classify %s for backup: it is not inside any known WordPress content directory (plugins, themes, mu-plugins, uploads, languages, wp-content, or the WordPress root).',
            $path === '' ? 'an empty path' : "the path \"$path\""
        ));
    }






    public function transformIdentifiableToPath($path)
    {
        $identifier            = $this->getIdentifierFromPath($path);
        $pathWithoutIdentifier = $this->getPathWithoutIdentifier($path);

        return $this->getIdentifierPath($identifier) . $pathWithoutIdentifier;
    }






    public function getPathWithoutIdentifier($path)
    {
        return substr($path, 8);
    }






    public function hasPathTraversal(string $identifiablePath): bool
    {
        $relativePath = $this->getPathWithoutIdentifier($identifiablePath);
        if ($relativePath === '') {
            return true;
        }

        if (strpos($relativePath, "\0") !== false) {
            return true;
        }

        $normalizedPath = str_replace('\\', '/', $relativePath);
        if ($normalizedPath[0] === '/' || preg_match('#^[a-zA-Z]:#', $normalizedPath) === 1) {
            return true;
        }

        return in_array('..', explode('/', $normalizedPath), true);
    }

    public function isPathWithinRoot(string $targetPath, string $root): bool
    {
        $normalizedTarget = str_replace('\\', '/', $targetPath);
        if (strpos($normalizedTarget, "\0") !== false || in_array('..', explode('/', $normalizedTarget), true)) {
            return false;
        }

        $realRoot = realpath($root);
        if ($realRoot === false) {
            return false;
        }

        if (is_link($targetPath)) {
            return false;
        }

        $deepestExisting = $targetPath;
        while (!file_exists($deepestExisting)) {
            if (is_link($deepestExisting)) {
                return false;
            }

            $parent = dirname($deepestExisting);
            if ($parent === $deepestExisting) {
                return false;
            }

            $deepestExisting = $parent;
        }

        $realExisting = realpath($deepestExisting);
        if ($realExisting === false) {
            return false;
        }

        $realExisting = rtrim($realExisting, '/\\') . DIRECTORY_SEPARATOR;
        $realRoot     = rtrim($realRoot, '/\\') . DIRECTORY_SEPARATOR;

        return strpos($realExisting, $realRoot) === 0;
    }






    public function getIdentifierFromPath($path)
    {
        return substr($path, 0, 8);
    }




    public function transformIdentifiableToRelativePath(string $string): string
    {
        $string = trim($string);
        if (empty($string)) {
            return $string;
        }

        $key  = substr($string, 0, 8);
        $path = $this->getRelativePath($key);
        if ($path !== $key && is_string($path)) {
            return substr_replace($string, $path, 0, 8);
        }

        return $string;
    }




    public function getRelativePath(string $identifier): string
    {
        static $cache = [];

        if (!empty($cache) && !empty($identifier) && isset($cache[$identifier])) {
            return $cache[$identifier];
        }

        $path = [
            self::IDENTIFIER_ABSPATH    => '',
            self::IDENTIFIER_WP_CONTENT => 'wp-content/',
            self::IDENTIFIER_PLUGINS    => 'wp-content/plugins/',
            self::IDENTIFIER_THEMES     => 'wp-content/themes/',
            self::IDENTIFIER_MUPLUGINS  => 'wp-content/mu-plugins/',
            self::IDENTIFIER_UPLOADS    => 'wp-content/uploads/',
            self::IDENTIFIER_LANG       => 'wp-content/languages/',
        ];

        if (!empty($identifier) && isset($path[$identifier])) {
            $cache[$identifier] = $path[$identifier];
            return $cache[$identifier];
        }

 
        trigger_error(sprintf('[%s] Could not find a path for the placeholder: %s', __METHOD__, filter_var($identifier, FILTER_SANITIZE_SPECIAL_CHARS)));
        return $identifier;
    }

    public function getAbsolutePath(string $identifier): string
    {
        return $this->getIdentifierPath($identifier);
    }




    public function getIdentifierByPartName(string $key): string
    {
        static $cache = [];

        if (!empty($cache) && !empty($key) && !empty($cache[$key])) {
            return $cache[$key];
        }

        $list = [
            PartIdentifier::WP_CONTENT_PART_IDENTIFIER => PathIdentifier::IDENTIFIER_WP_CONTENT,
            PartIdentifier::PLUGIN_PART_IDENTIFIER     => PathIdentifier::IDENTIFIER_PLUGINS,
            PartIdentifier::THEME_PART_IDENTIFIER      => PathIdentifier::IDENTIFIER_THEMES,
            PartIdentifier::MU_PLUGIN_PART_IDENTIFIER  => PathIdentifier::IDENTIFIER_MUPLUGINS,
            PartIdentifier::UPLOAD_PART_IDENTIFIER     => PathIdentifier::IDENTIFIER_UPLOADS,
            PartIdentifier::LANGUAGE_PART_IDENTIFIER   => PathIdentifier::IDENTIFIER_LANG,
            PartIdentifier::DATABASE_PART_IDENTIFIER   => PathIdentifier::IDENTIFIER_UPLOADS,
            PartIdentifier::WP_ROOT_PART_IDENTIFIER    => PathIdentifier::IDENTIFIER_ABSPATH,
        ];

        if (!empty($key) && !empty($list[$key])) {
            $cache[$key] = $list[$key];
            return $cache[$key];
        }

        return '';
    }






    protected function getIdentifierPath($identifier)
    {
 
        switch ($identifier) {
            case self::IDENTIFIER_ABSPATH:
                return $this->directory->getAbspath();
            case self::IDENTIFIER_UPLOADS:
                return $this->directory->getUploadsDirectory();
            case self::IDENTIFIER_PLUGINS:
                return $this->directory->getPluginsDirectory();
            case self::IDENTIFIER_THEMES:
                return $this->directory->getActiveThemeParentDirectory();
            case self::IDENTIFIER_MUPLUGINS:
                return $this->directory->getMuPluginsDirectory();
            case self::IDENTIFIER_LANG:
                return $this->directory->getLangsDirectory();
            case self::IDENTIFIER_WP_CONTENT:
                return $this->directory->getWpContentDirectory();
            default:
                throw new \UnexpectedValueException(sprintf("[%s] Could not find a path for the placeholder: %s", __METHOD__, filter_var($identifier, FILTER_SANITIZE_SPECIAL_CHARS)));
        }
    }






    public function hasDropinsFile(string $identifiablePath): bool
    {
        if (!(strpos($identifiablePath, self::IDENTIFIER_WP_CONTENT) === 0)) {
            return false;
        }

        $dropinsFile = implode('|', PartIdentifier::DROP_IN_FILES);

        return preg_match('@^' . self::IDENTIFIER_WP_CONTENT . '(' . $dropinsFile . ')@', $identifiablePath) ? true : false;
    }
}
