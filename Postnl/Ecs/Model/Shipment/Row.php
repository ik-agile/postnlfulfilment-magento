<?php
namespace Postnl\Ecs\Model\Shipment;

class Row extends \Magento\Framework\Model\AbstractModel {

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSED = 'processed';
    const STATUS_ERROR = 'error';

    public function _construct()
    {
        parent::_construct();
        $this->_init('Postnl\Ecs\Model\Resource\Shipment\Row');
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
