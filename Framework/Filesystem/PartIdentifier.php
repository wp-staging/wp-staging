<?php

namespace WPStaging\Framework\Filesystem;

class PartIdentifier
{
    /** @var string */
    const DATABASE_PART_IDENTIFIER = 'wpstgdb';

    /** @var string */
    const MU_PLUGIN_PART_IDENTIFIER = 'muplugins';

    /** @var string */
    const PLUGIN_PART_IDENTIFIER = 'plugins';

    /** @var string */
    const THEME_PART_IDENTIFIER = 'themes';

    /** @var string */
    const UPLOAD_PART_IDENTIFIER = 'uploads';

    /** @var string */
    const LANGUAGE_PART_IDENTIFIER = 'lang';

    /** @var string */
    const DROPIN_PART_IDENTIFIER = 'dropins';

    /**
     * @var string
     * @deprecated use WP_CONTENT_PART_IDENTIFIER instead
     */
    const OTHER_WP_CONTENT_PART_IDENTIFIER = 'otherfiles';

    /** @var string */
    const WP_CONTENT_PART_IDENTIFIER = 'wpcontent';

    /**
     * @var string
     * @deprecated use WP_ROOT_PART_IDENTIFIER instead
     */
    const OTHER_WP_ROOT_PART_IDENTIFIER = 'rootfiles';

    /** @var string */
    const WP_ROOT_PART_IDENTIFIER = 'wproot';

    /** @var string */
    const DATABASE_PART_SIZE_IDENTIFIER = 'sqlSize';

    /** @var string */
    const MU_PLUGIN_PART_SIZE_IDENTIFIER = 'mupluginsSize';

    /** @var string */
    const PLUGIN_PART_SIZE_IDENTIFIER = 'pluginsSize';

    /** @var string */
    const THEME_PART_SIZE_IDENTIFIER = 'themesSize';

    /** @var string */
    const UPLOAD_PART_SIZE_IDENTIFIER = 'uploadsSize';

    /** @var string */
    const LANGUAGE_PART_SIZE_IDENTIFIER = 'langSize';

    /** @var string */
    const DROPIN_PART_SIZE_IDENTIFIER = 'dropinsSize';

   /** @var string */
    const WP_CONTENT_PART_SIZE_IDENTIFIER = 'wpcontentSize';

    /** @var string */
    const WP_ROOT_PART_SIZE_IDENTIFIER = 'wpRootSize';

    /**
     * List of drop-in files that need to be restored with a rename if their checksums differ.
     *
     * These files are specific to third party plugins like W3 Total Cache plugin and similar plugins and must be renamed with a `wpstg_bak.` prefix
     * if their checksums do not match the expected values. This ensures that any discrepancies or issues with
     * these files are avoided by preserving the original files with a backup prefix.
     * @var array
     */
    const DROP_IN_FILES = [
        'object-cache.php',
        'advanced-cache.php',
        'db.php',
        'db-error.php',
        'install.php',
        'maintenance.php',
        'php-error.php',
        'fatal-error-handler.php'
    ];
}
