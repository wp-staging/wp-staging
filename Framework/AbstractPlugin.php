<?php

namespace WPStaging\Framework;

use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Adapter\Hooks;
use WPStaging\Framework\Container\Container;
use WPStaging\WPStaging;

/**
 * Work In Progress - Used as refactored replacement for existing wp-staging-pro.php and as new plugin entry point
 * Class AbstractPlugin
 * @package WPStaging\Framework
 */

abstract class AbstractPlugin implements PluginInterface
{
    const APP_DEV = 'dev';
    const APP_PROD = 'prod';

    /** @var Container */
    protected $container;

    /** @var array */
    protected $components = [];

    public function __construct(Container $container = null)
    {
        if ($container) {
            $this->setContainer($container);
        }
    }

    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    public function init()
    {
        if (!$this->container) {
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new InvalidPluginException(static::class);
        }

        $this->initDependencies();
        $this->registerLifeCycle();
        $this->loadLanguages();

        /** @var Hooks $hooks */
        $hooks = $this->container->get(Hooks::class);

        foreach ($this->components as $id => $options) {
            $this->container->setInitialized($id, $options);
        }

        $hooks->init();
    }

    /**
     * @noinspection PhpUnused
     * @param string $id
     * @param array $options
     */
    public function addComponent($id, array $options = [])
    {
        if (array_key_exists($id, $this->components)) {
            return;
        }

        $this->components[$id] = $options;
    }

    /**
     * @noinspection PhpUnused
     * @param string $id
     */
    public function removeComponent($id)
    {
        $key = array_search($id, $this->components, true);
        if (false === $key) {
            return;
        }

        unset($this->components[$key]);
        $this->container->remove($id);
    }

    /**
     * @return string|null
     */
    public function getSlug()
    {
        return $this->container->getParameter('slug');
    }

    public function getDomain()
    {
        return $this->container->getParameter('domain');
    }

    /**
     * WP Staging Version Number
     * @return string|null
     */
    public function getVersion()
    {
        return WPStaging::getVersion();
    }

    /**
     * @noinspection PhpUnused
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * get_user_locale exists since WP 4.8 only
     * @return string
     */
    private function get_user_locale(){
        if (function_exists('get_user_locale')){
            return get_user_locale();
        }

        return get_locale();
    }

    private function loadLanguages()
    {
        /** @noinspection NullPointerExceptionInspection */
        $languagesDirectory = $this->container->get(Directory::class)->getPluginDirectory() . 'languages/';

        // Set filter for plugins languages directory
        $languagesDirectory = apply_filters($this->getSlug() . '_languages_directory', $languagesDirectory);

        // Traditional WP plugin locale filter
        $locale = apply_filters('plugin_locale', $this->get_user_locale(), 'wp-staging');
        $moFile = sprintf('%1$s-%2$s.mo', 'wp-staging', $locale);

        // Setup paths to current locale file
        $moFileLocal = $languagesDirectory . $moFile;
        $moFileGlobal = sprintf('%s/wp-staging/%s', WP_LANG_DIR, $moFile);

        if (file_exists($moFileGlobal)) {
            load_textdomain('wp-staging', $moFileGlobal);
        }
        elseif (file_exists($moFileLocal)) {
            load_textdomain('wp-staging', $moFileLocal);
        }
        else {
            load_plugin_textdomain('wp-staging', false, $languagesDirectory);
        }
    }

    private function initDependencies()
    {
        $this->container->set(Hooks::class, new Hooks);
        $this->container->set(Directory::class, new Directory($this->getDomain(), $this->getSlug()));
        $this->container->set(PluginInfo::class, new PluginInfo(
            $this->getVersion(), $this->getSlug(), $this->getDomain()
        ));
    }

    private function registerLifeCycle()
    {
        /** @noinspection NullPointerExceptionInspection */
        $file = $this->container->get(Directory::class)->getPluginDirectory() . $this->getSlug() . '.php';

        if (method_exists($this, 'onActivation')) {
            register_activation_hook($file, [$this, 'onActivation']);
        }

        if (method_exists($this, 'onDeactivate')) {
            register_deactivation_hook($file, [$this, 'onDeactivate']);
        }

        if (method_exists($this, 'onUninstall')) {
            // $this does not work hence the usage of get_called_class()
            register_uninstall_hook($file, [static::class, 'onUninstall']);
        }
    }
}
