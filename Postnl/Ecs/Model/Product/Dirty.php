<?php
namespace Postnl\Ecs\Model\Product;

class Dirty extends \Magento\Framework\Model\AbstractModel {

    public function _construct()
    {
        parent::_construct();
        $this->_init('Postnl\Ecs\Model\Resource\Product\Dirty');
    }
    
    public function beforeSave()
    {
        $now = (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
        if ($this->isObjectNew())
            $this->setCreatedAt($now);
        
        return parent::beforeSave();
    }
    
}
