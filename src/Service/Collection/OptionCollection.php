<?php /** @noinspection PhpUndefinedClassInspection */

//TODO PHP7.x; declare(strict_types=1);
//TODO PHP7.x type-hints & return types

namespace WPStaging\Service\Collection;

use JsonSerializable;
use WPStaging\Pro\Component\Job\Database\JobCreateSnapshot;
use WPStaging\Service\Entity\AbstractEntity;
use WPStaging\Service\Entity\IdentifyableEntityInterface;

class OptionCollection extends Collection implements JsonSerializable
{

    /**
     * @param string $id
     *
     * @return bool
     */
    public function doesIncludeId($id)
    {
        // We could use following for simplicity but not that performable
        // return array_key_exists($id, $this->toArray());
        /** @var IdentifyableEntityInterface $item */
        foreach ($this as $item) {
            if ($id === $item->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $id
     *
     * @return AbstractEntity|null
     * @noinspection PhpUnused
     */
    public function findById($id)
    {
        /** @var AbstractEntity $item */
        foreach ($this as $item) {
            if ($id === $item->getId()) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @param string $id
     */
    public function removeById($id)
    {
        $item = $this->findById($id);
        if (!$item) {
            return;
        }
        // TODO RPoC
        delete_transient(JobCreateSnapshot::TRANSIENT_PREFIX . $id);
        $this->detach($item);
    }

    public function filterByPrefix($prefix)
    {
        /** @var AbstractEntity $item */
        foreach ($this as $item) {
            if (0 !== strpos($item->getId(), $prefix)) {
                $this->detach($item);
            }
        }
    }

    public function sortBy($key, $sort = SORT_DESC)
    {
        $array = $this->toArray();
        $columns = array_column($array, $key);
        array_multisort($columns, $sort, $array);
        $this->removeAll($this);
        $this->attachAllByArray($array);
    }
}
