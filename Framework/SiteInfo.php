<?php

namespace WPStaging\Framework;

use WPStaging\Framework\Staging\CloneOptions;
use WPStaging\Framework\Utils\ThirdParty\Punycode;

/**
 * Class SiteInfo
 *
 * Provides information about the current site.
 *
 * @package WPStaging\Site
 */
class SiteInfo
{
    /**
     * The key used in DB to store is cloneable feature in clone options
     * @var string
     */
    const IS_CLONEABLE_KEY = 'isCloneable';

    /**
     * The file which make staging site cloneable
     * This way is depreciated
     * @var string
     */
    const CLONEABLE_FILE = '.wp-staging-cloneable';

    /**
     * The key used in DB to store whether site is staging or not
     * @var string
     */
    const IS_STAGING_KEY = 'wpstg_is_staging_site';

    /**
     * The file which makes a site a staging site
     * @var string
     */
    const STAGING_FILE = '.wp-staging';

    /**
     * @var CloneOptions
     */
    private $cloneOptions;

    /**
     * @var Punycode
     */
    private $punycode;

    public function __construct()
    {
        // TODO: inject using DI
        $this->cloneOptions = new CloneOptions();
        $this->punycode = new Punycode();
    }

    /**
     * @return bool True if is staging site. False otherwise.
     */
    public function isStagingSite()
    {
        if (get_option(self::IS_STAGING_KEY) === "true") {
            return true;
        }

        return file_exists(ABSPATH . self::STAGING_FILE);
    }

    /**
     * @return bool True if is staging site. False otherwise.
     *
     * @todo update with WPStaging/Framework/Staging/CloneOption once PR #717 is merged
     */
    public function isCloneable()
    {
        // Site should be cloneable if not staging i.e. production site
        if (!$this->isStagingSite()) {
            return true;
        }

        // Old condition to check if staging site is cloneable
        if (file_exists(ABSPATH . self::CLONEABLE_FILE)) {
            return true;
        }

        // New condition for checking whether staging is cloneable or not
        return $this->cloneOptions->get(self::IS_CLONEABLE_KEY);
    }

    /**
     * Check if WP is installed in sub directory
     * If siteurl and home are not identical we assume the site is located in a subdirectory
     * related to that instruction https://wordpress.org/support/article/giving-wordpress-its-own-directory/
     *
     * @return boolean
     */
    public function isInstalledInSubDir()
    {
        // Compare names without scheme (http/https) to bypass case where siteurl and home are stored with different schemes in database
        // This happens much more often than you expect
        $siteurl = preg_replace('#^https?://#', '', rtrim(get_option('siteurl'), '/'));
        $home = preg_replace('#^https?://#', '', rtrim(get_option('home'), '/'));

        // convert unicode(idn) to punycode(ascii) domain
        // turn exämple.com to xn--exmple-cua.com
        $home = $this->punycode->encode($home);
        $siteurl = $this->punycode->encode($siteurl);

        if ($home === $siteurl) {
            return false;
        }

        // Extended check when siteurl -> www.example.com and home url -> example.com or vice versa
        return !(($home === 'www.' . $siteurl) || ($siteurl === 'www.' . $home));
    }

    /**
     * Enable the cloning for current staging site.
     *
     * @return bool
     */
    public function enableStagingSiteCloning()
    {
        // Early Bail: if site is not staging
        if (!$this->isStagingSite()) {
            return false;
        }

        // Early Bail: if cloning already enabled
        if ($this->isCloneable()) {
            return true;
        }

        return $this->cloneOptions->set(self::IS_CLONEABLE_KEY, true);
    }

    /**
     * Enable the cloning for current staging site.
     *
     * @return bool
     */
    public function disableStagingSiteCloning()
    {
        // Early Bail: if site is not staging
        if (!$this->isStagingSite()) {
            return false;
        }

        // Early Bail: if cloning already disabled
        if (!$this->isCloneable()) {
            return true;
        }

        // First try disabling if cloneable feature exist due to old way.
        $cloneableFile = trailingslashit(ABSPATH) . self::CLONEABLE_FILE;
        if (file_exists($cloneableFile) && !unlink($cloneableFile)) {
            // Error if files exists but unable to unlink
            return false;
        }

        // Staging site may have been made cloneable through both ways
        // So now try disabling through new way
        return (!file_exists($cloneableFile) && $this->cloneOptions->delete(self::IS_CLONEABLE_KEY));
    }

    /**
     * @return bool True if "short_open_tags" is enabled, false if disabled.
     */
    public function isPhpShortTagsEnabled()
    {
        return in_array(strtolower(ini_get('short_open_tags')), ['1', 'on', 'true']);
    }

    /**
     * Is WP Bakery plugin active?
     *
     * @return bool
     */
    public function isWpBakeryActive()
    {
        return defined('WPB_VC_VERSION');
    }
}
