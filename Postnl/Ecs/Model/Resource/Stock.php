<?php
namespace Postnl\Ecs\Model\Resource;

class Stock extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    
    public function _construct()
    {    
        $this->_init('postnlecs_stock', 'stock_id');
    }
    
}
