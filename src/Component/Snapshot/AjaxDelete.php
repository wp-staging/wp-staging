<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types

namespace WPStaging\Component\Snapshot;

use Exception;
use WPStaging\Command\Database\Snapshot\SnapshotCommandException;
use WPStaging\Command\Database\Snapshot\SnapshotDto;
use WPStaging\Command\Database\SnapshotFactory;
use WPStaging\Manager\SnapshotManager;
use WPStaging\Framework\Component\AbstractTemplateComponent;

class AjaxDelete extends AbstractTemplateComponent
{

    public function registerHooks()
    {
        $this->addAction('wp_ajax_wpstg--snapshots--delete', 'render');
    }

    public function render()
    {
        if (!$this->isSecureAjax('wpstg_ajax_nonce', 'nonce')) {
            return;
        }

        $id = isset($_POST['id'])? sanitize_text_field($_POST['id']) : '';
        $isForce = isset($_POST['force']) && '1' === $_POST['force'];

        if ($isForce) {
            $this->forceDelete($id);
            return;
        }

        $this->delete($id);
    }

    protected function delete($prefix)
    {
        $dto = new SnapshotDto;
        $dto->setTargetPrefix($prefix);

        $handler = SnapshotFactory::make($dto, SnapshotFactory::DELETE_SNAPSHOT);

        try {
            $handler->handle();
            wp_send_json(true);
        } catch(SnapshotCommandException $e) {
            wp_send_json([
                'error' => true,
                'message' => sprintf(__('Failed to delete snapshot table(s) for ID: %s', 'wp-staging'), $prefix),
            ]);
        } catch(Exception $e) {
            wp_send_json([
                'error' => true,
                'message' => sprintf(__('Failed to delete snapshot table(s) for ID: %s', 'wp-staging'), $prefix),
            ]);
        }
    }

    protected function forceDelete($prefix)
    {
        if ((new SnapshotManager)->deleteByPrefix($prefix)) {
            wp_send_json(true);
            return;
        }

        wp_send_json([
            'error' => true,
            'message' => sprintf(__('Failed to delete snapshot table(s) for ID: %s', 'wp-staging'), $prefix),
        ]);
    }
}
