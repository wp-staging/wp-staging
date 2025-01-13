<?php

namespace WPStaging\Framework\Filesystem;

use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Adapter\DirectoryInterface;

/**
 * This class is used to shorten the full file path
 * to reduce the overall file size of the backup file.
 *
 * A file like wp-content/uploads/wp-staging-pro/wp-staging-pro.zip turn into
 * wpstg_p_/wp-staging-pro/wp-staging.zip
 *
 * @todo rename this class to PathShortener
 */

class PathIdentifier
{
    /** @var string */
    const IDENTIFIER_ABSPATH = 'wpstg_a_';

    /** @var string */
    const IDENTIFIER_WP_CONTENT = 'wpstg_c_';

    /** @var string */
    const IDENTIFIER_PLUGINS = 'wpstg_p_';

    /** @var string */
    const IDENTIFIER_THEMES = 'wpstg_t_';

    /** @var string */
    const IDENTIFIER_MUPLUGINS = 'wpstg_m_';

    /** @var string */
    const IDENTIFIER_UPLOADS = 'wpstg_u_';

    /** @var string */
    const IDENTIFIER_LANG = 'wpstg_l_';

    /**
     * @var string The identifier of the last match.
     *             We will try to match the path/identifier of the next item starting from this one. It's a form of cache,
     *             making it more efficient to transform long lists of similar paths.
     */
    protected $lastIdentifier;

    /** @var DirectoryInterface */
    protected $directory;

    public function __construct(DirectoryInterface $directory)
    {
        $this->directory = $directory;
    }

    /** @var string */
    public function getBackupDirectory()
    {
        return $this->directory->getBackupDirectory();
    }

    /**
     * Convert an absolute file path of a file into an abbreviated path.
     *
     * E.g.:
     *
     * /var/www/single/wp-content/plugins/index.php => wpstg_p_index.php
     * /var/www/single/wp-content/mu-plugins/index.php => wpstg_m_index.php
     * /var/www/single/wp-content/uploads/2019/image.png => wpstg_c_uploads/2019/image.png
     * /var/www/single/wp-content/themes/twentytwentyone/index.php => wpstg_t_twentytwentyone/index.php
     *
     * @param string $path /var/www/single/wp-content/plugins/index.php
     *
     * @return string wpstg_p_index.php
     */
    public function transformPathToIdentifiable($path)
    {
        // Start looking from the same placeholder as the last item, unless it was wp-content, which would cause false-positives.
        if (isset($this->lastIdentifier) && $this->lastIdentifier !== self::IDENTIFIER_WP_CONTENT) {
            $basePath = $this->getIdentifierPath($this->lastIdentifier);

            // Early bail: This item has the same type as the previous one.
            if (strpos($path, $basePath) === 0) {
                return $this->lastIdentifier . substr($path, strlen($basePath));
            }
        }

        // Uploads are usually the largest folders, so let's start with them.
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

        // This should never happen on Backups, as we only scan the folders above explicitly and don't follow links.
        throw new \RuntimeException("Unknown entity type for path: $path");
    }

    /**
     * @param string $path wpstg_p_index.php
     *
     * @return string /var/www/single/wp-content/plugins/index.php
     */
    public function transformIdentifiableToPath($path)
    {
        $identifier            = $this->getIdentifierFromPath($path);
        $pathWithoutIdentifier = $this->getPathWithoutIdentifier($path);

        return $this->getIdentifierPath($identifier) . $pathWithoutIdentifier;
    }

    /**
     * @param string $path wpstg_p_index.php
     *
     * @return string index.php
     */
    public function getPathWithoutIdentifier($path)
    {
        return substr($path, 8);
    }

    /**
     * @param string $path wpstg_p_index.php
     *
     * @return string wpstg_p_
     */
    public function getIdentifierFromPath($path)
    {
        return substr($path, 0, 8);
    }

    /**
     * @return string
     */
    public function transformIdentifiableToRelativePath(string $string): string
    {
        $key  = substr($string, 0, 8);
        $path = $this->getRelativePath($key);
        if (!empty($path) && is_string($path)) {
            return substr_replace($string, $path, 0, 8);
        }

        return $string;
    }

    /**
     * @return string
     */
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

        // Add __METHOD__ for debugging in wpstg-restore
        trigger_error(sprintf('[%s] Could not find a path for the placeholder: %s', __METHOD__, filter_var($identifier, FILTER_SANITIZE_SPECIAL_CHARS)));
        return $identifier;
    }

    public function getAbsolutePath(string $identifier): string
    {
        return $this->getIdentifierPath($identifier);
    }

    /**
     * @return string
     */
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

    /**
     * @param string $identifier wpstg_p_
     *
     * @return string /var/www/single/wp-content/plugins/
     */
    protected function getIdentifierPath($identifier)
    {
        // It is crucial that generic paths are placed last in this list. Eg: wp-content directory must be last.
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

    /**
     * @param string $identifiablePath wpstg_p_db.php
     *
     * @return bool
     */
    public function hasDropinsFile(string $identifiablePath): bool
    {
        if (!(strpos($identifiablePath, self::IDENTIFIER_WP_CONTENT) === 0)) {
            return false;
        }

        $dropinsFile = implode('|', PartIdentifier::DROP_IN_FILES);

        return preg_match('@^' . self::IDENTIFIER_WP_CONTENT . '(' . $dropinsFile . ')@', $identifiablePath) ? true : false;
    }
}
