<?php
namespace WPStaging\Utils;

/**
 * Class Autoloader
 * @package WPStaging\Utils
 */
class Autoloader
{
    /**
     * An associative array; "namespace" => "directory"
     * @var array
     */
    private $namespaces;

    /**
     * Register multiple namespaces
     * @param array $namespaces
     */
    public function registerNamespaces($namespaces)
    {
        foreach($namespaces as $namespace => $baseDirectory)
        {
            // A string
            if (is_string($baseDirectory))
            {
                $this->registerNamespace($namespace, $baseDirectory);
                continue;
            }

            // Multiple directories
            foreach ($baseDirectory as $directory)
            {
                $this->registerNamespace($namespace, $directory);
            }
        }
    }

    /**
     * Register a namespace
     * @param string $namespace
     * @param string $baseDirectory
     * @param bool $prepend
     */
    public function registerNamespace($namespace, $baseDirectory, $prepend = false)
    {
        // Normalization
        // Normalize namespace
        $namespace      = trim($namespace, "\\") . "\\";
        // Normalize base directory
        $baseDirectory  = rtrim($baseDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        // Namespace is not set
        if (false === isset($this->namespaces[$namespace]))
        {
            $this->namespaces[$namespace] = array();
        }

        // Prepend or add
        if ($prepend)
        {
            array_unshift($this->namespaces[$namespace], $baseDirectory);
        }
        else
        {
            array_push($this->namespaces[$namespace], $baseDirectory);
        }
    }

    /**
     * Loads the class file for given class name
     * @param string $class
     * @return bool
     */
    public function load($class)
    {
        $namespace = $class;

        // As long as we have a namespace
        while (false !== ($pos = strrpos($namespace, "\\")))
        {
            // Basic variables
            $namespace  = substr($class, 0, $pos +1);
            $className  = substr($class, $pos + 1);

            // Find file for given namespace & class name
            if ($this->findFile($namespace, $className))
            {
                return true;
            }

            // Trim to search another namespace
            $namespace = rtrim($namespace, "\\");
        }

        // Class not found
        return false;
    }

    /**
     * Attempts to find file for given namespace and class
     * @param string $namespace
     * @param string $class
     * @return bool
     */
    protected function findFile($namespace, $class)
    {
        // No registered base directory for given namespace
        if (false === isset($this->namespaces[$namespace]))
        {
            return false;
        }

        foreach ($this->namespaces[$namespace] as $baseDirectory)
        {
            // Look through base directory for given namespace
            $file = $baseDirectory . str_replace("\\", DIRECTORY_SEPARATOR, $class) . ".php";

            // File found
            if ($this->requireFile($file))
            {
                return true;
            }
        }

        // No file found
        return false;
    }

    /**
     * Requires file from FS if it exists
     * @param string $file
     * @return bool
     */
    protected function requireFile($file)
    {
        // File not found
        if (!file_exists($file))
        {
            return false;
        }

        require_once $file;
        return true;
    }

    /**
     * Registers autoloader with SPL autoloader stack
     */
    public function register()
    {
        spl_autoload_register(array($this, "load"));
    }
}