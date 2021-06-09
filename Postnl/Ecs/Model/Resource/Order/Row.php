<?php
namespace Postnl\Ecs\Model\Resource\Order;

class Row extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    
    public function _construct()
    {    
        $this->_init('postnlecs_order_row', 'order_row_id');
    }
    
}
