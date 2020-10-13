<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types

namespace WPStaging\Component\Snapshot;

use WPStaging\Repository\SnapshotRepository;
use WPStaging\Framework\Adapter\Hooks;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\TemplateEngine\TemplateEngine;

class AjaxListing extends AbstractTemplateComponent
{

    /** @var SnapshotRepository  */
    private $snapshotRepository;

    public function __construct(Hooks $hooks, TemplateEngine $templateEngine, SnapshotRepository $snapshotRepository)
    {
        $this->snapshotRepository = $snapshotRepository;
        parent::__construct($hooks, $templateEngine);
    }

    public function registerHooks()
    {
        $this->addAction('wp_ajax_wpstg--snapshots--listing', 'render');
    }

    public function render()
    {
        if (!$this->isSecureAjax('wpstg_ajax_nonce', 'nonce')) {
            return;
        }

        $snapshots = $this->snapshotRepository->findAll();
        if ($snapshots) {
            $snapshots->sortBy('updatedAt');
        }

        $result = $this->templateEngine->render(
            'Component/Backend/Snapshot/listing.php',
            [
                'snapshots' => $snapshots?: [],
            ]
        );
        wp_send_json($result);
    }
}
