<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types

namespace WPStaging\Component\Snapshot;

use WPStaging\Manager\Database\TableManager;
use WPStaging\Repository\SnapshotRepository;
use WPStaging\Framework\Adapter\Hooks;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\TemplateEngine\TemplateEngine;

class AjaxConfirmDelete extends AbstractTemplateComponent
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
        $this->addAction('wp_ajax_wpstg--snapshots--delete--confirm', 'render');
    }

    public function render()
    {
        if (!$this->isSecureAjax('wpstg_ajax_nonce', 'nonce')) {
            return;
        }

        $id = isset($_POST['id'])? sanitize_text_field($_POST['id']) : '';
        $snapshot = $this->snapshotRepository->find($id);
        if (!$snapshot) {
            wp_send_json(array(
                'error' => true,
                'message' => sprintf(__('Snapshot %s not found.', 'wp-staging'), $id),
                ));
        }

        $tables = (new TableManager)->findStartsWith($id);
        if (!$tables || 1 > $tables->count()) {
            wp_send_json(array(
                'error' => true,
                'message' => sprintf(__('Database tables for snapshot %s not found. You can still <a href="%%" id="wpstg-snapshot-force-delete" data-id="%s">delete the listed snapshot entry</a>.', 'wp-staging'),
                    $id,
                    $id
                ),
            ));
        }

        $result = $this->templateEngine->render(
            'Component/Backend/Snapshot/confirm-delete.php',
            [
                'snapshot' => $snapshot,
                'tables' => $tables,
            ]
        );
        wp_send_json($result);
    }
}
