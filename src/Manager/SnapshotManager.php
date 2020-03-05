<?php

namespace WPStaging\Manager;

use WPStaging\Repository\SnapshotRepository;

class SnapshotManager
{
    /**
     * @param string $prefix
     * @return bool
     */
    public function deleteByPrefix($prefix)
    {
        $repository = new SnapshotRepository;

        $snapshots = $repository->findAll();
        if (!$snapshots) {
            return true;
        }

        $snapshots->removeById($prefix);
        if ($repository->save($snapshots)) {
            return true;
        }

        return false;
    }
}
