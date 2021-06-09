<?php
namespace Postnl\Ecs\Model\Resource\Shipment;


class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    protected function _construct()
    {
        $this->_init('Postnl\Ecs\Model\Shipment', 'Postnl\Ecs\Model\Resource\Shipment');
    }
    
}
