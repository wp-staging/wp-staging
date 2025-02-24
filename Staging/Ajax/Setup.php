<?php

namespace WPStaging\Staging\Ajax;

use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\Job\Exception\ProcessLockedException;
use WPStaging\Framework\Job\ProcessLock;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Staging\Service\AbstractStagingSetup;
use WPStaging\Staging\Service\DirectoryScanner;
use WPStaging\Staging\Service\TableScanner;

class Setup extends AbstractTemplateComponent
{
    /**
     * @var AbstractStagingSetup
     */
    private $stagingSetup;

    /**
     * @var DirectoryScanner
     */
    private $directoryScanner;

    /**
     * @var TableScanner
     */
    private $tableScanner;

    /**
     * @var ProcessLock
     */
    private $processLock;

    public function __construct(TemplateEngine $templateEngine, AbstractStagingSetup $stagingSetup, DirectoryScanner $directoryScanner, TableScanner $tableScanner, ProcessLock $processLock)
    {
        parent::__construct($templateEngine);
        $this->stagingSetup     = $stagingSetup;
        $this->processLock      = $processLock;
        $this->directoryScanner = $directoryScanner;
        $this->tableScanner     = $tableScanner;
    }

    /**
     * @return void
     */
    public function ajaxSetup()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        try {
            $this->processLock->checkProcessLocked();
        } catch (ProcessLockedException $e) {
            wp_send_json_error($e->getMessage(), $e->getCode());
        }

        $this->stagingSetup->initNewStagingSite();
        $this->directoryScanner->setStagingSetup($this->stagingSetup);
        $this->tableScanner->setStagingSetup($this->stagingSetup);

        $result = $this->templateEngine->render(
            'staging/setup.php',
            [
                'stagingSetup'     => $this->stagingSetup,
                'stagingSiteDto'   => $this->stagingSetup->getStagingSiteDto(),
                'directoryScanner' => $this->directoryScanner,
                'tableScanner'     => $this->tableScanner
            ]
        );

        wp_send_json_success($result);
    }
}
