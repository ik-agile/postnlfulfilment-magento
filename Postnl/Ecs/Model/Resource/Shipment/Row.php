<?php
namespace Postnl\Ecs\Model\Resource\Shipment;

class Row extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    
    public function _construct()
    {    
        $this->_init('postnlecs_shipment_row', 'shipment_row_id');
    }
    
}
