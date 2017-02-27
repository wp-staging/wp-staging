<?php
namespace WPStaging\Backend\Modules\Jobs;

// No Direct Access
use WPStaging\Utils\Info;

if (!defined("WPINC"))
{
    die;
}

/**
 * Class JobWithCommandLine
 * I'm sorry for such mess, we need to support PHP 5.3
 * @package WPStaging\Backend\Modules\Job
 */
abstract class JobWithCommandLine extends Job
{

    /**
     * @var bool
     */
    protected $canUseExec;

    /**
     * @var bool
     */
    protected $canUsePopen;

    /**
     * Operating System
     * @var string
     */
    protected $OS;

    /**
     * JobWithCommandLine constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $info               = new Info();

        $this->OS           = $info->getOS();
        $this->canUseExec   = $info->canUse("exec");
        $this->canUsePopen  = $info->canUse("popen");

        // Windows Fix for Popen
        if ("WIN" === $this->OS && true === $this->canUsePopen)
        {
            $this->canUsePopen = class_exists("\\COM");
        }

        unset($info);
    }
}