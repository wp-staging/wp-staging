<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types

namespace WPStaging\Component\Snapshot;

use WPStaging\Manager\Database\TableDto;
use WPStaging\Manager\Database\TableManager;
use WPStaging\Repository\SnapshotRepository;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Adapter\Hooks;
use WPStaging\Framework\Collection\Collection;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\TemplateEngine\TemplateEngine;

class AjaxConfirmRestore extends AbstractTemplateComponent
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
        $this->addAction('wp_ajax_wpstg--snapshots--restore--confirm', 'render');
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

        $tblManager = new TableManager;

        $prodTables = $tblManager->findStartsWith();
        if (!$prodTables || 1 > $prodTables->count()) {
            wp_send_json(array(
                'error' => true,
                'message' => __('Production (live) database tables not found.', 'wp-staging'),
            ));
        }

        $snapshotTables = $tblManager->findStartsWith($id);
        if (!$snapshotTables || 1 > $snapshotTables->count()) {
            wp_send_json(array(
                'error' => true,
                'message' => sprintf(__('Database tables for snapshot %s not found.', 'wp-staging'), $id),
            ));
        }

        // TODO RPoC; perhaps just check; isNotSame
        $prefixProd = (new Database)->getPrefix();
        $result = $this->templateEngine->render(
            'Component/Backend/Snapshot/confirm-restore.php',
            [
                'snapshot' => $snapshot,
                'snapshotTables' => $snapshotTables,
                'prodTables' => $prodTables,
                'isTableChanged' => static function(TableDto $table, Collection $oppositeCollection) use($id, $prefixProd) {
                    $tableName = str_replace([$id, $prefixProd], null, $table->getName());
                    /** @var TableDto $item */
                    foreach($oppositeCollection as $item) {
                        $itemName = str_replace([$id, $prefixProd], null, $item->getName());
                        if ($tableName !== $itemName) {
                            continue;
                        }

                        return $item->getSize() !== $table->getSize();
                    }
                    return false;
                },
            ]
        );
        wp_send_json($result);
    }
}
