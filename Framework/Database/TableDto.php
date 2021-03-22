<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; type-hints & return types

namespace WPStaging\Framework\Database;

use DateTime;
use WPStaging\Framework\Interfaces\HydrateableInterface;

class TableDto implements HydrateableInterface
{
    /** @var string */
    private $name;

    /** @var int */
    private $rows;

    /** @var int */
    private $size;

    /** @var int */
    private $autoIncrement;

    /** @var DateTime */
    private $createdAt;

    /** @var DateTime */
    private $updatedAt;

    public function hydrate(array $data = [])
    {
        $this->setName($data['Name']);

        $this->setRows(isset($data['Rows']) ? (int) $data['Rows'] : 0);
        $this->setAutoIncrement(isset($data['Auto_increment']) ? $data['Auto_increment'] : null);
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->setCreatedAt(new DateTime(isset($data['Create_time']) ? $data['Create_time'] : ''));
        if (isset($data['Update_time']) && $data['Update_time']) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $this->setUpdatedAt(new DateTime($data['Update_time']));
        }

        if (isset($data['Data_length'], $data['Index_length'])) {
            $size = (int) $data['Data_length'] + (int) $data['Index_length'];
            $this->setSize($size);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * @param int $rows
     */
    public function setRows($rows)
    {
        $this->rows = $rows;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param int $size
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * @return int|null
     */
    public function getAutoIncrement()
    {
        return $this->autoIncrement;
    }

    /**
     * @param int|null $autoIncrement
     */
    public function setAutoIncrement($autoIncrement)
    {
        $this->autoIncrement = $autoIncrement;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return DateTime|null
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTime $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return string
     */
    public function getHumanReadableSize()
    {
        return size_format($this->size);
    }
}
