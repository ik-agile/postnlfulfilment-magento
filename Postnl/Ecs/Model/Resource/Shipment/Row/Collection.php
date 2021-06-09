<?php
namespace Postnl\Ecs\Model\Resource\Shipment\Row;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    public function _construct()
    {
        parent::_construct();
        $this->_init('Postnl\Ecs\Model\Shipment', 'Postnl\Ecs\Model\Resource\Shipment');
    }
    
}
