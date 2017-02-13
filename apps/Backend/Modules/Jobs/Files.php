<?php
namespace WPStaging\Backend\Modules\Jobs;

// No Direct Access
if (!defined("WPINC"))
{
    die;
}

/**
 * Class Files
 * @package WPStaging\Backend\Modules\Jobs
 */
class Files extends JobExec
{

    /**
     * Start Module
     * @return mixed
     */
    public function start()
    {
        // TODO: Implement start() method.

        // TODO: check if we can use EXEC or not
        // TODO: if we can use exec; WIN: exec("copy {$sourceFile} {$targetFile}"), LIN: exec("cp {$sourceFile} {$targetFile}")
    }
}