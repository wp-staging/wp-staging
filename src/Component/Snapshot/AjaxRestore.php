<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types

namespace WPStaging\Component\Snapshot;

use WPStaging\Component\Job\Dto\SnapshotRestoreDto;
use WPStaging\Pro\Component\Job\Database\JobRestoreSnapshot;
use WPStaging\Framework\Component\AbstractTemplateComponent;

class AjaxRestore extends AbstractTemplateComponent
{

    public function registerHooks()
    {
        $this->addAction('wp_ajax_wpstg--snapshots--restore', 'render');
    }

    public function render()
    {
        if (!$this->isSecureAjax('wpstg_ajax_nonce', 'nonce')) {
            return;
        }

        $id = isset($_POST['id'])? sanitize_text_field($_POST['id']) : '';
        $isReset = isset($_POST['isReset']) && 'true' === $_POST['isReset'];

        $dto = new SnapshotRestoreDto;
        $dto->setPrefix($id);
        $dto->setReset($isReset);
        wp_send_json((new JobRestoreSnapshot($dto))->execute());
    }
}
