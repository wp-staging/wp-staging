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
}
