<?php
namespace Postnl\Ecs\Model\Product;

class Row extends \Magento\Framework\Model\AbstractModel {

    public function _construct()
    {
        parent::_construct();
        $this->_init('Postnl\Ecs\Model\Resource\Product\Row');
    }
    
    public function beforeSave()
    {
        $now = (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
        if ($this->isObjectNew())
            $this->setCreatedAt($now);
        $this->setUpdatedAt($now);
        
        return parent::beforeSave();
    }
    
}
