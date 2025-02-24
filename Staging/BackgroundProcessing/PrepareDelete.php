<?php

namespace WPStaging\Staging\BackgroundProcessing;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\BackgroundProcessing\Job\PrepareJob;
use WPStaging\Framework\BackgroundProcessing\Queue;
use WPStaging\Framework\Job\ProcessLock;
use WPStaging\Framework\Utils\Times;
use WPStaging\Staging\Ajax\Delete\PrepareDelete as AjaxPrepareDelete;
use WPStaging\Staging\Jobs\StagingSiteDelete;

use function WPStaging\functions\debug_log;

/**
 * Class PrepareDelete
 * Prepares a Staging Site Delete Job to be executed using Background Processing.
 *
 * @package WPStaging\Staging\BackgroundProcessing
 */
class PrepareDelete extends PrepareJob
{
    /**
     * PrepareDelete constructor.
     *
     * @param AjaxPrepareDelete $ajaxPrepareDelete A reference to the object currently handling
     *                                             AJAX Delete preparation requests.
     * @param Queue             $queue             A reference to the instance of the Queue manager the class
     *                                             should use for processing.
     * @param ProcessLock       $processLock       A reference to the Process Lock manager the class should use
     *                                             to prevent concurrent processing of the job requests.
     * @param Times             $times             A reference to the Times utility class.
     */
    public function __construct(AjaxPrepareDelete $ajaxPrepareDelete, Queue $queue, ProcessLock $processLock, Times $times)
    {
        parent::__construct($ajaxPrepareDelete, $queue, $processLock, $times);
    }

    /**
     * Returns the default data configuration that will be used to prepare a Delete using
     * default settings.
     */
    public function getDefaultDataConfiguration(): array
    {
        return [
            'isDeletingTables' => false,
            'isDeletingFiles'  => false,
            'excludedTables'   => [],
        ];
    }

    protected function maybeInitJob(array $args)
    {
        if ($args['isInit']) {
            debug_log('[Schedule] Configuring JOB DATA DTO', 'info', false);
            $prepareDelete = WPStaging::make(AjaxPrepareDelete::class);
            $prepareDelete->prepare($args);
            $this->job = $prepareDelete->getJob();
        } else {
            $this->job = WPStaging::make(StagingSiteDelete::class);
        }
    }
}
