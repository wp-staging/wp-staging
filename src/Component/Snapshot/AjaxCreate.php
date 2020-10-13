<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types

namespace WPStaging\Component\Snapshot;

use WPStaging\Command\Database\Snapshot\SnapshotHandler;
use WPStaging\Component\Job\Dto\SnapshotCreateDto;
use WPStaging\Pro\Component\Job\Database\JobCreateSnapshot;
use WPStaging\Manager\Database\TableDto;
use WPStaging\Manager\Database\TableManager;
use WPStaging\Framework\Collection\Collection;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\Utils\Strings;

// TODO RPoC
class AjaxCreate extends AbstractTemplateComponent
{

    const TRANSIENT_KEY = 'wpstg_snapshot_create';

    public function registerHooks()
    {
        $this->addAction('wp_ajax_wpstg--snapshots--create', 'render');
    }

    public function render()
    {
        if (!$this->isSecureAjax('wpstg_ajax_nonce', 'nonce')) {
            return;
        }

        $dto = new SnapshotCreateDto;
        $dto->setName(sanitize_text_field(empty($_POST['name']) ? __('Manual Snapshot', 'wp-staging') : $_POST['name']));
        $dto->setNotes((new Strings)->sanitizeTextareaField(empty($_POST['notes']) ? __('Snapshot manually created by user action.', 'wp-staging') : $_POST['notes']));
        $dto->setJob(SnapshotCreateDto::JOB_MANUAL);
        $dto->setIncrement($this->provideIncrement());

        $response = (new JobCreateSnapshot($dto))->execute();

        if (true === $response->status) {
            delete_transient(self::TRANSIENT_KEY);
        }

        wp_send_json($response);
    }

    private function provideIncrement()
    {
        $transient = get_transient(self::TRANSIENT_KEY);
        if (false !== $transient) {
            return $transient;
        }

        $increment = $this->totalStandaloneSnapshots();
        set_transient(self::TRANSIENT_KEY, $increment, HOUR_IN_SECONDS);
        return $increment;
    }

    /**
     * @return int
     */
    private function totalStandaloneSnapshots()
    {
        /** @var TableDto[]|Collection $tables */
        $tables = (new TableManager)->findStartsWith(SnapshotHandler::PREFIX_MANUAL);
        if (!$tables) {
            return 0;
        }

        $found = [];
        foreach ($tables as $table) {
            $prefix = substr($table->getName(), 0, strpos($table->getName(), '_'));
            $increment = (int) str_replace(SnapshotHandler::PREFIX_MANUAL, null, $prefix);
            $found[$increment] = '';
        }
        return count($found);
    }
}
