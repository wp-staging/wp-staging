<?php

namespace WPStaging\Framework\Filesystem;

class PartIdentifier
{
 
    const DATABASE_PART_IDENTIFIER = 'wpstgdb';

 
    const MU_PLUGIN_PART_IDENTIFIER = 'muplugins';

 
    const PLUGIN_PART_IDENTIFIER = 'plugins';

 
    const THEME_PART_IDENTIFIER = 'themes';

 
    const UPLOAD_PART_IDENTIFIER = 'uploads';

 
    const LANGUAGE_PART_IDENTIFIER = 'lang';

 
    const DROPIN_PART_IDENTIFIER = 'dropins';





    const OTHER_WP_CONTENT_PART_IDENTIFIER = 'otherfiles';

 
    const WP_CONTENT_PART_IDENTIFIER = 'wpcontent';





    const OTHER_WP_ROOT_PART_IDENTIFIER = 'rootfiles';

 
    const WP_ROOT_PART_IDENTIFIER = 'wproot';

 
    const WP_ROOT_FILES_PART_IDENTIFIER = 'wproot_files';

 
    const WP_ADMIN_PART_IDENTIFIER = 'wpadmin';

 
    const WP_INCLUDES_PART_IDENTIFIER = 'wpincludes';

 
    const DATABASE_PART_SIZE_IDENTIFIER = 'sqlSize';

 
    const MU_PLUGIN_PART_SIZE_IDENTIFIER = 'mupluginsSize';

 
    const PLUGIN_PART_SIZE_IDENTIFIER = 'pluginsSize';

 
    const THEME_PART_SIZE_IDENTIFIER = 'themesSize';

 
    const UPLOAD_PART_SIZE_IDENTIFIER = 'uploadsSize';

 
    const LANGUAGE_PART_SIZE_IDENTIFIER = 'langSize';

 
    const DROPIN_PART_SIZE_IDENTIFIER = 'dropinsSize';

 
    const WP_CONTENT_PART_SIZE_IDENTIFIER = 'wpcontentSize';

 
    const WP_ROOT_PART_SIZE_IDENTIFIER = 'wpRootSize';









    const DROP_IN_FILES = [
        'object-cache.php',
        'advanced-cache.php',
        'db.php',
        'db-error.php',
        'install.php',
        'maintenance.php',
        'php-error.php',
        'fatal-error-handler.php',
    ];
}
