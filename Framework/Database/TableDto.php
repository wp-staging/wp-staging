<?php

 
 

namespace WPStaging\Framework\Database;

use DateTime;
use WPStaging\Framework\Interfaces\HydrateableInterface;
use WPStaging\Core\WPStaging;

class TableDto implements HydrateableInterface
{
 
    private $name;

 
    private $rows;

 
    private $size;

 
    private $autoIncrement;

 
    private $createdAt;

 
    private $updatedAt;

 
    private $isView = false;

    public function hydrate(array $data = [])
    {
        $this->setName($data['Name']);

        $this->setRows(isset($data['Rows']) ? (int) $data['Rows'] : 0);
        $this->setAutoIncrement(isset($data['Auto_increment']) ? $data['Auto_increment'] : null);
 
        $this->setCreatedAt(new DateTime(isset($data['Create_time']) ? $data['Create_time'] : ''));
        if (isset($data['Update_time']) && $data['Update_time']) {
 
            $this->setUpdatedAt(new DateTime($data['Update_time']));
        }

        if (isset($data['Data_length'], $data['Index_length'])) {
            $size = (int) $data['Data_length'] + (int) $data['Index_length'];
            $this->setSize($size);
        }

        if (isset($data['Comment']) && $data['Comment'] === 'VIEW') {
            $this->setIsView(true);
        }

        return $this;
    }




    public function getName()
    {
        return $this->name;
    }




    public function setName($name)
    {
        $this->name = $name;
    }




    public function getRows()
    {
        return $this->rows;
    }




    public function setRows($rows)
    {
        $this->rows = $rows;
    }




    public function getSize()
    {
        return $this->size;
    }




    public function setSize($size)
    {
        $this->size = $size;
    }




    public function getAutoIncrement()
    {
        return $this->autoIncrement;
    }




    public function setAutoIncrement($autoIncrement)
    {
        $this->autoIncrement = $autoIncrement;
    }




    public function getCreatedAt()
    {
        return $this->createdAt;
    }




    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }




    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }




    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }




    public function getIsView(): bool
    {
        return $this->isView;
    }





    public function setIsView(bool $isView)
    {
        $this->isView = $isView;
    }




    public function getHumanReadableSize()
    {
 
 
 
        if (WPStaging::isOnWordPressPlayground()) {
            return '';
        }

        return size_format($this->size);
    }
}
