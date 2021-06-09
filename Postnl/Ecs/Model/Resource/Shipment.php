<?php
namespace Postnl\Ecs\Model\Resource;


class Shipment extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    
    public function _construct()
    {    
        $this->_init('postnlecs_shipment', 'shipment_id');
    }
    
}
