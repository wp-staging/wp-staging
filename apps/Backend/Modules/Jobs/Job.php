<?php
namespace WPStaging\Backend\Modules\Jobs;

// No Direct Access
if (!defined("WPINC"))
{
    die;
}

use WPStaging\Backend\Modules\Jobs\Interfaces\JobInterface;
use WPStaging\WPStaging;
use WPStaging\Utils\Cache;

/**
 * Class Job
 * @package WPStaging\Backend\Modules\Jobs
 */
abstract class Job implements JobInterface
{

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var object
     */
    protected $options;

    /**
     * Job constructor.
     */
    public function __construct()
    {
        // Vars directory
        $this->cache    = new Cache(-1);

        $this->options  = $this->cache->get("clone_options");

        if (!$this->options)
        {
            $this->options = new \stdClass();
        }

        if (method_exists($this, "initialize"))
        {
            $this->initialize();
        }
    }

    /**
     * Save options
     * @param null|array|object $options
     * @return bool
     */
    protected function saveOptions($options = null)
    {
        // Get default options
        if (null === $options)
        {
            $options = $this->options;
        }

        // Ensure that it is an object
        $options = json_decode(json_encode($options));

        return $this->cache->save("clone_options", $options);
    }

    /**
     * @return object
     */
    public function getOptions()
    {
        return $this->options;
    }
}